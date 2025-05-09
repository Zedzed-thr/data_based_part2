<?php
// Initialize database connection
function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "projet_bd";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set to UTF-8
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Constants for Belgian bounding box
define('BELGIUM_MIN_LON', 2.51357303225);
define('BELGIUM_MIN_LAT', 49.5294835476);
define('BELGIUM_MAX_LON', 6.15665815596);
define('BELGIUM_MAX_LAT', 51.4750237087);

// Initialize variables
$message = '';
$selectedStop = null;
$stops = array();

// Connect to the database
$conn = connectDB();

// Function to validate coordinates are within Belgium
function isInBelgium($latitude, $longitude) {
    return ($latitude >= BELGIUM_MIN_LAT && $latitude <= BELGIUM_MAX_LAT &&
            $longitude >= BELGIUM_MIN_LON && $longitude <= BELGIUM_MAX_LON);
}

// Get all stops from the database
$sql = "SELECT ID, NOM, LATITUDE, LONGITUDE FROM ARRET ORDER BY NOM";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stops[] = $row;
    }
}

// Handle stop selection
if (isset($_POST['select_stop'])) {
    $stopId = $_POST['stop_id'];
    
    // Get selected stop details
    $stmt = $conn->prepare("SELECT ID, NOM, LATITUDE, LONGITUDE FROM ARRET WHERE ID = ?");
    $stmt->bind_param("i", $stopId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selectedStop = $result->fetch_assoc();
    }
    
    $stmt->close();
}

// Handle stop update
if (isset($_POST['update_stop'])) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get form data
        $oldId = $_POST['old_id'];
        $newId = $_POST['new_id'];
        $nom = $_POST['nom'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        
        // Validate coordinates
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new Exception("Les coordonnées doivent être numériques");
        }
        
        // Check if coordinates are within Belgium
        if (!isInBelgium($latitude, $longitude)) {
            throw new Exception("Les coordonnées doivent être situées en Belgique (latitude entre " . 
                                BELGIUM_MIN_LAT . " et " . BELGIUM_MAX_LAT . 
                                ", longitude entre " . BELGIUM_MIN_LON . " et " . BELGIUM_MAX_LON . ")");
        }
        
        // Check if the new ID already exists (if it's different from the old one)
        if ($oldId != $newId) {
            $stmt = $conn->prepare("SELECT ID FROM ARRET WHERE ID = ? AND ID != ?");
            $stmt->bind_param("ii", $newId, $oldId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception("L'identifiant " . $newId . " existe déjà");
            }
            
            $stmt->close();
        }
        
        // Update the stop
        if ($oldId == $newId) {
            // Update without changing ID
            $stmt = $conn->prepare("UPDATE ARRET SET NOM = ?, LATITUDE = ?, LONGITUDE = ? WHERE ID = ?");
            $stmt->bind_param("sddi", $nom, $latitude, $longitude, $oldId);
        } else {
            // We need to update foreign key references in other tables first
            
            // Update ARRET_DESSERVI
            $stmt = $conn->prepare("UPDATE ARRET_DESSERVI SET ARRET_ID = ? WHERE ARRET_ID = ?");
            $stmt->bind_param("ii", $newId, $oldId);
            $stmt->execute();
            $stmt->close();
            
            // Update HORRAIRE
            $stmt = $conn->prepare("UPDATE HORRAIRE SET ARRET_ID = ? WHERE ARRET_ID = ?");
            $stmt->bind_param("ii", $newId, $oldId);
            $stmt->execute();
            $stmt->close();
            
            // Now update the stop (or delete and insert)
            $stmt = $conn->prepare("DELETE FROM ARRET WHERE ID = ?");
            $stmt->bind_param("i", $oldId);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare("INSERT INTO ARRET (ID, NOM, LATITUDE, LONGITUDE) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isdd", $newId, $nom, $latitude, $longitude);
        }
        
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $message = "Arrêt mis à jour avec succès";
            $conn->commit();
            
            // Refresh selected stop data
            $stmt->close();
            $stmt = $conn->prepare("SELECT ID, NOM, LATITUDE, LONGITUDE FROM ARRET WHERE ID = ?");
            $stmt->bind_param("i", $newId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $selectedStop = $result->fetch_assoc();
            } else {
                $selectedStop = null;
            }
        } else {
            throw new Exception("Erreur lors de la mise à jour de l'arrêt");
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Erreur : " . $e->getMessage();
        
        // Keep the form data
        $selectedStop = array(
            'ID' => $_POST['old_id'],
            'NOM' => $_POST['nom'],
            'LATITUDE' => $_POST['latitude'],
            'LONGITUDE' => $_POST['longitude']
        );
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification d'un arrêt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
        }
        form {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            width: auto;
            padding: 10px 15px;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-panel {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modification d'un arrêt</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Erreur') === false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Form to select a stop -->
        <form method="post" action="">
            <h2>Sélectionner un arrêt</h2>
            <label for="stop_id">Arrêt:</label>
            <select name="stop_id" id="stop_id" required>
                <option value="">-- Sélectionner un arrêt --</option>
                <?php foreach ($stops as $stop): ?>
                    <option value="<?php echo htmlspecialchars($stop['ID']); ?>" <?php echo (isset($_POST['stop_id']) && $_POST['stop_id'] == $stop['ID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($stop['NOM']) . ' (ID: ' . $stop['ID'] . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="select_stop" value="Sélectionner">
        </form>
        
        <?php if ($selectedStop): ?>
            <!-- Form to edit the selected stop -->
            <form method="post" action="">
                <h2>Modifier l'arrêt: <?php echo htmlspecialchars($selectedStop['NOM']); ?></h2>
                
                <input type="hidden" name="old_id" value="<?php echo htmlspecialchars($selectedStop['ID']); ?>">
                
                <label for="new_id">ID:</label>
                <input type="number" name="new_id" id="new_id" value="<?php echo htmlspecialchars($selectedStop['ID']); ?>" required>
                
                <label for="nom">Nom:</label>
                <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($selectedStop['NOM']); ?>" required>
                
                <label for="latitude">Latitude:</label>
                <input type="text" name="latitude" id="latitude" value="<?php echo htmlspecialchars($selectedStop['LATITUDE']); ?>" required>
                
                <label for="longitude">Longitude:</label>
                <input type="text" name="longitude" id="longitude" value="<?php echo htmlspecialchars($selectedStop['LONGITUDE']); ?>" required>
                
                <div class="info-panel">
                    <p><strong>Limites coordonnées Belgique:</strong></p>
                    <p>Latitude: entre <?php echo BELGIUM_MIN_LAT; ?> et <?php echo BELGIUM_MAX_LAT; ?></p>
                    <p>Longitude: entre <?php echo BELGIUM_MIN_LON; ?> et <?php echo BELGIUM_MAX_LON; ?></p>
                </div>
                
                <input type="submit" name="update_stop" value="Mettre à jour">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
