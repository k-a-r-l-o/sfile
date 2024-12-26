<?php
// Include the configuration file
require_once __DIR__ . '/../../../config/config.php';

// Handle the POST request for adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['role'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert into tb_userdetails
        $stmt = $pdo->prepare("INSERT INTO tb_userdetails (user_fname, user_lname, user_email, user_role) 
                                VALUES (:fname, :lname, :email, :role)");

        // Bind parameters
        $stmt->bindParam(':fname', $_POST['fname']);
        $stmt->bindParam(':lname', $_POST['lname']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':role', $_POST['role']);

        // Execute the query
        $stmt->execute();

        // Fetch the inserted user ID
        $user_id = $pdo->lastInsertId();

        // Generate the username and password
        $username = $user_id . preg_replace("/[^a-zA-Z0-9]/", "", $_POST['fname']);
        $password = $user_id . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $_POST['fname']);

        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into tb_logindetails
        $stmt = $pdo->prepare("INSERT INTO tb_logindetails (username, password, user_id) 
                               VALUES (:username, :password, :user_id)");

        // Bind parameters for login details
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);

        // Execute the query
        $stmt->execute();

        $username = $_SESSION['username'];
        // Log the user addition
        $logStmt = $pdo->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
        $logStmt->execute([
            ':doer' => $username, // Replace with dynamic value if necessary
            ':action' => "Added user: {$_POST['fname']} {$_POST['lname']}"
        ]);

        // Return success response
        echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            // Return error response if the query fails
            echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
        }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editUserId'], $_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['role'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Update query including email
        $stmt = $pdo->prepare("UPDATE tb_userdetails 
                               SET user_fname = :fname, 
                                   user_lname = :lname, 
                                   user_email = :email, 
                                   user_role = :role
                               WHERE user_id = :id");

        // Bind parameters for update
        $stmt->bindParam(':id', $_POST['editUserId']);
        $stmt->bindParam(':fname', $_POST['fname']);
        $stmt->bindParam(':lname', $_POST['lname']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':role', $_POST['role']);

        // Execute the update query
        $stmt->execute();

        // Log the user update
        $logStmt = $pdo->prepare("INSERT INTO tb_logs (doer, log_action) VALUES (:doer, :action)");
        $logStmt->execute([
            ':doer' => 'Administrator',
            ':action' => "Edited user: {$_POST['fname']} {$_POST['lname']}, Email: {$_POST['email']}"
        ]);

        // Return success response
        echo json_encode(["success" => true]);

    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(["error" => $e->getMessage()]); // Return the detailed error for debugging
    }
    exit;
}

// Fetching users
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_users'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT user_id, user_fname, user_lname, user_email, user_role FROM tb_userdetails");
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($users);

        $pdo = null;
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());

        echo json_encode(["error" => "An error occurred while fetching users"]);
    }
    exit;
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
                    </a>
                </li>

                <li class="tab active">
                    <a href="index.php">
                        <span class="icon">
                            <ion-icon name="people-outline"></ion-icon>
                        </span>
                        <span class="title">Manage Users</span>
                    </a>
                </li>

                <li class="tab">
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
                            <input type="text" id="main-search" placeholder="Search User Id, Username....">
                            <ion-icon name="search-outline"></ion-icon>
                        </label>
                    </div>
                </div>

                <div class="user">
                    <img src="../../assets/img/admin.png">
                </div>
            </div>

            <div class="name">
                <h1>Manage Users</h1>
            </div>

            <div class="controls">
                <div class="filters">
                    <select id="action-filter" onchange="applyRoleFilter()">
                        <option value="">All</option>
                        <option value="Administrator">Administrator</option>
                        <option value="Head">Head</option>
                        <option value="Employee">Employee</option>
                    </select>
                </div>
            </div>

            <!-- ========================= Table ==================== -->
            <div class="user-table">
                <div class="user-table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email Address</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="user-list">
                            <!-- Users will be dynamically injected here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add User Button -->
            <button class="add-user-btn" onclick="openAddUserModal()">
                <i class="fas fa-plus"></i> Add User
            </button>

            <!-- Add User Modal -->
            <div id="addUserModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeAddUserModal()">&times;</span>
                    <h2>Add New User</h2>
                    <form id="addUserForm" onsubmit="addUser(event)">
                        <label for="fname">First Name: </label>
                        <input type="text" id="fname" name="fname" required> <!-- Fix name attribute -->
                        <label for="lname">Last Name: </label>
                        <input type="text" id="lname" name="lname" required> <!-- Fix name attribute -->
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required>
                        <label for="role">Role:</label>
                        <select id="role" name="role" required>
                            <option value="Administrator">Administrator</option>
                            <option value="Head">Head</option>
                            <option value="Employee">Employee</option>
                        </select>
                        <div class="modal-buttons">
                            <button type="submit">Add User</button>
                            <button type="button" onclick="closeAddUserModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeEditModal()">&times;</span>
                    <h2>Edit User</h2>
                    <form id="editUserForm" onsubmit="saveUserChanges(event)">
                        <label for="editUserId">User Id:</label>
                        <input type="text" id="editUserId" name="editUserId" readonly>

                        <div class="form-group">
                            <label for="fname">First Name:</label>
                            <input type="text" id="fname" name="fname" required>
                        </div>

                        <div class="form-group">
                            <label for="lname">Last Name:</label>
                            <input type="text" id="lname" name="lname" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="Administrator">Administrator</option>
                                <option value="Head">Head</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>

                        <div class="modal-buttons">
                            <button type="submit">Save Changes</button>
                            <button type="button" onclick="closeEditModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================= Delete User Confirmation Modal ==================== -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeDeleteModal()">&times;</span>
                    <div class="modal-header">
                        <ion-icon name="warning-outline" class="warning-icon"></ion-icon>
                    </div>
                    <h2>Are you sure?</h2>
                    <p>Do you really want to delete this record? This process cannot be undone.</p>
                    <div class="modal-buttons">
                        <button class="confirm-delete" onclick="confirmDelete()">Yes, Delete</button>
                        <button class="cancel-delete" onclick="closeDeleteModal()">Cancel</button>
                    </div>
                </div>
            </div>

        </div> <!-- Main end -->

    </div> <!-- Container end -->

    <!-- =========== Scripts =========  -->
    <script src="js/js.js"></script>

    <script>
        // Fetch and display users
        window.onload = function() {
            fetchUsers();
        };

        function fetchUsers() {
            fetch('?fetch_users=true') // AJAX request to get the users
                .then(response => response.json())
                .then(data => {
                    const userList = document.getElementById('user-list');
                    userList.innerHTML = ''; // Clear existing list

                    if (data.error) {
                        console.error(data.error);
                        alert('Error fetching data: ' + data.error);
                        return;
                    }

                    data.forEach(user => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${user.user_id}</td>
                            <td>${user.user_fname} ${user.user_lname}</td>
                            <td>${user.user_email}</td>
                            <td>${user.user_role}</td>
                            <td class="action-buttons">
                                <button 
                                    class="action-icon edit-icon" 
                                    data-id="${user.user_id}" 
                                    data-fname="${user.user_fname}" 
                                    data-lname="${user.user_lname}" 
                                    data-email="${user.user_email}" 
                                    data-role="${user.user_role}" 
                                    onclick="openEditModal(this)"
                                    aria-label="Edit user">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button 
                                    class="action-icon delete-icon" 
                                    data-id="${user.user_id}" 
                                    onclick="openDeleteModal(this)"
                                    aria-label="Delete user">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        userList.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error("Error fetching users:", error);
                    alert("An error occurred while fetching users. Please try again.");
                });
        }

        // Function to open the Add User Modal
        function openAddUserModal() {
            const addUserModal = document.getElementById('addUserModal');
            addUserModal.classList.add('show'); // Add the 'show' class to display the modal
        }

        // Function to close the Add User Modal
        function closeAddUserModal() {
            const addUserModal = document.getElementById('addUserModal');
            addUserModal.classList.remove('show'); // Remove the 'show' class to hide the modal
        }

        function addUser(event) {
            event.preventDefault(); // Prevent the form from reloading the page

            const formData = new FormData(document.getElementById('addUserForm'));

            fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User added successfully');
                        fetchUsers(); // Refresh the user list
                        closeAddUserModal(); // Close the modal
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error adding user:", error);
                    alert("An error occurred while adding the user.");
                });
        }

        function saveUserChanges(event) {
            event.preventDefault(); // Prevent form submission

            const formData = new FormData(document.getElementById('editUserForm'));

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User updated successfully');
                    fetchUsers(); // Refresh the user list
                    closeEditModal(); // Close the modal
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error("Error updating user:", error);
                alert("An error occurred while updating the user.");
            });
        }


        function openEditModal(button) {
            const userId = button.getAttribute('data-id');
            const fname = button.getAttribute('data-fname');
            const lname = button.getAttribute('data-lname');
            const email = button.getAttribute('data-email');
            const role = button.getAttribute('data-role');

            // Set the values in the modal form fields
            document.getElementById('editUserId').value = userId;
            document.getElementById('fname').value = fname;
            document.getElementById('lname').value = lname;
            document.getElementById('email').value = email;
            document.getElementById('role').value = role;

            // Show the modal
            const editUserModal = document.getElementById('editModal');
            editUserModal.classList.add('show');
        }

        // Function to close the Edit User Modal
        function closeEditModal() {
            const editModal = document.getElementById('editModal');
            editModal.classList.remove('show'); // Hide the modal
        }

        // Function to open the Delete User Confirmation Modal (with user ID from the clicked icon)
        function openDeleteModal(icon) {
            const deleteModal = document.getElementById('deleteModal');

            // Get user ID from the clicked icon's data attribute
            const userId = icon.getAttribute('data-id');
            // You can use the user ID here for delete confirmation or actions

            deleteModal.classList.add('show'); // Show the modal
        }

        // Function to close the Delete User Modal
        function closeDeleteModal() {
            const deleteModal = document.getElementById('deleteModal');
            deleteModal.classList.remove('show'); // Hide the modal
        }

        // Function to confirm delete action
        function confirmDelete() {
            // Handle the delete action here, for example, by calling an API or submitting a form
            console.log("User deleted!");
            closeDeleteModal(); // Close the modal after the delete action
        }

        // Event listener to close modals when clicking outside the modal content
        window.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                if (event.target === modal) {
                    modal.classList.remove('show'); // Close modal if clicked outside modal content
                }
            });
        });

        function applyRoleFilter() {
            const filterValue = document.getElementById('action-filter').value.toLowerCase(); // Get selected filter value
            const tableRows = document.querySelectorAll('.user-table tbody tr'); // Select all rows inside the table body

            tableRows.forEach(row => {
                const roleCell = row.querySelector('td:nth-child(4)'); // Get the cell in the 4th column (Role)
                const role = roleCell.textContent.trim().toLowerCase(); // Get the role text and normalize it

                // Show or hide rows based on the filter value
                if (filterValue === "" || role === filterValue) {
                    row.style.display = ""; // Show the row
                } else {
                    row.style.display = "none"; // Hide the row
                }
            });
        }

        document.getElementById('main-search').addEventListener('input', function () {
            const searchValue = this.value.toLowerCase().trim();
            const tableBody = document.querySelector('#user-list'); // Target the user list tbody
            const rows = tableBody.getElementsByTagName('tr');
            let hasMatch = false;

            Array.from(rows).forEach(row => {
                const rowText = Array.from(row.getElementsByTagName('td')).map(cell => cell.textContent.toLowerCase()).join(' ');

                if (rowText.includes(searchValue)) {
                    row.style.display = '';  // Show matching rows
                    hasMatch = true;
                } else {
                    row.style.display = 'none';  // Hide non-matching rows
                }
            });

            // Handle "No match found" message
            let noMatchMessage = document.getElementById('no-match-message');
            if (!hasMatch) {
                if (!noMatchMessage) {
                    noMatchMessage = document.createElement('tr');
                    noMatchMessage.id = 'no-match-message';
                    noMatchMessage.innerHTML = '<td colspan="5" style="text-align: center;">No match found.</td>'; // Updated colspan to 5 for the table
                    tableBody.appendChild(noMatchMessage);
                }
                noMatchMessage.style.display = ''; // Ensure the message is visible
            } else if (noMatchMessage) {
                noMatchMessage.style.display = 'none'; // Hide the message if there are matches
            }
        });

    </script>
</body>

</html>