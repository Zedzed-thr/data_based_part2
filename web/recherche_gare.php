<?php
// ==== PARTIE SQL ===
// connexion
$pdo = new PDO('mysql:host=db;port=3306;dbname=TRANSPORT;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// formulaire
$searchTerm = '';
$minNumber = null;
$results = [];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchTerm = trim($_POST['search_term'] ?? '');
    $minNumber = isset($_POST['min_number']) && is_numeric($_POST['min_number']) ? (int)$_POST['min_number'] : null;

    if ($searchTerm !== '') {
        try {
            // Requête SQL pour rechercher les gares
            $query = "
            SELECT 
                arret.ID,
                arret.NOM AS nom_gare,
                IFNULL(service.NOM,'Pas de service') AS nom_service,
                COUNT(DISTINCT horraire.TRAJET_ID) AS nb_trajets, -- on compte le nbr de trajet distinct qui passe par une gare
                SUM(CASE WHEN horraire.HEURE_ARRIVEE IS NOT NULL THEN 1 ELSE 0 END) AS nb_arrivees, -- +1 si nbr d'heure arrive/depart est non null sinn +0
                SUM(CASE WHEN horraire.HEURE_DEPART IS NOT NULL THEN 1 ELSE 0 END) AS nb_departs
            FROM ARRET arret
            LEFT JOIN HORRAIRE horraire ON arret.ID = horraire.ARRET_ID -- on lie les gares et les heures d'arrivee/depart par trajet
            LEFT JOIN TRAJET trajet ON horraire.TRAJET_ID = trajet.ID
            LEFT JOIN SERVICE service ON trajet.SERVICE_ID = service.ID
            WHERE LOWER(arret.NOM) LIKE LOWER(:searchTerm)
            GROUP BY arret.ID, arret.NOM, service.ID, service.NOM  -- on regroupe les results 
            ";
            
            // condition pour le nbr min
            if ($minNumber !== null) {
                $query .= " HAVING nb_trajets >= :minNumber OR nb_arrivees >= :minNumber OR nb_departs >= :minNumber";
            }
            //ordre decroissant
            $query .= " ORDER BY nb_trajets DESC, nb_arrivees DESC, nb_departs DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':searchTerm', '%'.$searchTerm.'%');
            
            if ($minNumber !== null) {
                $stmt->bindValue(':minNumber', $minNumber, PDO::PARAM_INT);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "Erreur lors de la recherche : " . $e->getMessage();
        }
    }
}
// === PARTIE HTML ====
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche de gares</title>      
    <link rel="stylesheet" type="text/css" href="/style.css"/>
</head>
<body>
    <header>
        <div class="navigation">
            <a href="/index.php">Index</a>
            <a href="/gestion_tables.php">Recherche</a>
            <a href="/ajout_service.php">Ajout service</a>
            <a href="/dates_service.php">Services disponibles</a>
            <a href="/stats_temps_arret.php">Statistiques arrêt</a>
            <a href="/recherche_gare.php" class="active">Recherche gare</a>
            <a href="/gestion_iti_trajet.php">Gestion des itinéraires et trajets</a>
            <a href="/modification_arret.php">Modifier arrêt</a>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Erreur') === false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <h1>Recherche de gares</h1>
        
        <form method="post">
            <label for="search_term">Nom de la gare :</label>
            <input type="text" id="search_term" name="search_term" value="<?= htmlspecialchars($searchTerm) ?>" required>
            
            <label for="min_number">Nombre minimum d'arrêts/arrivées/départs (optionel) :</label>
            <input type="number" id="min_number" name="min_number" min="0" value="<?= $minNumber !== null ? htmlspecialchars($minNumber) : '' ?>">
            
            <button type="submit">Rechercher</button>
        </form>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($searchTerm)): ?>
            <div class="error">Veuillez entrer le nom d'une gare</div>
        <?php elseif (!empty($results)): ?>
            <h2>Résultats de la recherche</h2>
            <table>
                <thead>
                    <tr>
                        <th>Gare</th>
                        <th>Service</th>
                        <th>Nombre de trajets</th>
                        <th>Nombre d'arrivées</th>
                        <th>Nombre de départs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nom_gare']) ?></td>
                            <td><?= htmlspecialchars($row['nom_service']) ?></td>
                            <td><?= htmlspecialchars($row['nb_trajets']) ?></td>
                            <td><?= htmlspecialchars($row['nb_arrivees']) ?></td>
                            <td><?= htmlspecialchars($row['nb_departs']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($results)): ?>
            <p class="no-results">Aucun résultat trouvé pour cette gare.</p>
        <?php endif; ?>
    </div>
</body>
</html>