<?php 
$pdo = new PDO('mysql:host=db;port=3306;dbname=TRANSPORT;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


//par défaut, on affiche les agences

$table = isset($_GET['table']) ? $_GET['table'] : 'AGENCE';

if($table == 'AGENCE') {
    //valeur par défaut pour les formulaires
    $id_value = isset($_POST['id']) ? trim($_POST['id']) : '';
    $nom_value = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $url_value = isset($_POST['url']) ? trim($_POST['url']) : '';
    $fuseau_value = isset($_POST['fuseau']) ? trim($_POST['fuseau']) : '';
    $telephone_value = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $siege_value = isset($_POST['siege']) ? trim($_POST['siege']) : '';

    $query = "SELECT * FROM AGENCE WHERE 1=1";
    $params = []; 

    if(isset($_POST["search_agence"])) { // si l'utilisateur applique un filtre
        if (!empty($id_value)) {
            $params[":id"] = $id_value;
            $query .= " AND ID = :id";
        }
    
        if (!empty($nom_value)) {
            $params[":nom"] = '%'.$nom_value.'%';
            $query .= " AND LOWER(NOM) LIKE LOWER(:nom)";
        }
        
        if (!empty($url_value)) {
            $params[":url"] = '%'.$url_value.'%';
            $query .= " AND LOWER(URL) LIKE LOWER(:url)";
        }
        
        if (!empty($fuseau_value)) {
            $params[":fuseau"] = $fuseau_value;
            $query .= " AND LOWER(FUSEAU_HORAIRE) = LOWER(:fuseau)";
        }
        
        if (!empty($telephone_value)) {
            $params[":telephone"] = '%'.$telephone_value.'%';
            $query .= " AND LOWER(TELEPHONE) LIKE LOWER(:telephone)";
        }
        
        if (!empty($siege_value)) {
            $params[":siege"] = '%'.$siege_value.'%';
            $query .= " AND LOWER(SIEGE) LIKE LOWER(:siege)";
        }
    }
    
    $req = $pdo->prepare($query); 
    $req->execute($params);

    $results_agence = $req->fetchAll();

}
elseif($table == 'HORAIRE') {
    //valeur par défaut pour les formulaires
    $trajet_id_value = isset($_POST['trajet_id']) ? trim($_POST['trajet_id']) : '';
    $itineraire_id_value = isset($_POST['itineraire_id']) ? trim($_POST['itineraire_id']) : '';
    $arret_id_value = isset($_POST['arret_id']) ? trim($_POST['arret_id']) : '';
    $heure_arrivee_value = isset($_POST['heure_arrivee']) ? trim($_POST['heure_arrivee']) : '';
    $heure_depart_value = isset($_POST['heure_depart']) ? trim($_POST['heure_depart']) : '';

    $query = "SELECT * FROM HORRAIRE WHERE 1=1";
    $params = []; 

    if(isset($_POST["search_horaire"])) {

        if (!empty($trajet_id_value)) {
            $params[":trajet_id"] = "%".$trajet_id_value."%"; 
            $query .= " AND LOWER(TRAJET_ID) LIKE LOWER(:trajet_id)";
        }
    
        if (!empty($itineraire_id_value)) {
            $params[":itineraire_id"] = $itineraire_id_value;
            $query .= " AND ITINERAIRE_ID = :itineraire_id";
        }
        
        if (!empty($arret_id_value)) {
            $params[":arret_id"] = $arret_id_value;
            $query .= " AND ARRET_ID = :arret_id";
        }
        
        if (!empty($heure_arrivee_value)) {
            $params[":heure_arrivee"] = "%".$heure_arrivee_value."%";
            $query .= " AND LOWER(HEURE_ARRIVEE) LIKE LOWER(:heure_arrivee)";
        }
        
        if (!empty($heure_depart_value)) {
            $params[":heure_depart"] = "%".$heure_depart_value."%";
            $query .= " AND LOWER(HEURE_DEPART) LIKE LOWER(:heure_depart)";
        }
    }
    $req = $pdo->prepare($query); 
    $req->execute($params);

    $results_horaire = $req->fetchAll();
}
elseif($table == 'EXCEPTION') {
    //valeur par défaut pour les formulaires 
    $service_id_value = isset($_POST['service_id']) ? trim($_POST['service_id']) : '';
    $date_value = isset($_POST['date']) ? trim($_POST['date']) : '';
    $code_value = isset($_POST['code']) ? trim($_POST['code']) : '';

    $query = "SELECT * FROM EXCEPTION WHERE 1=1";
    $params = []; 

    if(isset($_POST["search_exception"])) {

        if (!empty($service_id_value)) {
            $params[":service_id"] = $service_id_value;
            $query .= " AND SERVICE_ID = :service_id";
        }
        
        if (!empty($date_value)) {
            $params[":date"] = $date_value;
            $query .= " AND DATE = :date";
        }
        
        if (!empty($code_value)) {
            $params[":code"] = $code_value;
            $query .= " AND CODE = :code";
        }
    }

        $req = $pdo->prepare($query); 
        $req->execute($params);

        $results_exception = $req->fetchAll();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>GTFS Database - Table Query</title>
    <link rel="stylesheet" type="text/css" href="/style.css"/>
</head>
<body>
    <h1>GTFS Database Query Tool</h1>
    
    <div class="navigation">
        <a href="?table=AGENCE" <?= $table == 'AGENCE' ? 'class="active"' : '' ?>>AGENCE</a>
        <a href="?table=HORAIRE" <?= $table == 'HORAIRE' ? 'class="active"' : '' ?>>HORAIRE</a>
        <a href="?table=EXCEPTION" <?= $table == 'EXCEPTION' ? 'class="active"' : '' ?>>EXCEPTION</a>
    </div>

    <?php if($table == 'AGENCE'): ?> <!-- Ici c'est pour les agences -->

        <h2>Query AGENCE Table</h2>
        <form method="POST" action="table_filtering_edit.php?table=AGENCE">
            <input type="hidden" name="table" value="AGENCE">
            
            <label for="id">ID:</label>
            <input type="number" name="id" id="id" value="<?=$id_value?>">
            <br>
            
            <label for="nom">NOM (contains):</label>
            <input type="text" name="nom" id="nom" value="<?=$nom_value?>">
            <br>
            
            <label for="url">URL (contains):</label>
            <input type="text" name="url" id="url" value="<?=$url_value?>">
            <br>
            
            <label for="fuseau">FUSEAU_HORAIRE:</label>
            <input type="text" name="fuseau" id="fuseau" value="<?=$fuseau_value?>">
            <br>
            
            <label for="telephone">TELEPHONE (contains):</label>
            <input type="text" name="telephone" id="telephone" value="<?=$telephone_value?>">
            <br>
            
            <label for="siege">SIEGE (contains):</label>
            <input type="text" name="siege" id="siege" value="<?=$siege_value?>">
            <br>
            
            <input type="submit" value="Rechercher" name="search_agence">
        </form>

        <!-- A partir d'ici, on va afficher les résultats de la requête --> 

        <?php if(count($results_agence) > 0): ?>
            <h3>Résultats : <?=count($results_agence)?> enregistrements trouvés</h3>
            <table>
                <tr><th>ID</th><th>NOM</th><th>URL</th><th>FUSEAU_HORAIRE</th><th>TELEPHONE</th><th>SIEGE</th></tr>
                <?php foreach($results_agence as $agence): ?>
                    <tr>
                        <td><?=$agence["ID"]?></td>
                        <td><?=$agence["NOM"]?></td>
                        <td><?=$agence["URL"]?></td>
                        <td><?=$agence["FUSEAU_HORAIRE"]?></td>
                        <td><?=$agence["TELEPHONE"]?></td>
                        <td><?=$agence["SIEGE"]?></td>
                    </tr>
                <?php endforeach; ?>
            </table>


        <?php else: ?> 
            <p>Aucun résultats n'a été trouvé.</p>
        <?php endif; ?>



    <?php elseif($table == 'HORAIRE'): ?> <!-- Ici c'est pour les horaires -->

        <h2>Query HORAIRE Table</h2>
        <form method="POST" action="table_filtering_edit.php?table=HORAIRE">
            <input type="hidden" name="table" value="HORAIRE">
            
            <label for="trajet_id">TRAJET_ID:</label>
            <input type="text" name="trajet_id" id="trajet_id" value="<?=$trajet_id_value?>">
            <br>
            
            <label for="itineraire_id">ITINERAIRE_ID:</label>
            <input type="number" name="itineraire_id" id="itineraire_id" value="<?=$itineraire_id_value?>">
            <br>
            
            <label for="arret_id">ARRET_ID:</label>
            <input type="number" name="arret_id" id="arret_id" value="<?=$arret_id_value?>">
            <br>
            
            <label for="heure_arrivee">HEURE_ARRIVEE (contains):</label>
            <input type="text" name="heure_arrivee" id="heure_arrivee" value="<?=$heure_arrivee_value?>">
            <br>
            
            <label for="heure_depart">HEURE_DEPART (contains):</label>
            <input type="text" name="heure_depart" id="heure_depart" value="<?=$heure_depart_value?>">
            <br>
            
            <input type="submit" value="Rechercher" name="search_horaire">
        </form>

        <?php if(count($results_horaire) > 0): ?>
            <h3>Résultats : <?=count($results_horaire)?> enregistrements trouvés</h3>
            <table>
                <tr><th>TRAJET_ID</th><th>ITINERAIRE_ID</th><th>ARRET_ID</th><th>HEURE_ARRIVEE</th><th>HEURE_DEPART</th></tr>
                <?php foreach($results_horaire as $horaire): ?>
                    <tr>
                        <td><?=$horaire["TRAJET_ID"]?></td>
                        <td><?=$horaire["ITINERAIRE_ID"]?></td>
                        <td><?=$horaire["ARRET_ID"]?></td>
                        <td><?=$horaire["HEURE_ARRIVEE"]?></td>
                        <td><?=$horaire["HEURE_DEPART"]?></td>
                    </tr>
                <?php endforeach; ?>
            </table>


        <?php else: ?> 
            <p>Aucun résultats n'a été trouvé.</p>
        <?php endif; ?>


    
    <?php elseif($table == 'EXCEPTION'): ?> <!-- Ici c'est pour les exceptions -->

        <h2>Query EXCEPTION Table</h2>
        <form method="POST" action="table_filtering_edit.php?table=EXCEPTION">
            <input type="hidden" name="table" value="EXCEPTION">
            
            <label for="service_id">SERVICE_ID:</label>
            <input type="number" name="service_id" id="service_id" value="<?=$service_id_value?>">
            <br>
            
            <label for="date">DATE:</label>
            <input type="date" name="date" id="date" value="<?=$date_value?>">
            <br>
            
            <label for="code">CODE (contains):</label>
            <input type="number" name="code" id="code" value="<?=$code_value?>">
            <br>
            
            <input type="submit" value="Rechercher" name="search_exception">
        </form>

        <?php if(count($results_exception) > 0): ?>
            <h3>Résultats : <?=count($results_exception)?> enregistrements trouvés</h3>
            <table>
                <tr><th>SERVICE_ID</th><th>DATE</th><th>CODE</th></tr>
                <?php foreach($results_exception as $exception): ?>
                    <tr>
                        <td><?=$exception["SERVICE_ID"]?></td>
                        <td><?=$exception["DATE"]?></td>
                        <td><?=$exception["CODE"]?></td>
                    </tr>
                <?php endforeach; ?>
            </table>


        <?php else: ?> 
            <p>Aucun résultats n'a été trouvé.</p>
        <?php endif; ?>


    
    <?php else: ?>
        <p>Choix de table invalide</p>
    <?php endif; ?>
