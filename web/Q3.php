<?php 
//A MODIFIER DANS TRANSPORT.SQL ==> NOM SERVICE DOIT ETRE UNIQUE !
$pdo = new PDO('mysql:host=db;port=3306;dbname=TRANSPORT;charset=utf8', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if($_SERVER["REQUEST_METHOD"] === "POST") {
    //$test = isset($_POST["lundi"]) ? "1" : "0";
    //echo $test;
    try {
        if(!isset($_POST["nom"]) || empty($_POST["nom"])) {
            throw new RuntimeException("Le nom du service ne peut être vide");
        }

        if(!isset($_POST["date_debut"]) || empty($_POST["date_debut"])) {
            throw new RuntimeException("Il est nécessaire d'introduire une date de début");
        } 

        if(!isset($_POST["date_fin"]) || empty($_POST["date_fin"])) {
            throw new RuntimeException("Il est nécessaire d'introduire une date de fin");
        }

        $datedebut = new Datetime($_POST["date_debut"]); 
        $datefin = new Datetime($_POST["date_fin"]);
        $nom = trim($_POST["nom"]); 

        if($datefin < $datedebut) {
            throw new RuntimeException("La date de fin ne peut pas être antérieure à la date du début");
        }


        try {

            $pdo->beginTransaction(); 
    
            $query = "INSERT INTO SERVICE(NOM,LUNDI,MARDI,MERCREDI,JEUDI,VENDREDI,SAMEDI,DIMANCHE,DATE_DEBUT,DATE_FIN)
                      VALUES(:nom,:lundi,:mardi,:mercredi,:jeudi,:vendredi,:samedi,:dimanche,:date_debut,:date_fin)";
    
            $req = $pdo->prepare($query);
            $req->execute([
                ":nom" => $nom, 
                ":lundi" => isset($_POST["lundi"]) ? 1 : 0,
                ":mardi" => isset($_POST["mardi"]) ? 1 : 0,
                ":mercredi" => isset($_POST["mercredi"]) ? 1 : 0,
                ":jeudi" => isset($_POST["jeudi"]) ? 1 : 0,
                ":vendredi" => isset($_POST["vendredi"]) ? 1 : 0,
                ":samedi" => isset($_POST["samedi"]) ? 1 : 0,
                ":dimanche" => isset($_POST["dimanche"]) ? 1 : 0,
                ":date_debut" => $datedebut->format("Y-m-d"),
                ":date_fin" => $datefin->format("Y-m-d")
            ]);

            $serviceId = (int)$pdo->lastInsertId(); 

            $exceptions = [];
            if(isset($_POST["exception"]) && !empty($_POST["exception"])) {
                $text_exception = trim($_POST["exception"]);
                $lines = explode("\n", $text_exception);
                if($lines){
                    foreach($lines as $line) {
                        $segments = explode(" ",$line); 
                        if(count($segments) == 2){ 
                            $code_string = strtoupper(trim($segments[1]));
                            if($code_string == "INCLUS") {
                                $code = 1;
                            }
                            elseif($code_string == "EXCLUS") {
                                $code = 2;
                            }
                            else {
                                throw new RuntimeException("Format invalide pour la définition des exceptions");
                            }
                            $date_e = new Datetime($segments[0]);
                            if($date_e < $datedebut || $date_e > $datefin) {
                                throw new RuntimeException("La date de l'exception doit être comprise entre la date du début du service et celle de fin.");
                            }
                            $exceptions[] = [":service_id" => $serviceId, ":date" => $date_e->format("Y-m-d"),":code" => $code];
                        }
                        else 
                        {
                            throw new RuntimeException("Format invalide pour la définition des exceptions");
                        }
                    }
                }
            }

            if($exceptions) {
                $query = "INSERT INTO EXCEPTION(SERVICE_ID, DATE,CODE) VALUES(:service_id,:date,:code)"; 
                $req = $pdo->prepare($query); 
                foreach($exceptions as $exception) {
                    $req->execute($exception);
                }
            }
    
            $pdo->commit();

    
        }catch(Exception $e) {
            echo "Erreur: ". $e->getMessage();    
            $pdo->rollBack();
        }
    }catch(Exception $e) {
        echo "Erreur: ". $e->getMessage();    
    }

    

}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajout Service</title>
    <link rel="stylesheet" type="text/css" href="/style.css"/>
</head>
<body>
<div class="container">
    <h3>Ajout d'un service </h3>
    <form method="post" action="Q3.php"> 
        <label for="nom">Nom du service : </label><br/>
        <input type="text" name="nom" id="nom"><br/>

        <label for="date_debut">Date de début : </label><br/>
        <input type="date" name="date_debut" id="date_debut"><br/>

        <label for="date_fin">Date de fin : </label><br/>
        <input type="date" name="date_fin" id="date_fin"><br/>

        <div class="day">
            <label for="lundi">Lundi</label>
            <input type="checkbox" name="lundi" value="lundi">
        </div>

        <div class="day">
            <label for="mardi"> Mardi</label>
            <input type="checkbox" name="mardi" value="mardi">
        </div>
        
        <div class="day">
            <label for="mercredi">Mercredi</label><br>
            <input type="checkbox" name="mercredi" value="mercredi">
        </div>

        <div class="day">
            <label for="jeudi"> Jeudi</label><br>
            <input type="checkbox" name="jeudi" value="jeudi">
        </div>

        <div class="day">
            <label for="vendredi"> Vendredi</label><br>
            <input type="checkbox" name="vendredi" value="vendredi">
        </div>

        <div class="day">
            <label for="samedi"> Samedi</label><br>
            <input type="checkbox" name="samedi" value="samedi">
        </div>

        <div class="day">
            <label for="dimanche"> Dimanche</label><br>
            <input type="checkbox" name="dimanche" value="dimanche">
        </div>

        <label for="exception"> Liste des exceptions : </label><br/>
        <textarea row="5" name="exception" id="exception"></textarea>
        <br/>
        <input type="submit" value="Soumettre">
    </form>
</div>

</body>
</html>