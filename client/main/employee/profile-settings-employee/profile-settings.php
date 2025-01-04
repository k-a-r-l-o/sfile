<?php
session_start();
require_once '../../../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user details
    $query = "SELECT user_id, user_fname, user_lname, user_email, user_role FROM tb_client_userdetails WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: profile-settings?error=user_not_found");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching user details: " . $e->getMessage());
    header("Location: profile-settings?error=database_error");
    exit;
}

// Handle form submission for updating first name and last name
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);

    if (empty($firstname) || empty($lastname)) {
        header("Location: profile-settings?error=empty_fields");
        exit;
    }

    try {
        // Update first name and last name
        $updateQuery = "UPDATE tb_client_userdetails SET user_fname = :firstname, user_lname = :lastname WHERE user_id = :user_id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
        $updateStmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
        $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $updateStmt->execute();

        header("Location: profile-settings?success=profile_updated");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating user details: " . $e->getMessage());
        header("Location: profile-settings?error=database_error");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SecureFile</title>
    <link
      rel="shortcut icon"
      type="image/png"
      href="../../../../assets/img/logo.png"
    />
    <link rel="stylesheet" href="../../../../assets/css/styles.min.css" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/tabler-icons@1.30.1/dist/tabler-icons.min.css"
    />
  </head>

  <body>
    <!--  Body Wrapper -->
    <div
      class="page-wrapper"
      id="main-wrapper"
      data-layout="vertical"
      data-navbarbg="skin6"
      data-sidebartype="full"
      data-sidebar-position="fixed"
      data-header-position="fixed"
    >
      <!-- Sidebar Start -->
      <aside class="left-sidebar">
        <!-- Sidebar scroll-->
        <div>
          <div
            class="brand-logo d-flex align-items-center justify-content-between"
          >
            <a href="../index.html" class="text-nowrap logo-img">
              <img src="../../../../assets/img/light-logo-employee.svg" alt="" />
            </a>
            <div
              class="close-btn d-xl-none d-block sidebartoggler cursor-pointer"
              id="sidebarCollapse"
            >
              <i class="ti ti-x fs-8"></i>
            </div>
          </div>
          <!-- Sidebar navigation-->
          <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
            <ul id="sidebarnav">
              <li class="nav-small-cap">
                <i class="ti ti-dots nav-small-cap-icon fs-6"></i>
                <span class="hide-menu">Home</span>
              </li>
              <li class="sidebar-item">
                <a class="sidebar-link" href="../" aria-expanded="false">
                  <span>
                    <iconify-icon
                      icon="solar:home-smile-bold-duotone"
                      class="fs-6"
                    ></iconify-icon>
                  </span>
                  <span class="hide-menu">Dashboard</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a class="sidebar-link" href="../upload-files" aria-expanded="false">
                  <span>
                    <iconify-icon
                      icon="solar:upload-minimalistic-bold-duotone"
                      class="fs-6"
                    ></iconify-icon>
                  </span>
                  <span class="hide-menu">Upload Files</span>
                </a>
              </li>
              <li class="sidebar-item">
                <a class="sidebar-link" href="../my-files" aria-expanded="false">
                  <span>
                    <iconify-icon
                      icon="solar:layers-minimalistic-bold-duotone"
                      class="fs-6"
                    ></iconify-icon>
                  </span>
                  <span class="hide-menu">My Files</span>
                </a>
              </li>
            </ul>
          </nav>
          <!-- End Sidebar navigation -->
        </div>
        <!-- End Sidebar scroll-->
      </aside>
      <!--  Sidebar End -->
      <!--  Main wrapper -->
      <div class="body-wrapper">
        <!--  Header Start -->
        <header class="app-header">
          <nav class="navbar navbar-expand-lg navbar-light">
            <ul class="navbar-nav">
              <li class="nav-item d-block d-xl-none">
                <a
                  class="nav-link sidebartoggler nav-icon-hover"
                  id="headerCollapse"
                  href="javascript:void(0)"
                >
                  <i class="ti ti-menu-2"></i>
                </a>
              </li>
            </ul>
            <div
              class="navbar-collapse justify-content-end px-0"
              id="navbarNav"
            >
              <ul
                class="navbar-nav flex-row ms-auto align-items-center justify-content-end"
              >
                <li class="nav-item dropdown">
                  <a
                    class="nav-link nav-icon-hover"
                    href="javascript:void(0)"
                    id="drop2"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                  >
                    <img
                      src="../../../../assets/images/profile/user-1.jpg"
                      alt=""
                      width="35"
                      height="35"
                      class="rounded-circle"
                    />
                  </a>
                  <div
                    class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up"
                    aria-labelledby="drop2"
                  >
                    <div class="message-body">
                      <a
                        href="../profile-settings-employee/profile.html"
                        class="d-flex align-items-center gap-2 dropdown-item"
                      >
                        <i class="ti ti-user fs-6"></i>
                        <p class="mb-0 fs-3">My Profile</p>
                      </a>
                      <a
                        href="../../../session/signout.php"
                        class="btn btn-outline-primary mx-3 mt-2 d-block"
                        >Logout</a
                      >
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </nav>
        </header>
        <!--  Header End -->
        <div class="container-fluid">
          <div class="row">
              <h3 class="col-lg-8">Profile Settings</h3>
      
              <div class="col-lg-12">  
                  <div class="card">  
                      <div class="card-body">  
                          <!-- Tabs for Profile and Password as links -->  
                          <div class="d-flex mb-4">  
                              <a href="../profile-settings-employee/profile.html" class="tab-button active" id="profileTab">Personal Information</a>  
                              <a href="../profile-settings-employee/password.html" class="tab-button" id="passwordTab">Password</a>  
                          </div>  
                
                          <!-- Profile Content -->  
                          <div id="profileContent" class="tab-content">  
                          <form id="profileForm" action="profile-settings.php" method="POST">
                              <div class="mb-3">  
                                  <label for="user_id" class="form-label">User ID </label>  
                                  <input type="text" class="form-control" name="user_id" id="user_id" value="<?= htmlspecialchars($user['user_id']) ?>" readonly />  
                              </div>
                              <div class="mb-3">  
                                  <label for="email" class="form-label">Email </label>  
                                  <input type="text" class="form-control" name="email" id="email" value="<?= htmlspecialchars($user['user_email']) ?>" readonly />  
                              </div>  
                              <div class="mb-3">  
                                  <label for="firstname" class="form-label">First Name <i>(can be changed)</i></label>  
                                  <input type="text" class="form-control" name="firstname" id="firstname" value="<?= htmlspecialchars($user['user_fname']) ?>" required />  
                              </div>  
                              <div class="mb-3">  
                                  <label for="lastname" class="form-label">Last Name <i>(can be changed)</i></label>  
                                  <input type="text" class="form-control" name="lastname" id="lastname" value="<?= htmlspecialchars($user['user_lname']) ?>" required />  
                              </div>   
                              <div class="mb-3">
                                  <label for="role" class="form-label">Role</label>
                                  <input type="text" class="form-control" name="role" id="role" value="<?= htmlspecialchars($user['user_role']) ?>" readonly />
                              </div>                                
                              <button type="submit" class="btn btn-primary" id="updateProfileButton">Update Profile</button>  
                          </form>
                          </div>  
                      </div>  
                  </div>  
              </div>
          </div>
      </div>
      
      <!-- Modal -->
      <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="updateProfileModalLabel">Confirm Update</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                      <p>Are you sure you want to update your profile with the following changes?</p>  
                      <p><strong>First Name:</strong> <span id="clientFirstName"></span></p>  
                      <p><strong>Last Name:</strong> <span id="clientLastName"></span></p> 
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                      <button type="button" class="btn btn-primary" id="confirmUpdateClient">Confirm</button>
                  </div>
              </div>
          </div>
      </div>
      
      <!-- Include necessary scripts -->
      <script src="../../../../assets/libs/jquery/dist/jquery.min.js"></script>
      <script src="../../../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
      <script src="../../../../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
      <script src="../../../../assets/libs/simplebar/dist/simplebar.js"></script>
      <script src="../../../../assets/js/sidebarmenu.js"></script>
      <script src="../../../../assets/js/app.min.js"></script>
      <script src="../../../../assets/js/dashboard.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
      
      <script>
document.addEventListener('DOMContentLoaded', function () {
    // Extract query parameters
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get("id");
    const email = urlParams.get("email");
    const firstname = urlParams.get("fname");
    const lastname = urlParams.get("lname");
    const role = urlParams.get("role");

    // Check if all parameters exist
    if (id && email && firstname && lastname && role) {
        // Populate input fields
        document.getElementById("user_id").value = id;
        document.getElementById("email").value = email;
        document.getElementById("firstname").value = firstname;
        document.getElementById("lastname").value = lastname;
        document.getElementById("role").value = role;
    } else {
        console.error("Error: Missing required query parameters.");
        alert("Error: Missing required query parameters.");
        window.location.href = "profile-settings?error=missing_params";
        return;
    }

    // Handle Update Profile Button Click
    const updateProfileButton = document.getElementById('updateProfileButton');
    const updateProfileModal = new bootstrap.Modal(document.getElementById('updateProfileModal'));

    updateProfileButton.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent default form submission

        // Get updated values
        const firstName = document.getElementById('firstname').value;
        const lastName = document.getElementById('lastname').value;

        // Populate modal fields with updated values
        document.getElementById('clientFirstName').textContent = firstName;
        document.getElementById('clientLastName').textContent = lastName;

        // Show confirmation modal
        updateProfileModal.show();
    });

    // Handle Confirm Update Button in Modal
    document.getElementById('confirmUpdateClient').addEventListener('click', function () {
        document.getElementById('profileForm').submit(); // Submit the form
    });
});

      </script>

  </body>
</html>
