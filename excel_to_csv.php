<?php
// Include database connection
require_once 'db_connect.php';

$message = '';

// Handle file upload
if (isset($_POST['submit'])) {
    // Check if file was uploaded without errors
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
        $file_name = $_FILES['excel_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check if file is an Excel file
        if ($file_ext == 'xlsx' || $file_ext == 'xls') {
            // Create data directory if it doesn't exist
            if (!file_exists('data')) {
                mkdir('data', 0777, true);
            }
            
            // Move the uploaded file to the data directory
            $target_file = 'data/' . basename($file_name);
            if (move_uploaded_file($_FILES['excel_file']['tmp_name'], $target_file)) {
                $message = "<div class='success'>Excel file uploaded successfully. Please use the CSV Import page to import students.</div>";
                
                // Create a link to the CSV import page
                $message .= "<div class='info'><a href='import_students.php' class='btn'>Go to CSV Import</a></div>";
                
                // If we have COM objects available (Windows with MS Excel), try to convert
                if (class_exists('COM')) {
                    try {
                        // Create CSV file path
                        $csv_file = 'data/students.csv';
                        
                        // Create new COM objects
                        $excel = new COM("Excel.Application") or die("Unable to instantiate Excel");
                        $excel->Visible = false;
                        $excel->DisplayAlerts = false;
                        
                        // Open the Excel file
                        $workbook = $excel->Workbooks->Open(realpath($target_file));
                        $worksheet = $workbook->Worksheets(1);
                        
                        // Save as CSV
                        $workbook->SaveAs(realpath(dirname($csv_file)) . "\\" . basename($csv_file), 6); // 6 = CSV format
                        
                        // Close and release objects
                        $workbook->Close(false);
                        $excel->Quit();
                        
                        $worksheet = null;
                        $workbook = null;
                        $excel = null;
                        
                        $message .= "<div class='success'>Excel file successfully converted to CSV. You can now import students from the CSV.</div>";
                    } catch (Exception $e) {
                        $message .= "<div class='error'>Failed to convert Excel to CSV automatically. Please convert it manually.</div>";
                    }
                } else {
                    $message .= "<div class='info'>Automatic Excel to CSV conversion is not available. Please convert your Excel file to CSV format manually and upload it on the CSV Import page.</div>";
                }
            } else {
                $message = "<div class='error'>Error uploading file. Please try again.</div>";
            }
        } else {
            $message = "<div class='error'>Please upload an Excel file (.xlsx or .xls).</div>";
        }
    } else {
        $message = "<div class='error'>Error uploading file. Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Excel Upload</title>
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
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            margin-right: 15px;
            text-decoration: none;
            color: #337ab7;
        }
        .excel-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Excel Bestand Uploaden</h1>
        
        <div class="nav-links">
            <a href="index.html">Terug naar Fotobooth</a>
            <a href="manage_students.php">Studenten Beheren</a>
            <a href="import_students.php">CSV Importeren</a>
            <a href="setup_database.php">Database Setup</a>
        </div>
        
        <?php echo $message; ?>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file">Upload Excel Bestand:</label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required>
            </div>
            
            <button type="submit" name="submit">Uploaden</button>
        </form>
        
        <div class="excel-info">
            <h3>Excel Formaat</h3>
            <p>Zorg ervoor dat je Excel bestand de volgende kolommen bevat:</p>
            <ul>
                <li>Kolom A: Klas</li>
                <li>Kolom B: Voornaam</li>
                <li>Kolom C: Achternaam</li>
            </ul>
            <p>De eerste rij moet de kolomnamen bevatten. Elke volgende rij bevat de gegevens van één student.</p>
            <p>Na het uploaden van het Excel bestand, kun je naar de CSV Import pagina gaan om de studenten te importeren.</p>
        </div>
    </div>
</body>
</html>
