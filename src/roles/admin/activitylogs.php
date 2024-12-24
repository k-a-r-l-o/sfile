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
        ORDER BY l.log_date DESC
    ";

    // Apply date filter if set
    if (isset($_GET['dateFilter'])) {
        $filter = $_GET['dateFilter'];
        if ($filter === 'week') {
            $query .= " AND l.log_date >= CURDATE() - INTERVAL 1 WEEK";
        } elseif ($filter === 'month') {
            $query .= " AND l.log_date >= CURDATE() - INTERVAL 1 MONTH";
        } elseif ($filter === 'year') {
            $query .= " AND l.log_date >= CURDATE() - INTERVAL 1 YEAR";
        }
    }

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
                    <a href="signout.php">
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
                            <input type="text" id="main-search" placeholder="Search Log Id, Username...." oninput="filterLogs()">
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
                    <!-- <select id="action-filter" onchange="applyActionFilter()">
                        <option value="">All Actions</option>
                        <option value="Upload">Upload</option>
                        <option value="Download">Download</option>
                        <option value="Edit">Edit</option>
                        <option value="Delete">Delete</option>
                    </select> -->
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
                <table id="logsTable">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Username</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
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
            
             <!-- Export Buttons -->
            <div class="export-buttons">
                <button class="csv" onclick="exportLogs('csv')">Export as CSV</button>
                <button class="pdf" onclick="exportLogs('pdf')">Export as PDF</button>
            </div>
            
        </div>

    </div> <!-- Container end -->

    <script src="js/js.js"></script>

    <script>
    //   document.addEventListener("DOMContentLoaded", function() {
    //         fetch('activitylogs.php')
    //             .then(response => response.json())  // Parse JSON
    //             .then(data => {
    //                 const logsTableBody = document.getElementById('logs-table-body');
    //                 logsTableBody.innerHTML = '';  // Clear any existing rows

    //                 data.forEach(log => {
    //                     const row = document.createElement('tr');
    //                     row.innerHTML = `
    //                         <td>${log.log_id}</td>
    //                         <td>${log.username}</td>
    //                         <td>${new Date(log.log_date).toLocaleString()}</td>
    //                         <td>${log.log_action}</td>
    //                     `;
    //                     logsTableBody.appendChild(row);
    //                 });
    //             })
    //             .catch(err => console.error('Error fetching logs:', err));
    //     });

    //     // Function to filter logs by date range
    //     function applyDateFilter() {
    //         const filter = document.getElementById('date-filter').value;
    //         let url = 'activitylogs.php?dateFilter=' + filter;

    //         fetch(url)
    //             .then(response => response.json())
    //             .then(data => {
    //                 const logsTable = document.getElementById('logs-table-body');
    //                 logsTable.innerHTML = ''; // Clear existing rows

    //                 // Populate the table with filtered logs
    //                 data.forEach(log => {
    //                     const row = `<tr>
    //                         <td>${log.log_id}</td>
    //                         <td>${log.username}</td>
    //                         <td>${new Date(log.log_date).toLocaleString()}</td>
    //                         <td>${log.log_action}</td>
    //                     </tr>`;
    //                     logsTable.innerHTML += row;
    //                 });
    //             })
    //             .catch(err => console.error('Error filtering logs:', err));
    //     }
        function filterLogs() {
            const searchInput = document.getElementById('main-search').value.toLowerCase();
            const tableRows = document.querySelectorAll('#logs-table-body tr');
            let hasMatch = false;

            tableRows.forEach(row => {
                // Skip the "No match found" row if it exists
                if (row.id === 'no-match-message') return;

                // Check all cells in the row for a match
                const cells = Array.from(row.getElementsByTagName('td'));
                const matches = cells.some(cell => cell.textContent.toLowerCase().includes(searchInput));

                // Show/hide row based on match
                row.style.display = matches ? '' : 'none';

                if (matches) {
                    hasMatch = true;
                }
            });

            // Handle "No match found" message
            let noMatchMessage = document.getElementById('no-match-message');
            if (!hasMatch) {
                if (!noMatchMessage) {
                    // Create the message row if it doesn't exist
                    noMatchMessage = document.createElement('tr');
                    noMatchMessage.id = 'no-match-message';
                    noMatchMessage.innerHTML = '<td colspan="100%" style="text-align:center;">No match found.</td>';
                    document.getElementById('logs-table-body').appendChild(noMatchMessage);
                }
                noMatchMessage.style.display = ''; // Ensure the message is visible
            } else if (noMatchMessage) {
                noMatchMessage.style.display = 'none'; // Hide the message if rows match
            }
        }

        function applyActionFilter() {
            const actionFilter = document.getElementById('action-filter').value;
            const tableRows = document.querySelectorAll('#logs-table-body tr');

            tableRows.forEach(row => {
                const actionCell = row.getElementsByTagName('td')[2]; // Correct index to 2 for "Action"
                const matches = actionFilter === "" || actionCell.textContent.trim() === actionFilter;
                row.style.display = matches ? '' : 'none';
            });
        }

        function applyDateFilter() {
            const dateFilter = document.getElementById('date-filter').value;
            const now = new Date();
            const tableRows = document.querySelectorAll('#logs-table-body tr');

            tableRows.forEach(row => {
                const timestamp = new Date(row.getElementsByTagName('td')[4].textContent); // Correct index to 4 for "Timestamp"
                let matches = true;

                if (dateFilter === "week") {
                    const weekAgo = new Date();
                    weekAgo.setDate(now.getDate() - 7);
                    matches = timestamp >= weekAgo && timestamp <= now;
                } else if (dateFilter === "month") {
                    matches = timestamp.getMonth() === now.getMonth() && timestamp.getFullYear() === now.getFullYear();
                } else if (dateFilter === "year") {
                    matches = timestamp.getFullYear() === now.getFullYear();
                }

                row.style.display = matches ? '' : 'none';
            });
        }

        function exportLogs(format) {
            alert(`Exporting logs as ${format.toUpperCase()}...`);
        }

    </script>
</body>
</html>
