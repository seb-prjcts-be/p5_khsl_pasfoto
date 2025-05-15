<?php
// Include database connection for class information
require_once 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data["image"])) {
    $image = $data["image"];
    $image = str_replace("data:image/png;base64,", "", $image);
    $image = str_replace(" ", "+", $image);
    $imageData = base64_decode($image);

    // Get the class name from the request if available
    $class_folder = "general";
    
    if (isset($data["class_name"]) && !empty($data["class_name"])) {
        // Create a clean class name for the folder (remove special characters)
        $class_folder = preg_replace('/[^a-z0-9]/i', '_', $data["class_name"]);
    } else {
        // Try to get from session as fallback
        session_start();
        if (isset($_SESSION['current_class']) && !empty($_SESSION['current_class'])) {
            $class_folder = preg_replace('/[^a-z0-9]/i', '_', $_SESSION['current_class']);
        }
    }
    
    // Create the main images directory if it doesn't exist
    if (!file_exists("images")) {
        mkdir("images", 0777, true);
    }
    
    // Create the class-specific directory if it doesn't exist
    $class_path = "images/" . $class_folder;
    if (!file_exists($class_path)) {
        mkdir($class_path, 0777, true);
    }
    
    // Create a temporary file to work with
    $tempFile = tempnam(sys_get_temp_dir(), 'passport_');
    file_put_contents($tempFile, $imageData);
    
    // Load the image
    $sourceImage = imagecreatefrompng($tempFile);
    
    // Get dimensions
    $width = imagesx($sourceImage);
    $height = imagesy($sourceImage);
    
    // Standard passport photo dimensions (35×45mm)
    // We'll set the resolution to 300 DPI for printing
    // 35mm at 300 DPI = 413 pixels
    // 45mm at 300 DPI = 531 pixels
    $targetWidth = 413;
    $targetHeight = 531;
    
    // Create a new image with the target dimensions
    $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
    
    // Preserve transparency
    imagealphablending($targetImage, false);
    imagesavealpha($targetImage, true);
    $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
    imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
    
    // Resize and maintain aspect ratio
    imagecopyresampled(
        $targetImage, $sourceImage,
        0, 0, 0, 0,
        $targetWidth, $targetHeight,
        $width, $height
    );
    
    // Generate a unique filename in the class folder
    $filename = $class_path . "/" . uniqid() . ".png";
    
    // Save the image
    imagepng($targetImage, $filename, 9); // Maximum compression
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    unlink($tempFile);

    echo json_encode([
        "status" => "success", 
        "file" => $filename,
        "class" => $class_folder,
        "dimensions" => [
            "width" => $targetWidth,
            "height" => $targetHeight
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "No image data provided"]);
}
?>
