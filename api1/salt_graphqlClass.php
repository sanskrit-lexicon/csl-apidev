<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_graphqlClass.php — Salt API GraphQL face. Two root fields: entries, ids.
// Contract: doc/salt_graphql.md ; schema: csl-standards/data/schema/salt-api.graphql
//
// RECOMMENDED for production: wire webonyx/graphql-php against the SDL and resolve with the
// salt_common.php helpers (see the commented block at the bottom). The minimal hand-rolled
// dispatcher below lets the endpoint answer the two known query shapes during the pilot
// without adding a Composer dependency. Resolvers call the SAME salt_common helpers as the
// REST faces, so REST and GraphQL cannot diverge.
require_once(__DIR__ . '/salt_common.php');

class SaltGraphqlClass {
  public $json;

  public function __construct() {
    $body  = json_decode(file_get_contents('php://input'), true);
    $query = isset($body['query']) ? $body['query']
           : (isset($_REQUEST['query']) ? $_REQUEST['query'] : '');
    $vars  = isset($body['variables']) ? $body['variables'] : array();

    // TODO: replace this naive dispatch with webonyx/graphql-php (see bottom of file).
    if ((strpos($query, 'ids') !== false) && (strpos($query, 'entries') === false)) {
      $this->json = json_encode(array('data' => array('ids' => $this->resolveIds($vars))),
                                JSON_UNESCAPED_UNICODE);
    } else if (strpos($query, 'entries') !== false) {
      $this->json = json_encode(array('data' => array('entries' => $this->resolveEntries($query, $vars))),
                                JSON_UNESCAPED_UNICODE);
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
    $out = array();
    foreach (array_slice(salt_search($field, $q, $query_type, $size), 0, $size) as $lnum) {
      $out[] = salt_entry_build($lnum);
    }
    return $out;
  }

  // ids(ids: [String])
  private function resolveIds($vars) {
    $ids = isset($vars['ids']) ? $vars['ids'] : array();
    $out = array();
    foreach ($ids as $id) {
      $lnum = salt_id_to_lnum($id);
      if ($lnum !== null) { $out[] = salt_entry_build($lnum); }
    }
    return $out;
  }

  // Placeholder argument reader: prefer GraphQL variables; fall back to a literal-arg regex.
  // Real literal/variable parsing is the webonyx parser's job.
  private function arg($query, $vars, $name, $default) {
    if (isset($vars[$name])) { return $vars[$name]; }
    if (preg_match('/' . preg_quote($name, '/') . '\\s*:\\s*"?([A-Za-z0-9_\\-]+)"?/', $query, $m)) {
      return $m[1];
    }
    return $default;
  }
}

/* ---- Recommended production wiring (webonyx/graphql-php) -------------------------------
require_once __DIR__ . '/../vendor/autoload.php';
use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;

$schema = BuildSchema::build(file_get_contents(__DIR__ . '/salt-api.graphql'));  // SDL copy
$root = array(
  'entries' => function ($r, $a) {
    $lnums = salt_search(
      isset($a['field']) ? $a['field'] : 'headword_slp1',
      $a['query'],
      isset($a['queryType']) ? $a['queryType'] : 'term',
      isset($a['size']) ? $a['size'] : 25);
    return array_map('salt_entry_build', $lnums);
  },
  'ids' => function ($r, $a) {
    return array_map(function ($id) { return salt_entry_build(salt_id_to_lnum($id)); }, $a['ids']);
  },
);
$result = GraphQL::executeQuery($schema, $query, $root, null, $vars)->toArray();
--------------------------------------------------------------------------------------- */
?>
