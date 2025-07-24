<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    

    $query = "SELECT COUNT(*) AS CNT FROM admin_tab WHERE username = :username AND pwd = :password";
    $stmt = oci_parse($conn, $query);

    oci_bind_by_name($stmt, ":username", $username);
    oci_bind_by_name($stmt, ":password", $password);

    if (oci_execute($stmt)) {
        $row = oci_fetch_assoc($stmt);
        $count = $row['CNT'];

        if ($count > 0) {
            
            header("Location: Adminpage.php");
            exit;
        } else {
            echo "<script>alert('Nom d\\'utilisateur ou mot de passe incorrect.'); window.history.back();</script>";
        }
    } else {
        $e = oci_error($stmt);
        echo "<script>alert('Erreur lors de l\\'exécution de la requête');</script>";
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>

