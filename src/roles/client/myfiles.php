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

            <div class="storage-indicator" id="storage-indicator">  
                <div class="storage-header">  
                    <span class="storage-icon"><i class="fas fa-hdd"></i></span>
                    <span class="storage-status">Storage (0% full)</span>  
                </div>  
                <div class="progress-bar">  
                    <div class="progress" style="width: 0%;"></div>  
                </div>  
                <span class="storage-usage">0.00 GB of 2 GB used</span><br>  
                <span class="file-count" id="file-count">Total Files: 0</span>   
            </div> 

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
                        <option value="default">Select to sort</option> 
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
                                <td>draft.docx</td>
                                <td>2024-12-05 09:15 AM</td>
                                <td>18 KB</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="action-btn" onclick="toggleDropdown(event)">
                                            <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                        </button>
                                        <div class="dropdown-menu">  
                                            <a href="#" onclick="showModal('View', 'draft.docx')">View</a>  
                                            <a href="#" onclick="showModal('Download', 'draft.docx')">Download</a>  
                                            <a href="#" onclick="showModal('Rename', 'draft.docx')">Rename</a>  
                                            <a href="#" onclick="showModal('Delete', 'draft.docx')">Delete</a>  
                                            <a href="#" onclick="showModal('Share', 'draft.docx')">Share</a> 
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>activitylogs.xls</td>
                                <td>2024-11-15 09:15 AM</td>
                                <td>1.8 MB</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="action-btn" onclick="toggleDropdown(event)">
                                            <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="showModal('View ', 'activitylogs.xls')">View</a>
                                            <a href="#" onclick="showModal('Download', 'activitylogs.xls')">Download</a>
                                            <a href="#" onclick="showModal('Rename', 'activitylogs.xls')">Rename</a>
                                            <a href="#" onclick="showModal('Delete', 'activitylogs.xls')">Delete</a>
                                            <a href="#" onclick="showModal('Share', 'activitylogs.xls')">Share</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>Case Study.pdf</td>
                                <td>2024-12-31 09:15 AM</td>
                                <td>80 MB</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="action-btn" onclick="toggleDropdown(event)">
                                            <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="showModal('View', 'Case Study.pdf')">View</a>
                                            <a href="#" onclick="showModal('Download', 'Case Study.pdf')">Download</a>
                                            <a href="#" onclick="showModal('Rename', 'Case Study.pdf')">Rename</a>
                                            <a href="#" onclick="showModal('Delete', 'Case Study.pdf')">Delete</a>
                                            <a href="#" onclick="showModal('Share', 'Case Study.pdf')">Share</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td>Debut-Shots.zip</td>
                                <td>2023-12-12 09:15 AM</td>
                                <td>1.5 GB</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="action-btn" onclick="toggleDropdown(event)">
                                            <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="#" onclick="showModal('View', 'Debut-Shots.zip')">View</a>
                                            <a href="#" onclick="showModal('Download', 'Debut-Shots.zip')">Download</a>
                                            <a href="#" onclick="showModal('Rename', 'Debut-Shots.zip')">Rename</a>
                                            <a href="#" onclick="showModal('Delete', 'Debut-Shots.zip')">Delete</a>
                                            <a href="#" onclick="showModal('Share', 'Debut-Shots.zip')">Share</a>
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

                <!-- Share Field -->  
                <div id="share-input" style="display: none;">  
                    <label for="share-field">Share with (email):</label>  
                    <input type="email" id="share-field" placeholder="Enter email address..." />  
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

            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');

            // Check if the row is the 3rd-to-last or beyond
            if (rowIndex >= rows.length - 1) {
                dropdown.style.top = '';  
                dropdown.style.bottom = '25px'; 
            } else {
                dropdown.style.top = '25px';  
                dropdown.style.bottom = '';  
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
            const shareInput = document.getElementById('share-input'); // New share input  
            const shareField = document.getElementById('share-field'); // New share field  
            const dropdownMenu = document.querySelector('.dropdown-menu');   

            modal.classList.add('show');  

            // Hide the dropdown menu when the modal is shown  
            if (dropdownMenu) {  
                dropdownMenu.style.display = 'none';   
            }  

            // Reset modal content and actions  
            modalTitle.innerText = `${action} Action`;  
            modalMessage.innerText = `Are you sure you want to ${action.toLowerCase()} the file: ${fileName}?`;  
            renameInput.style.display = 'none';   
            shareInput.style.display = 'none'; // Hide share input initially  

            // Handle specific actions  
            if (action === 'Rename') {  
                modalMessage.innerText = `Enter a new name for the file: ${fileName}`;  
                renameInput.style.display = 'block';   
                renameField.value = fileName;   
            } else if (action === 'Share') {  
                modalMessage.innerText = `Enter an email to share the file: ${fileName}`;  
                shareInput.style.display = 'block';   
                shareField.value = ''; // Clear the share field  
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
                    case 'Share':  
                        const email = shareField.value;  
                        alert(`File shared with ${email}`);  
                        break;  
                    default:  
                        alert('Action not defined!');  
                        break;  
                }  
                modal.classList.remove('show');   

                // Ensure the dropdown menu is visible again after modal closes  
                if (dropdownMenu) {  
                    dropdownMenu.style.display = 'block';   
                }  
            };  

            // Cancel button event  
            document.getElementById('cancel-btn').onclick = function() {  
                modal.classList.remove('show');   

                if (dropdownMenu) {  
                    dropdownMenu.style.display = 'block';   
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
            const fileItems = document.querySelectorAll('#files-table-body tr:not(#no-match-message)'); 
            let hasMatch = false;

            // Iterate through all file rows to check for matches
            fileItems.forEach(item => {
                const fileName = item.querySelector('td:nth-child(1)').textContent.toLowerCase(); 
                const matches = fileName.includes(searchInput);

                item.style.display = matches ? '' : 'none';

                if (matches) {
                    hasMatch = true;
                }
            });

            // Handle the "No match found" message
            let noMatchMessage = document.getElementById('no-match-message');
            if (!hasMatch) {
                if (!noMatchMessage) {
                    noMatchMessage = document.createElement('tr');
                    noMatchMessage.id = 'no-match-message';
                    noMatchMessage.innerHTML = '<td colspan="100%" style="text-align:center; font-style: italic;">No match found.</td>';
                    document.getElementById('files-table-body').appendChild(noMatchMessage);
                }
                noMatchMessage.style.display = ''; 
            } else {
                if (noMatchMessage) {
                    noMatchMessage.style.display = 'none';
                }
            }
        }

        function applySort() {
            const sortOption = document.getElementById('sort-options').value;

            if (sortOption === 'default') {
                return;
            }

            const rows = Array.from(document.querySelectorAll('.client-table tbody tr'));

            rows.sort((rowA, rowB) => {
                const filenameA = rowA.cells[0].textContent.toLowerCase();
                const filenameB = rowB.cells[0].textContent.toLowerCase();
                const filetypeA = getFileType(filenameA);
                const filetypeB = getFileType(filenameB);
                const filesizeA = convertFileSize(rowA.cells[2].textContent);
                const filesizeB = convertFileSize(rowB.cells[2].textContent);
                const dateA = parseDate(rowA.cells[1].textContent.trim());
                const dateB = parseDate(rowB.cells[1].textContent.trim());

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
                        return dateA - dateB; // Ascending order
                    case 'date-desc':
                        return dateB - dateA; // Descending order
                    default:
                        return 0;
                }
            });

            // Re-append sorted rows to the table body
            const tbody = document.querySelector('.client-table tbody');
            rows.forEach(row => tbody.appendChild(row));
        }

        // Helper function to convert file size to bytes
        function convertFileSize(sizeString) {
            const sizeValue = parseFloat(sizeString);
            const sizeUnit = sizeString.trim().slice(-2).toUpperCase(); 

            switch (sizeUnit) {
                case 'KB':
                    return sizeValue * 1024; 
                case 'MB':
                    return sizeValue * 1024 * 1024; 
                case 'GB':
                    return sizeValue * 1024 * 1024 * 1024; 
                default:
                    return 0; 
            }
        }

        // Helper function to extract file type from filename
        function getFileType(filename) {
            const parts = filename.split('.');
            return parts.length > 1 ? parts.pop().toLowerCase() : ''; 
        }

        // Helper function to parse date strings
        function parseDate(dateString) {
            return new Date(dateString.replace(/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}) (AM|PM)/, (match, year, month, day, hour, minute, period) => {
                hour = period === 'PM' && hour !== '12' ? parseInt(hour) + 12 : hour;
                hour = period === 'AM' && hour === '12' ? '00' : hour; 
                return `${year}-${month}-${day}T${hour}:${minute}:00`; 
            }));
        }

        function applyDateFilter() {
            const dateFilter = document.getElementById('date-filter').value;
            const fileItems = document.querySelectorAll('.client-table tbody tr');

            fileItems.forEach(item => {
                const dateText = item.querySelector('td:nth-child(2)').textContent.trim();
                const fileDate = new Date(dateText);  
                const currentDate = new Date(); 
                let matches = false;

                // Get the date range based on the selected filter
                switch (dateFilter) {
                    case 'week':
                        const oneWeekAgo = new Date(currentDate);
                        oneWeekAgo.setDate(currentDate.getDate() - 7);
                        matches = fileDate >= oneWeekAgo && fileDate <= currentDate;
                        break;
                    case 'month':
                        const startOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                        matches = fileDate >= startOfMonth && fileDate <= currentDate;
                        break;
                    case 'year':
                        const startOfYear = new Date(currentDate.getFullYear(), 0, 1);
                        matches = fileDate >= startOfYear && fileDate <= currentDate;
                        break;
                    case 'all':
                    default:
                        matches = true;
                        break;
                }

                // Show or hide the file row based on the date filter
                item.style.display = matches ? '' : 'none';
            });
        }

        function updateStorageIndicator() {
            const tableBody = document.getElementById('files-table-body'); 
            const rows = tableBody.getElementsByTagName('tr');
            let totalSize = 0;
            let fileCount = rows.length;

            // Loop through each row to calculate total file size
            for (let row of rows) {
                const sizeCell = row.cells[2].innerText; 
                const sizeValue = parseFloat(sizeCell); 
                const sizeUnit = sizeCell.slice(-2); 

                // Convert the file size to bytes
                let sizeInBytes;
                switch (sizeUnit) {
                    case 'KB':
                        sizeInBytes = sizeValue * 1024;
                        break;
                    case 'MB':
                        sizeInBytes = sizeValue * 1024 * 1024;
                        break;
                    case 'GB':
                        sizeInBytes = sizeValue * 1024 * 1024 * 1024;
                        break;
                    default:
                        sizeInBytes = 0;
                }

                totalSize += sizeInBytes; 
            }

            // Convert total size to appropriate unit (KB, MB, or GB)
            let totalSizeDisplay, totalSizeUnit;
            if (totalSize < 1024 * 1024) {
                totalSizeDisplay = (totalSize / 1024).toFixed(2);
                totalSizeUnit = 'KB';
            } else if (totalSize < 1024 * 1024 * 1024) {
                totalSizeDisplay = (totalSize / (1024 * 1024)).toFixed(2);
                totalSizeUnit = 'MB';
            } else {
                totalSizeDisplay = (totalSize / (1024 * 1024 * 1024)).toFixed(2);
                totalSizeUnit = 'GB';
            }

            // Total storage in bytes (2GB)
            const totalStorageInBytes = 2 * 1024 * 1024 * 1024; 
            const usedPercentage = (totalSize / totalStorageInBytes) * 100; 

            // Update the storage indicator text
            document.querySelector('.storage-status').innerText = `Storage (${usedPercentage.toFixed(0)}% full)`;
            document.querySelector('.storage-usage').innerText = `${totalSizeDisplay} ${totalSizeUnit} of 2 GB used`;
            document.getElementById('file-count').innerText = `Total Files: ${fileCount}`;
            
            // Update the progress bar width and color
            const progressBar = document.querySelector('.progress');
            progressBar.style.width = `${usedPercentage}%`;

            // Change progress bar color based on used percentage
            if (usedPercentage >= 90) {
                progressBar.style.backgroundColor = 'red'; 
            } else if (usedPercentage >= 75) {
                progressBar.style.backgroundColor = 'orange'; 
            } else {
                progressBar.style.backgroundColor = 'green'; 
            }

            // Change the storage icon to a warning icon if storage is 75% or more
            const storageIcon = document.querySelector('.storage-icon');
            if (usedPercentage >= 75) {
                storageIcon.innerHTML = '<span class="warning-icon">⚠️</span>'; 
            } else {
                storageIcon.innerHTML = '<i class="fas fa-hdd"></i>'; 
            }
        }

        // Call the function on page load
        window.onload = function() {
            updateStorageIndicator();
        };

    </script>

</body>
</html>
