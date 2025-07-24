<?php
// Connexion à la base Oracle
require_once 'db.php';

// Function to validate phone number
function validatePhoneNumber($phone) {
    // Remove all non-digit characters for validation
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if phone number has appropriate length (8-15 digits)
    if (strlen($cleanPhone) < 8 || strlen($cleanPhone) > 15) {
        return false;
    }
    
    // Basic pattern check for common formats
    $patterns = [
        '/^(\+33|0)[1-9](\d{8})$/',  // French format
        '/^\+?[1-9]\d{7,14}$/'       // International format
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return true;
        }
    }
    
    return false;
}

// Function to validate reservation date and time
function validateReservationDateTime($date, $time) {
    $errors = [];
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = "Format de date invalide. Utilisez YYYY-MM-DD.";
        return $errors;
    }
    
    // Validate time format
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        $errors[] = "Format d'heure invalide. Utilisez HH:MM.";
        return $errors;
    }
    
    // Create datetime object for further validation
    $datetime = DateTime::createFromFormat('Y-m-d H:i', "$date $time");
    
    if (!$datetime) {
        $errors[] = "Date ou heure invalide.";
        return $errors;
    }
    
    // Check if date is not in the past
    $now = new DateTime();
    if ($datetime <= $now) {
        $errors[] = "La date de réservation doit être dans le futur.";
    }
    
    // Check if date is not too far in the future (e.g., max 1 year)
    $maxDate = clone $now;
    $maxDate->add(new DateInterval('P1Y'));
    if ($datetime > $maxDate) {
        $errors[] = "La date de réservation ne peut pas dépasser un an.";
    }
    
    // Check restaurant opening hours (assuming 11:00 to 23:00)
    $hour = (int)$datetime->format('H');
    $minute = (int)$datetime->format('i');
    
    if ($hour < 11 || $hour > 23 || ($hour == 23 && $minute > 0)) {
        $errors[] = "Les réservations sont acceptées entre 11h00 et 23h00.";
    }
    
    // Check if it's not a Monday (assuming restaurant is closed on Mondays)
    if ($datetime->format('N') == 1) {
        $errors[] = "Le restaurant est fermé le lundi.";
    }
    
    return $errors;
}

// Récupérer les données du formulaire
$choix = $_POST['selected-item'] ?? '';
$nom = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$date = $_POST['date'] ?? '';
$heure = $_POST['time'] ?? '';
$nb_personnes = (int)($_POST['guests'] ?? 0);

// Array to collect all validation errors
$validation_errors = [];

// Basic field validation
if (empty($nom)) {
    $validation_errors[] = "Le nom est requis.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $validation_errors[] = "Une adresse email valide est requise.";
}

if (empty($choix)) {
    $validation_errors[] = "Veuillez sélectionner un choix du menu.";
}

if ($nb_personnes < 1 || $nb_personnes > 20) {
    $validation_errors[] = "Le nombre de personnes doit être entre 1 et 20.";
}

// Validate phone number
if (empty($phone)) {
    $validation_errors[] = "Le numéro de téléphone est requis.";
} elseif (!validatePhoneNumber($phone)) {
    $validation_errors[] = "Le numéro de téléphone n'est pas valide. Utilisez un format comme +33123456789 ou 0123456789.";
}

// Validate reservation date and time
if (empty($date)) {
    $validation_errors[] = "La date de réservation est requise.";
}

if (empty($heure)) {
    $validation_errors[] = "L'heure de réservation est requise.";
}

if (!empty($date) && !empty($heure)) {
    $datetime_errors = validateReservationDateTime($date, $heure);
    $validation_errors = array_merge($validation_errors, $datetime_errors);
}

// If there are validation errors, display them and stop execution
if (!empty($validation_errors)) {
    echo "<div style='color: red; font-weight: bold; border: 1px solid red; padding: 10px; margin: 10px; background-color: #ffe6e6;'>";
    echo "<h3>Erreurs de validation :</h3>";
    echo "<ul>";
    foreach ($validation_errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    oci_close($conn);
    exit;
}

// If validation passes, continue with the original logic
$reservation_datetime = date('Y-m-d H:i:s', strtotime("$date $heure"));

// 1. Vérifier si le client existe déjà
$sql_check_client = "SELECT client_id FROM Client WHERE email = :email";
$stid = oci_parse($conn, $sql_check_client);
oci_bind_by_name($stid, ":email", $email);
oci_execute($stid);
$row = oci_fetch_assoc($stid);

if ($row) {
    // Client exists, use existing client_id
    $client_id = $row['CLIENT_ID'];
} else {
    // Client doesn't exist, create new client
    
    // First, insert into usr table and get the sequence value
    $sql_get_seq = "SELECT seq_usr.NEXTVAL as new_id FROM DUAL";
    $stmt_seq = oci_parse($conn, $sql_get_seq);
    oci_execute($stmt_seq);
    $row_seq = oci_fetch_assoc($stmt_seq);
    $new_user_id = $row_seq['NEW_ID'];
    
    // Now insert with the known ID
    $sql_usr = "INSERT INTO usr (user_id, full_name, email)
                VALUES (:user_id, :name, :email)";
    $stmt_usr = oci_parse($conn, $sql_usr);
    oci_bind_by_name($stmt_usr, ":user_id", $new_user_id);
    oci_bind_by_name($stmt_usr, ":name", $nom);
    oci_bind_by_name($stmt_usr, ":email", $email);
    oci_execute($stmt_usr);
    
    // Now insert into Client table using the returned user_id
    $sql_client = "INSERT INTO Client (client_id, full_name, num_phone, email)
                   VALUES (:user_id, :name, :phone, :email)";
    $stmt_client = oci_parse($conn, $sql_client);
    oci_bind_by_name($stmt_client, ":user_id", $new_user_id);
    oci_bind_by_name($stmt_client, ":name", $nom);
    oci_bind_by_name($stmt_client, ":phone", $phone);
    oci_bind_by_name($stmt_client, ":email", $email);
    oci_execute($stmt_client);
    
    // Set client_id for reservation
    $client_id = $new_user_id;
}

// 2. Check for existing reservations at the same time (avoid double booking)
$sql_check_existing = "SELECT COUNT(*) as count_reservations 
                       FROM Reservation 
                       WHERE client_id = :client_id 
                       AND reservation_datetime BETWEEN 
                           TO_TIMESTAMP(:datetime, 'YYYY-MM-DD HH24:MI:SS') - INTERVAL '1' HOUR
                           AND TO_TIMESTAMP(:datetime, 'YYYY-MM-DD HH24:MI:SS') + INTERVAL '1' HOUR";

$stid_check = oci_parse($conn, $sql_check_existing);
oci_bind_by_name($stid_check, ":client_id", $client_id);
oci_bind_by_name($stid_check, ":datetime", $reservation_datetime);
oci_execute($stid_check);
$existing_reservation = oci_fetch_assoc($stid_check);

if ($existing_reservation && $existing_reservation['COUNT_RESERVATIONS'] > 0) {
    echo "<div style='color: red; font-weight: bold;'>Vous avez déjà une réservation proche de cette heure. Veuillez choisir un autre créneau.</div>";
    oci_close($conn);
    exit;
}

// 3. Attribuer une table disponible
// Find a table with enough seats
$sql_table = "SELECT table_id FROM RestaurantTable 
              WHERE seats >= :nb_personnes 
              AND ROWNUM = 1";

$stid_table = oci_parse($conn, $sql_table);
oci_bind_by_name($stid_table, ":nb_personnes", $nb_personnes);
oci_execute($stid_table);
$table_row = oci_fetch_assoc($stid_table);

if (!$table_row) {
    echo "<div style='color: red; font-weight: bold;'>Désolé, aucune table n'est disponible pour ce créneau horaire et ce nombre de personnes.</div>";
    oci_close($conn);
    exit;
}

$table_id = $table_row['TABLE_ID'];

// 4. Insertion de la réservation
// Préparation de la date pour Oracle
$sql_date_format = "TO_TIMESTAMP(:datetime, 'YYYY-MM-DD HH24:MI:SS')";

// Construction de la requête d'insertion
$sql_insert = "INSERT INTO Reservation (
    reservation_id, 
    reservation_datetime, 
    nbr_personnes, 
    choix_item, 
    client_id, 
    table_id
) VALUES (
    seq_reservation.NEXTVAL, 
    $sql_date_format,
    :nb_personnes, 
    :choix, 
    :client_id, 
    :table_id
)";

// Préparation et exécution de la requête
$stid_insert = oci_parse($conn, $sql_insert);
oci_bind_by_name($stid_insert, ":datetime", $reservation_datetime);
oci_bind_by_name($stid_insert, ":nb_personnes", $nb_personnes);
oci_bind_by_name($stid_insert, ":choix", $choix);
oci_bind_by_name($stid_insert, ":client_id", $client_id);
oci_bind_by_name($stid_insert, ":table_id", $table_id);

$result = oci_execute($stid_insert);

if ($result) {
    echo "<div style='color: green; font-weight: bold; border: 1px solid green; padding: 10px; margin: 10px; background-color: #e6ffe6;'>";
    echo "<h3>Réservation enregistrée avec succès !</h3>";
    echo "<p><strong>Détails de votre réservation :</strong></p>";
    echo "<ul>";
    echo "<li>Nom: " . htmlspecialchars($nom) . "</li>";
    echo "<li>Email: " . htmlspecialchars($email) . "</li>";
    echo "<li>Téléphone: " . htmlspecialchars($phone) . "</li>";
    echo "<li>Date et heure: " . date('d/m/Y à H:i', strtotime($reservation_datetime)) . "</li>";
    echo "<li>Nombre de personnes: " . $nb_personnes . "</li>";
    echo "<li>Choix du menu: " . htmlspecialchars($choix) . "</li>";
    echo "<li>Table assignée: " . $table_id . "</li>";
    echo "</ul>";
    echo "</div>";
} else {
    $e = oci_error($stid_insert);
    echo "<div style='color: red; font-weight: bold; border: 1px solid red; padding: 10px; margin: 10px; background-color: #ffe6e6;'>";
    echo "<h3>Erreur lors de l'insertion</h3>";
    echo "<p>Message d'erreur : " . htmlspecialchars($e['message']) . "</p>";
    echo "<p>Code d'erreur: " . $e['code'] . "</p>";
    echo "</div>";
}

oci_close($conn);
?>