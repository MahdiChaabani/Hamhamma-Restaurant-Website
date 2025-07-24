<?php
header('Content-Type: application/json');

require_once 'db.php';

// Lecture des données envoyées par JavaScript
$data = json_decode(file_get_contents("php://input"), true);
$nom = strtolower(trim($data['nom']));
$date = trim($data['date']);

if (!$nom || !$date) {
    echo json_encode([]);
    exit;
}

// Préparation de la requête
$query = "
    SELECT 
        r.RESERVATION_ID,
        c.FULL_NAME,
        c.NUM_PHONE,
        r.CHOIX_ITEM,
        TO_CHAR(r.RESERVATION_DATETIME, 'YYYY-MM-DD') AS date_reservation,
        m.ITEM_ID
    FROM RESERVATION r
    JOIN CLIENT c ON r.CLIENT_ID = c.CLIENT_ID
    JOIN MENUITEM m ON r.CHOIX_ITEM = m.NAME_ITEM
    WHERE LOWER(c.FULL_NAME) LIKE :nom
      AND TRUNC(r.RESERVATION_DATETIME) = TO_DATE(:date_res, 'YYYY-MM-DD')
";

$stid = oci_parse($conn, $query);

// Liaison des paramètres
$searchNom = "%" . $nom . "%";
oci_bind_by_name($stid, ":nom", $searchNom);
oci_bind_by_name($stid, ":date_res", $date);

// Exécution
oci_execute($stid);

// Récupération des résultats
$results = [];
while ($row = oci_fetch_assoc($stid)) {
    $results[] = $row;
}

// Retour JSON
echo json_encode($results);

oci_free_statement($stid);
oci_close($conn);
?>
