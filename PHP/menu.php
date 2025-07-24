<?php
require_once 'db.php';

// Get all available menu items from database
$sql = "SELECT * FROM MenuItem WHERE DISPONIBLE = 1 ORDER BY ITEM_ID";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$menu_items = [];
while ($row = oci_fetch_assoc($stmt)) {
    $menu_items[] = $row;
}
oci_free_statement($stmt);

// Handle reservation form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_reservation'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $selected_item = trim($_POST['selected-item']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $guests = $_POST['guests'];
    
    // Combine date and time
    $reservation_datetime = $date . ' ' . $time;
    
    // First, insert or get client
    $client_sql = "INSERT INTO CLIENT (CLIENT_ID, FULL_NAME, NUM_PHONE, EMAIL) 
                   VALUES (SEQ_CLIENT.nextval, :name, :phone, :email)";
    $client_stmt = oci_parse($conn, $client_sql);
    oci_bind_by_name($client_stmt, ':name', $name);
    oci_bind_by_name($client_stmt, ':phone', $phone);
    oci_bind_by_name($client_stmt, ':email', $email);

    if (oci_execute($client_stmt)) {
        // Get the client ID
        $get_client_sql = "SELECT CLIENT_ID FROM CLIENT WHERE EMAIL = :email AND NUM_PHONE = :phone";
        $get_client_stmt = oci_parse($conn, $get_client_sql);
        oci_bind_by_name($get_client_stmt, ':email', $email);
        oci_bind_by_name($get_client_stmt, ':phone', $phone);
        oci_execute($get_client_stmt);

        $client_row = oci_fetch_assoc($get_client_stmt);
        $client_id = $client_row['CLIENT_ID'];

        // Insert reservation
        $reservation_sql = "INSERT INTO RESERVATION 
            (RESERVATION_ID, CLIENT_ID, CHOIX_ITEM, RESERVATION_DATETIME, NBR_PERSONNES) 
            VALUES 
            (SEQ_RESERVATION.nextval, :client_id, :item, 
             TO_TIMESTAMP(:datetime, 'YYYY-MM-DD HH24:MI'), :guests)";
        $reservation_stmt = oci_parse($conn, $reservation_sql);
        oci_bind_by_name($reservation_stmt, ':client_id', $client_id);
        oci_bind_by_name($reservation_stmt, ':item', $selected_item);
        oci_bind_by_name($reservation_stmt, ':datetime', $reservation_datetime);
        oci_bind_by_name($reservation_stmt, ':guests', $guests);
        
        if (oci_execute($reservation_stmt)) {
            $success_message = "Réservation effectuée avec succès!";
        } else {
            $error_message = "Erreur lors de la réservation.";
        }
        
        oci_free_statement($reservation_stmt);
        oci_free_statement($get_client_stmt);
    }
    oci_free_statement($client_stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../images/logo_1.png" type="image/x-icon">
    <link rel="stylesheet" href="../CSS/Messtyles.css">
    <script src="main.js"></script>
    <title>Menu</title>

    <style>
        .reservation-form {
            background: #ffffff;
            margin-left: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 500px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
    
        footer {
            background-color: #1a0e0ea9;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 1000px;
        }
        
        footer .social {
            border-radius: 50%;
        }
        
        footer .social-icons a {
            color: white;
            text-decoration: none;
            font-size: 20px;
            cursor: pointer;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    
    <div class="banner">
        <div class="navbar">
            <img src="../images/logo_1.png" alt="logo" class="logo"> 
            <div class="wavy">
                <h3>
                    <span class="span1" style="--i:1">h</span>
                    <span class="span1" style="--i:2">a</span>
                    <span class="span1" style="--i:3">m</span>
                    <span class="span1" style="--i:4">h</span>
                    <span class="span1" style="--i:5">a </span>
                    <span class="span1" style="--i:6">m</span>
                    <span class="span1" style="--i:7">a</span>
                    <span class="span1" style="--i:11">.</span> 
                    <span class="span1" style="--i:12">.</span>  
                    <span class="span1" style="--i:13">.</span>  
                </h3> 
            </div>
            <ul>
                <li class="li"><a href="../index.html">Accueil</a></li>
                <li class="li"><a href="../HTML/about.html">À propos</a></li>
                <li class="li"><a href="#">Menu</a></li>
                <li class="li"><a href="../HTML/contact_us_page.html">Contact</a></li>
                <li class="li"><a href="../HTML/Reservation_hamhamma.html">Réservation</a></li>
                <li class="li"><a href="../HTML/customer-review0.2.html">Avis</a></li>
                <li><a href="../HTML/Admin.html">Admin</a></li>
            </ul>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="success-message"><?= $success_message ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= $error_message ?></div>
    <?php endif; ?>

    <section class="menu-section" id="menu">
        <?php if (!empty($menu_items)): ?>
            <?php foreach ($menu_items as $item): ?>
                <div class="card">
                    <?php if (!empty($item['IMAGEPATH'])): ?>
                        <img src="<?= htmlspecialchars($item['IMAGEPATH']) ?>" alt="<?= htmlspecialchars($item['NAME_ITEM']) ?>">
                    <?php else: ?>
                        <img src="../images/default-dish.jpg" alt="<?= htmlspecialchars($item['NAME_ITEM']) ?>">
                    <?php endif; ?>
                    
                    <h3><?= htmlspecialchars($item['NAME_ITEM']) ?></h3>
                    
                    <p><?= htmlspecialchars($item['DESCRIPTION']) ?></p>
                    
                    <div class="stars">★★★★★</div>
                    
                    <div class="price"><?= number_format($item['PRIX'], 2) ?> DT</div>
                    
                    <a href="#selected-item">
                        <button onclick="selectItem('<?= htmlspecialchars($item['NAME_ITEM']) ?>')">Réservé</button>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <h3>Aucun plat disponible pour le moment</h3>
                <p>Revenez bientôt pour découvrir notre menu!</p>
            </div>
        <?php endif; ?>

        <form class="reservation-form" method="POST">
            <h2>Réserver un plat</h2>
            
            <div class="form-group">
                <div>
                    <label for="selected-item">Votre Choix:</label>
                    <input type="text" id="selected-item" name="selected-item" readonly required>
                </div><br>
                <div>
                    <label for="name">Nom complet</label>
                    <input type="text" id="name" name="name" placeholder="Entrez votre nom" required>
                    <div id="nameError" class="error"></div> 
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Entrez votre email" required>
                </div>
            </div>
    
            <div class="form-group">
                <div>
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone" placeholder="Entrez votre numéro de téléphone" required>
                    <div id="phoneError" class="error"></div>
                </div>
                <div>
                    <label for="date">Date de réservation</label>
                    <input type="date" id="date" name="date" required>
                    <div id="dateError" class="error"></div> 
                </div>
            </div>
    
            <div class="form-group">
                <div>
                    <label for="time">Heure</label>
                    <input type="time" id="time" name="time" required>
                </div>
                <div>
                    <label for="guests">Nombre de personnes</label>
                    <select id="guests" name="guests" required>
                        <option value="" disabled selected>Choisir...</option>
                        <option value="1">1 personne</option>
                        <option value="2">2 personnes</option>
                        <option value="3">3 personnes</option>
                        <option value="4">4 personnes</option>
                        <option value="5">5 personnes</option>
                        <option value="6">6 personnes</option>
                    </select>
                    <div id="guestsError" class="error"></div> 
                </div>
            </div>
    
            <div class="form-group full-width">
                <button type="submit" name="submit_reservation">Réserver</button>
            </div>
        </form>
        
   </section>

    <footer>
        <p>&copy; BIS : Mahdi Chaabani | Ghaith Homrani | Mehdi Znetti (S1) | Souhail Akermi.</p>
        <div class="social-icons">
            <a href="#"><img class="social" src="../images/Facebook_Logo_2023.png" width="30px" height="30px" alt=""></a>
            <a href="#"><img class="social" src="../images/twitterr.png" width="30px" height="30px" alt=""></a>
            <a href="#"><img class="social" src="../images/instaaa.png" width="30px" height="30px" alt=""></a>
        </div>
    </footer>

    <script>
        function selectItem(itemName) {
            document.getElementById('selected-item').value = itemName;
        }

        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
        });
    </script>
</body>
</html>