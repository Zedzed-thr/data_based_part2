<?php
// ==== PARTIE SQL ===
// connexion
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=transport;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// formulaire
$searchTerm = '';
$minNumber = null;
$results = [];

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
                service.NOM AS nom_service,
                COUNT(DISTINCT horraire.TRAJET_ID) AS nb_trajets, -- on compte le nbr de trajet distinct qui passe par une gare
                SUM(CASE WHEN horraire.HEURE_ARRIVEE IS NOT NULL THEN 1 ELSE 0 END) AS nb_arrivees, -- +1 si nbr d'heure arrive/depart est non null sinn +0
                SUM(CASE WHEN horraire.HEURE_DEPART IS NOT NULL THEN 1 ELSE 0 END) AS nb_departs
            FROM ARRET arret
            JOIN HORRAIRE horraire ON arret.ID = horraire.ARRET_ID -- on lie les gares et les heures d'arrivee/depart par trajet
            JOIN TRAJET trajet ON horraire.TRAJET_ID = trajet.ID
            JOIN SERVICE service ON trajet.SERVICE_ID = service.ID
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
            $error = "Erreur lors de la recherche : " . $e->getMessage();
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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #333;
        }
        form {
            background: #f4f4f4;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"] {
            padding: 8px;
            width: 300px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #333;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #333;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .no-results {
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Recherche de gares</h1>
    
    <form method="post">
        <label for="search_term">Nom de la gare :</label>
        <input type="text" id="search_term" name="search_term" value="<?= htmlspecialchars($searchTerm) ?>" required>
        
        <label for="min_number">Nombre minimum d'arrêts/arrivées/départs (optionel) :</label>
        <input type="number" id="min_number" name="min_number" min="0" value="<?= $minNumber !== null ? htmlspecialchars($minNumber) : '' ?>">
        
        <button type="submit">Rechercher</button>
    </form>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
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
</body>
</html>