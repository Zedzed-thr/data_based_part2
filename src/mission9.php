<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projet_BD";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for form values
$search_string = isset($_GET['station_search']) ? htmlspecialchars($_GET['station_search']) : '';
$min_count = isset($_GET['min_count']) ? intval($_GET['min_count']) : '';

// Prepare HTML header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Station Search</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f5f5f5; }
        label { margin-right: 10px; }
        input { margin-bottom: 10px; padding: 5px; }
        input[type="submit"] { background-color: #4CAF50; color: white; border: none; cursor: pointer; padding: 10px 15px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .search-info { margin-bottom: 10px; font-style: italic; }
    </style>
</head>
<body>
    <h1>Station Search</h1>
    
    <!-- Search Form -->
    <form method="GET" action="">
        <label for="station_search">Station Name Contains:</label>
        <input type="text" name="station_search" id="station_search" value="<?php echo $search_string; ?>">
        <br>
        
        <label for="min_count">Minimum Count (Optional):</label>
        <input type="number" name="min_count" id="min_count" value="<?php echo $min_count; ?>">
        <br>
        
        <input type="submit" value="Search">
    </form>
    
<?php
// Execute search if form is submitted
if (isset($_GET['station_search'])) {
    // Sanitize the input
    $search = $conn->real_escape_string($search_string);
    
    // Construct the SQL query
    // This query counts stops, arrivals, and departures per station and service
    $sql = "
    WITH StationCounts AS (
        SELECT 
            a.ID as ARRET_ID,
            a.NOM as STATION_NAME,
            s.ID as SERVICE_ID,
            s.NOM as SERVICE_NAME,
            COUNT(DISTINCT ad.ITINERAIRE_ID) as STOP_COUNT,
            COUNT(DISTINCT CASE WHEN h.HEURE_ARRIVEE IS NOT NULL THEN h.TRAJET_ID END) as ARRIVAL_COUNT,
            COUNT(DISTINCT CASE WHEN h.HEURE_DEPART IS NOT NULL THEN h.TRAJET_ID END) as DEPARTURE_COUNT
        FROM 
            ARRET a
        LEFT JOIN 
            ARRET_DESSERVI ad ON a.ID = ad.ARRET_ID
        LEFT JOIN 
            HORRAIRE h ON a.ID = h.ARRET_ID
        LEFT JOIN 
            TRAJET t ON h.TRAJET_ID = t.TRAJET_ID
        LEFT JOIN 
            SERVICE s ON t.SERVICE_ID = s.ID
        WHERE 
            LOWER(a.NOM) LIKE LOWER('%$search%')
        GROUP BY 
            a.ID, a.NOM, s.ID, s.NOM
    )
    SELECT 
        STATION_NAME,
        SERVICE_NAME,
        SUM(STOP_COUNT) as TOTAL_STOPS,
        SUM(ARRIVAL_COUNT) as TOTAL_ARRIVALS,
        SUM(DEPARTURE_COUNT) as TOTAL_DEPARTURES
    FROM 
        StationCounts
    GROUP BY 
        STATION_NAME, SERVICE_NAME
    HAVING 
        SUM(STOP_COUNT) >= " . ($min_count != '' ? $min_count : 0) . " OR 
        SUM(ARRIVAL_COUNT) >= " . ($min_count != '' ? $min_count : 0) . " OR 
        SUM(DEPARTURE_COUNT) >= " . ($min_count != '' ? $min_count : 0) . "
    ORDER BY 
        (SUM(STOP_COUNT) + SUM(ARRIVAL_COUNT) + SUM(DEPARTURE_COUNT)) DESC";
    
    // Execute the query
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "<p>Error: " . $conn->error . "</p>";
    } else {
        // Display search information
        echo "<div class='search-info'>";
        if ($search_string != '') {
            echo "Searching for stations containing: '<strong>" . htmlspecialchars($search_string) . "</strong>'";
            if ($min_count != '') {
                echo " with at least <strong>" . htmlspecialchars($min_count) . "</strong> stops, arrivals, or departures";
            }
            echo "<br>Found: " . $result->num_rows . " results";
        }
        echo "</div>";
        
        // Display results if any
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr>
                <th>Station Name</th>
                <th>Service Name</th>
                <th>Total Stops</th>
                <th>Total Arrivals</th>
                <th>Total Departures</th>
            </tr>";
            
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['STATION_NAME']) . "</td>";
                echo "<td>" . htmlspecialchars($row['SERVICE_NAME']) . "</td>";
                echo "<td>" . htmlspecialchars($row['TOTAL_STOPS']) . "</td>";
                echo "<td>" . htmlspecialchars($row['TOTAL_ARRIVALS']) . "</td>";
                echo "<td>" . htmlspecialchars($row['TOTAL_DEPARTURES']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No matching stations found.</p>";
        }
    }
}

$conn->close();
?>
</body>
</html>