let v;
let photoTaken = false;
let tempImage;
let footerHeight = 100; // Updated to match the new CSS footer height

// Standard passport photo dimensions in mm
const PASSPORT_WIDTH_MM = 35; // 3.5 cm
const PASSPORT_HEIGHT_MM = 45; // 4.5 cm
const FACE_HEIGHT_MIN_MM = 32; // Minimum face height from chin to crown
const FACE_HEIGHT_MAX_MM = 36; // Maximum face height from chin to crown

// Header height for student info
const headerHeight = 100;

function setup() {
  let canvas = createCanvas(565, 800, P2D, {
    willReadFrequently: true
  });
  canvas.parent("left");
  
  let constraints = { video: true };
  v = createCapture(constraints);
  v.hide();

  v.elt.onloadeddata = function () {
    console.log("Camera is geladen!");
  };
}

function draw() {
  background(255);

  // Camera now starts below the header
  let videoY = headerHeight;
  let bottomMargin = footerHeight + 10;

  // Draw student info at the top
  drawStudentInfo();

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
    
    // Draw passport photo guide overlay
    drawPassportGuide(x, y, w, h);
  } else {
    // Display the captured image (already mirrored during capture)
    image(tempImage, 0, 0, width, height);
  }
}

function drawPassportGuide(x, y, videoWidth, videoHeight) {
  // Calculate the passport photo dimensions in pixels
  // We'll make the guide match the standard 3.5×4.5 cm ratio
  const ratio = PASSPORT_HEIGHT_MM / PASSPORT_WIDTH_MM;
  
  let passportWidth, passportHeight;
  
  // Determine if we should constrain by width or height
  if (videoWidth / videoHeight > PASSPORT_WIDTH_MM / PASSPORT_HEIGHT_MM) {
    // Video is wider than passport ratio, constrain by height
    passportHeight = videoHeight * 0.8; // Use 80% of available height
    passportWidth = passportHeight / ratio;
  } else {
    // Video is taller than passport ratio, constrain by width
    passportWidth = videoWidth * 0.8; // Use 80% of available width
    passportHeight = passportWidth * ratio;
  }
  
  // Center the passport guide in the video area
  const passportX = x + (videoWidth - passportWidth) / 2;
  const passportY = y + (videoHeight - passportHeight) / 2;
  
  // Draw the passport photo outline with a more visible color and thicker line
  noFill();
  stroke(255, 0, 0, 220); // Bright red with high opacity
  strokeWeight(3);
  rect(passportX, passportY, passportWidth, passportHeight);
  
  // Calculate face height (approximately 75% of photo height)
  const faceHeight = passportHeight * 0.75;
  
  // Calculate face position (centered in the passport photo)
  const faceX = passportX + passportWidth / 2;
  const faceY = passportY + passportHeight * 0.4; // Position face slightly above center
  
  // Draw face outline oval with more visible color
  stroke(255, 255, 0, 220); // Bright yellow with high opacity
  ellipse(faceX, faceY, faceHeight * 0.7, faceHeight);
  
  // Draw horizontal lines for chin and crown
  const chinY = faceY + faceHeight / 2;
  const crownY = faceY - faceHeight / 2;
  
  // Draw lines for chin and crown with more visible color
  stroke(0, 255, 255, 220); // Cyan with high opacity
  line(passportX, chinY, passportX + passportWidth, chinY);
  line(passportX, crownY, passportX + passportWidth, crownY);
  
  // Add text instructions with more visible text
  noStroke();
  fill(255, 255, 255); // White text
  textSize(16); // Larger text
  textStyle(BOLD); // Bold text
  textAlign(CENTER);
  
  // Add semi-transparent background for text to make it more readable
  fill(0, 0, 0, 150); // Semi-transparent black background
  rect(width/2 - 200, passportY + passportHeight + 10, 400, 60, 5);
  
  // Add text on top of the background
  fill(255, 255, 255); // White text
  text("Plaats je gezicht binnen de gele ovaal", width / 2, passportY + passportHeight + 30);
  text("Gezichtshoogte: 32-36 mm (75% van fotohoogte)", width / 2, passportY + passportHeight + 55);
  textAlign(LEFT);
  textStyle(NORMAL); // Reset text style
}

function drawStudentInfo() {
  // Draw black background for student info header
  noStroke();
  fill(0); // Black background
  rect(0, 0, width, headerHeight); // Header height
  
  // Draw student information text
  fill(255); // White text
  textSize(16);
  textAlign(CENTER, TOP);
  
  // Draw header text
  text("Pasfoto voor:", width/2, 10);
  
  // Draw class and name info
  textSize(18);
  // text("Klas: " + className, width/2, 40);
  // text("Naam: " + firstName + " " + lastName, width/2, 70);
}

function keyPressed() {
  if (keyCode === 32 && !photoTaken) {
    takePhoto();
  }
}

function retryPhoto() {
  photoTaken = false;
  
  // Hide the dark overlay
  document.getElementById("photoTakenOverlay").style.display = "none";
  
  document.getElementById("takePhotoBtn").style.display = "inline";
  document.getElementById("statusMessage").innerText = "Klaar om een foto te maken";
  document.getElementById("retryBtn").style.display = "none";
  document.getElementById("saveBtn").style.display = "none";
  document.getElementById("shareBtn").style.display = "none";
}

function takePhoto() {
  photoTaken = true;
  
  // Create a temporary canvas to store the mirrored image
  let tempCanvas = document.createElement('canvas');
  tempCanvas.width = width;
  tempCanvas.height = height;
  let ctx = tempCanvas.getContext('2d');
  
  // Draw the current canvas to the temporary canvas
  ctx.drawImage(canvas.elt, 0, 0);
  
  // Create a p5.js image from the temporary canvas
  tempImage = createImage(width, height);
  tempImage.drawingContext.drawImage(tempCanvas, 0, 0);
  
  // Show the dark overlay to prevent clicking elsewhere
  document.getElementById("photoTakenOverlay").style.display = "block";

  document.getElementById("statusMessage").innerText = "Foto genomen!";
  document.getElementById("retryBtn").style.display = "inline";
  document.getElementById("saveBtn").style.display = "inline";
  document.getElementById("takePhotoBtn").style.display = "none";
  document.getElementById("shareBtn").style.display = "none";
}

function saveImage() {
  // Get the current passport guide dimensions and position
  const videoY = headerHeight;
  const videoRatio = v.width / v.height;
  const availableHeight = height - videoY - (footerHeight + 10);
  const availableWidth = width;
  
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
  
  // Create a new canvas for the cropped image
  let croppedCanvas = document.createElement('canvas');
  croppedCanvas.width = passportWidth;
  croppedCanvas.height = passportHeight;
  let ctx = croppedCanvas.getContext('2d');
  
  // Draw the cropped portion of the tempImage onto the new canvas
  // The tempImage already contains the mirrored version from takePhoto()
  ctx.drawImage(
    tempImage.canvas,
    passportX, passportY, passportWidth, passportHeight,
    0, 0, passportWidth, passportHeight
  );
  
  // Get the data URL from the cropped canvas
  let imageData = croppedCanvas.toDataURL("image/png");

  // Create animation element
  const animatedPhoto = document.createElement('img');
  animatedPhoto.src = imageData;
  animatedPhoto.className = 'photo-animation';
  animatedPhoto.style.top = '50%';
  animatedPhoto.style.left = '50%';
  animatedPhoto.style.transform = 'translate(-50%, -50%)';
  document.body.appendChild(animatedPhoto);

  // Update status message
  document.getElementById("statusMessage").innerText = "Foto wordt opgeslagen...";

  // Remove the animation element after animation completes
  setTimeout(() => {
    document.body.removeChild(animatedPhoto);
  }, 1000);

  // Get current class from URL if available
  const urlParams = new URLSearchParams(window.location.search);
  const classParam = urlParams.get('class');
  
  // Prepare data to send
  const postData = { 
    image: imageData
  };
  
  // Add class information if available
  if (classParam) {
    postData.class_name = classParam;
  }

  fetch("saveImage.php", {
    method: "POST",
    body: JSON.stringify(postData),
    headers: { "Content-Type": "application/json" }
  })
    .then(response => response.json())
    .then(data => {
      if (data.status === "success") {
        updateGallery();
        
        // Hide the dark overlay after successful save
        document.getElementById("photoTakenOverlay").style.display = "none";
        
        // Show success notification
        if (document.getElementById("notification")) {
          showNotification(true, "Succes", "De pasfoto is succesvol opgeslagen!");
        }
        
        retryPhoto();
      } else {
        // Show error notification
        if (document.getElementById("notification")) {
          showNotification(false, "Fout", "Er is een fout opgetreden bij het opslaan van de foto.");
        } else {
          console.error("Error saving photo:", data.message || "Unknown error");
        }
      }
    })
    .catch(error => {
      console.error("Error:", error);
      if (document.getElementById("notification")) {
        showNotification(false, "Fout", "Er is een fout opgetreden bij het opslaan van de foto.");
      }
    });
}

function updateGallery() {
  fetch("gallery.php")
    .then(response => response.text())
    .then(data => {
      document.getElementById("gallery").innerHTML = data;
    });
}

function downloadImage() {
  const imgSrc = document.getElementById("largeImg").src;
  
  const link = document.createElement('a');
  link.href = imgSrc;
  link.download = 'pasfoto.png';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function showImage(src) {
  document.getElementById("overlay").style.display = "flex";
  document.getElementById("largeImg").src = src;
}