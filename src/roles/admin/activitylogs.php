<?php
require_once __DIR__ . '/../../../config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Default query to fetch all logs
    $query = "
        SELECT 
            l.log_id, 
            COALESCE(CONCAT(u.user_fname, ' ', u.user_lname), 'System') AS username, 
            l.log_date, 
            l.log_action
        FROM tb_logs l
        LEFT JOIN tb_userdetails u ON l.doer = u.user_id
        WHERE 1=1
    ";

    // Apply date filter if set
    if (isset($_GET['dateFilter']) && in_array($_GET['dateFilter'], ['week', 'month', 'year'])) {
        $filter = $_GET['dateFilter'];
        if ($filter === 'week') {
            $query .= " AND l.log_date >= CURDATE() - INTERVAL 1 WEEK";
        } elseif ($filter === 'month') {
            $query .= " AND l.log_date >= CURDATE() - INTERVAL 1 MONTH";
        } elseif ($filter === 'year') {
            $query .= " AND l.log_date >= CURDATE() - INTERVAL 1 YEAR";
        }
    }
    $query .= " ORDER BY l.log_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching logs: " . $e->getMessage());
    $logs = []; // In case of error, return empty array
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/css.css">
    <title>SecureFile</title>
    
    <!-- Ionicons -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- =============== Navigation ================ -->
    <div class="container">
        <div class="navigation">
            <ul>
                <li>
                    <a href="#" class="logo">
                        <span class="icon">
                            <img src="../../assets/img/logo.png">
                        </span>
                        <span class="securefile">
                            <span class="secure">Secure</span><span class="file">File <br><span class="role">Administrator</span></span>
                            </span>
                        </span>
                        
                    </a>
                </li>

                <li class="tab">
                    <a href="index.php">
                        <span class="icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </span>
                        <span class="title">Manage Users</span>
                    </a>
                </li>

                <li class="tab active">
                    <a href="activitylogs.php">
                        <span class="icon">
                            <ion-icon name="document-text-outline"></ion-icon>
                        </span>
                        <span class="title">Activity Logs</span>
                    </a>
                </li>

                <li class="tab">
                    <a href="profile.php">
                        <span class="icon">
                            <ion-icon name="settings-outline"></ion-icon>
                        </span>
                        <span class="title">Profile Settings</span>
                    </a>
                </li>

                <li class="tab">
                    <a href="../../../signout.php">
                        <span class="icon">
                            <ion-icon name="log-out-outline"></ion-icon>
                        </span>
                        <span class="title">Sign Out</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- ========================= Main ==================== -->
        <div class="main">
            <div class="topbar">
                <div class="left-section">
                    <div class="toggle">
                        <ion-icon name="reorder-three-outline"></ion-icon>
                    </div>

                    <div class="search">
                        <label>
                            <input type="text" id="searchBox" placeholder="Search Log Id, Username....">
                            <ion-icon name="search-outline"></ion-icon>
                        </label>
                    </div>
                </div>

                <div class="user">
                    <img src="../../assets/img/admin.png">
                </div>
            </div>

            <div class="name"> 
                <h1>Activity Logs</h1>
            </div>

            <div class="controls">
                <div class="filters">
                    <form method="GET" action="activitylogs.php">
                    <select name="dateFilter" onchange="this.form.submit()">
                        <option value="all">All Time</option>
                        <option value="week" <?php echo isset($_GET['dateFilter']) && $_GET['dateFilter'] == 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo isset($_GET['dateFilter']) && $_GET['dateFilter'] == 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="year" <?php echo isset($_GET['dateFilter']) && $_GET['dateFilter'] == 'year' ? 'selected' : ''; ?>>This Year</option>
                    </select>
                </div>
            </div>

            <!-- ========================= Table ==================== -->
            <div class="user-table">
                <div class="user-table-wrapper">
                    <table id="logsTable">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Username</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo date("Y-m-d H:i:s", strtotime($log['log_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['log_action']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
             <!-- Export Buttons -->
            <div class="export-buttons">
                <button class="csv" onclick="exportLogs('csv')">Export</button>
                <button class="print" onclick="printActivityLogs()">Print</button>
            </div>
            
        </div>

    </div> <!-- Container end -->

    <script src="js/js.js"></script>

    <script>
        document.getElementById('searchBox').addEventListener('input', function () {
            const searchValue = this.value.toLowerCase().trim();
            const tableBody = document.querySelector('#logsTable tbody');
            const rows = tableBody.getElementsByTagName('tr');
            let hasMatch = false;

            Array.from(rows).forEach(row => {
                const rowText = Array.from(row.getElementsByTagName('td')).map(cell => cell.textContent.toLowerCase()).join(' ');

                if (rowText.includes(searchValue)) {
                    row.style.display = '';
                    hasMatch = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle "No match found" message
            let noMatchMessage = document.getElementById('no-match-message');
            if (!hasMatch) {
                if (!noMatchMessage) {
                    noMatchMessage = document.createElement('tr');
                    noMatchMessage.id = 'no-match-message';
                    noMatchMessage.innerHTML = '<td colspan="4" style="text-align: center;">No match found.</td>';
                    tableBody.appendChild(noMatchMessage);
                }
                noMatchMessage.style.display = '';
            } else if (noMatchMessage) {
                noMatchMessage.style.display = 'none';
            }
        });

        // Function to apply date filter
        function applyDateFilter() {
            const dateFilter = document.querySelector('[name="dateFilter"]').value;
            const now = new Date();
            const rows = document.querySelectorAll('#logsTable tbody tr');

            rows.forEach(row => {
                const dateCell = row.cells[2]; // Adjust based on table structure
                if (!dateCell) return;

                const rowDate = new Date(dateCell.textContent.trim());
                let isVisible = true;

                if (dateFilter === 'week') {
                    const weekAgo = new Date();
                    weekAgo.setDate(now.getDate() - 7);
                    isVisible = rowDate >= weekAgo && rowDate <= now;
                } else if (dateFilter === 'month') {
                    const monthAgo = new Date();
                    monthAgo.setMonth(now.getMonth() - 1);
                    isVisible = rowDate >= monthAgo && rowDate <= now;
                } else if (dateFilter === 'year') {
                    const yearAgo = new Date();
                    yearAgo.setFullYear(now.getFullYear() - 1);
                    isVisible = rowDate >= yearAgo && rowDate <= now;
                }

                row.style.display = isVisible ? '' : 'none';
            });
        }

        function exportLogs(format) {
            if (format === 'csv') {
                const table = document.getElementById('logsTable');
                let csvContent = '';

                // Loop through each row of the table
                Array.from(table.rows).forEach(row => {
                    const rowData = Array.from(row.cells).map(cell => {
                        let cellContent = cell.textContent;

                        // Handle date formatting issue (if applicable)
                        // Convert to a string (you can customize the format)
                        const datePattern = /\d{4}-\d{2}-\d{2}/; // Basic YYYY-MM-DD pattern
                        if (datePattern.test(cellContent)) {
                            const date = new Date(cellContent);
                            // Convert the date to a readable string format (MM/DD/YYYY)
                            cellContent = date.toLocaleDateString('en-US');
                        }

                        // Escape content for CSV (wrap with quotes to prevent issues with commas, line breaks, etc.)
                        return `"${cellContent.replace(/"/g, '""')}"`; // Handle quotes inside text
                    }).join(',');

                    // Add the formatted row data to the CSV content
                    csvContent += rowData + '\n';
                });

                // Create a Blob and trigger download
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'activity_logs.csv';
                a.click();
                URL.revokeObjectURL(url);
            }
        }

        function printActivityLogs() {
            // Save the logs data to localStorage
            const logs = [];
            document.querySelectorAll('#logsTable tbody tr').forEach(row => {
                const logData = {
                    log_id: row.cells[0].textContent,
                    username: row.cells[1].textContent,
                    log_date: row.cells[2].textContent,
                    log_action: row.cells[3].textContent
                };
                logs.push(logData);
            });

            localStorage.setItem('activityLogs', JSON.stringify(logs)); // Save logs in localStorage

            // Open the activitylogs_print.html in a new window or tab
            window.open('activitylogs_print.html', '_blank');
        }


    </script>
</body>
</html>
