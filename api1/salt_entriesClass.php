<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_entriesClass.php — Salt API "entries": C-SALT-compatible search.
//
// Thin wrapper: validates params, then calls the shared search + envelope builder in
// salt_common.php (the same helpers used by salt_idsClass and salt_graphqlClass, so the
// three faces cannot diverge). No new runtime: term/prefix/wildcard/fuzzy run over the
// headword index; match/match_phrase/regexp over the body wait for Phase 4.
//
// Contract : doc/salt_entries.md
// Profile  : csl-standards/docs/SALT_API_PROFILE.md  (data/schema/salt-api.openapi.yaml)
require_once(__DIR__ . '/salt_common.php');

class SaltEntriesClass {
  public $json;
  public $parm, $field, $query, $query_type, $size;

  public function __construct() {
    $this->parm       = new Parm();                       // dict, filterin(input), filter(output), accent, key
    $this->field      = isset($_REQUEST['field'])      ? $_REQUEST['field']      : 'headword_slp1';
    $this->query      = isset($_REQUEST['query'])      ? $_REQUEST['query']      : $this->parm->keyin;
    $this->query_type = isset($_REQUEST['query_type']) ? $_REQUEST['query_type'] : 'term';
    $this->size       = isset($_REQUEST['size'])       ? (int)$_REQUEST['size']  : 25;

    // ---- validate; 400 like C-SALT for unknown field / query_type ----
    if (!in_array($this->field, salt_fields(), true) ||
        !in_array($this->query_type, salt_query_types(), true)) {
      http_response_code(400);
      $this->json = json_encode(array('error' => "Missing or invalid parameter: 'field'"));
      return;
    }
    // body-text modes need an index not built for the MW pilot -> 400 (never silent-empty)
    if (in_array($this->query_type, array('match','match_phrase','regexp'), true)) {
      http_response_code(400);
      $this->json = json_encode(array('error' => "query_type '{$this->query_type}' not available until Phase 4"));
      return;
    }

    // ---- search + build (shared) ----
    $entries = salt_search_entries($this->parm, $this->field, $this->query, $this->query_type, $this->size);
    $this->json = json_encode(array('data' => array('entries' => $entries)), JSON_UNESCAPED_UNICODE);
  }
}
?>
