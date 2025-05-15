<?php
// This script creates a simple CSV file that can be used for testing
// Since we don't have PhpSpreadsheet or COM objects, we'll create a CSV file
// that can be opened in Excel

// Define the output file path
$outputFile = 'data/sample_students.csv';

// Sample data - Achternaam (last name), Voornaam (first name), Klas (class)
$data = [
    ['Achternaam', 'Voornaam', 'Klas'], // Header row
    ['Janssen', 'Jan', '1A'],
    ['Pieters', 'Piet', '1A'],
    ['Klaassen', 'Klaas', '1B'],
    ['Maertens', 'Marie', '1B'],
    ['Smets', 'Sophie', '2A'],
    ['Thys', 'Thomas', '2A'],
    ['Lemmens', 'Lisa', '3A'],
    ['Bosmans', 'Bart', '3A'],
    ['Engels', 'Emma', '4A'],
    ['Luyten', 'Lucas', '4A']
];

// Open file for writing
$fp = fopen($outputFile, 'w');

// Write data to CSV file with semicolon delimiter (European format)
foreach ($data as $row) {
    fputcsv($fp, $row, ';');
}

// Close the file
fclose($fp);

echo "Sample CSV file created at: $outputFile";
echo "<br><br>";
echo "You can open this file in Excel and save it as an Excel file (.xlsx) for testing.";
echo "<br><br>";
echo "The file format is: <strong>Achternaam; Voornaam; Klas</strong> (Last name; First name; Class)";
echo "<br><br>";
echo "The file uses semicolons (;) as separators, which is the standard format for Excel in Europe.";
echo "<br><br>";
echo "<a href='index.html'>Back to Home</a> | <a href='guide.php'>View Guide</a>";
?>
