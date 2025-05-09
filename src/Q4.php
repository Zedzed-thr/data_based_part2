<?php 

if($_SERVER["REQUEST_METHOD"] === "POST") {
    try{
        $pdo = new PDO('mysql:host=localhost;port=3306;dbname=transport;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        if(!isset($_POST["date_debut"]) || empty($_POST["date_debut"])) {
            throw new RuntimeException("Vous devez insérer une date de début");
        }

        if(!isset($_POST["date_debut"]) || empty($_POST["date_debut"])) {
            throw new RuntimeException("Vous devez insérer une date de fin");
        }

        $datedebut = new Datetime(trim($_POST["date_debut"]));
        $datefin = new Datetime(trim($_POST["date_fin"]));

        $query = "SELECT jour,group_concat(nom SEPARATOR ', ') as services from dates_services where jour between :date_debut and :date_fin group by jour order by jour"; 
        $req = $pdo->prepare($query);
        $req->execute([":date_debut" => $datedebut->format("Y-m-d"), ":date_fin" => $datefin->format("Y-m-d")]);

        $all_dates = [];
        while($dates = $req->fetch()){
            $all_dates[] = ["jour" => $dates["jour"], "services" => $dates["services"]];
        }

    } catch(Exception $e) {
        echo "Erreur : ". $e->getMessage();
    }


}

?> 

<!DOCTYPE html>
<html>
<head>
    <title>Services disponibles</title>
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
    <h3>Sélectionnez la période pour laquelle vous voulez afficher les services</h3>

    <form method="post" action="Q4.php"> 

        <label for="date_debut">Date de début : </label><br/>
        <input type="date" name="date_debut" id="date_debut"><br/>

        <label for="date_fin">Date de fin : </label><br/>
        <input type="date" name="date_fin" id="date_fin"><br/>

        <br/>
        <input type="submit" value="Soumettre">
    </form>

    <?php if(isset($all_dates)): ?>
        <h3>Liste des services pour les dates sélectionnées : </h3> 
        <table>
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Service(s)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($all_dates as $date): ?>
                    <tr>
                        <th scope="row"><?=$date["jour"]?></th>
                        <td><?=$date["services"]?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body> 
</html>
