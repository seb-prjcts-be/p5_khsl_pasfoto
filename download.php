<?php
// Get the image from the URL parameter
$imageFile = isset($_GET['img']) ? $_GET['img'] : '';

// Security check - only allow certain file patterns
if (!preg_match('/^[a-zA-Z0-9_-]+\.png$/', $imageFile)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Invalid file requested');
}

// Full path to the image file
$imagePath = './images/' . $imageFile;

// Check if the file exists
if (!file_exists($imagePath) || !is_file($imagePath)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// Get the file information
$fileInfo = pathinfo($imagePath);
$fileName = 'OKD_foto_' . date('Y-m-d') . '.png';

// Set the headers for forced download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($imagePath));

// Output the file contents
readfile($imagePath);
exit;
