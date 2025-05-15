<?php
// Include database connection
require_once 'db_connect.php';

// Function to display messages
function showMessage($type, $message) {
    return "<div class='$type'>$message</div>";
}

$message = '';
$excelFile = 'data/Informat_Export.xlsx';

// Check if the Excel file exists
if (!file_exists($excelFile)) {
    $message = showMessage('error', "Excel file not found: $excelFile");
} else {
    // We'll need to install a PHP library to read Excel files
    // For now, we'll check if the library is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $message = showMessage('error', 'PhpSpreadsheet library is not installed. Please install it using Composer.');
        $message .= showMessage('info', 'Run: composer require phpoffice/phpspreadsheet');
    } else {
        try {
            // Load the Excel file
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $success_count = 0;
            $error_count = 0;
            
            // Get the highest row number
            $highestRow = $worksheet->getHighestRow();
            
            // Start from row 2 (assuming row 1 is header)
            for ($row = 2; $row <= $highestRow; $row++) {
                $class_name = trim($worksheet->getCellByColumnAndRow(1, $row)->getValue());
                $first_name = trim($worksheet->getCellByColumnAndRow(2, $row)->getValue());
                $last_name = trim($worksheet->getCellByColumnAndRow(3, $row)->getValue());
                
                // Skip empty rows
                if (empty($class_name) || empty($first_name) || empty($last_name)) {
                    continue;
                }
                
                // Check if class exists, if not create it
                $stmt = $conn->prepare("SELECT id FROM classes WHERE class_name = ?");
                $stmt->bind_param("s", $class_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $class_id = $row['id'];
                } else {
                    // Create new class
                    $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
                    $stmt->bind_param("s", $class_name);
                    $stmt->execute();
                    $class_id = $conn->insert_id;
                }
                
                // Check if student already exists in this class
                $stmt = $conn->prepare("SELECT id FROM students WHERE class_id = ? AND first_name = ? AND last_name = ?");
                $stmt->bind_param("iss", $class_id, $first_name, $last_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    // Add new student
                    $stmt = $conn->prepare("INSERT INTO students (class_id, first_name, last_name) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $class_id, $first_name, $last_name);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    // Student already exists, skip
                    $error_count++;
                }
            }
            
            if ($success_count > 0) {
                $message = showMessage('success', "Successfully imported $success_count student(s). Skipped $error_count record(s).");
            } else {
                $message = showMessage('error', "No new students were imported. Skipped $error_count record(s).");
            }
        } catch (Exception $e) {
            $message = showMessage('error', 'Error reading Excel file: ' . $e->getMessage());
        }
    }
}

// Get all classes for the dropdown
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name");
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
    <title>Kunsthumaniora Sint-Lucas Gent - Excel Importeren</title>
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
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
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
        .info {
            background-color: #d9edf7;
            color: #31708f;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Excel Importeren</h1>
        
        <div class="nav-links">
            <a href="index.html">Terug naar Fotobooth</a>
            <a href="manage_students.php">Studenten Beheren</a>
            <a href="import_students.php">CSV Importeren</a>
            <a href="setup_database.php">Database Setup</a>
        </div>
        
        <?php echo $message; ?>
        
        <div class="info">
            <h3>Excel Bestand</h3>
            <p>Het systeem probeert automatisch studenten te importeren uit:</p>
            <p><code><?php echo $excelFile; ?></code></p>
            <p>Zorg ervoor dat het Excel bestand de volgende kolommen bevat:</p>
            <ul>
                <li>Kolom 1: Klas</li>
                <li>Kolom 2: Voornaam</li>
                <li>Kolom 3: Achternaam</li>
            </ul>
            <p>De eerste rij wordt als header beschouwd en overgeslagen.</p>
        </div>
        
        <form action="" method="post">
            <button type="submit" name="import">Importeren</button>
        </form>
        
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
                    <?php 
                    // Count students in this class
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
                    $stmt->bind_param("i", $class['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $student_count = $row['count'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo $student_count; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="2">Geen klassen gevonden. Importeer studenten om klassen aan te maken.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
