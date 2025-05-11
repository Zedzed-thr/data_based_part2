<?php 

$message = "";
if($_SERVER["REQUEST_METHOD"] === "POST") {
    try{
        $pdo = new PDO('mysql:host=db;port=3306;dbname=TRANSPORT;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        if(!isset($_POST["date_debut"]) || empty($_POST["date_debut"])) {
            throw new RuntimeException("Vous devez insérer une date de début.");
        }

        if(!isset($_POST["date_fin"]) || empty($_POST["date_fin"])) {
            throw new RuntimeException("Vous devez insérer une date de fin.");
        }

        $datedebut = new Datetime(trim($_POST["date_debut"]));
        $datefin = new Datetime(trim($_POST["date_fin"]));

        $query = "SELECT JOUR,group_concat(NOM SEPARATOR ', ') as SERVICES from DATES_SERVICES where JOUR between :date_debut and :date_fin group by JOUR order by JOUR"; 
        $req = $pdo->prepare($query);
        $req->execute([":date_debut" => $datedebut->format("Y-m-d"), ":date_fin" => $datefin->format("Y-m-d")]);

        $all_dates = [];
        while($dates = $req->fetch()){
            $all_dates[] = ["jour" => $dates["JOUR"], "services" => $dates["SERVICES"]];
        }

    } catch(Exception $e) {
        $message = "Erreur : ". $e->getMessage();
    }


}

?> 

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title>Services disponibles</title>
    <link rel="stylesheet" type="text/css" href="/style.css"/>
</head>
<body>

    <header>
		<div class="navigation">
			<a href="/index.php">Index</a>
			<a href="/gestion_tables.php">Recherche</a>
			<a href="/ajout_service.php">Ajout service</a>
			<a href="/dates_service.php" class="active">Services disponibles</a>
			<a href="/stats_temps_arret.php">Statistiques arrêt</a>
			<a href="/recherche_gare.php">Recherche gare</a>
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

        <h1>Sélectionnez la période pour laquelle vous voulez afficher les services</h1>

        <form method="post" action="dates_service.php"> 

            <label for="date_debut">Date de début : </label><br/>
            <input type="date" name="date_debut" id="date_debut" value=<?=isset($datedebut)?$datedebut->format("Y-m-d"):""?>><br/>

            <label for="date_fin">Date de fin : </label><br/>
            <input type="date" name="date_fin" id="date_fin" value=<?=isset($datefin)?$datefin->format("Y-m-d"):""?>><br/>

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
    </div>

</body> 
</html>
