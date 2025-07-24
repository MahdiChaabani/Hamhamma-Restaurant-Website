<?php

require_once 'db.php';

// Initialize variables
$debug_message = '';
$editing_item = null;
$editing_reservation = null;

// CSRF Token generation (add this for security)
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$query = "SELECT DISTINCT
    cl.client_id,
    r.reservation_id, 
    cl.full_name, 
    cl.num_phone, 
    cl.email,
    r.choix_item, 
    TO_CHAR(r.reservation_datetime, 'YYYY-MM-DD') AS date_reservation,
    TO_CHAR(r.reservation_datetime, 'HH24:MI') AS time_reservation
FROM Reservation r
JOIN Client cl ON r.client_id = cl.client_id

";

// Apply filters if set
$where_conditions = [];
$bind_params = [];

if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $where_conditions[] = "TO_CHAR(r.reservation_datetime, 'YYYY-MM-DD') = :filter_date";
    $bind_params[':filter_date'] = $_GET['filter_date'];
}

if (isset($_GET['search_name']) && !empty($_GET['search_name'])) {
    $where_conditions[] = "UPPER(cl.full_name) LIKE UPPER(:search_name)";
    $bind_params[':search_name'] = '%' . $_GET['search_name'] . '%';
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY r.reservation_datetime DESC";

$stid = oci_parse($conn, $query);

// Bind parameters
foreach ($bind_params as $param => $value) {
    oci_bind_by_name($stid, $param, $bind_params[$param]);
}

oci_execute($stid);

$results = [];
while ($row = oci_fetch_assoc($stid)) {
    $results[] = $row;
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// RESERVATION CRUD FUNCTIONS

// Prepare reservation editing
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['prepare_edit_reservation'])) {
    $editing_reservation = [
        'RESERVATION_ID' => filter_var($_POST['edit_reservation_id'], FILTER_VALIDATE_INT),
        'CLIENT_ID' => filter_var($_POST['edit_client_id'], FILTER_VALIDATE_INT),
        'FULL_NAME' => sanitize_input($_POST['edit_full_name']),
        'NUM_PHONE' => sanitize_input($_POST['edit_phone']),
        'EMAIL' => sanitize_input($_POST['edit_email']),
        'CHOIX_ITEM' => sanitize_input($_POST['edit_item']),
        'DATE_RESERVATION' => sanitize_input($_POST['edit_date']),
        'TIME_RESERVATION' => sanitize_input($_POST['edit_time'])
    ];
    
    $debug_message = "Mode édition activé pour la réservation ID: " . $editing_reservation['RESERVATION_ID'];
}

// Update reservation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_reservation'])) {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $debug_message = "Erreur de sécurité : token CSRF invalide";
    } else {
        $reservation_id = filter_var($_POST['reservation_id'], FILTER_VALIDATE_INT);
        $client_id = filter_var($_POST['client_id'], FILTER_VALIDATE_INT);
        $full_name = sanitize_input($_POST['full_name']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $item = sanitize_input($_POST['item']);
        $date = sanitize_input($_POST['reservation_date']);
        $time = sanitize_input($_POST['reservation_time']);
        
        if ($reservation_id && $client_id && $full_name && $phone && $email && $item && $date && $time) {
            // Update both Client and Reservation tables
            $datetime_str = $date . ' ' . $time . ':00';
            
            // Update Client table
            $sql_client = "UPDATE Client SET 
                            full_name = :full_name,
                            num_phone = :phone,
                            email = :email
                          WHERE client_id = :client_id";
            
            $stmt_client = oci_parse($conn, $sql_client);
            oci_bind_by_name($stmt_client, ':full_name', $full_name);
            oci_bind_by_name($stmt_client, ':phone', $phone);
            oci_bind_by_name($stmt_client, ':email', $email);
            oci_bind_by_name($stmt_client, ':client_id', $client_id);
            
            // Update Reservation table
            $sql_reservation = "UPDATE Reservation SET 
                               choix_item = :item,
                               reservation_datetime = TO_DATE(:datetime, 'YYYY-MM-DD HH24:MI:SS')
                              WHERE reservation_id = :reservation_id";
            
            $stmt_reservation = oci_parse($conn, $sql_reservation);
            oci_bind_by_name($stmt_reservation, ':item', $item);
            oci_bind_by_name($stmt_reservation, ':datetime', $datetime_str);
            oci_bind_by_name($stmt_reservation, ':reservation_id', $reservation_id);
            
            $client_success = oci_execute($stmt_client);
            $reservation_success = oci_execute($stmt_reservation);
            
            if ($client_success && $reservation_success) {
                $debug_message = "Réservation mise à jour avec succès";
                oci_free_statement($stmt_client);
                oci_free_statement($stmt_reservation);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $e_client = oci_error($stmt_client);
                $e_reservation = oci_error($stmt_reservation);
                $debug_message = "Erreur de mise à jour : " . 
                               ($e_client ? htmlentities($e_client['message']) : '') . 
                               ($e_reservation ? htmlentities($e_reservation['message']) : '');
            }
            
            oci_free_statement($stmt_client);
            oci_free_statement($stmt_reservation);
        } else {
            $debug_message = "Données invalides pour la mise à jour de la réservation";
        }
    }
}

// Delete reservation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_reservation'])) {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $debug_message = "Erreur de sécurité : token CSRF invalide";
    } else {
        $reservation_id = filter_var($_POST['reservation_id'], FILTER_VALIDATE_INT);
        
        if ($reservation_id) {
            $sql = "DELETE FROM Reservation WHERE reservation_id = :reservation_id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':reservation_id', $reservation_id);
            
            if (oci_execute($stmt)) {
                $debug_message = "Réservation supprimée avec succès";
                oci_free_statement($stmt);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $e = oci_error($stmt);
                $debug_message = "Erreur de suppression : " . htmlentities($e['message']);
                oci_free_statement($stmt);
            }
        } else {
            $debug_message = "ID de réservation invalide";
        }
    }
}

// EXISTING MENU ITEM CRUD FUNCTIONS 

// Item editing preparation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['prepare_edit'])) {
  $editing_item = [
    'ITEM_ID' => filter_var($_POST['edit_id'], FILTER_VALIDATE_INT),
    'NAME_ITEM' => sanitize_input($_POST['edit_name']),
    'DESCRIPTION' => sanitize_input($_POST['edit_desc']),
    'PRIX' => filter_var($_POST['edit_price'], FILTER_VALIDATE_FLOAT),
    'IMAGEPATH' => sanitize_input($_POST['edit_image']),
    'DISPONIBLE' => filter_var($_POST['edit_available'], FILTER_VALIDATE_INT)
  ];
  
  $debug_message = "Mode édition activé pour l'article ID: " . $editing_item['ITEM_ID'];
}

// Delete menu item
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_item'])) {
  $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);

  if ($item_id) {
    $sql = "DELETE FROM MenuItem WHERE ITEM_ID = :item_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':item_id', $item_id);
    
    if (!oci_execute($stmt)) {
      $e = oci_error($stmt);
      $debug_message = "Erreur de suppression : " . htmlentities($e['message']);
    } else {
      $debug_message = "Article supprimé avec succès";
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
    }
    
    oci_free_statement($stmt);
  } else {
    $debug_message = "ID d'article invalide";
  }
}

// Handle adding new item
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_item'])) {
  $name = sanitize_input($_POST['item_name']);
  $desc = sanitize_input($_POST['item_desc']);
  $price = filter_var($_POST['item_price'], FILTER_VALIDATE_FLOAT);
  $image = sanitize_input($_POST['item_image']);
  $available = isset($_POST['item_available']) ? 1 : 0;

  if ($name && $price !== false) {
    $sql = "INSERT INTO MenuItem (ITEM_ID, NAME_ITEM, DISPONIBLE, DESCRIPTION, IMAGEPATH, PRIX)
            VALUES (SEQ_MENUITEM.nextval, :item_name, :item_available, :item_desc, :item_image, :item_price)";
    $stmt = oci_parse($conn, $sql);

    oci_bind_by_name($stmt, ':item_name', $name);
    oci_bind_by_name($stmt, ':item_desc', $desc);
    oci_bind_by_name($stmt, ':item_image', $image);
    oci_bind_by_name($stmt, ':item_price', $price);
    oci_bind_by_name($stmt, ':item_available', $available);

    if (!oci_execute($stmt)) {
      $e = oci_error($stmt);
      $debug_message = "Erreur d'ajout : " . htmlentities($e['message']);
    } else {
      $debug_message = "Article ajouté avec succès";
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
    }

    oci_free_statement($stmt);
  } else {
    $debug_message = "Données invalides pour l'ajout d'article";
  }
}

// Handle updating item
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_item'])) {
  $item_id = filter_var($_POST['item_id'], FILTER_VALIDATE_INT);
  $name = sanitize_input($_POST['item_name']);
  $desc = sanitize_input($_POST['item_desc']);
  $price = filter_var($_POST['item_price'], FILTER_VALIDATE_FLOAT);
  $image = sanitize_input($_POST['item_image']);
  $available = isset($_POST['item_available']) ? 1 : 0;
  
  if ($item_id && $name && $price !== false) {
    $sql = "UPDATE MenuItem 
            SET NAME_ITEM = :item_name,
                DESCRIPTION = :item_desc, 
                PRIX = :item_price, 
                IMAGEPATH = :item_image, 
                DISPONIBLE = :item_available 
            WHERE ITEM_ID = :item_id";
            
    $stmt = oci_parse($conn, $sql);
    
    oci_bind_by_name($stmt, ':item_name', $name);
    oci_bind_by_name($stmt, ':item_desc', $desc);
    oci_bind_by_name($stmt, ':item_price', $price);
    oci_bind_by_name($stmt, ':item_image', $image);
    oci_bind_by_name($stmt, ':item_available', $available);
    oci_bind_by_name($stmt, ':item_id', $item_id);
    
    $result = oci_execute($stmt);
    
    if (!$result) {
      $e = oci_error($stmt);
      $debug_message = "Erreur de mise à jour : " . htmlentities($e['message']);
    } else {
      $debug_message = "Mise à jour réussie!";
      oci_free_statement($stmt);
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
    }
    
    oci_free_statement($stmt);
  } else {
    $debug_message = "Données invalides pour la mise à jour d'article";
  }
}

// Get all menu items
$sql = "SELECT * FROM MenuItem ORDER BY ITEM_ID DESC";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$items = [];
while ($row = oci_fetch_assoc($stmt)) {
  $items[] = $row;
}
oci_free_statement($stmt);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Admin</title>
  <link rel="stylesheet" href="../CSS/styleadmin.css">
  <style>
    .debug-message {
      background-color: #ffffcc;
      border: 1px solid #ffcc00;
      padding: 10px;
      margin: 10px 0;
      font-family: monospace;
    }
    .cancel-button {
      display: inline-block;
      margin-left: 10px;
      padding: 5px 10px;
      background-color: #ccc;
      color: #333;
      text-decoration: none;
      border-radius: 3px;
    }
    .item-card {
      border: 1px solid #ddd;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 5px;
      background-color: #f9f9f9;
    }
    .existing-items {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }
    .reservation-edit-form {
      background-color: #f0f8ff;
      border: 1px solid #add8e6;
      padding: 15px;
      margin: 15px 0;
      border-radius: 5px;
    }
    .btn-edit, .btn-delete {
      padding: 5px 10px;
      margin: 2px;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      font-size: 12px;
    }
    .btn-edit {
      background-color: #4CAF50;
      color: white;
    }
    .btn-delete {
      background-color: #f44336;
      color: white;
    }
    .btn-edit:hover {
      background-color: #45a049;
    }
    .btn-delete:hover {
      background-color: #da190b;
    }
  </style>
</head>
<body>

<div class="header">
<i class="bi bi-box-arrow-left"></i>
        <a href="../index.html"> <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z"/>
          <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
        </svg></a>
Panneau d'Administration</div>

<?php if (!empty($debug_message)): ?>
  <div class="debug-message">
    <?php echo $debug_message; ?>
  </div>
<?php endif; ?>

<div class="toolbar">
  <form method="GET" action="">
    <label for="filter-date">Date :</label>
    <input type="date" id="filter-date" name="filter_date" 
           value="<?= isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : '' ?>">

    <label for="search-name">Nom du client :</label>
    <input type="text" id="search-name" name="search_name"
           value="<?= isset($_GET['search_name']) ? htmlspecialchars($_GET['search_name']) : '' ?>">

    <button type="submit">Filtrer</button>
    <button type="submit" name="reset" value="1">Réinitialiser</button>
  </form>
</div>

<div class="main">
  
  <div class="data-section">
    <h3>Commandes des Clients</h3>
    
    <!-- Reservation Edit Form -->
    <?php if ($editing_reservation): ?>
    <div class="reservation-edit-form">
      <h4>Modifier la réservation #<?= $editing_reservation['RESERVATION_ID'] ?></h4>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="reservation_id" value="<?= $editing_reservation['RESERVATION_ID'] ?>">
        <input type="hidden" name="client_id" value="<?= $editing_reservation['CLIENT_ID'] ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
          <div>
            <label for="full_name">Nom complet:</label>
            <input type="text" name="full_name" id="full_name" required 
                   value="<?= htmlspecialchars($editing_reservation['FULL_NAME']) ?>">
          </div>
          
          <div>
            <label for="phone">Téléphone:</label>
            <input type="text" name="phone" id="phone" required 
                   value="<?= htmlspecialchars($editing_reservation['NUM_PHONE']) ?>">
          </div>
          
          <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required 
                   value="<?= htmlspecialchars($editing_reservation['EMAIL']) ?>">
          </div>
          
          <div>
            <label for="item">Article:</label>
            <input type="text" name="item" id="item" required 
                   value="<?= htmlspecialchars($editing_reservation['CHOIX_ITEM']) ?>">
          </div>
          
          <div>
            <label for="reservation_date">Date:</label>
            <input type="date" name="reservation_date" id="reservation_date" required 
                   value="<?= htmlspecialchars($editing_reservation['DATE_RESERVATION']) ?>">
          </div>
          
          <div>
            <label for="reservation_time">Heure:</label>
            <input type="time" name="reservation_time" id="reservation_time" required 
                   value="<?= htmlspecialchars($editing_reservation['TIME_RESERVATION']) ?>">
          </div>
        </div>
        
        <div style="margin-top: 15px;">
          <button type="submit" name="update_reservation">Mettre à jour la réservation</button>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="cancel-button">Annuler</a>
        </div>
      </form>
    </div>
    <?php endif; ?>
    
    <table>
      <thead>
        <tr>
            <th>ID Client</th>
            <th>Nom Client</th>
            <th>Téléphone</th>
            <th>E-mail</th>
            <th>Article</th>
            <th>Date</th>
            <th>ID Réservation</th>
            <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($results)): ?>
          <?php foreach ($results as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['CLIENT_ID']) ?></td>
              <td><?= htmlspecialchars($row['FULL_NAME']) ?></td>
              <td><?= htmlspecialchars($row['NUM_PHONE']) ?></td>
              <td><?= htmlspecialchars($row['EMAIL']) ?></td>
              <td><?= htmlspecialchars($row['CHOIX_ITEM']) ?></td>
              <td><?= htmlspecialchars($row['DATE_RESERVATION']) ?></td>
              <td><?= htmlspecialchars($row['RESERVATION_ID']) ?></td>
              <td>
                <!-- Edit Button -->
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                  <input type="hidden" name="edit_reservation_id" value="<?= $row['RESERVATION_ID'] ?>">
                  <input type="hidden" name="edit_client_id" value="<?= $row['CLIENT_ID'] ?>">
                  <input type="hidden" name="edit_full_name" value="<?= htmlspecialchars($row['FULL_NAME']) ?>">
                  <input type="hidden" name="edit_phone" value="<?= htmlspecialchars($row['NUM_PHONE']) ?>">
                  <input type="hidden" name="edit_email" value="<?= htmlspecialchars($row['EMAIL']) ?>">
                  <input type="hidden" name="edit_item" value="<?= htmlspecialchars($row['CHOIX_ITEM']) ?>">
                  <input type="hidden" name="edit_date" value="<?= htmlspecialchars($row['DATE_RESERVATION']) ?>">
                  <input type="hidden" name="edit_time" value="<?= htmlspecialchars($row['TIME_RESERVATION'] ?? '12:00') ?>">
                  <button type="submit" name="prepare_edit_reservation" class="btn-edit">Modifier</button>
                </form>
                
                <!-- Delete Button -->
                <form method="POST" style="display: inline;" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation?');">
                  <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                  <input type="hidden" name="reservation_id" value="<?= $row['RESERVATION_ID'] ?>">
                  <button type="submit" name="delete_reservation" class="btn-delete">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align: center;">Aucune commande trouvée</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    
    <div class="actions">
      <a href="../HTML/Reservation_hamhamma.html?admin=1" target="_blank">
        <button>Ajouter une réservation</button>
      </a>
    </div>
  </div>

  <div class="form-section">
    <h3><?= $editing_item ? "Modifier l'article #" . $editing_item['ITEM_ID'] : "Ajouter un article au menu" ?></h3>
    
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      
      <?php if ($editing_item): ?>
        <input type="hidden" name="item_id" value="<?= $editing_item['ITEM_ID'] ?>">
      <?php endif; ?>

      <label for="item-name">Nom de l'article</label>
      <input type="text" name="item_name" id="item-name" required 
             value="<?= $editing_item ? htmlspecialchars($editing_item['NAME_ITEM']) : '' ?>">

      <label for="item-desc">Description</label>
      <input type="text" name="item_desc" id="item-desc" 
             value="<?= $editing_item ? htmlspecialchars($editing_item['DESCRIPTION']) : '' ?>">

      <label for="item-price">Prix (DT)</label>
      <input type="number" step="0.01" name="item_price" id="item-price" required 
             value="<?= $editing_item ? $editing_item['PRIX'] : '' ?>">

      <label for="item-image">URL de l'image</label>
      <input type="text" name="item_image" id="item-image" 
             value="<?= $editing_item ? htmlspecialchars($editing_item['IMAGEPATH']) : '' ?>">

      <div>
        <label>
          <input type="checkbox" name="item_available" 
                 <?= ($editing_item && intval($editing_item['DISPONIBLE']) === 1) ? 'checked' : '' ?>> 
          Disponible
        </label>
      </div>

      <div style="margin-top: 15px;">
        <button type="submit" name="<?= $editing_item ? 'update_item' : 'add_item' ?>">
          <?= $editing_item ? "Mettre à jour" : "Ajouter l'article" ?>
        </button>
        
        <?php if ($editing_item): ?>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="cancel-button">Annuler</a>
        <?php endif; ?>
      </div>
    </form>

    <hr style="margin: 25px 0; border-color: #ffeacc;">

    <h3>Éléments existants</h3>
    <div class="existing-items">
      <?php foreach ($items as $item): ?>
        <div class="item-card">
          <strong><?= htmlspecialchars($item['NAME_ITEM']) ?></strong><br>
          <em><?= htmlspecialchars($item['DESCRIPTION']) ?></em><br>
          <div>Prix : <strong><?= number_format($item['PRIX'], 2) ?> DT</strong></div>
          <div>Statut :
            <span style="color: <?= $item['DISPONIBLE'] ? 'green' : 'red' ?>;">
              <?= $item['DISPONIBLE'] ? 'Disponible' : 'Indisponible' ?>
            </span>
          </div>
          <div style="margin-top: 10px; display: flex; gap: 8px;">
            <!-- Modifier Form -->
            <form method="POST" style="display: inline;">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <input type="hidden" name="edit_id" value="<?= $item['ITEM_ID'] ?>">
              <input type="hidden" name="edit_name" value="<?= htmlspecialchars($item['NAME_ITEM']) ?>">
              <input type="hidden" name="edit_desc" value="<?= htmlspecialchars($item['DESCRIPTION']) ?>">
              <input type="hidden" name="edit_price" value="<?= $item['PRIX'] ?>">
              <input type="hidden" name="edit_image" value="<?= htmlspecialchars($item['IMAGEPATH']) ?>">
              <input type="hidden" name="edit_available" value="<?= $item['DISPONIBLE'] ?>">
              <button type="submit" name="prepare_edit">Modifier</button>
            </form>

            <!-- Supprimer Form -->
            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet élément?');">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <input type="hidden" name="item_id" value="<?= $item['ITEM_ID'] ?>">
              <button type="submit" name="delete_item">Supprimer</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      
      <?php if (empty($items)): ?>
        <p>Aucun élément de menu trouvé. Commencez par ajouter des articles!</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  // Simple script to handle the reset button
  document.addEventListener('DOMContentLoaded', function() {
    const resetButton = document.querySelector('button[name="reset"]');
    if (resetButton) {
      resetButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = '<?= $_SERVER['PHP_SELF'] ?>';
      });
    }
    
    // Auto-focus on edit forms when they appear
    const editForm = document.querySelector('.reservation-edit-form');
    if (editForm) {
      const firstInput = editForm.querySelector('input[type="text"]');
      if (firstInput) {
        firstInput.focus();
      }
    }
    
    // Confirmation for delete actions with more detailed message
    const deleteButtons = document.querySelectorAll('button[name="delete_reservation"]');
    deleteButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        const row = this.closest('tr');
        const clientName = row.querySelector('td:nth-child(2)').textContent;
        const reservationId = row.querySelector('td:nth-child(7)').textContent;
        
        const confirmMessage = `Êtes-vous sûr de vouloir supprimer la réservation #${reservationId} de ${clientName}?\n\nCette action est irréversible.`;
        
        if (!confirm(confirmMessage)) {
          e.preventDefault();
        }
      });
    });
    
    // Confirmation for menu item deletion
    const deleteItemButtons = document.querySelectorAll('button[name="delete_item"]');
    deleteItemButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        const itemCard = this.closest('.item-card');
        const itemName = itemCard.querySelector('strong').textContent;
        
        const confirmMessage = `Êtes-vous sûr de vouloir supprimer l'article "${itemName}" du menu?\n\nCette action est irréversible et peut affecter les réservations existantes.`;
        
        if (!confirm(confirmMessage)) {
          e.preventDefault();
        }
      });
    });
    
    // Smooth scroll to edit form when editing
    const editReservationForm = document.querySelector('.reservation-edit-form');
    if (editReservationForm) {
      editReservationForm.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
      });
    }
    
    // Form validation for reservation editing
    const reservationForm = document.querySelector('form[method="POST"]:has(button[name="update_reservation"])');
    if (reservationForm) {
      reservationForm.addEventListener('submit', function(e) {
        const phone = this.querySelector('input[name="phone"]').value;
        const email = this.querySelector('input[name="email"]').value;
        const date = this.querySelector('input[name="reservation_date"]').value;
        const time = this.querySelector('input[name="reservation_time"]').value;
        
        // Basic phone validation 
        const phoneRegex = /^[0-9+\-\s\(\)]+$/;
        if (!phoneRegex.test(phone)) {
          alert('Format de téléphone invalide');
          e.preventDefault();
          return;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          alert('Format d\'email invalide');
          e.preventDefault();
          return;
        }
        
        // Date validation (not in the past)
        const selectedDate = new Date(date + 'T' + time);
        const now = new Date();
        if (selectedDate < now) {
          const confirmPast = confirm('La date/heure sélectionnée est dans le passé. Voulez-vous continuer?');
          if (!confirmPast) {
            e.preventDefault();
            return;
          }
        }
      });
    }
    
    
    setInterval(function() {
      if (!document.querySelector('.reservation-edit-form')) {
        // Only refresh if not currently editing
        window.location.reload();
      }
    }, 300000); // 5 minutes
    
  });
  
  // Function to print the reservations table
  function printReservations() {
    const printWindow = window.open('', '_blank');
    const table = document.querySelector('.data-section table').outerHTML;
    const styles = `
      <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions { display: none; }
      </style>
    `;
    
    printWindow.document.write(`
      <html>
        <head>
          <title>Réservations - ${new Date().toLocaleDateString()}</title>
          ${styles}
        </head>
        <body>
          <h1>Réservations - ${new Date().toLocaleDateString()}</h1>
          ${table}
        </body>
      </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
  }
  
  
</script>

</body>
</html>