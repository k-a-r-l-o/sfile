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
                            <input type="text" id="main-search" placeholder="Search filename...">
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

            <!-- Modal for alerts -->
            <div id="alert-modal" class="alert-modal">
                <div class="alert-modal-content">
                    <span id="close-modal" class="close-modal">&times;</span>
                    <p id="modal-message">This is a sample message.</p>
                    <button class="alert-btn" id="alert-close-btn">Okay</button>
                </div>
            </div>


        </div> <!-- Main end -->

    </div> <!-- Container end -->

    <!-- =========== Scripts =========  -->
    <script src="js/client.js"></script>

    <script>
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

        // Function to handle file validation and preparation
        function handleFiles(files) {
            for (let file of files) {
                if (allFiles.has(file.name)) {
                    showModal(`You have already selected the file: ${file.name}`);
                    return;
                }
                allFiles.add(file.name); // Add to the set to track already selected files

                if (uploadedFiles.length + 1 > limitfile) {
                    showModal(`You can upload a maximum of ${limitfile} files.`);
                    return;
                }

                const listItem = document.createElement("li");
                listItem.className = "file-item";
                listItem.innerHTML = `
                    <span>${file.name}</span>
                    <div class="file-progress"></div>
                    <button class="remove-btn">X</button>
                `;

                const progressBar = createProgressBar();
                listItem.querySelector(".file-progress").appendChild(progressBar);

                const removeButton = listItem.querySelector(".remove-btn");
                removeButton.addEventListener("click", () => {
                    fileList.removeChild(listItem);
                    uploadedFiles = uploadedFiles.filter((f) => f.file !== file);
                    allFiles.delete(file.name); // Remove from set
                    updateFileCountIndicator();
                });

                fileList.appendChild(listItem);
                uploadedFiles.push({ file, progressBar });
                updateFileCountIndicator();
            }
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

        // Simulate Progress Bar for Each File Upload
        async function simulateFileUpload(fileObj) {
            const progressBarFill = fileObj.progressBar.querySelector(".file-progress-fill");
            return new Promise((resolve) => {
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 20; // Increment progress randomly
                    progressBarFill.style.width = `${Math.min(progress, 100)}%`;

                    if (progress >= 100) {
                        clearInterval(interval);
                        resolve();
                    }
                }, 300);
            });
        }

        // Function to show the modal
        function showModal(message) {
            const modal = document.getElementById('alert-modal');
            const modalMessage = document.getElementById('modal-message');
            const modalTitle = document.getElementById('modal-title');
            
            modalMessage.textContent = message; // Set the message
            modal.classList.add('show');  // Add the 'show' class to trigger modal display

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

        // Upload Files Button Event
        uploadButton.addEventListener("click", async () => {
            if (uploadedFiles.length === 0) {
                showModal("Please select at least one file to upload.");
                return;
            }

            // Simulate file upload and progress bar completion
            for (const fileObj of uploadedFiles) {
                await simulateFileUpload(fileObj);
            }

            // Add uploaded file to the table
            uploadedFiles.forEach((fileObj) => {
                const row = document.createElement("tr");
                row.innerHTML = `<td>${fileObj.file.name}</td><td>${fileObj.file.size} bytes</td><td>${new Date().toLocaleString()}</td>`;
                uploadedFilesTable.appendChild(row);
            });

            // Remove files from the list and reset the selection
            fileList.innerHTML = "";
            uploadedFiles = [];
            allFiles.clear();
            updateFileCountIndicator();

            // Show success modal
            showModal("Files uploaded successfully!");
        });
    </script>
</body>

</html>
