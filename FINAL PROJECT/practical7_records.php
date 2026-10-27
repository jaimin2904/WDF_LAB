<?php
/**
 * Practical 7: Display Stored CSV/JSON Records
 * Reads records from the CSV and JSON storage files and displays them in a table.
 */

$csvFile = __DIR__ . '/practical7_data.csv';
$jsonFile = __DIR__ . '/practical7_data.json';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Stored Records</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table.records {
            border-collapse: collapse;
            margin: 10px auto;
            max-width: 90%;
        }
        table.records th, table.records td {
            border: 2px solid rgb(17, 8, 8);
            padding: 8px 12px;
            text-align: left;
        }
        table.records th {
            background-color: rgb(144, 164, 165);
            color: white;
        }
        table.records tr:nth-child(even) {
            background-color: #eaf4fc;
        }
        .no-data {
            color: #888;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>

    <div id="header">
        <h1 align="center">STUDENTHUB</h1>
    </div>

    <br>

    <div align="center">
        <a href="home.HTML"><button type="button">Home</button></a>
        <a href="practical7_form.HTML"><button type="button">Contact Form</button></a>
    </div>

    <br>
    <hr>
    <br>

    <div align="center">

        <h2><u>Stored Records (from JSON File)</u></h2>

        <br>

        <?php
        if (file_exists($jsonFile)) {
            $jsonData = json_decode(file_get_contents($jsonFile), true);

            if (is_array($jsonData) && count($jsonData) > 0) {
                echo '<table class="records">';
                echo '<tr>';
                echo '<th>#</th>';
                echo '<th>Full Name</th>';
                echo '<th>Email</th>';
                echo '<th>Phone</th>';
                echo '<th>Course</th>';
                echo '<th>Message</th>';
                echo '<th>Timestamp</th>';
                echo '</tr>';

                $count = 1;
                foreach ($jsonData as $record) {
                    echo '<tr>';
                    echo '<td>' . $count . '</td>';
                    echo '<td>' . htmlspecialchars($record['fullname']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['phone']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['course']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['message']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['timestamp']) . '</td>';
                    echo '</tr>';
                    $count++;
                }

                echo '</table>';
            } else {
                echo '<p class="no-data">No records found. Please submit the form first.</p>';
            }
        } else {
            echo '<p class="no-data">No records found. Please submit the form first.</p>';
        }
        ?>

        <br>

        <h2><u>Stored Records (from CSV File)</u></h2>

        <br>

        <?php
        if (file_exists($csvFile)) {
            $csvHandle = fopen($csvFile, 'r');

            if ($csvHandle) {
                $header = fgetcsv($csvHandle);

                if ($header) {
                    echo '<table class="records">';
                    echo '<tr>';
                    echo '<th>#</th>';
                    foreach ($header as $col) {
                        echo '<th>' . htmlspecialchars($col) . '</th>';
                    }
                    echo '</tr>';

                    $count = 1;
                    while (($row = fgetcsv($csvHandle)) !== false) {
                        echo '<tr>';
                        echo '<td>' . $count . '</td>';
                        foreach ($row as $cell) {
                            echo '<td>' . htmlspecialchars($cell) . '</td>';
                        }
                        echo '</tr>';
                        $count++;
                    }

                    echo '</table>';
                } else {
                    echo '<p class="no-data">CSV file is empty.</p>';
                }

                fclose($csvHandle);
            } else {
                echo '<p class="no-data">Unable to read CSV file.</p>';
            }
        } else {
            echo '<p class="no-data">No CSV records found. Please submit the form first.</p>';
        }
        ?>

        <br><br>

        <a href="practical7_form.HTML">
            <button type="button">Go Back to Form</button>
        </a>

        <br><br>

        <a href="home.HTML">
            <button type="button">BACK</button>
        </a>

    </div>

    <br>
    <hr>

    <footer align="center">
        <h4>@ 2026 Jaimin Khambhaliya</h4>
    </footer>

</body>
</html>
