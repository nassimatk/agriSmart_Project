<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $userId = $_SESSION['user_id'];
        
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['photo']['name']);
        $extension = strtolower($fileInfo['extension']);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($extension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé.']);
            exit;
        }
        
        // Generate a unique file name
        $newFileName = uniqid('plant_') . '.' . $extension;
        $uploadFilePath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFilePath)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO plant_photos (user_id, image_path, description) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $uploadFilePath, $description);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Photo ajoutée avec succès !',
                    'photo' => [
                        'id' => $conn->insert_id,
                        'image_path' => $uploadFilePath,
                        'description' => htmlspecialchars($description),
                        'upload_date' => date('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement dans la base de données.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement du fichier.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucune photo reçue ou une erreur est survenue.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
