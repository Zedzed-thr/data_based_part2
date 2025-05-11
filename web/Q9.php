<?php 
//TOUJOURS BIEN VERIFIER QU'UN NOM D'ITI RESTE UNIQUE
session_start();

$pdo = new PDO('mysql:host=db;port=3306;dbname=TRANSPORT;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

//première requête : on sélectionne tous les itinéraire 

$all_iti = [];
$query_iti = "SELECT ID,NOM,TYPE from ITINERAIRE"; 
$req = $pdo->query($query_iti); 
while($data_iti = $req->fetch()){
    $all_iti[] = ["id" => $data_iti["ID"],"nom" => $data_iti["NOM"],"type" => $data_iti["TYPE"]];
}


if($_SERVER["REQUEST_METHOD"] === "POST") {
    if(isset($_POST["delete_iti"])) {
        $id_iti = trim($_POST["delete_iti"]);
        try {
            $pdo->beginTransaction(); 

            $query = "DELETE FROM ARRET_DESSERVI where ITINERAIRE_ID=:id";
            $req = $pdo->prepare($query);
            $req->execute([":id" => $id_iti]);

            $query = "DELETE FROM HORRAIRE where ITINERAIRE_ID=:id";
            $req = $pdo->prepare($query);
            $req->execute([":id" => $id_iti]);

            $query = "DELETE FROM TRAJET where ITINERAIRE_ID=:id";
            $req = $pdo->prepare($query);
            $req->execute([":id" => $id_iti]);

            $query = "DELETE FROM ITINERAIRE where ID=:id";
            $req = $pdo->prepare($query);
            $req->execute([":id" => $id_iti]);

            $pdo->commit();
        } catch(Exception $e) {
            echo "Erreur : ".$e->getMessage();
            $pdo->rollBack();
        }

    }

    if(isset($_POST["iti_ajout_trajet"]) && isset($_POST["dir_ajout_trajet"])) {
        print_r($_POST);
        try {
            $direction = (int) trim($_POST["dir_ajout_trajet"]);
            $id_iti = trim($_POST["iti_ajout_trajet"]);

            $ordre = ($direction == 0) ? "ASC" : "DESC";
            $query = "SELECT A.ID,A.NOM from ARRET_DESSERVI
                    as AD join ARRET as A on AD.ARRET_ID=A.ID
                    where AD.ITINERAIRE_ID = :id_iti ORDER BY AD.SEQUENCE ".$ordre.";";
            $req = $pdo->prepare($query); 
            $req->execute([":id_iti" => $id_iti]);

            $arrets = $req->fetchAll();

            $_SESSION["id_iti"] = $id_iti;
            $_SESSION["arrets"] = $arrets;
            $_SESSION["ordre"] = $ordre;
            $_SESSION["direction"] = $direction;
            
            $req_service = $pdo->prepare("SELECT ID,NOM from SERVICE");
            $req_service->execute(); 

            $services = $req_service->fetchAll();        

        } catch(Exception $e) {
            echo "Erreur : ".$e->getMessage();
        }
    }

    if(isset($_POST["ajouter_trajet"]) && isset($_SESSION["arrets"])) {

        try {
            $arrets = $_SESSION["arrets"]; 

            if(!isset($_POST["identifiant_trajet"]) || empty($_POST["identifiant_trajet"])) {
                throw new Exception("Vous devez spécifier un identifiant pour le trajet"); 
            }

            if(!isset($_POST["service_trajet"]) || empty($_POST["service_trajet"])) {
                throw new Exception("Vous devez spécifier un service pour le trajet");
            }

            $pdo->beginTransaction(); 


            $identifiant_trajet = trim($_POST["identifiant_trajet"]);
            $service_trajet = trim($_POST["service_trajet"]);
            $query = "INSERT into TRAJET(ID,SERVICE_ID,ITINERAIRE_ID,DIRECTION) VALUES(:id,:service_id,:iti_id,:direction)";
            $req = $pdo->prepare($query); 
            $req->execute([":id" => $identifiant_trajet,
                           ":service_id" => $service_trajet, 
                           ":iti_id" => $_SESSION["id_iti"],
                           ":direction" => $_SESSION["direction"]]);

            foreach($arrets as $sequence => $arret) {
                if($sequence == 0) { // départ

                    if(isset($_POST[$arret["ID"]."_depart"]) && !empty($_POST[$arret["ID"]."_depart"])) {
                        $heure_depart = new Datetime($_POST[$arret["ID"]."_depart"]);
                        $query = "INSERT INTO HORRAIRE(TRAJET_ID,ITINERAIRE_ID,ARRET_ID,HEURE_DEPART) 
                                  VALUES(:trajet_id,:itineraire_id,:arret_id,:heure_depart)"; 
                        $req = $pdo->prepare($query);
                        $req->execute([":trajet_id" => $identifiant_trajet,
                                       ":itineraire_id" => $_SESSION["id_iti"],
                                       ":arret_id" => $arret["ID"],
                                       ":heure_depart" => $heure_depart->format("H:i:s")]);
                    } 
                    else {
                        throw new Exception("Vous devez spécifier une heure de départ pour la première gare");
                    }
                } 
                else if($sequence == count($arrets) - 1) { // terminus
                    if(isset($_POST[$arret["ID"]."_arrivee"]) && !empty($_POST[$arret["ID"]."_arrivee"])) {
                        $heure_arrivee = new Datetime($_POST[$arret["ID"]."_arrivee"]);

                        $query = "INSERT INTO HORRAIRE(TRAJET_ID,ITINERAIRE_ID,ARRET_ID,HEURE_ARRIVEE) 
                                  VALUES(:trajet_id,:itineraire_id,:arret_id,:heure_arrivee)"; 
                        $req = $pdo->prepare($query);
                        $req->execute([":trajet_id" => $identifiant_trajet,
                                       ":itineraire_id" => $_SESSION["id_iti"],
                                       ":arret_id" => $arret["ID"],
                                       ":heure_arrivee" => $heure_arrivee->format("H:i:s")]);

                    }
                    else {
                        throw new Exception("Vous devez spécifier une heure d'arrivée pour le terminus");
                    }

                }
                else { // gare intermédiaire
                    if(isset($_POST[$arret["ID"]."_depart"]) && isset($_POST[$arret["ID"]."_arrivee"]) 
                       && !empty($_POST[$arret["ID"]."_depart"]) && !empty($_POST[$arret["ID"]."_depart"])) {
                        $heure_depart = new Datetime($_POST[$arret["ID"]."_depart"]);
                        $heure_arrivee = new Datetime($_POST[$arret["ID"]."_arrivee"]);

                        $query = "INSERT INTO HORRAIRE(TRAJET_ID,ITINERAIRE_ID,ARRET_ID,HEURE_ARRIVEE,HEURE_DEPART) 
                                  VALUES(:trajet_id,:itineraire_id,:arret_id,:heure_arrivee,:heure_depart)"; 
                        $req = $pdo->prepare($query);
                        $req->execute([":trajet_id" => $identifiant_trajet,
                                       ":itineraire_id" => $_SESSION["id_iti"],
                                       ":arret_id" => $arret["ID"],
                                       ":heure_arrivee" => $heure_arrivee->format("H:i:s"),
                                       ":heure_depart" => $heure_depart->format("H:i:s")]);

                    }
                    else {
                        throw new Exception("Vous devez spécifier une heure de départ et d'arrivée pour toutes les gares intermédiaires");
                    }

                }
            }

            $pdo->commit();
        } catch(Exception $e) {
            $pdo->rollBack(); 
            echo "Erreur : ".$e->getMessage();
        }
    }


}

// sélection de tous les itinéraires pour l'ajout d'un trajet 
?>


<!DOCTYPE html>
<html>
<head>
    <title>Gestion des itinéraires et services</title>
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

<h3>Suppresion d'un itinéraire</h3>
<form method="post" action="Q9.php">
    <select name="delete_iti">
        <option value="">--Choisissez un itinéraire--</option>
        <?php foreach($all_iti as $iti): ?>
            <option value=<?=$iti['id']?>><?=$iti["type"]." ".$iti['nom']?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="Supprimer">
</form>

<h3>Choix d'un itinéraire</h3>
<form method="post" action="Q9.php">
    <select name="iti_ajout_trajet">
        <option value="">--Choisissez un itinéraire--</option>
        <?php foreach($all_iti as $iti): ?>
            <option value=<?=$iti['id']?>><?=$iti["type"]." ".$iti['nom']?></option>
        <?php endforeach; ?>
    </select>
    <select name="dir_ajout_trajet">
            <option value="">--Choisissez une direction--</option>
            <option value="0">0</option>
            <option value="1">1</option>
    </select>
    <input type="submit" value="Ajouter">
</form>

<?php if (isset($_POST["iti_ajout_trajet"]) && isset($_POST["dir_ajout_trajet"])): ?> 

    <form method="post" action="Q9.php"> 

        <input type="text" name="identifiant_trajet" placeholder="Identifiant du trajet">

        <select name="service_trajet">
            <option value="">--Choisissez un service--</option>
            <?php foreach($services as $service): ?>
                <option value=<?=$service["ID"]?>><?=$service["NOM"]?>
            <?php endforeach; ?>
        </select><br/><br/>

        <?php foreach($arrets as $sequence => $arret): ?>
            <?=$arret["NOM"]?><br/>
            <?php if($sequence == 0): ?>
                <input type="text" name='<?=$arret["ID"]?>_arrivee' id='<?=$arret["ID"]?>_arrivee'  placeholder="Heure de d'arrivée" disabled>
                <input type="text" name='<?=$arret["ID"]?>_depart' id='<?=$arret["ID"]?>_depart' placeholder="Heure de départ"><br/>
            <?php elseif($sequence == count($arrets) - 1): ?>
                <input type="text" name='<?=$arret["ID"]?>_arrivee' id='<?=$arret["ID"]?>_arrivee'  placeholder="Heure de d'arrivée">
                <input type="text" name='<?=$arret["ID"]?>_depart' id='<?=$arret["ID"]?>_depart' placeholder="Heure de départ" disabled><br/>
            <?php else: ?>
                <input type="text" name='<?=$arret["ID"]?>_arrivee' id='<?=$arret["ID"]?>_arrivee'  placeholder="Heure de d'arrivée">
                <input type="text" name='<?=$arret["ID"]?>_depart' id='<?=$arret["ID"]?>_depart' placeholder="Heure de départ"><br/>
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="submit" value="Ajouter trajet" name="ajouter_trajet">
    </form>

<?php endif; ?>

</body>
</html>