<?php

$db_username = 'mahdi';
$db_password = '1234';
$db_connection = 'localhost/XE';

// Connect to Oracle database
$conn = oci_connect($db_username, $db_password, $db_connection);
if (!$conn) {
  $e = oci_error();
  die("Erreur de connexion Oracle : " . htmlentities($e['message']));
}
?>