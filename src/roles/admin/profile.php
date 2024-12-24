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

                <li class="tab">
                    <a href="activitylogs.php">
                        <span class="icon">
                            <ion-icon name="document-text-outline"></ion-icon>
                        </span>
                        <span class="title">Activity Logs</span>
                    </a>
                </li>

                <li class="tab active">
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
                </div>

                <div class="user">
                    <img src="../../assets/img/admin.png" id="userpic">
                </div>
            </div>

            <div class="name"> 
                <h1>Profile Settings</h1>
            </div>

            <!-- ========================= Profile Settings ==================== -->
            <div class="profile-settings">

                <!-- ========================= Edit Profile Picture ==================== -->
                <div class="edit-profile-picture">
                    <!-- <h3>Edit Profile Picture</h3> -->
                    <div class="profile-pic-container">
                        <img src="../../assets/img/admin.png" id="edit-profile-pic" alt="Profile Picture">
                        <label for="profile-pic-input" class="change-icon">
                            <ion-icon name="camera-outline"></ion-icon> Change Picture
                        </label>
                        <input type="file" id="profile-pic-input" name="profile-pic" accept="image/*" onchange="updateProfilePic(event)">
                    </div>
                </div>

                <!-- ========================= Profile Information ==================== -->
                <div class="profile-info">
                    <h3>Profile Information</h3>
                    <form id="personal-info-form" onsubmit="return validateAndSubmit(event)">
                        <!-- First Name and Last Name -->
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="first-name">First Name</label>
                                <input type="text" id="first-name" name="first-name" placeholder="Enter your first name" required>
                                <small class="error-message" id="first-name-error"></small>
                            </div>
                            <div class="form-group">
                                <label for="last-name">Last Name</label>
                                <input type="text" id="last-name" name="last-name" placeholder="Enter your last name" required>
                                <small class="error-message" id="last-name-error"></small>
                            </div>
                        </div>

                        <!-- Contact Number and Birthday -->
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="contact-number">Contact Number</label>
                                <input type="tel" id="contact-number" name="contact-number" placeholder="Enter your contact number" required>
                                <small class="error-message" id="contact-number-error"></small>
                            </div>
                            <div class="form-group">
                                <label for="birthday">Birthday:</label>
                                <input type="date" id="birthday" name="birthday">
                                <div id="birthday-error" class="error-message"></div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                            <small class="error-message" id="email-error"></small>
                        </div>
                    </form>
                </div>

                <!-- ========================= Reset Password ==================== -->
                <div class="form-group">
                <div class="change-password">
                <label for="password">Change Password: </label>
                    <!-- Reset Password Button -->
                    <form action="reset_password.php" method="POST">
                        <button type="submit" name="reset-password-btn" class="reset-password-btn">Reset Password</button>
                    </form>
                </div>
                </div>


                <!-- ========================= Save and Cancel Buttons ==================== -->
                <div class="form-buttons">
                    <button type="button" class="btn save-btn" onclick="saveChanges()">Save Changes</button>
                    <button type="button" class="btn cancel-btn" onclick="cancelEdit()">Cancel</button>
                </div>
                
            </div> <!-- Profile Settings End -->

            <div id="message-modal" class="modal-settings">
                <div class="modal-settings-content">
                    <span class="close" id="close-modal">&times;</span>
                    <p id="modal-message"></p>
                    <button id="modal-ok-button">OK</button>
                </div>
            </div>

        </div> <!-- Main end -->

    </div> <!-- Container end -->

    <!-- =========== Scripts =========  -->
    <script src="js/js.js"></script>

    <script>
        // Update Profile Picture function
        function updateProfilePic(event) {
            const profilePic = event.target.files[0];
            if (profilePic) {
                const newPicUrl = URL.createObjectURL(profilePic);
                document.getElementById('userpic').src = newPicUrl;
                document.getElementById('edit-profile-pic').src = newPicUrl;
                showModal("Profile picture updated successfully!");
            }
            }

            // Update Personal Information function
            function updatePersonalInfo(event) {
            event.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const birthday = document.getElementById('birthday').value;
            const contact = document.getElementById('contact').value;
            const role = document.getElementById('role').value;

            showModal("Profile updated successfully!");
            console.log("Updated Profile:", { name, email, birthday, contact, role });
            }

            // Save Changes function
            function saveChanges() {
            const profileIsValid = validateAndSubmit(new Event("submit"));
            const passwordIsValid = validatePassword(new Event("submit"));

            if (profileIsValid && passwordIsValid) {
                showModal("Changes saved successfully!");
            }
            }

            // Cancel editing function
            function cancelEdit() {
            document.getElementById("personal-info-form").reset();
            document.getElementById("password-form").reset();
            showModal("Edit cancelled!");
        }


        function validateAndSubmit(event) {
            event.preventDefault();

            // Validation flags
            let isValid = true;

            // First Name Validation
            const firstName = document.getElementById("first-name");
            const firstNameError = document.getElementById("first-name-error");
            if (!/^[a-zA-Z]+$/.test(firstName.value)) {
                firstNameError.textContent = "First name must contain only letters.";
                firstNameError.style.display = "block";
                isValid = false;
            } else {
                firstNameError.style.display = "none";
            }

            // Last Name Validation
            const lastName = document.getElementById("last-name");
            const lastNameError = document.getElementById("last-name-error");
            if (!/^[a-zA-Z]+$/.test(lastName.value)) {
                lastNameError.textContent = "Last name must contain only letters.";
                lastNameError.style.display = "block";
                isValid = false;
            } else {
                lastNameError.style.display = "none";
            }

            // Contact Number Validation
            const contactNumber = document.getElementById("contact-number");
            const contactNumberError = document.getElementById("contact-number-error");
            if (!/^\d+$/.test(contactNumber.value)) {
                contactNumberError.textContent = "Contact number must contain only numbers.";
                contactNumberError.style.display = "block";
                isValid = false;
            } else {
                contactNumberError.style.display = "none";
            }

            // Email Validation
            const email = document.getElementById("email");
            const emailError = document.getElementById("email-error");
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                emailError.textContent = "Enter a valid email address.";
                emailError.style.display = "block";
                isValid = false;
            } else {
                emailError.style.display = "none";
            }

            // Birthday Validation (Check if age is 18+)
            const birthday = document.getElementById("birthday");
            const birthdayError = document.getElementById("birthday-error");
            const today = new Date();
            const birthDate = new Date(birthday.value);
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDifference = today.getMonth() - birthDate.getMonth();

            // Adjust age if the current month/day is before the birth month/day
            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 18) {
                birthdayError.textContent = "You must be at least 18 years old.";
                birthdayError.style.display = "block";
                isValid = false;
            } else {
                birthdayError.style.display = "none";
            }

            return isValid;
        }

        //Password Validation with Specification
        function validatePassword(event) {
            event.preventDefault();

            const password = document.getElementById("new-password");
            const confirmPassword = document.getElementById("confirm-password");
            const passwordError = document.getElementById("password-error");
            const confirmPasswordError = document.getElementById("confirm-password-error");

            const passwordCriteria = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;

            if (!passwordCriteria.test(password.value)) {
                passwordError.textContent = "Password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
                passwordError.style.display = "block";
                return false;
            } else {
                passwordError.style.display = "none";
            }

            if (password.value !== confirmPassword.value) {
                confirmPasswordError.textContent = "Passwords do not match.";
                confirmPasswordError.style.display = "block";
                return false;
            } else {
                confirmPasswordError.style.display = "none";
            }

            return true;
        }

        //modal for when save changes or cancel edit
        function showModal(message) {
            const modal = document.getElementById("message-modal");
            const modalMessage = document.getElementById("modal-message");
            const modalOkButton = document.getElementById("modal-ok-button");

            modalMessage.textContent = message;
            modal.style.display = "block";

            // Close modal when clicking the close button or OK button
            document.getElementById("close-modal").onclick = () => {
                modal.style.display = "none";
            };

            modalOkButton.onclick = () => {
                modal.style.display = "none";
            };

            // Close the modal when clicking outside it
            window.onclick = (event) => {
                if (event.target === modal) {
                modal.style.display = "none";
                }
            };
        }
    </script>

</body>
</html>

