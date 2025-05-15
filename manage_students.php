<?php
// Include database connection
require_once 'db_connect.php';

$message = '';

// Check if admin mode is enabled
$admin_mode = isset($_GET['admin']) && $_GET['admin'] == 1;

// Handle student deletion (now just deletes the photo)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $student_id = $_GET['delete'];
    
    // Get student photo path
    $stmt = $conn->prepare("SELECT photo_path FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $photo_path = $row['photo_path'];
        
        // Delete the photo file if it exists
        if (!empty($photo_path) && file_exists($photo_path)) {
            unlink($photo_path);
            
            // Update the student record to remove photo path
            $stmt = $conn->prepare("UPDATE students SET photo_path = NULL WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            
            if ($stmt->execute()) {
                $message = "<div class='success'>Foto succesvol verwijderd.</div>";
            } else {
                $message = "<div class='error'>Fout bij het bijwerken van de studentgegevens: " . $conn->error . "</div>";
            }
        } else {
            $message = "<div class='info'>Deze student heeft geen foto om te verwijderen.</div>";
        }
    }
}

// Handle complete student deletion (separate function)
// Only available in admin mode
if ($admin_mode && isset($_GET['remove_student']) && is_numeric($_GET['remove_student'])) {
    $student_id = $_GET['remove_student'];
    
    // Get student photo path before deleting
    $stmt = $conn->prepare("SELECT photo_path FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $photo_path = $row['photo_path'];
        
        // Delete the photo file if it exists
        if (!empty($photo_path) && file_exists($photo_path)) {
            unlink($photo_path);
        }
        
        // Delete the student from database
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        
        if ($stmt->execute()) {
            $message = "<div class='success'>Student succesvol verwijderd.</div>";
        } else {
            $message = "<div class='error'>Fout bij het verwijderen van de student: " . $conn->error . "</div>";
        }
    }
}

// Get filter values
$filter_class = isset($_GET['class']) ? $_GET['class'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get all classes for the dropdown
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Build query to get students based on filters
$query = "SELECT s.*, c.class_name 
          FROM students s 
          JOIN classes c ON s.class_id = c.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_class)) {
    $query .= " AND c.id = ?";
    $params[] = $filter_class;
    $types .= "i";
}

if (!empty($search_term)) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ?)";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY c.class_name, s.last_name, s.first_name";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch all students
$students = [];
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
    <title>Kunsthumaniora Sint-Lucas Gent - Studenten Beheren</title>
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
            display: inline-block;
            margin-right: 10px;
            font-weight: bold;
        }
        input[type="text"], select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
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
        .actions a {
            margin-right: 10px;
            text-decoration: none;
        }
        .photo-preview {
            max-width: 100px;
            max-height: 100px;
        }
        .filters {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .no-photo {
            color: #999;
            font-style: italic;
        }
        .admin-link {
            background-color: #f44336;
            color: white !important;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .admin-notice {
            background-color: #ffeb3b;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Studenten Beheren</h1>
        
        <div class="nav-links">
            <a href="index.html">Terug naar Fotobooth</a>
            <a href="import_students.php">Studenten Importeren</a>
            <a href="setup_database.php">Database Setup</a>
            <?php if ($admin_mode): ?>
                <a href="manage_students.php" class="admin-link">Verlaat Admin Modus</a>
            <?php else: ?>
                <a href="manage_students.php?admin=1" class="admin-link">Admin Modus</a>
            <?php endif; ?>
        </div>
        
        <?php if ($admin_mode): ?>
            <div class="admin-notice">
                <p><strong>Admin Modus Actief</strong> - Extra functies beschikbaar</p>
            </div>
        <?php endif; ?>
        
        <?php echo $message; ?>
        
        <form action="" method="get" class="filters">
            <div class="form-group">
                <label for="class">Klas:</label>
                <select name="class" id="class">
                    <option value="">Alle klassen</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo ($filter_class == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="search">Zoeken:</label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Naam...">
            </div>
            
            <button type="submit">Filteren</button>
        </form>
        
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
                                <a href="manage_students.php?delete=<?php echo $student['id']; ?>" class="btn-delete" onclick="return confirm('Weet je zeker dat je de foto van deze student wilt verwijderen?')">Foto verwijderen</a>
                                <?php if ($admin_mode): ?>
                                    <a href="manage_students.php?remove_student=<?php echo $student['id']; ?>" class="btn-delete" onclick="return confirm('Weet je zeker dat je deze student wilt verwijderen?')">Verwijderen</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
