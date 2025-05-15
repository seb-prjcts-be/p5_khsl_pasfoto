<?php
// Include database connection to get statistics
require_once 'db_connect.php';

// Get statistics
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
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunsthumaniora Sint-Lucas Gent - Pasfoto Systeem Gids</title>
    <link rel="stylesheet" href="css/global.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .stat-box {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
            margin: 0 10px;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
        }
        .step-container {
            margin-bottom: 40px;
        }
        .step {
            background-color: #f8f9fa;
            border-left: 5px solid #3498db;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .step h3 {
            margin-top: 0;
            color: #3498db;
        }
        .step-number {
            display: inline-block;
            background-color: #3498db;
            color: white;
            width: 30px;
            height: 30px;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .button-container {
            margin-top: 15px;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .screenshot {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tip {
            background-color: #e8f4f8;
            border-left: 5px solid #2ecc71;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }
        .warning {
            background-color: #fff5e6;
            border-left: 5px solid #e67e22;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }
        .nav-menu {
            background-color: #f8f9fa;
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
            color: #3498db;
            font-weight: bold;
        }
        .nav-menu a:hover {
            text-decoration: underline;
        }
        .workflow-diagram {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .workflow-step {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px;
        }
        .workflow-arrow {
            display: inline-block;
            font-size: 24px;
            color: #666;
            margin: 0 5px;
        }
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
            }
            .stat-box {
                margin: 10px 0;
            }
            .workflow-diagram {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kunsthumaniora Sint-Lucas Gent</h1>
        <h2>Pasfoto Systeem Handleiding</h2>
    </div>

    <div class="nav-menu">
        <ul>
            <li><a href="index.html">Fotobooth</a></li>
            <li><a href="manage_students.php">Studenten Beheren</a></li>
            <li><a href="import_students.php">Studenten Importeren</a></li>
            <li><a href="manual_add_students.php">Studenten Toevoegen</a></li>
            <li><a href="manage_classes.php">Klassen Beheren</a></li>
            <li><a href="import_classes.php">Klassen Importeren</a></li>
            <li><a href="setup_database.php">Database Setup</a></li>
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

    <h2>Workflow Overzicht</h2>
    <div class="workflow-diagram">
        <div class="workflow-step">1. Database Setup</div>
        <span class="workflow-arrow">→</span>
        <div class="workflow-step">2. Klassen Beheren</div>
        <span class="workflow-arrow">→</span>
        <div class="workflow-step">3. Studenten Importeren</div>
        <span class="workflow-arrow">→</span>
        <div class="workflow-step">4. Pasfoto's Maken</div>
        <span class="workflow-arrow">→</span>
        <div class="workflow-step">5. Beheren</div>
    </div>

    <div class="step-container">
        <h2>Stap-voor-stap Handleiding</h2>
        
        <div class="step">
            <h3><span class="step-number">1</span> Database Setup</h3>
            <p>Voordat je het systeem kunt gebruiken, moet de database worden opgezet. Dit hoeft slechts één keer te gebeuren.</p>
            <div class="button-container">
                <a href="setup_database.php" class="button">Database Setup</a>
            </div>
            <div class="tip">
                <strong>Tip:</strong> Als je problemen ondervindt met de database, kun je deze stap opnieuw uitvoeren om de database te resetten.
            </div>
        </div>
        
        <div class="step">
            <h3><span class="step-number">2</span> Klassen Beheren</h3>
            <p>Je kunt klassen op verschillende manieren beheren:</p>
            
            <h4>Optie A: Klassen importeren uit CSV</h4>
            <p>Importeer unieke klassen uit een CSV-bestand met studentgegevens. Het systeem zal automatisch alle unieke klassen uit de derde kolom (Klas) extraheren.</p>
            <div class="button-container">
                <a href="import_classes.php" class="button">Klassen Importeren</a>
            </div>
            
            <h4>Optie B: Klassen handmatig beheren</h4>
            <p>Voeg, bewerk of verwijder klassen handmatig via de klassenbeheerpagina.</p>
            <div class="button-container">
                <a href="manage_classes.php" class="button">Klassen Beheren</a>
            </div>
            
            <div class="tip">
                <strong>Tip:</strong> Het is aan te raden om eerst de klassen te importeren of aan te maken voordat je studenten toevoegt.
            </div>
            
            <div class="warning">
                <strong>Let op:</strong> Je kunt een klas alleen verwijderen als er geen studenten aan gekoppeld zijn.
            </div>
        </div>
        
        <div class="step">
            <h3><span class="step-number">3</span> Studenten Importeren</h3>
            <p>Er zijn verschillende manieren om studenten toe te voegen aan het systeem:</p>
            
            <h4>Optie A: CSV-bestand importeren</h4>
            <p>Upload een CSV-bestand met de gegevens van de studenten. Het bestand moet de volgende kolommen bevatten: <strong>Achternaam, Voornaam, Klas</strong>.</p>
            <div class="button-container">
                <a href="import_students.php" class="button">Studenten Importeren</a>
            </div>
            
            <h4>Optie B: Handmatig studenten toevoegen</h4>
            <p>Voeg studenten één voor één toe of in bulk door een lijst met gegevens te plakken.</p>
            <div class="button-container">
                <a href="manual_add_students.php" class="button">Studenten Toevoegen</a>
            </div>
            
            <div class="tip">
                <strong>Tip:</strong> Voor het gemakkelijk importeren van grote aantallen studenten raden we aan om een CSV-bestand te gebruiken.
            </div>
            
            <div class="warning">
                <strong>Let op:</strong> Controleer na het importeren of alle studenten correct zijn toegevoegd door naar de pagina "Studenten Beheren" te gaan.
            </div>
        </div>
        
        <div class="step">
            <h3><span class="step-number">4</span> Pasfoto's Maken</h3>
            <p>Nadat de studenten zijn toegevoegd, kun je pasfoto's voor hen maken:</p>
            
            <h4>Optie A: Via de fotobooth</h4>
            <p>Gebruik de fotobooth om foto's te maken en op te slaan.</p>
            <div class="button-container">
                <a href="index.html" class="button">Naar Fotobooth</a>
            </div>
            
            <h4>Optie B: Via studentenbeheer</h4>
            <p>Ga naar de pagina "Studenten Beheren" en klik op "Foto nemen" naast de naam van een student.</p>
            <div class="button-container">
                <a href="manage_students.php" class="button">Studenten Beheren</a>
            </div>
            
            <div class="tip">
                <strong>Tip:</strong> Zorg voor goede belichting en een neutrale achtergrond voor de beste resultaten.
            </div>
        </div>
        
        <div class="step">
            <h3><span class="step-number">5</span> Beheren</h3>
            <p>Je kunt zowel studenten als klassen beheren:</p>
            
            <h4>Studenten Beheren</h4>
            <p>Op de pagina "Studenten Beheren" kun je:</p>
            <ul>
                <li>Alle studenten en hun gegevens bekijken</li>
                <li>Controleren welke studenten al een pasfoto hebben</li>
                <li>Studenten verwijderen indien nodig</li>
                <li>Foto's nemen voor specifieke studenten</li>
            </ul>
            <div class="button-container">
                <a href="manage_students.php" class="button">Studenten Beheren</a>
            </div>
            
            <h4>Klassen Beheren</h4>
            <p>Op de pagina "Klassen Beheren" kun je:</p>
            <ul>
                <li>Alle klassen bekijken</li>
                <li>Nieuwe klassen toevoegen</li>
                <li>Bestaande klassen bewerken</li>
                <li>Klassen verwijderen (alleen als er geen studenten aan gekoppeld zijn)</li>
                <li>Zien hoeveel studenten er in elke klas zitten</li>
            </ul>
            <div class="button-container">
                <a href="manage_classes.php" class="button">Klassen Beheren</a>
            </div>
        </div>
    </div>
    
    <div class="step-container">
        <h2>Veelgestelde Vragen</h2>
        
        <div class="step">
            <h3>Hoe maak ik een CSV-bestand?</h3>
            <p>Je kunt een CSV-bestand maken in Excel door:</p>
            <ol>
                <li>Een spreadsheet te maken met de kolommen: <strong>Achternaam, Voornaam, Klas</strong></li>
                <li>Ga naar Bestand > Opslaan als</li>
                <li>Kies "CSV (gescheiden door lijstscheidingsteken) (*.csv)" als bestandstype</li>
                <li>Klik op Opslaan</li>
            </ol>
            <div class="button-container">
                <a href="create_sample_excel.php" class="button">Voorbeeldbestand Maken</a>
            </div>
        </div>
        
        <div class="step">
            <h3>Wat als een student al bestaat?</h3>
            <p>Het systeem controleert op duplicaten op basis van klas, voornaam en achternaam. Als een student al bestaat, wordt deze overgeslagen tijdens het importeren.</p>
        </div>
        
        <div class="step">
            <h3>Hoe worden de foto's opgeslagen?</h3>
            <p>Foto's worden opgeslagen met de bestandsnaam in het formaat: klas_voornaam_achternaam_[uniek_id].png</p>
            <p>De foto's worden gekoppeld aan de studentenrecords in de database.</p>
        </div>
        
        <div class="step">
            <h3>Kan ik klassen verwijderen?</h3>
            <p>Je kunt alleen klassen verwijderen als er geen studenten aan gekoppeld zijn. Verwijder eerst alle studenten uit een klas voordat je de klas zelf verwijdert.</p>
        </div>
        
        <div class="step">
            <h3>Hoe importeer ik alleen klassen uit een CSV-bestand?</h3>
            <p>Gebruik de functie "Klassen Importeren" om unieke klassen uit een CSV-bestand te extraheren. Het systeem zal automatisch alle unieke klassen uit de derde kolom (Klas) van het bestand halen.</p>
        </div>
    </div>
    
    <div class="step-container">
        <h2>Technische Informatie</h2>
        
        <div class="step">
            <h3>Databasestructuur</h3>
            <p>Het systeem gebruikt twee hoofdtabellen:</p>
            <ul>
                <li><strong>classes</strong>: Bevat informatie over de klassen</li>
                <li><strong>students</strong>: Bevat informatie over de studenten, inclusief verwijzingen naar hun klassen en pasfoto's</li>
            </ul>
        </div>
        
        <div class="step">
            <h3>Bestandsstructuur</h3>
            <p>Belangrijke bestanden in het systeem:</p>
            <ul>
                <li><strong>index.html</strong>: De hoofdpagina met de fotobooth</li>
                <li><strong>setup_database.php</strong>: Script voor het opzetten van de database</li>
                <li><strong>import_students.php</strong>: Pagina voor het importeren van studenten via CSV</li>
                <li><strong>import_classes.php</strong>: Pagina voor het importeren van klassen uit een CSV-bestand</li>
                <li><strong>manage_students.php</strong>: Pagina voor het beheren van studenten</li>
                <li><strong>manage_classes.php</strong>: Pagina voor het beheren van klassen</li>
                <li><strong>take_photo.php</strong>: Pagina voor het maken van pasfoto's</li>
                <li><strong>manual_add_students.php</strong>: Pagina voor het handmatig toevoegen van studenten</li>
            </ul>
        </div>
    </div>
    
    <footer style="text-align: center; margin-top: 50px; padding: 20px; border-top: 1px solid #ddd; color: #666;">
        <p>Kunsthumaniora Sint-Lucas Gent - Pasfoto Systeem</p>
        <p>  <?php echo date('Y'); ?> - Alle rechten voorbehouden</p>
    </footer>
</body>
</html>
