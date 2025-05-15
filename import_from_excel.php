<?php
// Include database connection
require_once 'db_connect.php';

$message = '';
$excelFile = 'data/Informat_Export.xlsx';

// Function to extract data from Excel file using native PHP functions
function extractDataFromExcel($file) {
    $data = [];
    
    // Check if the file exists
    if (!file_exists($file)) {
        return false;
    }
    
    // Create a temporary file for the extracted data
    $tempDir = sys_get_temp_dir();
    $tempFile = tempnam($tempDir, 'excel');
    
    // Copy the Excel file to the temp file
    copy($file, $tempFile);
    
    // Rename the temp file to .zip (Excel files are essentially ZIP files)
    $zipFile = $tempFile . '.zip';
    rename($tempFile, $zipFile);
    
    // Extract the ZIP file
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === true) {
        // Extract the sheet data XML file
        if (($index = $zip->locateName('xl/worksheets/sheet1.xml')) !== false) {
            $xml = $zip->getFromIndex($index);
            $zip->close();
            
            // Parse the XML
            $dom = new DOMDocument();
            $dom->loadXML($xml, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
            
            // Get all rows
            $rows = $dom->getElementsByTagName('row');
            
            // Process each row
            foreach ($rows as $rowIndex => $row) {
                // Skip header row (row 1)
                if ($rowIndex === 0) continue;
                
                $cells = $row->getElementsByTagName('c');
                $rowData = [];
                
                // Get cell data for columns A, B, C (0, 1, 2)
                for ($i = 0; $i < 3; $i++) {
                    $cellValue = '';
                    
                    if (isset($cells[$i])) {
                        $cell = $cells[$i];
                        
                        // Get value based on cell type
                        if ($cell->hasAttribute('t') && $cell->getAttribute('t') == 's') {
                            // String value, need to look up in shared strings
                            $valueNode = $cell->getElementsByTagName('v');
                            if ($valueNode->length > 0) {
                                $cellValue = $valueNode->item(0)->nodeValue;
                            }
                        } else {
                            // Numeric or other value
                            $valueNode = $cell->getElementsByTagName('v');
                            if ($valueNode->length > 0) {
                                $cellValue = $valueNode->item(0)->nodeValue;
                            }
                        }
                    }
                    
                    $rowData[] = $cellValue;
                }
                
                // Add row data if we have all three columns
                if (count($rowData) >= 3) {
                    $data[] = $rowData;
                }
            }
        }
    }
    
    // Clean up
    unlink($zipFile);
    
    return $data;
}

// Process the Excel file if it exists
if (file_exists($excelFile)) {
    // Try to extract data from the Excel file
    $data = extractDataFromExcel($excelFile);
    
    if ($data === false) {
        $message = "<div class='error'>Failed to read Excel file: $excelFile</div>";
    } else {
        $success_count = 0;
        $error_count = 0;
        
        // Process each row of data
        foreach ($data as $row) {
            $class_name = trim($row[0]);
            $first_name = trim($row[1]);
            $last_name = trim($row[2]);
            
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
            $message = "<div class='success'>Successfully imported $success_count student(s). Skipped $error_count record(s).</div>";
        } else {
            $message = "<div class='error'>No new students were imported. Skipped $error_count record(s).</div>";
        }
    }
} else {
    $message = "<div class='error'>Excel file not found: $excelFile</div>";
}

// Get all classes for the table
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
                <li>Kolom A: Klas</li>
                <li>Kolom B: Voornaam</li>
                <li>Kolom C: Achternaam</li>
            </ul>
            <p>De eerste rij wordt als header beschouwd en overgeslagen.</p>
        </div>
        
        <div class="actions">
            <a href="import_from_excel.php" class="btn">Opnieuw Importeren</a>
            <a href="manage_students.php" class="btn">Studenten Beheren</a>
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
