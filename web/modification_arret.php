<?php
$pdo = new PDO('mysql:host=db;port=3306;dbname=TRANSPORT;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

//pour la bounding box
define('BELGIUM_MIN_LON', 2.51357303225);
define('BELGIUM_MIN_LAT', 49.5294835476);
define('BELGIUM_MAX_LON', 6.15665815596);
define('BELGIUM_MAX_LAT', 51.4750237087);

function isInBelgium($latitude, $longitude) {
    return ($latitude >= BELGIUM_MIN_LAT && $latitude <= BELGIUM_MAX_LAT &&
            $longitude >= BELGIUM_MIN_LON && $longitude <= BELGIUM_MAX_LON);
}

$message = '';
$selectedStop = null;

$query = "SELECT ID, NOM FROM ARRET ORDER BY NOM";
$req = $pdo->prepare($query);
$req->execute();
$stops = $req->fetchAll();

if (isset($_POST['select_stop'])) { // récupérer toutes les infos relatives à un arrêt
    $stopId = $_POST['stop_id'];
    
    $req = $pdo->prepare("SELECT ID, NOM, LATITUDE, LONGITUDE FROM ARRET WHERE ID = :id");
    $req->execute([":id" => $stopId]);
    $selectedStop = $req->fetch();

}


if (isset($_POST['update_stop'])) { // si l'utilisateur fait le choix de modifier un arrêt
    
    try {

        $pdo->beginTransaction();

        $oldId = trim($_POST['old_id']);
        $newId = trim($_POST['new_id']);
        $nom = trim($_POST['nom']);
        $latitude = trim($_POST['latitude']);
        $longitude = trim($_POST['longitude']);
        
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new Exception("Les coordonnées doivent être numériques");
        }
        
        if (!isInBelgium($latitude, $longitude)) {
            throw new Exception("Les coordonnées doivent être situées en Belgique (latitude entre " . 
                                BELGIUM_MIN_LAT . " et " . BELGIUM_MAX_LAT . 
                                ", longitude entre " . BELGIUM_MIN_LON . " et " . BELGIUM_MAX_LON . ")");
        }
        
        // on vérifie si le nouvel id existe déjà (dans le cas cas où on le change)
        if ($oldId != $newId) {
            $req = $pdo->prepare("SELECT ID FROM ARRET WHERE ID = :new_id AND ID != :old_id");
            $req->execute([":new_id" => $newId, ":old_id" => $oldId]);
            $result = $req->fetchAll();
            
            if (count($result) > 0) {
                throw new Exception("L'identifiant " . $newId . " existe déjà");
            }
        }
        
        // modification de l'arrêt si on ne change pas l'iD
        $flag_change = false;
        if ($oldId == $newId) {
            $req = $pdo->prepare("UPDATE ARRET SET NOM = :nom, LATITUDE = :latitude, LONGITUDE = :longitude WHERE ID = :oldId");
            $req->execute([":nom" => $nom,":latitude" => $latitude,":longitude" => $longitude,":oldId" => $oldId]);

            $flag_change = ($req->rowCount() > 0) ? true : false;
        } else {

            // on ajoute l'arrêt avec le nouvel id
            $req = $pdo->prepare("INSERT INTO ARRET (ID, NOM, LATITUDE, LONGITUDE) VALUES (:id, :nom, :latitude, :longitude)");
            $req->execute([":id" => $newId, ":nom" => $nom, ":latitude" => $latitude, ":longitude" => $longitude]);
            $flag_update = ($req->rowCount() > 0) ? true : false;
            // il faut donc modifier les clés étrangères des autres tables dans le cas où on change l'ID
            
            // Update ARRET_DESSERVI
            $req = $pdo->prepare("UPDATE ARRET_DESSERVI SET ARRET_ID = :newId WHERE ARRET_ID = :oldId");
            $req->execute([":newId" => $newId, ":oldId" => $oldId]);
            
            // Update HORRAIRE
            $req = $pdo->prepare("UPDATE HORRAIRE SET ARRET_ID = :newId WHERE ARRET_ID = :oldId");
            $req->execute([":newId" => $newId, ":oldId" => $oldId]);
            
            // on supprime l'arrêt avec l'ancien id ==> on évite les problèmes de contraintes ainsi
            $req = $pdo->prepare("DELETE FROM ARRET WHERE ID = :oldId");
            $req->execute([":oldId" => $oldId]);
            $flag_delete = ($req->rowCount() > 0) ? true : false;

            $flag_change = ($flag_update && $flag_delete) ? true : false;
        }
        
        if ($flag_change) {
            $message = "Arrêt mis à jour avec succès";
            
            $stmt = $pdo->prepare("SELECT ID, NOM, LATITUDE, LONGITUDE FROM ARRET WHERE ID = :newId");
            $stmt->execute([":newId" => $newId]);
            $selectedStop = $stmt->fetch();
            
        } else {
            throw new Exception("Erreur lors de la mise à jour de l'arrêt");
        }
        
        $pdo->commit(); // si tout s'est bien passé
    } catch (Exception $e) {
        
        $pdo->rollback();
        $message = "Erreur : " . $e->getMessage();
        
        //pour le formulaire
        $selectedStop = [
            'ID' => $_POST['old_id'],
            'NOM' => $_POST['nom'],
            'LATITUDE' => $_POST['latitude'],
            'LONGITUDE' => $_POST['longitude']
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification d'un arrêt</title>
    <link rel="stylesheet"  type="text/css" href="/style.css"/>
</head>
<body>
    
    <header>
        <div class="navigation">
            <a href="/index.php">Index</a>
            <a href="/gestion_tables.php">Recherche</a>
            <a href="/ajout_service.php">Ajout service</a>
            <a href="/dates_service.php">Services disponibles</a>
            <a href="/stats_temps_arret.php">Statistiques arrêt</a>
            <a href="/recherche_gare.php">Recherche gare</a>
            <a href="/gestion_iti_trajet.php">Gestion des itinéraires et trajets</a>
            <a href="/modification_arret.php" class="active">Modifier arrêt</a>
        </div>
    </header>

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
