<?php
$db_user = "SP"; // Replace with your database username
$db_password = "SP#987"; // Replace with your database password
$db_connect_string = "AL32UTF8"; // Replace with your database connect string (e.g., //localhost/XE)

$DB = oci_connect($db_user, $db_password,'OMDB', $db_connect_string);
// $GLOBALS['DB'] = oci_connect('SP','SP#987','OMDB', 'AL32UTF8');

if (!$DB) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}
?>