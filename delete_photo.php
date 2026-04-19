<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Get the photo ID from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$photo_id = isset($data['id']) ? (int)$data['id'] : null;

if (!$photo_id) {
    echo json_encode(['success' => false, 'message' => 'ID photo manquant']);
    exit;
}

// Verify ownership and get file path
$stmt = $conn->prepare("SELECT image_path FROM plant_photos WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $photo_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $image_path = $row['image_path'];
    
    // Delete from filesystem
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    
    // Delete from database
    $delete_stmt = $conn->prepare("DELETE FROM plant_photos WHERE id = ?");
    $delete_stmt->bind_param("i", $photo_id);
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur base de donnees']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Photo introuvable ou non autorisée']);
}
?>
