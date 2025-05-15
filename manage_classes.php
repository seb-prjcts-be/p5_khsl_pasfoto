<?php
// Include database connection
require_once 'db_connect.php';

$message = '';

// Handle class deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $class_id = $_GET['delete'];
    
    // Check if there are students in this class
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $message = "<div class='error'>Cannot delete class because it contains students. Please move or delete the students first.</div>";
    } else {
        // Delete the class
        $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->bind_param("i", $class_id);
        
        if ($stmt->execute()) {
            $message = "<div class='success'>Class deleted successfully.</div>";
        } else {
            $message = "<div class='error'>Error deleting class: " . $conn->error . "</div>";
        }
    }
}

// Handle class addition
if (isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    
    if (empty($class_name)) {
        $message = "<div class='error'>Class name cannot be empty.</div>";
    } else {
        // Check if class already exists
        $stmt = $conn->prepare("SELECT id FROM classes WHERE class_name = ?");
        $stmt->bind_param("s", $class_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "<div class='error'>Class already exists.</div>";
        } else {
            // Add new class
            $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
            $stmt->bind_param("s", $class_name);
            
            if ($stmt->execute()) {
                $message = "<div class='success'>Class added successfully.</div>";
            } else {
                $message = "<div class='error'>Error adding class: " . $conn->error . "</div>";
            }
        }
    }
}

// Handle class update
if (isset($_POST['update_class'])) {
    $class_id = $_POST['class_id'];
    $class_name = trim($_POST['class_name']);
    
    if (empty($class_name)) {
        $message = "<div class='error'>Class name cannot be empty.</div>";
    } else {
        // Check if class already exists with this name (excluding the current class)
        $stmt = $conn->prepare("SELECT id FROM classes WHERE class_name = ? AND id != ?");
        $stmt->bind_param("si", $class_name, $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "<div class='error'>Another class with this name already exists.</div>";
        } else {
            // Update class
            $stmt = $conn->prepare("UPDATE classes SET class_name = ? WHERE id = ?");
            $stmt->bind_param("si", $class_name, $class_id);
            
            if ($stmt->execute()) {
                $message = "<div class='success'>Class updated successfully.</div>";
            } else {
                $message = "<div class='error'>Error updating class: " . $conn->error . "</div>";
            }
        }
    }
}

// Get all classes
$classes = [];
$result = $conn->query("SELECT * FROM classes ORDER BY class_name");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Count students in this class
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $student_result = $stmt->get_result();
        $student_row = $student_result->fetch_assoc();
        
        $row['student_count'] = $student_row['count'];
        $classes[] = $row;
    }
}

// Get class for editing if edit parameter is set
$edit_class = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $class_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $edit_class = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Klassen Beheren</title>
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
        input[type="text"] {
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
        .btn-danger {
            background-color: #d9534f;
        }
        .btn-danger:hover {
            background-color: #c9302c;
        }
        .btn-warning {
            background-color: #f0ad4e;
        }
        .btn-warning:hover {
            background-color: #ec971f;
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
        .actions {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Klassen Beheren</h1>
        
        <div class="nav-links">
            <a href="index.html">Terug naar Fotobooth</a>
            <a href="manage_students.php">Studenten Beheren</a>
            <a href="import_classes.php">Klassen Importeren</a>
            <a href="import_students.php">Studenten Importeren</a>
            <a href="guide.php">Handleiding</a>
        </div>
        
        <?php echo $message; ?>
        
        <?php if ($edit_class): ?>
            <h2>Klas Bewerken</h2>
            <form action="" method="post">
                <input type="hidden" name="class_id" value="<?php echo $edit_class['id']; ?>">
                <div class="form-group">
                    <label for="class_name">Klasnaam:</label>
                    <input type="text" name="class_name" id="class_name" value="<?php echo htmlspecialchars($edit_class['class_name']); ?>" required>
                </div>
                <button type="submit" name="update_class">Klas Bijwerken</button>
                <a href="manage_classes.php" class="btn btn-warning">Annuleren</a>
            </form>
        <?php else: ?>
            <h2>Nieuwe Klas Toevoegen</h2>
            <form action="" method="post">
                <div class="form-group">
                    <label for="class_name">Klasnaam:</label>
                    <input type="text" name="class_name" id="class_name" required>
                </div>
                <button type="submit" name="add_class">Klas Toevoegen</button>
            </form>
        <?php endif; ?>
        
        <h2>Bestaande Klassen</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Klas</th>
                    <th>Aantal Studenten</th>
                    <th>Aangemaakt op</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="5">Geen klassen gevonden.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><?php echo $class['id']; ?></td>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo $class['student_count']; ?></td>
                            <td><?php echo $class['created_at']; ?></td>
                            <td class="actions">
                                <a href="manage_classes.php?edit=<?php echo $class['id']; ?>" class="btn btn-warning">Bewerken</a>
                                <?php if ($class['student_count'] == 0): ?>
                                    <a href="manage_classes.php?delete=<?php echo $class['id']; ?>" class="btn btn-danger" onclick="return confirm('Weet je zeker dat je deze klas wilt verwijderen?')">Verwijderen</a>
                                <?php else: ?>
                                    <span title="Kan niet verwijderen omdat er studenten in deze klas zitten" style="color: #999; cursor: not-allowed;">Verwijderen</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px;">
            <a href="import_classes.php" class="btn">Klassen Importeren</a>
        </div>
    </div>
</body>
</html>
