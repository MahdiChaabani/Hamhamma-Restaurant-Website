<?php
// Clear any previous output
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent any unwanted output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once 'db.php';

// Initialize log file
$log_file = 'oracle_log.txt';

try {
    // Check connection
    if (!$conn) {
        throw new Exception(oci_error()['message']);
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception('Méthode non autorisée. Utilisez POST.');
    }

    // Get and validate form data
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = filter_input(INPUT_POST, 'comnt', FILTER_SANITIZE_STRING) ?? '';

    if (!$item_id || !$rating) {
        throw new Exception('Données invalides: item_id et rating sont requis');
    }

    // Enregistrer les tentatives de connexion pour débogage
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Tentative de connexion à Oracle\n", FILE_APPEND);

    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Connexion réussie\n", FILE_APPEND);

    // Journaliser les données reçues
    $data_log = "Données reçues: item_id=$item_id, rating=$rating, comment=$comment";
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - $data_log\n", FILE_APPEND);

    $validate_item_query = "SELECT COUNT(*) AS CNT FROM \"MENUITEM\" WHERE ITEM_ID = :item_id";
    $validate_stmt = oci_parse($conn, $validate_item_query);
    oci_bind_by_name($validate_stmt, ':item_id', $item_id);
    oci_execute($validate_stmt);
    $item_row = oci_fetch_assoc($validate_stmt);

    if ($item_row['CNT'] == 0) {
        throw new Exception('Invalid ITEM_ID');
    }

    $table_check = "SELECT table_name FROM user_tables WHERE table_name = 'CLIENTFEEDBACK'";
    $stmt_check = oci_parse($conn, $table_check);
    oci_execute($stmt_check);
    $table_exists = false;
    while ($row = oci_fetch_array($stmt_check, OCI_ASSOC)) {
        $table_exists = true;
    }

    if (!$table_exists) {
        // La table n'existe pas, la créer
        $create_table = "CREATE TABLE CLIENTFEEDBACK (
            FEEDBACK_ID NUMBER PRIMARY KEY,
            ITEM_ID NUMBER NOT NULL,
            RATING NUMBER(1) NOT NULL CHECK (RATING BETWEEN 1 AND 5),
            COMNT VARCHAR2(1000),
            DATE_INTERACTED TIMESTAMP DEFAULT SYSTIMESTAMP
        )";
        $stmt_create = oci_parse($conn, $create_table);
        oci_execute($stmt_create);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Table CLIENTFEEDBACK créée\n", FILE_APPEND);
    }

    $insert_query = "INSERT INTO \"CLIENTFEEDBACK\" 
                    (FEEDBACK_ID, ITEM_ID, RATING, COMNT, DATE_INTERACTED)
                    VALUES 
                    (\"SEQ_CLIENTFEEDBACK\".NEXTVAL,
                     :item_id, 
                     :rating, 
                     :comnt, 
                     SYSTIMESTAMP)";
    $stmt = oci_parse($conn, $insert_query);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Exécution insertion\n", FILE_APPEND);

    // Bind variables
    oci_bind_by_name($stmt, ':item_id', $item_id);
    oci_bind_by_name($stmt, ':rating', $rating);
    oci_bind_by_name($stmt, ':comnt', $comment);

    $result = oci_execute($stmt);

    if (!$result) {
        $e = oci_error($stmt);
        throw new Exception('Erreur lors de l\'exécution de la requête: ' . $e['message']);
    }
    echo json_encode([
        'status' => 'success',
        'message' => 'Feedback enregistré ou mis à jour avec succès'
    ]);

} catch (Exception $e) {
    // Log error
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Send error response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn)) {
        oci_close($conn);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Connexion fermée\n", FILE_APPEND);
    }
}
?>