<?php
// Include database connection
require_once 'db_connect.php';

$message = '';

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

// Get statistics for the dashboard
$stats = [
    'classes' => 0,
    'students' => 0,
    'photos' => 0
];

// Count classes
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
if ($result && $row = $result->fetch_assoc()) {
    $stats['classes'] = $row['count'];
}

// Count students
$result = $conn->query("SELECT COUNT(*) as count FROM students");
if ($result && $row = $result->fetch_assoc()) {
    $stats['students'] = $row['count'];
}

// Count photos (students with photo_path not null)
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE photo_path IS NOT NULL");
if ($result && $row = $result->fetch_assoc()) {
    $stats['photos'] = $row['count'];
}

// Color scheme
$colors = [
  "#61B199", "#C0CE68", "#FBBF2E", "#F89A3C",
  "#F3722E", "#EF4424", "#ED1C26", "#E92B59", "#E23D96",
  "#BF428A", "#9F4A81", "#69619B", "#367BB7", "#0091D1", "#F0E933"
];

// Primary colors for main elements
$primaryColor = $colors[7]; // "#E92B59" - Pink
$secondaryColor = $colors[0]; // "#61B199" - Teal
$accentColor = $colors[2]; // "#FBBF2E" - Yellow
$buttonColor = $colors[12]; // "#367BB7" - Blue
$deleteButtonColor = $colors[5]; // "#EF4424" - Red
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Pasfoto Systeem</title>
    <link rel="stylesheet" href="photobooth.css">
    <link rel="stylesheet" href="css/global.css">
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
            --accent-color: <?php echo $accentColor; ?>;
            --button-color: <?php echo $buttonColor; ?>;
            --delete-button-color: <?php echo $deleteButtonColor; ?>;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 30px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .stat-box {
            background-color: white;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
            margin: 0 10px;
            border-top: 4px solid var(--secondary-color);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--secondary-color);
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
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
            background-color: var(--accent-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: <?php echo $colors[3]; ?>; /* Darker yellow/orange */
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #eee;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: <?php echo $colors[14]; ?>; /* Light yellow */
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f0f0f0;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            margin-right: 15px;
            text-decoration: none;
            color: var(--button-color);
        }
        .actions a {
            margin-right: 10px;
            text-decoration: none;
        }
        .photo-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 3px;
            border: 2px solid #eee;
        }
        .filters {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .no-photo {
            color: #999;
            font-style: italic;
        }
        .nav-menu {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .nav-menu ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
        }
        .nav-menu li {
            margin-right: 15px;
            margin-bottom: 10px;
        }
        .nav-menu a {
            text-decoration: none;
            color: var(--button-color);
            font-weight: bold;
            transition: color 0.3s;
        }
        .nav-menu a:hover {
            color: <?php echo $colors[13]; ?>; /* Brighter blue */
        }
        .take-photo-btn {
            background-color: var(--button-color);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s, transform 0.2s;
        }
        .take-photo-btn:hover {
            background-color: <?php echo $colors[13]; ?>; /* Brighter blue */
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .delete-btn {
            background-color: var(--delete-button-color);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s, transform 0.2s;
        }
        .delete-btn:hover {
            background-color: <?php echo $colors[6]; ?>; /* Brighter red */
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        h2 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Kunsthumaniora Sint-Lucas Gent</h1>
            <h2>Pasfoto Systeem</h2>
        </div>

        <div class="nav-menu">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="import_students.php">Studenten Importeren</a></li>
                <li><a href="manual_add_students.php">Studenten Toevoegen</a></li>
                <li><a href="manage_classes.php">Klassen Beheren</a></li>
                <li><a href="import_classes.php">Klassen Importeren</a></li>
                <li><a href="guide.php">Handleiding</a></li>
            </ul>
        </div>

        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['classes']; ?></div>
                <div class="stat-label">Klassen</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['students']; ?></div>
                <div class="stat-label">Studenten</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['photos']; ?></div>
                <div class="stat-label">Pasfoto's</div>
            </div>
        </div>

        <?php echo $message; ?>
        
        <h2>Pasfoto's Nemen</h2>
        
        <p>Selecteer een student om een pasfoto te nemen:</p>
        
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
                                <a href="take_photo.php?student_id=<?php echo $student['id']; ?>" class="take-photo-btn">Foto nemen</a>
                                <a href="manage_students.php?delete=<?php echo $student['id']; ?>" class="delete-btn" onclick="return confirm('Weet je zeker dat je deze student wilt verwijderen?')">Verwijderen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
