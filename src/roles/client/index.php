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

                <li class="tab active">
                    <a href="../client/index.php">
                        <span class="icon">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                        </span>
                        <span class="title">Upload Files</span>
                    </a>
                </li>

                <li class="tab">
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
                            <input type="text" id="main-search" placeholder="Search filename..." oninput="filterFiles()">
                            <ion-icon name="search-outline"></ion-icon>
                        </label>
                    </div>
                </div>

                <div class="user">
                    <img src="../../assets/img/admin.png">
                </div>
            </div>

            <div class="name">
                <h1>Upload Files</h1>
            </div>

            <!-- Upload File Section -->
            <div class="upload-container">
                <div class="drag-area" id="drag-area">
                    <p>Drag & Drop files here or click to upload</p>
                    <input type="file" id="file-input" multiple style="display: none;">
                </div>
                
                <p id="file-count-indicator">0/10 Files</p>
                <ul id="file-list" class="file-list"></ul>
                <button id="upload-btn" class="upload-btn">Proceed to Upload</button>
            </div>

            <div class="name">
                <h4>Recent Uploaded Files</h4>
            </div>

            <!-- Table Displaying Uploaded Files -->
            <div class="client-table">
                <div class="client-table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>File Size</th>
                                <th>Upload Date</th>
                            </tr>
                        </thead>
                        <tbody id="uploaded-files-table">
                            <!-- Uploaded file rows will display here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal for alerts -->  
            <div id="alert-modal" class="alert-modal">  
                <div class="alert-modal-content">  
                    <span id="close-modal" class="close-modal">&times;</span>  
                    <div class="modal-icon">  
                        <i id="modal-icon" class="fas fa-info-circle"></i> <!-- Default icon -->  
                    </div>  
                    <h2 id="modal-title">Alert</h2> <!-- Title for the modal -->  
                    <p id="modal-message">This is a sample message.</p>  
                    <button class="alert-btn" id="alert-close-btn">Okay</button>  
                </div>  
            </div>


        </div> <!-- Main end -->

    </div> <!-- Container end -->

    <!-- =========== Scripts =========  -->
    <script src="js/client.js"></script>

    <script>
        function filterFiles() {
            const searchInput = document.getElementById('main-search').value.toLowerCase();
            const fileItems = document.querySelectorAll('#uploaded-files-table tr:not(#no-match-message)'); // Select file rows in the correct table
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
                    noMatchMessage.innerHTML = '<td colspan="3" style="text-align:center; font-style: italic;">No match found.</td>';
                    document.getElementById('uploaded-files-table').appendChild(noMatchMessage);
                }
                noMatchMessage.style.display = ''; // Ensure the message is visible
            } else {
                // If matches are found, hide or remove the "No match found" row
                if (noMatchMessage) {
                    noMatchMessage.style.display = 'none';
                }
            }
        }

        const dragArea = document.getElementById("drag-area");
        const fileInput = document.getElementById("file-input");
        const fileList = document.getElementById("file-list");
        const uploadButton = document.getElementById("upload-btn");
        const uploadedFilesTable = document.getElementById("uploaded-files-table");
        const fileCountIndicator = document.getElementById("file-count-indicator");
        const limitfile = 10;
        let uploadedFiles = [];
        let allFiles = new Set();

        // Function to create a progress bar
        function createProgressBar() {
            const progressBar = document.createElement("div");
            progressBar.className = "file-progress-bar";
            progressBar.innerHTML = `<div class="file-progress-fill"></div>`;
            return progressBar;
        }

        // Update the file count indicator
        function updateFileCountIndicator() {
            fileCountIndicator.textContent = `${uploadedFiles.length}/${limitfile} Files`;
        }

        // Drag & Drop Events
        dragArea.addEventListener("click", () => fileInput.click());
        dragArea.addEventListener("dragover", (e) => {
            e.preventDefault();
            dragArea.classList.add("highlight");
        });
        dragArea.addEventListener("dragleave", () => {
            dragArea.classList.remove("highlight");
        });
        dragArea.addEventListener("drop", (e) => {
            e.preventDefault();
            dragArea.classList.remove("highlight");
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        // Input File Event
        fileInput.addEventListener("change", () => {
            handleFiles(fileInput.files);
            fileInput.value = ""; // Reset input
        });

        
        // Function to show the modal
        function showModal(message, type = 'info') {  
            const modal = document.getElementById('alert-modal');  
            const modalMessage = document.getElementById('modal-message');  
            const modalTitle = document.getElementById('modal-title');  
            const modalIcon = document.getElementById('modal-icon');  

            modalMessage.textContent = message; // Set the message  

            // Set icon based on type  
            switch (type) {  
                case 'success':  
                    modalIcon.className = 'fas fa-check-circle'; // Success icon  
                    modalTitle.textContent = "Success!";  
                    break;  
                case 'error':  
                    modalIcon.className = 'fas fa-exclamation-circle'; // Error icon  
                    modalTitle.textContent = "Error!";  
                    break;  
                case 'info':  
                default:  
                    modalIcon.className = 'fas fa-info-circle'; // Info icon  
                    modalTitle.textContent = "Information";  
                    break;  
            }  

            modal.classList.add('show');  // Show the modal  

            const closeModal = document.getElementById('close-modal');  
            closeModal.onclick = () => modal.classList.remove('show');  

            // Close the modal if clicked outside  
            window.onclick = (event) => {  
                if (event.target === modal) {  
                    modal.classList.remove('show');  
                }  
            };  

            const alertCloseButton = document.getElementById('alert-close-btn');  
            alertCloseButton.onclick = () => modal.classList.remove('show');  
        }

        // Function to close the modal
        function closeModalAction(modal) {
            modal.style.opacity = 0; // Fade-out effect
            setTimeout(() => {
                modal.style.display = 'none'; // Hide the modal after the fade-out
            }, 300);
        }

        // Optional: Set auto-close behavior after a certain duration
        function showAutoCloseModal(message) {
            showModal(message);
            setTimeout(() => {
                const modal = document.getElementById('alert-modal');
                closeModalAction(modal);
            }, 3000); // Close after 3 seconds
        }

        // Simulate Progress Bar for Each File Upload
        async function simulateFileUpload(fileObj) {
            const progressBarFill = fileObj.progressBar.querySelector(".file-progress-fill");
            const fileSize = fileObj.file.size; // Get file size in bytes

            let uploadSpeed = 1; // Default upload speed (1% per interval)

            // Adjust upload speed based on file size
            if (fileSize < 1024 * 1024) { // Small file (KB)
                uploadSpeed = 10; // Faster upload speed for smaller files
            } else if (fileSize < 1024 * 1024 * 10) { // Medium file (less than 10MB)
                uploadSpeed = 5; // Moderate upload speed for medium-sized files
            } else { // Larger files (MB and GB)
                uploadSpeed = 2; // Slower upload speed for larger files
            }

            return new Promise((resolve) => {
                let progress = 0;
                const interval = setInterval(() => {
                    if (fileObj.canceled) {
                        clearInterval(interval);
                        progressBarFill.style.width = "0%"; // Reset progress bar
                        return;
                    }
                    progress += Math.random() * uploadSpeed; // Increment progress
                    progressBarFill.style.width = `${Math.min(progress, 100)}%`; // Update progress

                    if (progress >= 100) {
                        clearInterval(interval);
                        resolve();
                    }
                }, 300); // Simulate upload every 300ms
            });
        }

        // Function to handle file validation and preparation
        function handleFiles(files) {
            for (let file of files) {

                if (uploadedFiles.length + 1 > limitfile) {
                    showModal(`You can upload a maximum of ${limitfile} files.`);
                    return;
                }

                const listItem = document.createElement("li");
                listItem.className = "file-item";
                listItem.innerHTML = `
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${formatFileSize(file.size)}</span>
                    <div class="file-progress"></div>
                    <button class="remove-btn">X</button>
                `;

                const progressBar = createProgressBar();
                listItem.querySelector(".file-progress").appendChild(progressBar);

                const removeButton = listItem.querySelector(".remove-btn");
                removeButton.addEventListener("click", () => {
                    const fileObj = uploadedFiles.find((f) => f.file === file);
                    if (fileObj) {
                        fileObj.canceled = true;

                        // Abort the upload request if it's in progress
                        if (fileObj.controller) {
                            fileObj.controller.abort(); // Abort the fetch request if uploading
                        }

                        showModal(`Upload for ${file.name} has been canceled.`);
                        // Remove file from list if not uploading
                        fileList.removeChild(listItem);
                        uploadedFiles = uploadedFiles.filter((f) => f.file !== file);
                        allFiles.delete(file.name); // Remove from set
                        updateFileCountIndicator();
                    }
                });

                fileList.appendChild(listItem);
                uploadedFiles.push({ file, progressBar, active: true, uploading: false, canceled: false });
                updateFileCountIndicator();
            }
        }

        let uploadedFileNames = new Map(); 

        // Upload Files Button Event
        uploadButton.addEventListener("click", async () => {
            let successfulUploads = 0;

            if (uploadedFiles.length === 0) {
                showModal("Please select at least one file to upload.");
                return;
            }

            // Simulate file upload and progress bar completion
            for (const fileObj of [...uploadedFiles]) {
                if (!fileObj.active || fileObj.canceled) {
                    continue; // Skip inactive files
                }

                fileObj.uploading = true;
                
                try {
                    await simulateFileUpload(fileObj);

                    if (fileObj.canceled) {
                        console.log(`Upload for ${fileObj.file.name} was canceled.`);
                        continue; // Skip saving canceled files
                    }

                    const formData = new FormData();
                    formData.append('file', fileObj.file);

                    const response = await fetch('upload.php', {
                        method: 'POST',
                        body: formData,
                    });

                    const result = await response.json();
                    if (result.status === 'success') {
                        successfulUploads++;
                        showModal(successfulUploads + ' file(s) uploaded successfully!');
                        
                        // Remove file from the DOM and list
                        const listItem = fileObj.progressBar.closest(".file-item");
                        if (listItem) {
                            listItem.remove(); // Remove the file item from the DOM
                        }
                        uploadedFiles = uploadedFiles.filter((f) => f !== fileObj); // Remove from the list
                        allFiles.delete(fileObj.file.name); // Remove from the set
                        updateFileCountIndicator(); // Update file count indicator

                        // Handle duplicate filenames
                        let originalFileName = fileObj.file.name;
                        let fileName = originalFileName;
                        if (uploadedFileNames.has(originalFileName)) {
                            const count = uploadedFileNames.get(originalFileName) + 1;
                            uploadedFileNames.set(originalFileName, count);
                            const extIndex = fileName.lastIndexOf(".");
                            if (extIndex !== -1) {
                                const namePart = fileName.substring(0, extIndex);
                                const extPart = fileName.substring(extIndex);
                                fileName = `${namePart} (${count})${extPart}`;
                            } else {
                                fileName = `${fileName} (${count})`; // No extension case
                            }
                        } else {
                            uploadedFileNames.set(originalFileName, 0); // First occurrence
                        }
                        const finalFileName = result.fileName;

                        // Add the file(s) to the table
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${fileName}</td>
                            <td>${formatFileSize(fileObj.file.size)}</td> <!-- File size displayed as KB, MB, or GB -->
                            <td>${new Date().toLocaleString()}</td>     
                        `;
                        uploadedFilesTable.appendChild(row);
                    } else {
                        console.error('Error:', result.message);
                    }
                } catch (error) {
                    console.error('Upload failed:', error);
                }
            }

        });

        // Function to format file size in KB, MB, or GB
        function formatFileSize(sizeInBytes) {
            if (sizeInBytes < 1024) {
                return `${sizeInBytes} B`;
            } else if (sizeInBytes < 1048576) {
                return (sizeInBytes / 1024).toFixed(2) + " KB";
            } else if (sizeInBytes < 1073741824) {
                return (sizeInBytes / 1048576).toFixed(2) + " MB";
            } else {
                return (sizeInBytes / 1073741824).toFixed(2) + " GB";
            }
        }


    </script>
</body>

</html>
