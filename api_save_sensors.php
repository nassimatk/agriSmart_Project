<?php
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if($data) {
        $air_temp = $data['air_temperature'] ?? 0;
        $air_hum = $data['air_humidity'] ?? 0;
        $soil_temp = $data['soil_temperature'] ?? 0;
        $soil_moist = $data['soil_moisture'] ?? 0;
        $light = $data['light_intensity'] ?? 0;
        $prediction = $data['prediction'] ?? 'N/A';
        
        $stmt = $conn->prepare("INSERT INTO sensor_readings (air_temperature, air_humidity, soil_temperature, soil_moisture, light_intensity, prediction) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ddddds", $air_temp, $air_hum, $soil_temp, $soil_moist, $light, $prediction);
        if($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Données enregistrées !"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erreur MySQL : " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Données JSON invalides"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "POST attendu"]);
}
?>
