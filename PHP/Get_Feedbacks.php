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


$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$sql = "SELECT cf.RATING, cf.COMNT, cf.DATE_INTERACTED, mi.NAME_ITEM AS ITEM_NAME
        FROM \"CLIENTFEEDBACK\" cf
        JOIN \"MENUITEM\" mi ON cf.ITEM_ID = mi.ITEM_ID
        ORDER BY cf.DATE_INTERACTED DESC";

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

$feedbacks = [];
while ($row = oci_fetch_assoc($stid)) {
    $feedbacks[] = [
        'item_name' => mb_convert_encoding($row['ITEM_NAME'], 'UTF-8', 'ISO-8859-1'),
        'rating' => intval($row['RATING']),
        'comnt' => mb_convert_encoding($row['COMNT'], 'UTF-8', 'ISO-8859-1'),
        'date' => $row['DATE_INTERACTED']
    ];
}

oci_free_statement($stid);
oci_close($conn);

echo json_encode($feedbacks, JSON_UNESCAPED_UNICODE);
?>
