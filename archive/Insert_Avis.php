<?php
require_once 'db.php';
if (!$conn) {
    $e = oci_error();
    die("Connexion échouée : " . $e['message']);
}


$item_id = $_POST['item_id'];
$rating = $_POST['rating'];
$comnt = $_POST['comnt'];
$date_interacted = date('Y-m-d H:i:s');

// Préparer la requête
$sql = "INSERT INTO ClientFeedback (feedback_id,item_id, rating, comnt, date_interacted)
        VALUES (seq_feedback.nextval,:item_id, :rating, :comnt, TO_TIMESTAMP(:date_interacted, 'YYYY-MM-DD HH24:MI:SS'))";

$stid = oci_parse($conn, $sql);


oci_bind_by_name($stid, ':item_id', $item_id);
oci_bind_by_name($stid, ':rating', $rating);
oci_bind_by_name($stid, ':comnt', $comnt);
oci_bind_by_name($stid, ':date_interacted', $date_interacted);

// Exécution
if (oci_execute($stid)) {
    echo "Merci pour votre avis !";
} else {
    $e = oci_error($stid);
    echo "Erreur : " . $e['message'];
}

oci_free_statement($stid);
oci_close($conn);
?>
