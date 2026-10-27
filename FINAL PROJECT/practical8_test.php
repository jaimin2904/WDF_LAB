<?php
/**
 * Practical 8: Database Connection Test
 * Tests the MySQLi connection and displays database info.
 */

require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - DB Connection Test</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-msg {
            color: green;
            font-weight: bold;
            background-color: #d4edda;
            border: 2px solid green;
            padding: 12px;
            border-radius: 8px;
            margin: 10px auto;
            max-width: 500px;
        }
        .info-table {
            border-collapse: collapse;
            margin: 10px auto;
        }
        .info-table th, .info-table td {
            border: 2px solid rgb(17, 8, 8);
            padding: 8px 15px;
            text-align: left;
        }
        .info-table th {
            background-color: rgb(144, 164, 165);
            color: white;
        }
        .info-table tr:nth-child(even) {
            background-color: #eaf4fc;
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
    </div>

    <br>
    <hr>
    <br>

    <div align="center">

        <h2><u>Database Connection Test (Practical 8)</u></h2>

        <br>

        <div class="success-msg">
            <p>✅ Database connection successful!</p>
        </div>

        <br>

        <h3>Database Information</h3>

        <table class="info-table">
            <tr>
                <th>Property</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Host</td>
                <td><?php echo htmlspecialchars($conn->host_info); ?></td>
            </tr>
            <tr>
                <td>Server Version</td>
                <td><?php echo htmlspecialchars($conn->server_info); ?></td>
            </tr>
            <tr>
                <td>Database</td>
                <td>studenthub</td>
            </tr>
            <tr>
                <td>Character Set</td>
                <td><?php echo htmlspecialchars($conn->character_set_name()); ?></td>
            </tr>
        </table>

        <br>

        <h3>Tables in Database</h3>

        <table class="info-table">
            <tr>
                <th>#</th>
                <th>Table Name</th>
                <th>Row Count</th>
            </tr>
            <?php
            $tables = $conn->query("SHOW TABLES");
            $count = 1;
            while ($row = $tables->fetch_row()) {
                $tableName = $row[0];
                $countResult = $conn->query("SELECT COUNT(*) AS cnt FROM `$tableName`");
                $rowCount = $countResult->fetch_assoc()['cnt'];
                echo '<tr>';
                echo '<td>' . $count . '</td>';
                echo '<td>' . htmlspecialchars($tableName) . '</td>';
                echo '<td>' . $rowCount . '</td>';
                echo '</tr>';
                $count++;
            }
            ?>
        </table>

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
<?php $conn->close(); ?>
