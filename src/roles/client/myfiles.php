<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/client.css">
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
                            <span class="secure">Secure</span><span class="file">File</span>
                        </span>
                    </a>
                </li>

                <li class="tab">
                    <a href="../client/index.php">
                        <span class="icon">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                        </span>
                        <span class="title">Upload Files</span>
                    </a>
                </li>

                <li class="tab active">
                    <a href="../client/myfiles.php">
                        <span class="icon">
                            <ion-icon name="folder-outline"></ion-icon>
                        </span>
                        <span class="title">My Files</span>
                    </a>
                </li>

                <li class="tab">
                    <a href="../client/profileclient.php">
                        <span class="icon">
                            <ion-icon name="settings-outline"></ion-icon>
                        </span>
                        <span class="title">Profile Settings</span>
                    </a>
                </li>

                <li class="tab">
                    <a href="/SECUREFILE/securefile/signout.php">
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
                            <input type="text" id="main-search" placeholder="Search Filename..." oninput="filterFiles()">
                            <ion-icon name="search-outline"></ion-icon>
                        </label>
                    </div>
                </div>

                <div class="user">
                    <img src="../../assets/img/admin.png" alt="User Image">
                </div>
            </div>

            <div class="name"> 
                <h1>My Files</h1>
            </div>

            <!-- Controls section -->
            <div class="controls">
                <div class="filters">
                    <select id="sort-options" onchange="applySort()">
                        <option value="default">Select to sort</option> <!-- Default option with no effect -->
                        <option value="filename-asc">Filename (A-Z)</option>
                        <option value="filename-desc">Filename (Z-A)</option>
                        <option value="filetype-asc">File Type (A-Z)</option>
                        <option value="filetype-desc">File Type (Z-A)</option>
                        <option value="filesize-asc">File Size (Smallest)</option>
                        <option value="filesize-desc">File Size (Largest)</option>
                        <option value="date-asc">Date (Oldest)</option>
                        <option value="date-desc">Date (Newest)</option>
                    </select> 
                    <select id="date-filter" onchange="applyDateFilter()">
                        <option value="all">All Time</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
            </div>
            
            <!-- ========================= File List ==================== -->
            <div class="client-table">
                <div class="client-table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Date & Time Uploaded</th>
                                <th>File Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="files-table-body">
                            <tr>
                                <td>data.csv</td>
                                <td>2024-12-10 11:00 AM</td>
                                <td>16 KB</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="action-btn" onclick="toggleDropdown(event)">
                                            <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="alert('View clicked')">View</a>
                                            <a href="#" onclick="alert('Download clicked')">Download</a>
                                            <a href="#" onclick="alert('Rename clicked')">Rename</a>
                                            <a href="#" onclick="alert('Delete clicked')">Delete</a>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <td>report.pdf</td>
                                <td>2024-12-12 10:30 AM</td>
                                <td>146 KB</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="action-btn" onclick="toggleDropdown(event)">
                                            <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="alert('View clicked')">View</a>
                                            <a href="#" onclick="alert('Download clicked')">Download</a>
                                            <a href="#" onclick="alert('Rename clicked')">Rename</a>
                                            <a href="#" onclick="alert('Delete clicked')">Delete</a>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <td>draft.docx</td>
                                <td>2024-12-05 09:15 AM</td>
                                <td>18 KB</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="action-btn" onclick="toggleDropdown(event)">
                                            <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="alert('View clicked')">View</a>
                                            <a href="#" onclick="alert('Download clicked')">Download</a>
                                            <a href="#" onclick="alert('Rename clicked')">Rename</a>
                                            <a href="#" onclick="alert('Delete clicked')">Delete</a>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Enhanced Modal Structure -->
        <div id="action-modal" class="modal">
            <div class="modal-content">
                <h3 id="modal-title">Action</h3>
                <p id="modal-message">Are you sure you want to proceed?</p>

                <!-- Rename Field -->
                <div id="rename-input" style="display: none;">
                    <label for="rename-field">New Filename:</label>
                    <input type="text" id="rename-field" placeholder="Enter new filename..." />
                </div>

                <!-- Modal Buttons -->
                <div class="modal-buttons">
                    <button id="confirm-btn" class="btn confirm">Confirm</button>
                    <button id="cancel-btn" class="btn cancel">Cancel</button>
                </div>
            </div>
        </div>

    </div> <!-- Container end -->

    <script src="js/client.js"></script>

    <script>
        function toggleDropdown(event) {
            const dropdown = event.target.closest('.action-menu').querySelector('.dropdown-menu');
            const table = event.target.closest('table');
            const rows = table.querySelectorAll('tr');
            const row = event.target.closest('tr');
            const rowIndex = Array.from(rows).indexOf(row);

            // Close all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');

            // Check if the row is the 3rd-to-last or beyond
            if (rowIndex >= rows.length - 1) {
                dropdown.style.top = '';  // Reset any previous styles
                dropdown.style.bottom = '25px';  // Position it above
            } else {
                dropdown.style.top = '25px';  // Default position below
                dropdown.style.bottom = '';  // Reset bottom
            }

            // Toggle the current dropdown
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
        }

        // Show the modal with the action details
        function showModal(action, fileName) {
            const modal = document.getElementById('action-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            const renameInput = document.getElementById('rename-input');
            const renameField = document.getElementById('rename-field');
            const dropdownMenu = document.querySelector('.dropdown-menu'); // Get the dropdown menu

            modal.classList.add('show'); // Show modal with fade-in effect

            // Hide the dropdown menu when the modal is shown
            if (dropdownMenu) {
                dropdownMenu.style.display = 'none'; // Hide the dropdown menu
            }

            // Reset modal content and actions
            modalTitle.innerText = `${action} Action`;
            modalMessage.innerText = `Are you sure you want to ${action.toLowerCase()} the file: ${fileName}?`;
            renameInput.style.display = 'none'; // Hide rename input by default

            // Handle specific actions
            if (action === 'Rename') {
                modalMessage.innerText = `Enter a new name for the file: ${fileName}`;
                renameInput.style.display = 'block'; // Show the rename input field
                renameField.value = fileName; // Pre-fill the current file name
            }

            // Action when Confirm is clicked
            document.getElementById('confirm-btn').onclick = function() {
                switch (action) {
                    case 'View':
                        window.open(`path/to/files/${fileName}`, '_blank');
                        break;
                    case 'Download':
                        window.location.href = `path/to/files/${fileName}?download=true`;
                        break;
                    case 'Rename':
                        const newFileName = renameField.value;
                        alert(`File renamed to ${newFileName}`);
                        break;
                    case 'Delete':
                        alert(`File ${fileName} deleted!`);
                        break;
                    default:
                        alert('Action not defined!');
                        break;
                }
                modal.classList.remove('show'); // Hide modal after action

                // Ensure the dropdown menu is visible again after modal closes
                if (dropdownMenu) {
                    dropdownMenu.style.display = 'block'; // Show the dropdown menu
                }
            };

            // Cancel button event
            document.getElementById('cancel-btn').onclick = function() {
                modal.classList.remove('show'); // Close the modal with fade-out effect

                // Ensure the dropdown menu is visible again after modal closes
                if (dropdownMenu) {
                    dropdownMenu.style.display = 'block'; // Show the dropdown menu
                }
            };
        }

        // Update the action links to open the modal
        function updateActionLinks() {
            const actionLinks = document.querySelectorAll('.dropdown-menu a');
            actionLinks.forEach(link => {
                link.onclick = function(event) {
                    event.preventDefault();
                    const action = link.innerText;
                    const fileName = link.closest('tr').querySelector('td').innerText;
                    showModal(action, fileName);
                };
            });
        }

        // Close dropdown if clicked outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.action-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
            }
        });
        // Ensure the action links are updated after page load
        window.onload = updateActionLinks;


        function filterFiles() {
            const searchInput = document.getElementById('main-search').value.toLowerCase();
            const fileItems = document.querySelectorAll('#files-table-body tr:not(#no-match-message)'); // Exclude the "No match found" row
            let hasMatch = false;

            // Iterate through all file rows to check for matches
            fileItems.forEach(item => {
                const fileName = item.querySelector('td:nth-child(1)').textContent.toLowerCase(); // Filename column
                const matches = fileName.includes(searchInput);

                // Show or hide the row based on the match
                item.style.display = matches ? '' : 'none';

                // If any row matches, set hasMatch to true
                if (matches) {
                    hasMatch = true;
                }
            });

            // Handle the "No match found" message
            let noMatchMessage = document.getElementById('no-match-message');
            if (!hasMatch) {
                // If no matches, ensure the "No match found" row exists
                if (!noMatchMessage) {
                    noMatchMessage = document.createElement('tr');
                    noMatchMessage.id = 'no-match-message';
                    noMatchMessage.innerHTML = '<td colspan="100%" style="text-align:center; font-style: italic;">No match found.</td>';
                    document.getElementById('files-table-body').appendChild(noMatchMessage);
                }
                noMatchMessage.style.display = ''; // Ensure the message is visible
            } else {
                // If matches are found, hide or remove the "No match found" row
                if (noMatchMessage) {
                    noMatchMessage.style.display = 'none';
                }
            }
        }

        function applySort() {
            const sortOption = document.getElementById('sort-options').value;
            
            // If the default option is selected, do nothing
            if (sortOption === 'default') {
                return;
            }

            const rows = Array.from(document.querySelectorAll('.client-table tbody tr'));

            rows.sort((rowA, rowB) => {
                // Get relevant columns for sorting
                const filenameA = rowA.cells[0].textContent.toLowerCase();
                const filenameB = rowB.cells[0].textContent.toLowerCase();
                const filetypeA = filenameA.split('.').pop(); // Extract file extension
                const filetypeB = filenameB.split('.').pop();
                const filesizeA = parseInt(rowA.cells[2].textContent.replace(/[^0-9]/g, ''), 10); // Extract numeric value from size
                const filesizeB = parseInt(rowB.cells[2].textContent.replace(/[^0-9]/g, ''), 10);
                const dateA = new Date(rowA.cells[1].textContent.trim());
                const dateB = new Date(rowB.cells[1].textContent.trim());

                // Sort based on selected criteria
                switch (sortOption) {
                    case 'filename-asc':
                        return filenameA.localeCompare(filenameB);
                    case 'filename-desc':
                        return filenameB.localeCompare(filenameA);
                    case 'filetype-asc':
                        return filetypeA.localeCompare(filetypeB);
                    case 'filetype-desc':
                        return filetypeB.localeCompare(filetypeA);
                    case 'filesize-asc':
                        return filesizeA - filesizeB;
                    case 'filesize-desc':
                        return filesizeB - filesizeA;
                    case 'date-asc':
                        return dateA - dateB;
                    case 'date-desc':
                        return dateB - dateA;
                    default:
                        return 0;
                }
            });

            // Re-append sorted rows to the table body
            const tbody = document.querySelector('.client-table tbody');
            rows.forEach(row => tbody.appendChild(row));
        }

        function applyDateFilter() {
            const dateFilter = document.getElementById('date-filter').value;
            const fileItems = document.querySelectorAll('.client-table tbody tr');

            fileItems.forEach(item => {
                const dateText = item.querySelector('td:nth-child(2)').textContent.trim();
                const fileDate = new Date(dateText);  // Convert the date string to a Date object
                const currentDate = new Date(); // Get the current date
                let matches = false;

                // Get the date range based on the selected filter
                switch (dateFilter) {
                    case 'week':
                        // Check if the file's date is within the last 7 days
                        const oneWeekAgo = new Date(currentDate);
                        oneWeekAgo.setDate(currentDate.getDate() - 7);
                        matches = fileDate >= oneWeekAgo && fileDate <= currentDate;
                        break;
                    case 'month':
                        // Check if the file's date is within the current month
                        const startOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                        matches = fileDate >= startOfMonth && fileDate <= currentDate;
                        break;
                    case 'year':
                        // Check if the file's date is within the current year
                        const startOfYear = new Date(currentDate.getFullYear(), 0, 1);
                        matches = fileDate >= startOfYear && fileDate <= currentDate;
                        break;
                    case 'all':
                    default:
                        // Show all files if 'All Time' is selected
                        matches = true;
                        break;
                }

                // Show or hide the file row based on the date filter
                item.style.display = matches ? '' : 'none';
            });
        }


    </script>

</body>
</html>
