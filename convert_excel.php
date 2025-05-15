<?php
// Function to convert Excel to CSV using COM objects (Windows only)
function convertExcelToCSV($excelFile, $csvFile) {
    try {
        // Create new COM objects - requires Windows and MS Excel installed
        $excel = new COM("Excel.Application") or die("Unable to instantiate Excel");
        $excel->Visible = false;
        $excel->DisplayAlerts = false;
        
        // Open the Excel file
        $workbook = $excel->Workbooks->Open(realpath($excelFile));
        $worksheet = $workbook->Worksheets(1);
        
        // Save as CSV
        $workbook->SaveAs(realpath(dirname($csvFile)) . "\\" . basename($csvFile), 6); // 6 = CSV format
        
        // Close and release objects
        $workbook->Close(false);
        $excel->Quit();
        
        $worksheet = null;
        $workbook = null;
        $excel = null;
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Set file paths
$excelFile = __DIR__ . '/data/Informat_Export.xlsx';
$csvFile = __DIR__ . '/data/students.csv';

// Check if the Excel file exists
if (!file_exists($excelFile)) {
    die("Excel file not found: $excelFile");
}

// Try to convert Excel to CSV
if (convertExcelToCSV($excelFile, $csvFile)) {
    echo "Excel file successfully converted to CSV. <a href='import_students.php'>Go to Import Students</a>";
} else {
    echo "Failed to convert Excel file to CSV. Make sure Microsoft Excel is installed on the server.";
}
?>
