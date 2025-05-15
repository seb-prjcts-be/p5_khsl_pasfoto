<?php
// Get the image from the URL parameter
$imageFile = isset($_GET['img']) ? $_GET['img'] : '';

// Security check - only allow certain file patterns
if (!preg_match('/^[a-zA-Z0-9_-]+\.png$/', $imageFile)) {
    $imageFile = ''; // Reset if invalid format
}

// Full path to the image file
$imagePath = './images/' . $imageFile;

// Only proceed if the image exists
$imageExists = file_exists($imagePath) && !empty($imageFile);

// Get the absolute URL to the image
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host . dirname($_SERVER['PHP_SELF']);
$imageUrl = $baseUrl . '/images/' . $imageFile;
$downloadUrl = $baseUrl . '/download.php?img=' . $imageFile;

// Full absolute URL for the current page
$currentPageUrl = $protocol . $host . $_SERVER['REQUEST_URI'];

// Create a URL-encoded version for social media sharing
$shareUrl = urlencode($currentPageUrl);
$shareTitle = urlencode('Mijn foto van de openkunstendag van Kunsthumaniora Sint-Lucas Gent');

// For direct Facebook sharing with image
if ($imageExists) {
    list($width, $height) = getimagesize($imagePath);
}
?>
<!DOCTYPE html>
<html lang="nl" prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Jouw Foto</title>
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="css/global.css">
    
    <!-- Facebook Open Graph meta tags -->
    <meta property="og:title" content="Mijn foto van Kunsthumaniora Sint-Lucas Gent">
    <meta property="og:description" content="Bekijk mijn foto van de openkunstendag van Kunsthumaniora Sint-Lucas Gent">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $currentPageUrl; ?>">
    <?php if ($imageExists): ?>
    <meta property="og:image" content="<?php echo $imageUrl; ?>">
    <meta property="og:image:secure_url" content="<?php echo str_replace('http://', 'https://', $imageUrl); ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="<?php echo $width; ?>">
    <meta property="og:image:height" content="<?php echo $height; ?>">
    <meta property="og:image:alt" content="Foto van de openkunstendag van Kunsthumaniora Sint-Lucas Gent">
    <?php endif; ?>
    
    <!-- Twitter Card meta tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Mijn foto van Kunsthumaniora Sint-Lucas Gent">
    <meta name="twitter:description" content="Bekijk mijn foto van de openkunstendag van Kunsthumaniora Sint-Lucas Gent">
    <?php if ($imageExists): ?>
    <meta name="twitter:image" content="<?php echo $imageUrl; ?>">
    <?php endif; ?>
    
    <!-- Social sharing icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Page-specific styles */
        .main-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin: 20px auto;
            padding: 15px;
            text-align: center;
            width: 100%;
            position: relative;
        }
        
        /* Full height photo container */
        .photo-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 300px);
            min-height: 200px;
        }
        
        /* Image styling for vertical stretching */
        .photo {
            height: 100%;
            width: auto;
            object-fit: contain;
            border-radius: 5px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
        }
        
        header {
            background-color: #000;
            color: white;
            padding: 20px 0;
            text-align: center;
            width: 100%;
        }
        
        .logo {
            max-width: 200px;
            height: auto;
        }
        
        h1 {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            margin: 10px 0;
        }
        
        h2 {
            font-size: clamp(1.2rem, 3vw, 2rem);
            margin: 15px 0;
        }
        
        /* Responsive actions layout */
        .action-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
            width: 100%;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            font-size: clamp(0.875rem, 2vw, 1rem);
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
            white-space: nowrap;
        }
        
        .btn i {
            margin-right: 8px;
            font-size: clamp(1rem, 2vw, 1.25rem);
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-facebook {
            background-color: #3b5998;
            color: white;
        }
        
        .btn-website {
            background-color: #008CBA;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .error-message {
            color: #f44336;
            font-size: 18px;
            margin: 40px 0;
        }
        
        footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px 15px;
            color: #666;
            width: 100%;
        }
        
        /* Facebook Debug Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            overflow-y: auto;
            max-height: 80vh;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .photo-container {
                height: calc(100vh - 350px);
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                margin: 5px 0;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
        
        /* For very small screens */
        @media (max-width: 480px) {
            .photo-container {
                height: calc(100vh - 400px);
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 14px;
            }
            
            .btn i {
                margin-right: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header>
            <div class="container">
                <img src="images_system/logo_wit.png" alt="Kunsthumaniora Sint-Lucas Gent" class="logo">
                <!-- <h1>Openkunstdag 2025</h1> -->
            </div>
        </header>
        
        <div class="container">
            <div class="main-content">
                <?php if ($imageExists): ?>
                    <!-- <h2>Jouw foto van Kunsthumaniora Sint-Lucas Gent</h2> -->
                    
                    <div class="photo-container">
                        <img src="<?php echo $imagePath; ?>" alt="Openkunstendag Foto" class="photo">
                    </div>
                    
                    <div class="action-buttons">
                        <a href="<?php echo $downloadUrl; ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download Foto
                        </a>
                        
                        <!-- Option 1: Standard Facebook sharer -->
                        <!-- <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" 
                           target="_blank" class="btn btn-facebook">
                            <i class="fab fa-facebook-f"></i> Delen op Facebook
                        </a>
                        <a href="#" onclick="shareFacebookWithImage(); return false;" class="btn btn-facebook" style="background-color: #4267B2;">
                            <i class="fab fa-facebook-f"></i> Direct delen met foto
                        </a> -->
                        
                        <a href="https://lucasgent.be" target="_blank" class="btn btn-website">
                            <i class="bi bi-window-fullscreen"></i> Bezoek onze website
                        </a>
                    </div>
                    
                    <!-- <p>
                        Bedankt voor je bezoek aan onze openkunstendag! Bekijk meer over onze school en opleidingen op 
                        <a href="https://lucasgent.be" target="_blank">lucasgent.be</a>
                    </p> -->
                    
                    <p>
                        <a href="#" onclick="openDebugModal(); return false;">Problemen met delen? Klik hier voor hulp</a>
                    </p>
                <?php else: ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Sorry, de gevraagde foto kon niet worden gevonden.</p>
                    </div>
                    <div class="action-buttons">
                        <a href="https://lucasgent.be" target="_blank" class="btn btn-website">
                            <i class="fas fa-school"></i> Bezoek onze website
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <footer>
                <p>&copy; 2025 Kunsthumaniora Sint-Lucas Gent. Alle rechten voorbehouden.</p>
            </footer>
        </div>
    </div>
    
    <!-- Debug Modal -->
    <div id="debugModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDebugModal()">&times;</span>
            <h3>Hulp bij het delen op Facebook</h3>
            <p>Om ervoor te zorgen dat de foto correct wordt weergegeven bij het delen op Facebook:</p>
            <ol>
                <li>
                    <strong>Gebruik de "Direct delen met foto" knop</strong> - Deze methode forceert Facebook om de afbeelding direct te gebruiken.
                </li>
                <li>
                    <strong>Vernieuw de Facebook cache</strong> - Facebook bewaart informatie over links. Je kunt de cache vernieuwen via de 
                    <a href="https://developers.facebook.com/tools/debug/?q=<?php echo urlencode($currentPageUrl); ?>" target="_blank">Facebook Sharing Debugger</a>.
                </li>
                <li>
                    <strong>Controleer of de afbeelding toegankelijk is</strong> - Facebook moet de afbeelding kunnen bereiken. Als je deze pagina lokaal bekijkt, 
                    zal Facebook de afbeelding mogelijk niet kunnen laden.
                </li>
            </ol>
            
            <div style="margin-top: 20px;">
                <h4>Technische informatie:</h4>
                <p>URL voor delen: <code><?php echo htmlspecialchars($currentPageUrl); ?></code></p>
                <p>Afbeeldings-URL: <code><?php echo htmlspecialchars($imageUrl); ?></code></p>
            </div>
        </div>
    </div>
    
    <script>
        // Function to share with Facebook Feed Dialog (direct image sharing)
        function shareFacebookWithImage() {
            <?php if ($imageExists): ?>
            FB.ui({
                method: 'feed',
                link: '<?php echo $currentPageUrl; ?>',
                picture: '<?php echo $imageUrl; ?>',
                caption: 'Mijn foto van de openkunstendag van Kunsthumaniora Sint-Lucas Gent',
                description: 'Bekijk mijn foto van de openkunstendag van Kunsthumaniora Sint-Lucas Gent',
            }, function(response){});
            <?php else: ?>
            alert('Geen afbeelding gevonden om te delen.');
            <?php endif; ?>
        }
        
        // Debug modal functions
        function openDebugModal() {
            document.getElementById('debugModal').style.display = 'block';
        }
        
        function closeDebugModal() {
            document.getElementById('debugModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('debugModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <!-- Facebook SDK -->
    <div id="fb-root"></div>
    <script>
        window.fbAsyncInit = function() {
            FB.init({
                appId: '', // Optional: your Facebook App ID if you have one
                version: 'v16.0',
                xfbml: true
            });
        };
        
        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script>
</body>
</html>
