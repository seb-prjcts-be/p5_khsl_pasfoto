<?php
// Include database connection
require_once 'db_connect.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data["image"]) && isset($data["student_id"]) && isset($data["filename"])) {
    // Get the image data, student ID, and filename
    $image = $data["image"];
    $student_id = $data["student_id"];
    $filename = $data["filename"];
    
    // Clean up the image data
    $image = str_replace("data:image/png;base64,", "", $image);
    $image = str_replace(" ", "+", $image);
    $imageData = base64_decode($image);
    
    // Get the student's class information
    $stmt = $conn->prepare("SELECT c.class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_class = $result->fetch_assoc()['class_name'];
    
    // Create a clean class name for the folder (remove special characters)
    $class_folder = preg_replace('/[^a-z0-9]/i', '_', $student_class);
    
    // Create the main student_photos directory if it doesn't exist
    if (!file_exists("student_photos")) {
        mkdir("student_photos", 0777, true);
    }
    
    // Create the class-specific directory if it doesn't exist
    $class_path = "student_photos/" . $class_folder;
    if (!file_exists($class_path)) {
        mkdir($class_path, 0777, true);
    }
    
    // Create a unique filename with the student's name
    $filepath = $class_path . "/" . $filename . "_" . uniqid() . ".png";
    
    // Save the image file
    if (file_put_contents($filepath, $imageData)) {
        // Update the student record with the photo path
        $update_stmt = $conn->prepare("UPDATE students SET photo_path = ? WHERE id = ?");
        $update_stmt->bind_param("si", $filepath, $student_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(["status" => "success", "file" => $filepath]);
        } else {
            // If database update fails, delete the saved file
            unlink($filepath);
            echo json_encode(["status" => "error", "message" => "Database update failed"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save image"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing required data"]);
}
?>
