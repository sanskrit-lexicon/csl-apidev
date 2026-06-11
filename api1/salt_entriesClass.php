<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_entriesClass.php — builds the Salt "entries" envelope from CSL data.
//
// SKELETON (Phase 1, MW pilot). Reuses Parm (parm.php) for dict/input/output/accent/key,
// and the existing getword data path (GetwordClass / getword_data.php / dal) for record
// content. No new runtime: term/prefix/wildcard/regexp/fuzzy run over the headword index;
// match/match_phrase over the body wait for Phase 4 (Elasticsearch or SQLite FTS5).
//
// Contract : doc/salt_entries.md
// Profile  : csl-standards/docs/SALT_API_PROFILE.md  (data/schema/salt-api.openapi.yaml)
require_once(__DIR__ . '/../parm.php');
// require_once(__DIR__ . '/../getwordClass.php');  // structured record data (doc/getword.md §1.1.8)
// require_once(__DIR__ . '/../dalglob.php');        // headword-index access for the search modes

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
    $okFields = array('id','headword_slp1','sense','re_headwords_slp1','created','xml');
    $okTypes  = array('term','fuzzy','match','match_phrase','prefix','wildcard','regexp');
    if (!in_array($this->field, $okFields, true) || !in_array($this->query_type, $okTypes, true)) {
      http_response_code(400);
      $this->json = json_encode(array('error' => "Missing or invalid parameter: 'field'"));
      return;
    }
    // body-text modes need an index not built for the MW pilot -> 400 (never silent-empty)
    if (in_array($this->field, array('sense','xml'), true) &&
        in_array($this->query_type, array('match','match_phrase'), true)) {
      http_response_code(400);
      $this->json = json_encode(array('error' => 'body search not available until Phase 4'));
      return;
    }

    // ---- 1. find matching records ; 2. build one Salt entry per record ----
    $lnums   = $this->search();
    $entries = array();
    foreach (array_slice($lnums, 0, $this->size) as $lnum) {
      $entries[] = $this->buildEntry($lnum);
    }
    $this->json = json_encode(array('data' => array('entries' => $entries)),
                              JSON_UNESCAPED_UNICODE);
  }

  private function search() {
    // TODO: dispatch on $this->query_type over the HEADWORD field (no ES needed):
    //   term     -> existing getword key lookup ($this->parm->key)
    //   prefix   -> listhier neighborhood / starts-with
    //   wildcard -> glob over the headword index
    //   regexp   -> regex over the headword index
    //   fuzzy    -> getsuggest path
    // Return an array of lnum (Cologne record ids).
    return array();
  }

  private function buildEntry($lnum) {
    $rec = $this->getRecord($lnum);                       // TODO: structured getword record
    $key = isset($rec['key']) ? $rec['key'] : $this->parm->key;   // SLP1 headword
    // homonym suffix only when the headword has >1 homonym (matches C-SALT lemma-ka-1..4)
    $hom   = isset($rec['homonym']) ? $rec['homonym'] : null;
    $count = isset($rec['homonymCount']) ? $rec['homonymCount'] : 1;
    $suffix = ($hom && $count > 1) ? "-$hom" : '';

    return array(
      'id'                => "lemma-{$key}{$suffix}",      // matches C-SALT exactly
      'headword_slp1'     => $key,
      'sense'             => isset($rec['sense'])        ? $rec['sense']        : array(),  // best-effort pre-TEI
      're_headwords_slp1' => isset($rec['re_headwords']) ? $rec['re_headwords'] : array(),  // run-ons
      'created'           => isset($rec['created'])      ? $rec['created']      : null,
      'xml'               => null,                          // TEI: Phase 5 (never display-XML)
      'csl' => array(
        'lnum'         => (string)$lnum,
        'page'         => isset($rec['pageNumber']) ? $rec['pageNumber'] : null,
        'column'       => isset($rec['columnId'])   ? $rec['columnId']   : null,
        'scanUrl'      => isset($rec['imgUrl'])     ? $rec['imgUrl']     : null,
        'html'         => isset($rec['html'])       ? $rec['html']       : null,
        'text'         => isset($rec['text'])       ? $rec['text']       : null,
        'xmlCsl'       => isset($rec['xml'])        ? $rec['xml']        : null, // CSL display-XML, now
        'references'   => isset($rec['references']) ? $rec['references'] : array(),
        'headwordDeva' => $this->translit($key, 'deva'),
        'headwordIast' => $this->translit($key, 'roman'),
        'accentedKey'  => isset($rec['key2'])       ? $rec['key2']       : null, // e.g. agn/i
      ),
    );
  }

  private function getRecord($lnum) {
    // TODO: fetch structured record for $lnum via GetwordClass / getword_data.php.
    return array();
  }

  private function translit($slp1, $to) {
    // TODO: transcoder_processString($slp1, 'slp1', $to)
    return $slp1;
  }
}
?>
