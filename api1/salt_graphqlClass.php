<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_graphqlClass.php — Salt API GraphQL face. Two root fields: entries, ids.
// Contract: doc/salt_graphql.md ; schema: csl-standards/data/schema/salt-api.graphql
//
// RECOMMENDED for production: wire webonyx/graphql-php against the SDL and resolve with the
// salt_common.php helpers (see the commented block at the bottom). The minimal hand-rolled
// dispatcher below answers the two known query shapes during the pilot without a Composer
// dependency. Resolvers call the SAME salt_common helpers as the REST faces, so REST and
// GraphQL cannot diverge.
//
// VERIFY: the naive dispatcher reads scalar args from GraphQL variables or a literal-arg
// regex; array literals (e.g. ids: ["a","b"]) only work via variables until webonyx is wired.
require_once(__DIR__ . '/salt_common.php');

class SaltGraphqlClass {
  public $json;
  public $parm;
  private $entriesError = null;

  public function __construct() {
    $this->parm = new Parm();                       // dict from $_REQUEST['dict']
    $body  = json_decode(file_get_contents('php://input'), true);
    $query = isset($body['query']) ? $body['query']
           : (isset($_REQUEST['query']) ? $_REQUEST['query'] : '');
    $vars  = isset($body['variables']) ? $body['variables'] : array();

    if ((strpos($query, 'ids') !== false) && (strpos($query, 'entries') === false)) {
      $this->json = json_encode(array('data' => array('ids' => $this->resolveIds($vars))),
                                JSON_UNESCAPED_UNICODE);
    } else if (strpos($query, 'entries') !== false) {
      $entries = $this->resolveEntries($query, $vars);
      if ($this->entriesError !== null) {
        http_response_code(400);
        $this->json = json_encode(array('errors' => array(array('message' => $this->entriesError))));
      } else {
        $this->json = json_encode(array('data' => array('entries' => $entries)), JSON_UNESCAPED_UNICODE);
      }
    } else {
      http_response_code(400);
      $this->json = json_encode(array('errors' => array(
        array('message' => 'Only the entries and ids queries are supported'))));
    }
  }

  // entries(field, query, queryType, size)  — note camelCase queryType in GraphQL
  private function resolveEntries($query, $vars) {
    $field      = $this->arg($query, $vars, 'field', 'headword_slp1');
    $q          = $this->arg($query, $vars, 'query', '');
    $query_type = $this->arg($query, $vars, 'queryType', 'term');
    $size       = (int)$this->arg($query, $vars, 'size', 25);
    // Validate against the SAME whitelists as the REST face (salt_entriesClass),
    // so the three faces do not diverge: an unknown field/queryType is a request
    // error, not a silently-empty result.
    if (!in_array($field, salt_fields(), true)) {
      $this->entriesError = "Missing or invalid parameter: 'field'"; return array();
    }
    if (!in_array($field, salt_phase1_fields(), true)) {
      $this->entriesError = salt_phase1_field_error($field); return array();
    }
    if (!in_array($query_type, salt_query_types(), true)) {
      $this->entriesError = "Missing or invalid parameter: 'queryType'"; return array();
    }
    if (in_array($query_type, array('match','match_phrase','regexp'), true)) {
      $this->entriesError = "queryType '$query_type' not available until Phase 4"; return array();
    }
    return salt_search_entries($this->parm, $field, $q, $query_type, $size);
  }

  // ids(ids: [String])  — pass ids via variables until webonyx is wired
  private function resolveIds($vars) {
    $ids = isset($vars['ids']) ? $vars['ids'] : array();
    $out = array();
    foreach ($ids as $id) {
      foreach (salt_entries_for_id($this->parm, $id) as $e) { $out[] = $e; }
    }
    return $out;
  }

  // Placeholder argument reader: prefer GraphQL variables; fall back to a literal-arg regex.
  private function arg($query, $vars, $name, $default) {
    if (isset($vars[$name])) { return $vars[$name]; }
    // Quoted string value: capture the FULL contents between the quotes so that
    // wildcards (a*), diacritics, %-escapes and spaces survive — the previous
    // [A-Za-z0-9_-]+ class silently truncated e.g. query:"a*" to "a".
    if (preg_match('/' . preg_quote($name, '/') . '\\s*:\\s*"([^"]*)"/', $query, $m)) {
      return $m[1];
    }
    // Unquoted value: GraphQL enum / number (queryType: term, size: 5).
    if (preg_match('/' . preg_quote($name, '/') . '\\s*:\\s*([A-Za-z0-9_]+)/', $query, $m)) {
      return $m[1];
    }
    return $default;
  }
}

/* ---- Recommended production wiring (webonyx/graphql-php) -------------------------------
require_once __DIR__ . '/../vendor/autoload.php';
use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;

$parm   = new Parm();
$schema = BuildSchema::build(file_get_contents(__DIR__ . '/salt-api.graphql'));  // SDL copy
$root = array(
  'entries' => function ($r, $a) use ($parm) {
    return salt_search_entries($parm,
      isset($a['field']) ? $a['field'] : 'headword_slp1',
      $a['query'],
      isset($a['queryType']) ? $a['queryType'] : 'term',
      isset($a['size']) ? $a['size'] : 25);
  },
  'ids' => function ($r, $a) use ($parm) {
    $out = array();
    foreach ($a['ids'] as $id) { foreach (salt_entries_for_id($parm, $id) as $e) { $out[] = $e; } }
    return $out;
  },
);
$result = GraphQL::executeQuery($schema, $query, $root, null, $vars)->toArray();
--------------------------------------------------------------------------------------- */
?>
