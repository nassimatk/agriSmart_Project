<?php
require_once 'db.php';
header('Content-Type: application/json');

$sql = "SELECT * FROM sensor_readings ORDER BY timestamp DESC LIMIT 10"; // get the last 10 readings for graph
$result = $conn->query($sql);

$history = [];
while($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$history = array_reverse($history); // To put chronological order left to right

if (count($history) > 0) {
    // Current is the latest
    $latest = end($history);
    echo json_encode([
        "success" => true,
        "current" => $latest,
        "history" => $history
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Aucune donnée disponible"]);
}
?>
