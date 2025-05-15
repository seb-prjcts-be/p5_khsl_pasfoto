<?php
// Include database connection
require_once 'db_connect.php';

// Check if student_id is provided
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    header('Location: manage_students.php');
    exit;
}

$student_id = $_GET['student_id'];

// Get student information
$stmt = $conn->prepare("SELECT s.*, c.class_name 
                        FROM students s 
                        JOIN classes c ON s.class_id = c.id 
                        WHERE s.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Student not found, redirect back to manage page
    header('Location: manage_students.php');
    exit;
}

$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Pasfoto nemen</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.4.0/p5.min.js"></script>
    <link rel="stylesheet" href="photobooth.css">
    <link rel="stylesheet" href="css/global.css">
    <style>
        #container {
            display: flex;
            flex-direction: row;
            height: calc(100vh - 100px);
        }
        #left {
            flex: 1;
            position: relative;
        }
        #footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: center;
            height: 100px;
        }
        button {
            margin: 0 5px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
        }
        #photoTakenOverlay {
            display: none; /* Keep this element but don't display it */
        }
        
        button {
            margin: 0 5px;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
        }
        button:hover {
            background-color: #45a049;
        }
        #notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            z-index: 200;
            display: none;
            text-align: center;
        }
        #notification.success {
            border-left: 5px solid #4CAF50;
        }
        #notification.error {
            border-left: 5px solid #f44336;
        }
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 100;
            display: none;
        }
        .button-container {
            display: flex;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div id="container">
        <div id="left">
            <!-- P5.js canvas will be inserted here -->
        </div>
    </div>
    
    <!-- Footer met dienstmededelingen en knoppen -->
    <div id="footer">
        <span id="statusMessage">Klaar om een foto te maken</span>
        <div class="button-container">
            <button onclick="saveImage()" id="saveBtn" style="display: none;">Opslaan</button>
            <button id="takePhotoBtn" onclick="takePhoto()">Neem Foto</button>
            <button onclick="retryPhoto()" id="retryBtn" style="display: none;">Opnieuw Proberen</button>
            <button onclick="goBack()" id="backBtn">Terug</button>
        </div>
    </div>

    <!-- Dark overlay when photo is taken -->
    <div id="photoTakenOverlay"></div>
    
    <!-- Notification popup -->
    <div id="notification">
        <h3 id="notificationTitle">Bericht</h3>
        <p id="notificationMessage"></p>
        <button onclick="closeNotification()">OK</button>
    </div>
    
    <!-- Overlay for notifications -->
    <div id="overlay"></div>

    <script>
        // Student information
        const studentId = <?php echo $student_id; ?>;
        const className = "<?php echo addslashes($student['class_name']); ?>";
        const firstName = "<?php echo addslashes($student['first_name']); ?>";
        const lastName = "<?php echo addslashes($student['last_name']); ?>";
        
        let bg;
        let logo;
        let full_logo_zwart;
        let full_logo_wit;
        let type;
        let factor = 2;
        let v;
        let title01 = "OPENKUNST";
        let title02 = "DAG";
        let wanneer = "22 MAART 2025";
        let adres01 = "OUDE HOUTLEI 44";
        let adres02 = "9000 GENT";
        let colors = [
          "#61B199", "#C0CE68",  "#FBBF2E", "#F89A3C",
          "#F3722E", "#EF4424", "#ED1C26", "#E92B59", "#E23D96",
          "#BF428A", "#9F4A81", "#69619B", "#367BB7", "#0091D1","#F0E933"
        ];
        let picker01;
        let picker02;
        let picker03; 
        let photoTaken = false;
        let tempImage;
        let preview = false;
        let footerHeight = 100; 

        function preload() {
          bg = loadImage("start_interactie.png");
          full_logo_zwart = loadImage("images_system/logo_zwart.png");
          full_logo_wit = loadImage("images_system/logo_wit.png");
          type = loadFont("Basel-Grotesk-Regular.otf");
          typelight = loadFont("Basel-Grotesk-Regular.otf");
        }

        function setup() {
          let canvas = createCanvas(565, 800, P2D, {
            willReadFrequently: true
          });
          canvas.parent("left");

          bg.resize(565, 800);
          
          picker01 = random(colors);
          do {
            picker02 = random(colors);
          } while (picker02 === picker01);
          
          do {
            picker03 = random(colors);
          } while (picker03 === picker01 || picker03 === picker02);
          
          logo = random([full_logo_zwart, full_logo_wit]);

          let constraints = { video: true };
          v = createCapture(constraints);
          v.hide();

          v.elt.onloadeddata = function () {
            console.log("Camera is geladen!");
          };
        }

        function draw() {
          background(255);

          let videoY = 0; 
          let bottomMargin = footerHeight + 10; 

          if (!photoTaken) {
            let videoRatio = v.width / v.height;
            let canvasRatio = width / height;
            
            let availableHeight = height - videoY - bottomMargin;
            let availableWidth = width;

            let w, h;
            if (videoRatio > canvasRatio) {
              w = availableWidth;
              h = w / videoRatio;
              
              if (h > availableHeight) {
                h = availableHeight;
                w = h * videoRatio;
              }
            } else {
              h = availableHeight;
              w = h * videoRatio;
              
              if (w > availableWidth) {
                w = availableWidth;
                h = w / videoRatio;
              }
            }

            let x = (width - w) / 2;
            let y = videoY; 

            // Apply mirror effect
            push();
            translate(width, 0);
            scale(-1, 1);
            image(v, width - x - w, y, w, h);
            pop();
            
            // Draw guidelines on top (these won't be captured in the photo)
            drawPassportGuide(x, y, w, h);
          } else if (tempImage) {
            // Only draw tempImage if it exists
            image(tempImage, 0, 0);
          }

          generate_bg();
        }

        function keyPressed() {
          if (keyCode === 32 && !photoTaken) {
            takePhoto();
          }
        }

        function retryPhoto() { 
          photoTaken = false;
          
          // Reset UI elements without using the overlay
          document.getElementById("statusMessage").innerText = "Klaar om een foto te maken";
          document.getElementById("retryBtn").style.display = "none";
          document.getElementById("saveBtn").style.display = "none";
          document.getElementById("takePhotoBtn").style.display = "inline";
        }

        function takePhoto() { 
          try {
            // First capture the clean video frame without guidelines
            // Store current drawing state
            let cleanCanvas = createGraphics(width, height);
            
            // Draw the video with mirror effect to the clean canvas
            let videoY = 0;
            let bottomMargin = footerHeight + 10;
            let videoRatio = v.width / v.height;
            let canvasRatio = width / height;
            
            let availableHeight = height - videoY - bottomMargin;
            let availableWidth = width;
            
            let w, h;
            if (videoRatio > canvasRatio) {
              w = availableWidth;
              h = w / videoRatio;
              
              if (h > availableHeight) {
                h = availableHeight;
                w = h * videoRatio;
              }
            } else {
              h = availableHeight;
              w = h * videoRatio;
              
              if (w > availableWidth) {
                w = availableWidth;
                h = w / videoRatio;
              }
            }
            
            let x = (width - w) / 2;
            let y = videoY;
            
            // Apply mirror effect to clean canvas
            cleanCanvas.push();
            cleanCanvas.translate(width, 0);
            cleanCanvas.scale(-1, 1);
            cleanCanvas.image(v, width - x - w, y, w, h);
            cleanCanvas.pop();
            
            // Set photo taken state
            photoTaken = true;
            
            // Use the clean canvas as the photo
            tempImage = cleanCanvas;
            
            // Update UI elements
            document.getElementById("statusMessage").innerText = "Foto genomen!";
            document.getElementById("retryBtn").style.display = "inline";
            document.getElementById("saveBtn").style.display = "inline";
            document.getElementById("takePhotoBtn").style.display = "none";
          } catch (error) {
            console.error("Error in takePhoto:", error);
            alert("Er is een fout opgetreden bij het nemen van de foto. Probeer het opnieuw.");
            retryPhoto();
          }
        }

        function saveImage() {
          try {
            // Get the current passport guide dimensions and position
            let videoY = 0;
            let videoRatio = v.width / v.height;
            let bottomMargin = footerHeight + 10;
            let availableHeight = height - videoY - bottomMargin;
            let availableWidth = width;
            
            let w, h;
            if (videoRatio > availableWidth / availableHeight) {
              w = availableWidth;
              h = w / videoRatio;
              
              if (h > availableHeight) {
                h = availableHeight;
                w = h * videoRatio;
              }
            } else {
              h = availableHeight;
              w = h * videoRatio;
              
              if (w > availableWidth) {
                w = availableWidth;
                h = w / videoRatio;
              }
            }
            
            const x = (width - w) / 2;
            const y = videoY;
            
            // Calculate passport dimensions
            const PASSPORT_WIDTH_MM = 35; // 3.5 cm
            const PASSPORT_HEIGHT_MM = 45; // 4.5 cm
            const ratio = PASSPORT_HEIGHT_MM / PASSPORT_WIDTH_MM;
            let passportWidth, passportHeight;
            
            if (w / h > PASSPORT_WIDTH_MM / PASSPORT_HEIGHT_MM) {
              passportHeight = h * 0.8;
              passportWidth = passportHeight / ratio;
            } else {
              passportWidth = w * 0.8;
              passportHeight = passportWidth * ratio;
            }
            
            // Calculate passport position
            const passportX = x + (w - passportWidth) / 2;
            const passportY = y + (h - passportHeight) / 2;
            
            // Create a graphics buffer for the cropped image
            let pg = createGraphics(passportWidth, passportHeight);
            pg.image(tempImage, 0, 0, passportWidth, passportHeight, passportX, passportY, passportWidth, passportHeight);
            
            // Get the data URL from the cropped canvas
            let imageData = pg.canvas.toDataURL("image/png");
            
            // Create a filename based on class and student name
            const filename = `${className}_${firstName}_${lastName}`.replace(/[^a-z0-9]/gi, '_').toLowerCase();

            // Update status message
            document.getElementById("statusMessage").innerText = "Foto wordt opgeslagen...";

            fetch("save_student_photo.php", {
              method: "POST",
              body: JSON.stringify({ 
                image: imageData,
                student_id: studentId,
                filename: filename
              }),
              headers: { "Content-Type": "application/json" }
            })
              .then(response => response.json())
              .then(data => {
                if (data.status === "success") {
                  // Show success notification without using the overlay
                  showNotification(true, "Succes", "De pasfoto is succesvol opgeslagen!");
                  
                  // Reset the photo state
                  retryPhoto();
                } else {
                  showNotification(false, "Fout", "Er is een fout opgetreden bij het opslaan van de foto.");
                }
              })
              .catch(error => {
                console.error("Error:", error);
                showNotification(false, "Fout", "Er is een fout opgetreden bij het opslaan van de foto.");
              });
          } catch (error) {
            console.error("Error in saveImage:", error);
            showNotification(false, "Fout", "Er is een fout opgetreden bij het verwerken van de foto.");
            retryPhoto();
          }
        }

        function goBack() {
          window.location.href = 'manage_students.php';
        }
        
        function showNotification(isSuccess, title, message) {
          // Get notification elements
          const notification = document.getElementById('notification');
          const notificationTitle = document.getElementById('notificationTitle');
          const notificationMessage = document.getElementById('notificationMessage');
          
          // Set content
          notificationTitle.innerText = title;
          notificationMessage.innerText = message;
          
          // Apply appropriate styling
          notification.className = isSuccess ? 'success' : 'error';
          
          // Show notification and overlay
          document.getElementById('overlay').style.display = 'block';
          notification.style.display = 'block';
        }
        
        function closeNotification() {
          document.getElementById('notification').style.display = 'none';
          document.getElementById('overlay').style.display = 'none';
        }
        
        function generate_bg() {
          // We're removing the top rectangle to move everything up
          // The student info will be shown in the black footer bar
          
          // Bottom rectangle
          noStroke();
          fill(picker02);
          rect(0, height - footerHeight, width, footerHeight);
          
          // Student info in the footer
          fill(255);
          textFont(type);
          textSize(14);
          textAlign(LEFT, CENTER);
          text(`${className} - ${firstName} ${lastName}`, 10, height - footerHeight/2);
        }
        
        function drawPassportGuide(x, y, w, h) {
          // Calculate the passport photo dimensions in pixels
          // Standard passport photo dimensions (35×45mm)
          const PASSPORT_WIDTH_MM = 35; // 3.5 cm
          const PASSPORT_HEIGHT_MM = 45; // 4.5 cm
          const ratio = PASSPORT_HEIGHT_MM / PASSPORT_WIDTH_MM;
          
          let passportWidth, passportHeight;
          
          // Determine if we should constrain by width or height
          if (w / h > PASSPORT_WIDTH_MM / PASSPORT_HEIGHT_MM) {
            // Video is wider than passport ratio, constrain by height
            passportHeight = h * 0.8; // Use 80% of available height
            passportWidth = passportHeight / ratio;
          } else {
            // Video is taller than passport ratio, constrain by width
            passportWidth = w * 0.8; // Use 80% of available width
            passportHeight = passportWidth * ratio;
          }
          
          // Center the passport guide in the video area
          const passportX = x + (w - passportWidth) / 2;
          const passportY = y + (h - passportHeight) / 2;
          
          // Draw the passport photo outline
          noFill();
          stroke(255, 0, 0); // Bright red
          strokeWeight(4); // Thicker line
          rect(passportX, passportY, passportWidth, passportHeight);
          
          // Calculate face height (approximately 75% of photo height)
          const faceHeight = passportHeight * 0.75;
          
          // Calculate face position (centered in the passport photo)
          const faceX = passportX + passportWidth / 2;
          const faceY = passportY + passportHeight * 0.4; // Position face slightly above center
          
          // Draw face outline oval
          stroke(255, 255, 0); // Bright yellow
          strokeWeight(3); // Thicker line
          ellipse(faceX, faceY, faceHeight * 0.7, faceHeight);
          
          // Calculate chin and crown positions
          const chinY = faceY + faceHeight / 2;
          const crownY = faceY - faceHeight / 2;
          
          // Draw the chin and crown lines
          stroke(0, 255, 255); // Bright cyan
          strokeWeight(2); // Thicker line
          line(passportX, chinY, passportX + passportWidth, chinY);
          line(passportX, crownY, passportX + passportWidth, crownY);
          
          // Add text instructions with background
          // First draw text background
          noStroke();
          fill(0, 0, 0, 200); // Semi-transparent black background
          rect(width/2 - 200, passportY + passportHeight + 10, 400, 60);
          
          // Then draw text
          fill(255); // White text
          textSize(16);
          textAlign(CENTER);
          text("Plaats je gezicht binnen de gele ovaal", width / 2, passportY + passportHeight + 30);
          text("Gezichtshoogte: 32-36 mm (75% van fotohoogte)", width / 2, passportY + passportHeight + 55);
          textAlign(LEFT);
        }
    </script>
</body>
</html>
