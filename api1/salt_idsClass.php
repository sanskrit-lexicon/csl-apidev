<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_idsClass.php — Salt API "ids": batch fetch entries by id (a get-by-id, not a search).
// Mirrors Kosh ids(ids). Contract: doc/salt_ids.md
require_once(__DIR__ . '/salt_common.php');

class SaltIdsClass {
  public $json;

  public function __construct() {
    $ids = salt_multi_param('ids');                 // repeated ids= (C-SALT multi-value)
    if (empty($ids)) {
      http_response_code(400);
      $this->json = json_encode(array('error' => "Missing or invalid parameter: 'ids'"));
      return;
    }
    $entries = array();
    foreach ($ids as $id) {
      $lnum = salt_id_to_lnum($id);
      if ($lnum !== null) {
        $entries[] = salt_entry_build($lnum);
      }
    }
    $this->json = json_encode(array('data' => array('ids' => $entries)), JSON_UNESCAPED_UNICODE);
  }
}
?>
