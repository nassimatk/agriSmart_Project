<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, image_path, description, upload_date FROM plant_photos WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$photos = [];
while ($row = $result->fetch_assoc()) {
    $row['description'] = htmlspecialchars($row['description']);
    $photos[] = $row;
}

$stmt->close();

echo json_encode(['success' => true, 'photos' => $photos]);
?>
