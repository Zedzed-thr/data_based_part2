<?php 
//A MODIFIER DANS TRANSPORT.SQL ==> NOM SERVICE DOIT ETRE UNIQUE !
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=transport;charset=utf8', 'root', '');
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
    
            $query = "INSERT into service(nom,lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche,date_debut,date_fin)
                      VALUES(:nom,:lundi,:mardi,:mercredi,:jeudi,:vendredi,:samedi,:dimanche,:date_debut,:date_fin)";
    
            $req = $pdo->prepare($query);
            $req->execute([
                ":nom" => $nom, 
                ":lundi" => isset($_POST["lundi"]) ? true : false,
                ":mardi" => isset($_POST["mardi"]) ? true :false,
                ":mercredi" => isset($_POST["mercredi"]) ? true : false,
                ":jeudi" => isset($_POST["jeudi"]) ? true : false,
                ":vendredi" => isset($_POST["vendredi"]) ? true : false,
                ":samedi" => isset($_POST["samedi"]) ? true : false,
                ":dimanche" => isset($_POST["dimanche"]) ? true : false,
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
                $query = "INSERT INTO exception(service_id, date,code) VALUES(:service_id,:date,:code)"; 
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

<form method="post" action="Q3.php"> 
    <label for="nom">Nom du service : </label><br/>
    <input type="text" name="nom" id="nom"><br/>

    <label for="date_debut">Date de début : </label><br/>
    <input type="date" name="date_debut" id="date_debut"><br/>

    <label for="date_fin">Date de fin : </label><br/>
    <input type="date" name="date_fin" id="date_fin"><br/>

    <input type="checkbox" name="lundi" value="lundi">
    <label for="lundi"> Lundi</label><br>

    <input type="checkbox" name="mardi" value="mardi">
    <label for="mardi"> Mardi</label><br>
   
    <input type="checkbox" name="mercredi" value="mercredi">
    <label for="mercredi"> Mercredi</label><br> 

    <input type="checkbox" name="jeudi" value="jeudi">
    <label for="jeudi"> Jeudi</label><br>

    <input type="checkbox" name="vendredi" value="vendredi">
    <label for="vendredi"> Vendredi</label><br>

    <input type="checkbox" name="samedi" value="samedi">
    <label for="samedi"> Samedi</label><br>

    <input type="checkbox" name="dimanche" value="dimanche">
    <label for="dimanche"> Dimanche</label><br>

    <label for="exception"> Liste des exceptions : </label><br/>
    <textarea row="5" name="exception" id="exception"></textarea>
    <br/>
    <input type="submit" value="Soumettre">
</form>

</body>
</html>