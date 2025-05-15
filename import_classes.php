<?php
// Include database connection
require_once 'db_connect.php';

$message = '';

// Handle CSV upload
if (isset($_POST['submit'])) {
    // Check if file was uploaded without errors
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file_name = $_FILES['csv_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check if file is a CSV
        if ($file_ext == 'csv') {
            // Open uploaded CSV file with read-only mode
            $csv_file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            
            // Check if we should skip the first line (headers)
            $skip_first_line = isset($_POST['skip_first_line']) ? true : false;
            
            // Determine the delimiter (comma or semicolon)
            $delimiter = isset($_POST['delimiter']) ? $_POST['delimiter'] : ';';
            
            if ($skip_first_line) {
                fgetcsv($csv_file, 10000, $delimiter);
            }
            
            $classes = [];
            
            // Parse data from CSV file line by line to extract unique classes
            while (($getData = fgetcsv($csv_file, 10000, $delimiter)) !== FALSE) {
                // Check if we have at least 3 columns (last_name, first_name, class_name)
                if (count($getData) >= 3) {
                    $class_name = trim($getData[2]); // Class is in the third column
                    
                    // Skip empty class names
                    if (!empty($class_name) && !in_array($class_name, $classes)) {
                        $classes[] = $class_name;
                    }
                }
            }
            
            fclose($csv_file);
            
            // Clear existing classes if requested
            if (isset($_POST['clear_classes']) && $_POST['clear_classes'] == 'yes') {
                // First check if there are students in the database
                $result = $conn->query("SELECT COUNT(*) as count FROM students");
                $row = $result->fetch_assoc();
                $student_count = $row['count'];
                
                if ($student_count > 0) {
                    $message = "<div class='error'>Kan bestaande klassen niet wissen omdat er nog studenten in de database zijn. Verwijder eerst alle studenten.</div>";
                } else {
                    // No students, safe to delete all classes
                    // We can't use TRUNCATE because of foreign key constraints
                    // Instead, use DELETE and then reset the auto-increment
                    $conn->query("DELETE FROM classes");
                    $conn->query("ALTER TABLE classes AUTO_INCREMENT = 1");
                    $message = "<div class='success'>Alle bestaande klassen zijn gewist.</div>";
                }
            }
            
            // Import new classes
            $success_count = 0;
            $error_count = 0;
            
            foreach ($classes as $class_name) {
                // Check if class already exists
                $stmt = $conn->prepare("SELECT id FROM classes WHERE class_name = ?");
                $stmt->bind_param("s", $class_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    // Add new class
                    $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
                    $stmt->bind_param("s", $class_name);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    // Class already exists, skip
                    $error_count++;
                }
            }
            
            if ($success_count > 0) {
                $message .= "<div class='success'>Successfully imported $success_count class(es). Skipped $error_count duplicate(s).</div>";
            } else if (count($classes) > 0) {
                $message .= "<div class='error'>No new classes were imported. All $error_count classes already exist.</div>";
            } else {
                $message .= "<div class='error'>No classes found in the CSV file. Make sure the file has the correct format.</div>";
            }
        } else {
            $message = "<div class='error'>Please upload a CSV file.</div>";
        }
    } else {
        $message = "<div class='error'>Error uploading file. Please try again.</div>";
    }
}

// Get all classes for display
$classes = [];
$result = $conn->query("SELECT c.*, COUNT(s.id) as student_count 
                        FROM classes c 
                        LEFT JOIN students s ON c.id = s.class_id 
                        GROUP BY c.id 
                        ORDER BY c.class_name");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Klassen Importeren</title>
    <link rel="stylesheet" href="photobooth.css">
    <link rel="stylesheet" href="css/global.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .checkbox-group input {
            margin-right: 10px;
        }
        .radio-group {
            margin-bottom: 15px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .radio-option input {
            margin-right: 10px;
        }
        button, .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
            margin-right: 10px;
        }
        button:hover, .btn:hover {
            background-color: #45a049;
        }
        .danger-btn {
            background-color: #d9534f;
        }
        .danger-btn:hover {
            background-color: #c9302c;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            margin-right: 15px;
            text-decoration: none;
            color: #337ab7;
        }
        .csv-template {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .warning {
            background-color: #fcf8e3;
            color: #8a6d3b;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Klassen Importeren</h1>
        
        <div class="nav-links">
            <a href="index.html">Terug naar Fotobooth</a>
            <a href="manage_classes.php">Klassen Beheren</a>
            <a href="setup_database.php">Database Setup</a>
            <a href="guide.php">Handleiding</a>
        </div>
        
        <?php echo $message; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">Upload CSV Bestand:</label>
                <input type="file" name="csv_file" id="csv_file" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="skip_first_line" id="skip_first_line" checked>
                <label for="skip_first_line">Eerste regel overslaan (bevat kolomnamen)</label>
            </div>
            
            <div class="radio-group">
                <label>Scheidingsteken:</label>
                <div class="radio-option">
                    <input type="radio" name="delimiter" id="delimiter_semicolon" value=";" checked>
                    <label for="delimiter_semicolon">Puntkomma (;) - Excel standaard in Europa</label>
                </div>
                <div class="radio-option">
                    <input type="radio" name="delimiter" id="delimiter_comma" value=",">
                    <label for="delimiter_comma">Komma (,)</label>
                </div>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="clear_classes" id="clear_classes" value="yes">
                <label for="clear_classes">Bestaande klassen wissen (alleen mogelijk als er geen studenten zijn)</label>
            </div>
            
            <div class="warning">
                <strong>Let op:</strong> Het systeem zal automatisch alle unieke klassen uit de derde kolom (Klas) van het CSV-bestand extraheren.
            </div>
            
            <button type="submit" name="submit">Importeren</button>
            <a href="manage_classes.php" class="btn">Klassen Beheren</a>
        </form>
        
        <div class="csv-template">
            <h3>CSV Bestand Formaat</h3>
            <p>Het CSV-bestand moet de volgende kolommen hebben:</p>
            <ol>
                <li><strong>Achternaam</strong> - De achternaam van de student</li>
                <li><strong>Voornaam</strong> - De voornaam van de student</li>
                <li><strong>Klas</strong> - De klas van de student (deze kolom wordt gebruikt voor het importeren van klassen)</li>
            </ol>
            <p>Bijvoorbeeld:</p>
            <pre>Janssen;Jan;1A
Pieters;Piet;1B
Klaassen;Klaas;2A</pre>
            <p>Je kunt een voorbeeldbestand genereren via <a href="create_sample_excel.php">deze link</a>.</p>
            <p><strong>Opmerking:</strong> Als je bestand kolomnamen in de eerste regel heeft, vink dan "Eerste regel overslaan" aan.</p>
        </div>
        
        <h2>Bestaande Klassen</h2>
        <table>
            <thead>
                <tr>
                    <th>Klas</th>
                    <th>Aantal Studenten</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo $class['student_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="2">Geen klassen gevonden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
