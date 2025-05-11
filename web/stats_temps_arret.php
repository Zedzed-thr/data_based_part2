<?php
// ==== PARTIE SQL ====
// connexion
$pdo = new PDO('mysql:host=db;port=3306;dbname=TRANSPORT;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "
SELECT 
    IFNULL(trajet_num.NOM_ITINERAIRE, 'TOTAL GLOBAL') AS itineraire,
    IFNULL(trajet_num.NUMERO_TRAJET, 'MOYENNE') AS trajet,
    TIME_FORMAT(SEC_TO_TIME(AVG(TIMESTAMPDIFF(SECOND, horraire.HEURE_ARRIVEE, horraire.HEURE_DEPART))), '%H:%i:%s') AS temps_moyen,
    CASE WHEN trajet_num.NOM_ITINERAIRE IS NULL THEN 2 ELSE 
         CASE WHEN trajet_num.NUMERO_TRAJET IS NULL THEN 1 ELSE 0 END 
    END AS tri_order,
    trajet_num.NUMERO_TRAJET AS num_trajet_int
FROM 
    HORRAIRE horraire
JOIN 
    TRAJETS_INDEX trajet_num ON horraire.TRAJET_ID = trajet_num.TRAJET_ID
WHERE 
    horraire.HEURE_ARRIVEE IS NOT NULL AND horraire.HEURE_DEPART IS NOT NULL AND trajet_num.NOM_ITINERAIRE IS NOT NULL
GROUP BY 
    trajet_num.NOM_ITINERAIRE, trajet_num.NUMERO_TRAJET WITH ROLLUP
";

$results = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Tri des results
usort($results, function($a, $b) {
    // D'abord par ordre de tri (2=global en dernier)
    if ($a['tri_order'] == 2 || $b['tri_order'] == 2) {
        return $a['tri_order'] <=> $b['tri_order'];
    }
    
    // Ensuite par nom d'itinéraire
    if ($a['itineraire'] != $b['itineraire']) {
        return strcmp($a['itineraire'], $b['itineraire']);
    }
    
    // Pour le même itinéraire, les trajets d'abord (0), puis la moyenne (1)
    if ($a['tri_order'] == $b['tri_order']) {
        // Si ce sont deux trajets, tri par numéro
        if ($a['tri_order'] == 0) {
            return $a['num_trajet_int'] <=> $b['num_trajet_int'];
        }
        return 0;
    }
    
    // Mettre les moyennes (1) après les trajets (0) pour le même itinéraire
    return $a['tri_order'] <=> $b['tri_order'];
});

// ===== PARTIE HTML ========
?>
<!DOCTYPE html>
<html>
<head>
    <title>Statistiques temps d'arrêt</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        tr:nth-child(even) { background-color: rgb(222, 176, 24); }
        .total-itineraire { font-weight: bold; background-color: rgb(23, 144, 200) !important; }
        .total-global { font-weight: bold; background-color: #4CAF50 !important; color: white; }
        th { background-color: #333; color: white; }
    </style>
</head>
<body>

<h1>Temps d'arrêt moyen par itinéraire</h1>

<table>
    <thead>
        <tr>
            <th>Itinéraire</th>
            <th>Trajet</th>
            <th>Temps moyen</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $current_itineraire = null;
        foreach ($results as $row): 
            // On saute les lignes vides
            if (empty($row['itineraire'])) continue;
            
            // Si c'est un nouvel itinéraire et que ce n'est pas le premier
            if ($current_itineraire !== $row['itineraire'] && $current_itineraire !== null) {
                // On cherche la moyenne de l'itinéraire précédent
                foreach ($results as $moyenne_row) {
                    if ($moyenne_row['itineraire'] === $current_itineraire && $moyenne_row['trajet'] === 'MOYENNE') {
                        ?>
                        <tr class="total-itineraire">
                            <td><?= htmlspecialchars($moyenne_row['itineraire']) ?></td>
                            <td>Moyenne</td>
                            <td><?= $moyenne_row['temps_moyen'] ?></td>
                        </tr>
                        <?php
                        break;
                    }
                }
            }
            $current_itineraire = $row['itineraire'];
            // On affiche seulement les trajets (pas les moyennes ici)
            if ($row['trajet'] !== 'MOYENNE' && $row['itineraire'] !== 'TOTAL GLOBAL') {
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['itineraire']) ?></td>
                    <td><?= htmlspecialchars($row['trajet']) ?></td>
                    <td><?= $row['temps_moyen'] ?></td>
                </tr>
                <?php
            }
        endforeach; 
        
        // Afficher la moyenne du dernier itinéraire
        if ($current_itineraire !== null && $current_itineraire !== 'TOTAL GLOBAL') {
            foreach ($results as $moyenne_row) {
                if ($moyenne_row['itineraire'] === $current_itineraire && $moyenne_row['trajet'] === 'MOYENNE') {
                    ?>
                    <tr class="total-itineraire">
                        <td><?= htmlspecialchars($moyenne_row['itineraire']) ?></td>
                        <td>Moyenne</td>
                        <td><?= $moyenne_row['temps_moyen'] ?></td>
                    </tr>
                    <?php
                    break;
                }
            }
        }
        
        // Afficher le total global
        foreach ($results as $row) {
            if ($row['itineraire'] === 'TOTAL GLOBAL') {
                ?>
                <tr class="total-global">
                    <td>Total global</td>
                    <td></td>
                    <td><?= $row['temps_moyen'] ?></td>
                </tr>
                <?php
                break;
            }
        }
        ?>
    </tbody>
</table>

</body>
</html>