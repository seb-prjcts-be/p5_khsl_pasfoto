<?php
// Include database connection
require_once 'db_connect.php';

$message = '';

// Handle form submission for adding a student
if (isset($_POST['add_student'])) {
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $class_name = trim($_POST['class_name']);
    
    // Validate input
    if (empty($class_name) || empty($first_name) || empty($last_name)) {
        $message = "<div class='error'>All fields are required.</div>";
    } else {
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
                $message = "<div class='success'>Student successfully added.</div>";
            } else {
                $message = "<div class='error'>Error adding student: " . $conn->error . "</div>";
            }
        } else {
            $message = "<div class='error'>Student already exists in this class.</div>";
        }
    }
}

// Handle bulk add form submission
if (isset($_POST['bulk_add'])) {
    $bulk_data = trim($_POST['bulk_data']);
    $lines = explode("\n", $bulk_data);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = explode(",", $line);
        if (count($parts) < 3) continue;
        
        $last_name = trim($parts[0]);
        $first_name = trim($parts[1]);
        $class_name = trim($parts[2]);
        
        // Skip empty values
        if (empty($class_name) || empty($first_name) || empty($last_name)) continue;
        
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
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = "<div class='success'>Successfully added $success_count student(s). Skipped $error_count record(s).</div>";
    } else {
        $message = "<div class='error'>No new students were added. Skipped $error_count record(s).</div>";
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
    <title>Kunsthumaniora Sint-Lucas Gent - Studenten Toevoegen</title>
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
        input[type="text"], select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 200px;
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
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            background-color: #f8f9fa;
        }
        .tab.active {
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Studenten Toevoegen</h1>
        
        <div class="nav-links">
            <a href="index.html">Terug naar Fotobooth</a>
            <a href="manage_students.php">Studenten Beheren</a>
            <a href="import_students.php">CSV Importeren</a>
            <a href="setup_database.php">Database Setup</a>
            <a href="guide.php">Handleiding</a>
        </div>
        
        <?php echo $message; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="switchTab('single')">Enkele Student</div>
            <div class="tab" onclick="switchTab('bulk')">Meerdere Studenten</div>
        </div>
        
        <div id="single-tab" class="tab-content active">
            <h2>Student Toevoegen</h2>
            <form action="" method="post">
                <div class="form-group">
                    <label for="last_name">Achternaam:</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="first_name">Voornaam:</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="class_name">Klas:</label>
                    <input type="text" name="class_name" id="class_name" required>
                </div>
                
                <button type="submit" name="add_student">Student Toevoegen</button>
            </form>
        </div>
        
        <div id="bulk-tab" class="tab-content">
            <h2>Meerdere Studenten Toevoegen</h2>
            <div class="info">
                <p>Voer elke student in op een nieuwe regel in het formaat: Achternaam,Voornaam,Klas</p>
                <p>Bijvoorbeeld:</p>
                <pre>Janssen,Jan,1A
Pieters,Piet,1B
Klaassen,Klaas,2A</pre>
            </div>
            <form action="" method="post">
                <div class="form-group">
                    <label for="bulk_data">Studenten Data:</label>
                    <textarea name="bulk_data" id="bulk_data" required></textarea>
                </div>
                
                <button type="submit" name="bulk_add">Studenten Toevoegen</button>
            </form>
        </div>
        
        <h2>Bestaande Studenten</h2>
        <table>
            <thead>
                <tr>
                    <th>Klas</th>
                    <th>Achternaam</th>
                    <th>Voornaam</th>
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
                            <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name']); ?></td>
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
    
    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.tab').forEach(function(tab) {
                tab.classList.remove('active');
            });
            
            // Activate the selected tab and content
            document.getElementById(tabId + '-tab').classList.add('active');
            document.querySelector('.tab:nth-child(' + (tabId === 'single' ? '1' : '2') + ')').classList.add('active');
        }
    </script>
</body>
</html>
