<?php
// Database connection
$servername = "db";
$username = "root";
$password = "";
$dbname = "TRANSPORT";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to prevent SQL injection
function sanitize($conn, $input) {
    return $conn->real_escape_string($input);
}

// Which table to query
$table = isset($_GET['table']) ? $_GET['table'] : 'AGENCE';

// HTML header
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>GTFS Database - Table Query</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f5f5f5; }
        label { margin-right: 10px; }
        input, select { margin-bottom: 10px; padding: 5px; }
        input[type="submit"] { background-color: #4CAF50; color: white; border: none; cursor: pointer; padding: 10px 15px; }
        .navigation { margin-bottom: 20px; }
        .navigation a { padding: 10px; margin-right: 5px; background-color: #eee; text-decoration: none; color: #333; }
        .navigation a.active { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>GTFS Database Query Tool</h1>
    
    <div class="navigation">
        <a href="?table=AGENCE" <?= $table == 'AGENCE' ? 'class="active"' : '' ?>>AGENCE</a>
        <a href="?table=HORAIRE" <?= $table == 'HORAIRE' ? 'class="active"' : '' ?>>HORAIRE</a>
        <a href="?table=EXCEPTION" <?= $table == 'EXCEPTION' ? 'class="active"' : '' ?>>EXCEPTION</a>
    </div>
HTML;

// Display appropriate form based on selected table
switch($table) {
    case 'AGENCE':
        displayAgenceForm();
        executeAgenceQuery($conn);
        break;
    case 'HORAIRE':
        displayHoraireForm();
        executeHoraireQuery($conn);
        break;
    case 'EXCEPTION':
        displayExceptionForm();
        executeExceptionQuery($conn);
        break;
    default:
        echo "<p>Invalid table selection.</p>";
}

echo "</body></html>";

// Close connection
$conn->close();

// Function to display AGENCE form
function displayAgenceForm() {
    // Pre-compute all values before the heredoc
    $id_value = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';
    $nom_value = isset($_GET['nom']) ? htmlspecialchars($_GET['nom']) : '';
    $url_value = isset($_GET['url']) ? htmlspecialchars($_GET['url']) : '';
    $fuseau_value = isset($_GET['fuseau']) ? htmlspecialchars($_GET['fuseau']) : '';
    $telephone_value = isset($_GET['telephone']) ? htmlspecialchars($_GET['telephone']) : '';
    $siege_value = isset($_GET['siege']) ? htmlspecialchars($_GET['siege']) : '';
    
    // Now use these variables in the heredoc
    echo <<<HTML
    <h2>Query AGENCE Table</h2>
    <form method="GET" action="">
        <input type="hidden" name="table" value="AGENCE">
        
        <label for="id">ID:</label>
        <input type="number" name="id" id="id" value="$id_value">
        <br>
        
        <label for="nom">NOM (contains):</label>
        <input type="text" name="nom" id="nom" value="$nom_value">
        <br>
        
        <label for="url">URL (contains):</label>
        <input type="text" name="url" id="url" value="$url_value">
        <br>
        
        <label for="fuseau">FUSEAU_HORAIRE:</label>
        <input type="text" name="fuseau" id="fuseau" value="$fuseau_value">
        <br>
        
        <label for="telephone">TELEPHONE (contains):</label>
        <input type="text" name="telephone" id="telephone" value="$telephone_value">
        <br>
        
        <label for="siege">SIEGE (contains):</label>
        <input type="text" name="siege" id="siege" value="$siege_value">
        <br>
        
        <input type="submit" value="Query">
    </form>
HTML;
}
// Function to execute AGENCE query
function executeAgenceQuery($conn) {
    // Start building the query
    $query = "SELECT * FROM AGENCE WHERE 1=1";
    $params = [];
    
    // Add filters based on form input
    if (!empty($_GET['id'])) {
        $id = sanitize($conn, $_GET['id']);
        $query .= " AND ID = $id";
    }
    
    if (!empty($_GET['nom'])) {
        $nom = sanitize($conn, $_GET['nom']);
        $query .= " AND LOWER(NOM) LIKE LOWER('%$nom%')";
    }
    
    if (!empty($_GET['url'])) {
        $url = sanitize($conn, $_GET['url']);
        $query .= " AND LOWER(URL) LIKE LOWER('%$url%')";
    }
    
    if (!empty($_GET['fuseau'])) {
        $fuseau = sanitize($conn, $_GET['fuseau']);
        $query .= " AND FUSEAU_HORAIRE = '$fuseau'";
    }
    
    if (!empty($_GET['telephone'])) {
        $telephone = sanitize($conn, $_GET['telephone']);
        $query .= " AND LOWER(TELEPHONE) LIKE LOWER('%$telephone%')";
    }
    
    if (!empty($_GET['siege'])) {
        $siege = sanitize($conn, $_GET['siege']);
        $query .= " AND LOWER(SIEGE) LIKE LOWER('%$siege%')";
    }
    
    // Execute query and display results
    $result = $conn->query($query);
    
    if (!$result) {
        echo "<p>Error: " . $conn->error . "</p>";
        return;
    }
    
    if ($result->num_rows > 0) {
        echo "<h3>Results: " . $result->num_rows . " records found</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>NOM</th><th>URL</th><th>FUSEAU_HORAIRE</th><th>TELEPHONE</th><th>SIEGE</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['NOM']) . "</td>";
            echo "<td>" . htmlspecialchars($row['URL']) . "</td>";
            echo "<td>" . htmlspecialchars($row['FUSEAU_HORAIRE']) . "</td>";
            echo "<td>" . htmlspecialchars($row['TELEPHONE']) . "</td>";
            echo "<td>" . htmlspecialchars($row['SIEGE']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No results found.</p>";
    }
}

// Function to display HORAIRE form
function displayHoraireForm() {
    // Pre-compute values
    $trajet_id_value = isset($_GET['trajet_id']) ? htmlspecialchars($_GET['trajet_id']) : '';
    $itineraire_id_value = isset($_GET['itineraire_id']) ? htmlspecialchars($_GET['itineraire_id']) : '';
    $arret_id_value = isset($_GET['arret_id']) ? htmlspecialchars($_GET['arret_id']) : '';
    $heure_arrivee_value = isset($_GET['heure_arrivee']) ? htmlspecialchars($_GET['heure_arrivee']) : '';
    $heure_depart_value = isset($_GET['heure_depart']) ? htmlspecialchars($_GET['heure_depart']) : '';
    
    echo <<<HTML
    <h2>Query HORAIRE Table</h2>
    <form method="GET" action="">
        <input type="hidden" name="table" value="HORAIRE">
        
        <label for="trajet_id">TRAJET_ID:</label>
        <input type="number" name="trajet_id" id="trajet_id" value="$trajet_id_value">
        <br>
        
        <label for="itineraire_id">ITINERAIRE_ID:</label>
        <input type="number" name="itineraire_id" id="itineraire_id" value="$itineraire_id_value">
        <br>
        
        <label for="arret_id">ARRET_ID:</label>
        <input type="number" name="arret_id" id="arret_id" value="$arret_id_value">
        <br>
        
        <label for="heure_arrivee">HEURE_ARRIVEE (contains):</label>
        <input type="text" name="heure_arrivee" id="heure_arrivee" value="$heure_arrivee_value">
        <br>
        
        <label for="heure_depart">HEURE_DEPART (contains):</label>
        <input type="text" name="heure_depart" id="heure_depart" value="$heure_depart_value">
        <br>
        
        <input type="submit" value="Query">
    </form>
HTML;
}
// Function to execute HORAIRE query
function executeHoraireQuery($conn) {
    // Start building the query - note that the table name is HORRAIRE (with double R) as specified in the document
    $query = "SELECT * FROM HORRAIRE WHERE 1=1";
    
    // Add filters based on form input
    if (!empty($_GET['trajet_id'])) {
        $trajet_id = sanitize($conn, $_GET['trajet_id']);
        $query .= " AND TRAJET_ID = $trajet_id";
    }
    
    if (!empty($_GET['itineraire_id'])) {
        $itineraire_id = sanitize($conn, $_GET['itineraire_id']);
        $query .= " AND ITINERAIRE_ID = $itineraire_id";
    }
    
    if (!empty($_GET['arret_id'])) {
        $arret_id = sanitize($conn, $_GET['arret_id']);
        $query .= " AND ARRET_ID = $arret_id";
    }
    
    if (!empty($_GET['heure_arrivee'])) {
        $heure_arrivee = sanitize($conn, $_GET['heure_arrivee']);
        $query .= " AND LOWER(HEURE_ARRIVEE) LIKE LOWER('%$heure_arrivee%')";
    }
    
    if (!empty($_GET['heure_depart'])) {
        $heure_depart = sanitize($conn, $_GET['heure_depart']);
        $query .= " AND LOWER(HEURE_DEPART) LIKE LOWER('%$heure_depart%')";
    }
    
    // Execute query and display results
    $result = $conn->query($query);
    
    if (!$result) {
        echo "<p>Error: " . $conn->error . "</p>";
        return;
    }
    
    if ($result->num_rows > 0) {
        echo "<h3>Results: " . $result->num_rows . " records found</h3>";
        echo "<table>";
        echo "<tr><th>TRAJET_ID</th><th>ITINERAIRE_ID</th><th>ARRET_ID</th><th>HEURE_ARRIVEE</th><th>HEURE_DEPART</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['TRAJET_ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ITINERAIRE_ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ARRET_ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['HEURE_ARRIVEE']) . "</td>";
            echo "<td>" . htmlspecialchars($row['HEURE_DEPART']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No results found.</p>";
    }
}

// Function to display EXCEPTION form
function displayExceptionForm() {
    // Pre-compute values
    $service_id_value = isset($_GET['service_id']) ? htmlspecialchars($_GET['service_id']) : '';
    $date_value = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '';
    $code_value = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';
    
    echo <<<HTML
    <h2>Query EXCEPTION Table</h2>
    <form method="GET" action="">
        <input type="hidden" name="table" value="EXCEPTION">
        
        <label for="service_id">SERVICE_ID:</label>
        <input type="number" name="service_id" id="service_id" value="$service_id_value">
        <br>
        
        <label for="date">DATE:</label>
        <input type="date" name="date" id="date" value="$date_value">
        <br>
        
        <label for="code">CODE (contains):</label>
        <input type="text" name="code" id="code" value="$code_value">
        <br>
        
        <input type="submit" value="Query">
    </form>
HTML;
}
// Function to execute EXCEPTION query
function executeExceptionQuery($conn) {
    // Start building the query
    $query = "SELECT * FROM EXCEPTION WHERE 1=1";
    
    // Add filters based on form input
    if (!empty($_GET['service_id'])) {
        $service_id = sanitize($conn, $_GET['service_id']);
        $query .= " AND SERVICE_ID = $service_id";
    }
    
    if (!empty($_GET['date'])) {
        $date = sanitize($conn, $_GET['date']);
        $query .= " AND DATE = '$date'";
    }
    
    if (!empty($_GET['code'])) {
        $code = sanitize($conn, $_GET['code']);
        $query .= " AND LOWER(CODE) LIKE LOWER('%$code%')";
    }
    
    // Execute query and display results
    $result = $conn->query($query);
    
    if (!$result) {
        echo "<p>Error: " . $conn->error . "</p>";
        return;
    }
    
    if ($result->num_rows > 0) {
        echo "<h3>Results: " . $result->num_rows . " records found</h3>";
        echo "<table>";
        echo "<tr><th>SERVICE_ID</th><th>DATE</th><th>CODE</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['SERVICE_ID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['DATE']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CODE']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No results found.</p>";
    }
}
?>