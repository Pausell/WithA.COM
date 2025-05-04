<?php
require_once 'config.php';
require_once 'functions.php';

// Function to export data from the 'page' table as CSV
function exportPageTable($db) {
    // Fetch data from the 'page' table
    $sql = "SELECT * FROM page";
    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        // Set the headers for CSV file download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="page_table.csv"');
        
        // Open output stream for writing CSV data
        $output = fopen('php://output', 'w');

        // Write the column headers to the CSV file
        fputcsv($output, array('id', 'user_id', 'view_loc', 'the_title', 'the_data', 'the_style', 'the_body', 'the_tags', 'edited', 'created_at', 'can_edit'));

        // Fetch and write data rows to the CSV file
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }

        // Close the output stream
        fclose($output);
    } else {
        echo "No data found in the 'page' table.";
    }
}

// Call the function to export the 'page' table as CSV
exportPageTable($db);



// Function to import data from a CSV file into the 'page' table
function importPageTable($db, $csvFilePath) {
    // Open the CSV file for reading
    $csvFile = fopen($csvFilePath, 'r');

    if ($csvFile) {
        // Read the column headers from the first line of the CSV file
        $headers = fgetcsv($csvFile);

        // Prepare the INSERT statement for the 'page' table
        $sql = "INSERT INTO page (" . implode(',', $headers) . ") VALUES (";

        // Add placeholders for the values in the INSERT statement
        $sql .= str_repeat('?,', count($headers) - 1) . "?)";

        // Prepare the statement
        $stmt = $db->prepare($sql);

        // Read each data row from the CSV file and insert it into the table
        while (($data = fgetcsv($csvFile)) !== false) {
            // Bind the values from the CSV row to the prepared statement parameters
            $stmt->bind_param('sssssssssss', ...$data); // Adjust the data types ('sssssssssss') according to the column types in your table

            // Execute the statement
            $stmt->execute();
        }

        // Close the file and statement
        fclose($csvFile);
        $stmt->close();

        echo "CSV data imported successfully into the 'page' table.";
    } else {
        echo "Error opening the CSV file.";
    }
}

// Call the function to import data from the CSV file into the 'page' table
importPageTable($db, 'page_table.csv'); // Adjust the file path according to your CSV file location
?>
