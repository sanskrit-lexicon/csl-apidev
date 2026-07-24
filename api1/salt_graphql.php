<?php
require_once(__DIR__ . '/../security_headers.php');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
?>
<?php
// salt_graphql.php — Salt API: GraphQL endpoint (POST). Contract: doc/salt_graphql.md
header("Access-Control-Allow-Origin: *");
header('content-type: application/json; charset=utf-8');
require_once(__DIR__ . '/salt_graphqlClass.php');
function saltGraphqlCall() {
  $temp = new SaltGraphqlClass();
  echo $temp->json;
}
saltGraphqlCall();
?>
