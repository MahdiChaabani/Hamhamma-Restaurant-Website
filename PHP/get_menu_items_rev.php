<?php
// Prevent warnings from being displayed
ini_set('display_errors', 0);

// Clean any previous output
ob_clean();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$sql = "SELECT ITEM_ID, NAME_ITEM 
        FROM MENUITEM 
        WHERE DISPONIBLE = 1 
        ORDER BY ITEM_ID";

$stid = oci_parse($conn, $sql);
if (!$stid) {
    echo json_encode(['error' => 'Query parse failed']);
    exit;
}

if (!oci_execute($stid)) {
    $error = oci_error($stid);
    echo json_encode(['error' => $error['message']]);
    exit;
}

$items = [];
while ($row = oci_fetch_assoc($stid)) {
    $items[] = [
        'id' => $row['ITEM_ID'],
        'name' => mb_convert_encoding($row['NAME_ITEM'], 'UTF-8', 'ISO-8859-1')
    ];
}

oci_free_statement($stid);
oci_close($conn);

echo json_encode($items, JSON_UNESCAPED_UNICODE);
?>