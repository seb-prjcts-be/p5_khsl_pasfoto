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
    // Since we can't directly read Excel files without libraries, let's create a form
    // to allow the user to upload a CSV version of the file
    $message = showMessage('info', "Excel file found: $excelFile");
    $message .= showMessage('info', "Please convert this Excel file to CSV format and upload it using the CSV Import page.");
    $message .= "<div class='actions'><a href='import_students.php' class='btn'>Go to CSV Import</a></div>";
}

// Get all classes for the table
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Get all students
$students = [];
$query = "SELECT s.*, c.class_name 
          FROM students s 
          JOIN classes c ON s.class_id = c.id 
          ORDER BY c.class_name, s.last_name, s.first_name";
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Excel Data</title>
    <link rel="stylesheet" href="photobooth.css">
    <link rel="stylesheet" href="css/global.css">
    <style>
        .container {
            max-width: 1000px;
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
        .actions {
            margin: 20px 0;
        }
        .photo-preview {
            max-width: 100px;
            max-height: 100px;
        }
        .no-photo {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Excel Data</h1>
        
        <div class="nav-links">
            <a href="index.html">Terug naar Fotobooth</a>
            <a href="manage_students.php">Studenten Beheren</a>
            <a href="import_students.php">CSV Importeren</a>
            <a href="setup_database.php">Database Setup</a>
        </div>
        
        <?php echo $message; ?>
        
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
        
        <h2>Bestaande Studenten</h2>
        <table>
            <thead>
                <tr>
                    <th>Klas</th>
                    <th>Voornaam</th>
                    <th>Achternaam</th>
                    <th>Foto</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5">Geen studenten gevonden.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                            <td>
                                <?php if (!empty($student['photo_path']) && file_exists($student['photo_path'])): ?>
                                    <img src="<?php echo $student['photo_path']; ?>" alt="Pasfoto" class="photo-preview">
                                <?php else: ?>
                                    <span class="no-photo">Geen foto</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="take_photo.php?student_id=<?php echo $student['id']; ?>" class="btn-photo">Foto nemen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
