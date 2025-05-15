<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Ensure no PHP errors are output
error_reporting(0);
ini_set('display_errors', 0);

// Ensure we're outputting JSON
header('Content-Type: application/json');

// Function to log email sends
function logEmailSend($email, $imageUrl, $subscribedToNewsletter = false) {
    $timestamp = date('Y-m-d H:i:s');
    $newsletterInfo = $subscribedToNewsletter ? " [NEWSLETTER SUBSCRIPTION]" : "";
    $logEntry = sprintf("[%s]%s Email sent to: %s, Image: %s\n", $timestamp, $newsletterInfo, $email, $imageUrl);
    $logFile = __DIR__ . '/email_log.txt';
    
    // Create directory if it doesn't exist
    $dir = dirname($logFile);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Function to log newsletter subscriptions
function logNewsletterSubscription($email) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = sprintf("[%s] Newsletter subscription: %s\n", $timestamp, $email);
    $logFile = __DIR__ . '/newsletter_subscriptions.txt';
    
    // Create directory if it doesn't exist
    $dir = dirname($logFile);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['email']) || !isset($data['imageUrl'])) {
        throw new Exception('Missing required parameters');
    }

    $to = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    $imageUrl = filter_var($data['imageUrl'], FILTER_SANITIZE_URL);
    
    // Determine protocol based on server settings
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    
    // Create links to the new view photo page instead of direct image
    $viewPhotoUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/view-photo.php?img=' . $imageUrl;
    
    // Handle newsletter subscription if requested
    $subscribeNewsletter = isset($data['subscribeNewsletter']) ? (bool)$data['subscribeNewsletter'] : false;
    if ($subscribeNewsletter) {
        logNewsletterSubscription($to);
    }

    $mail = new PHPMailer(true);

    // Debug settings - set to 0 for production
    $mail->SMTPDebug = 0;

    // Server settings
    $mail->isSMTP();
    $mail->Host = 'send.one.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'khsl_openkunstendag@lab44.be';
    $mail->Password = 'it$4)*$&mW5aKR?';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';

    // Recipients
    $mail->setFrom('khsl_openkunstendag@lab44.be', 'Kunsthumaniora Sint-Lucas Gent');
    $mail->addAddress($to);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Kunsthumaniora Sint-Lucas Gent - Jouw foto van op onze openkunstendag.';
    
    // HTML Message with view photo page link
    $htmlMessage = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <h2>Hallo!</h2>
        <p>Er is een foto met je gedeeld van de openkunstendag van Kunsthumaniora Sint-Lucas Gent.</p>
        <div style='margin: 20px 0;'>
            <p>Klik op de onderstaande knop om je foto te bekijken en te downloaden:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$viewPhotoUrl}' target='_blank' style='display: inline-block; padding: 15px 25px; background-color: #C0CE68; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>Bekijk Jouw Foto</a>
            </div>
            <p style='font-size: 14px; color: #666;'>Of kopieer en plak deze link in je browser: {$viewPhotoUrl}</p>
        </div>";
    
    // Add newsletter subscription confirmation if applicable
    if ($subscribeNewsletter) {
        $htmlMessage .= "
        <p>Bedankt voor je inschrijving op onze nieuwsbrief. Je zult regelmatig updates ontvangen over onze activiteiten en evenementen.</p>";
    }
    
    $htmlMessage .= "
        <p>Met vriendelijke groet,<br>Kunsthumaniora Sint-Lucas Gent</p>
    </body>
    </html>";
    
    // Plain text alternative
    $textMessage = "Hallo!\n\n";
    $textMessage .= "Er is een foto met je gedeeld van de openkunstendag van Kunsthumaniora Sint-Lucas Gent.\n\n";
    $textMessage .= "Bekijk je foto hier: {$viewPhotoUrl}\n\n";
    
    // Add newsletter subscription confirmation if applicable
    if ($subscribeNewsletter) {
        $textMessage .= "Bedankt voor je inschrijving op onze nieuwsbrief. Je zult regelmatig updates ontvangen over onze activiteiten en evenementen.\n\n";
    }
    
    $textMessage .= "Met vriendelijke groet,\nKunsthumaniora Sint-Lucas Gent";

    $mail->Body = $htmlMessage;
    $mail->AltBody = $textMessage;

    if ($mail->send()) {
        // Log successful email send
        logEmailSend($to, $imageUrl, $subscribeNewsletter);
        $response = ['status' => 'success', 'message' => 'Email sent successfully'];
    }
} catch (Exception $e) {
    error_log("PHPMailer Error: " . $e->getMessage());
    $response = [
        'status' => 'error',
        'message' => 'Email could not be sent: ' . $e->getMessage()
    ];
}

// Ensure we only output valid JSON
echo json_encode($response);
?>
