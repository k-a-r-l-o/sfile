<?php
// Include your database connection file
require_once '../../../config/config.php';
require_once '../../../config/database.php';  // Ensure you include the database class

// Create an instance of the database class
$database = new database();

// Open the connection
$database->openConnection();

// Fetch file_name and file_id from URL if they exist
$file_name = isset($_GET['file_name']) ? $_GET['file_name'] : '';
$file_id = isset($_GET['file_id']) ? $_GET['file_id'] : '';

// Check if the form was submitted (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Fetch file_id and file_name from POST data (if available, otherwise use URL data)
  $file_id = $_POST['file_id'] ?? $file_id;
  $file_name = $_POST['file_name'] ?? $file_name;

  try {
    // Ensure $pdo is defined and functional
    $pdo = $database->getConnection();
    if ($pdo === null) {
      throw new Exception('Database connection is not established.');
    }

    // Update the file status to 0 (deleted)
    $stmt = $pdo->prepare("UPDATE tb_files SET status = 0 WHERE file_id = :file_id AND name = :file_name");
    $stmt->bindParam(':file_id', $file_id);
    $stmt->bindParam(':file_name', $file_name);

    if ($stmt->execute()) {
      echo "<script>alert('File deleted successfully.'); window.location.href='my-files.html';</script>";
      // Fetch the current user's email and role
      $doerUserId = $_SESSION['client_user_id'];
      $userStmt = $pdo->prepare("
            SELECT user_email, user_role 
            FROM tb_client_userdetails 
            WHERE user_id = :user_id AND user_status = 1
        ");
      $userStmt->bindParam(':user_id', $doerUserId);
      $userStmt->execute();
      $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
      $logRole = $userDetails['user_role'] ?? 'Unknown';

      // Log the user addition
      $logAction = "Deleted file $file_name successfully";
      $logdate = date('Y-m-d H:i:s');
      $logStmt = $pdo->prepare("
            INSERT INTO tb_logs (doer, log_date, role, log_action) 
            VALUES (:doer, :log_date, :role, :action)
        ");
      $logStmt->execute([
        ':doer' => $doerUserId,
        ':log_date' => $logdate,
        ':role' => $logRole,
        ':action' => $logAction
      ]);
      exit();
    } else {
      echo "<p>Error deleting the file.</p>";
    }
  } catch (Exception $e) {
    echo "Error: " . $e->getMessage();
  }
}
?>

<!-- HTML part -->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SecureFile</title>
  <link
    rel="shortcut icon"
    type="image/png"
    href="../../../assets/img/logo.png" />
  <link rel="stylesheet" href="../../../assets/css/styles.min.css" />
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/tabler-icons@1.30.1/dist/tabler-icons.min.css" />
</head>

<body>
  <!-- Body Wrapper -->
  <div
    class="page-wrapper"
    id="main-wrapper"
    data-layout="vertical"
    data-navbarbg="skin6"
    data-sidebartype="full"
    data-sidebar-position="fixed"
    data-header-position="fixed">
    <!-- Sidebar Start -->
    <aside class="left-sidebar">
      <div>
        <div
          class="brand-logo d-flex align-items-center justify-content-between">
          <a href="./" class="text-nowrap logo-img">
            <img src="../../../assets/img/light-logo-employee.svg" alt="" />
          </a>
          <div
            class="close-btn d-xl-none d-block sidebartoggler cursor-pointer"
            id="sidebarCollapse">
            <i class="ti ti-x fs-8"></i>
          </div>
        </div>
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
          <ul id="sidebarnav">
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-6"></i>
              <span class="hide-menu">Home</span>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="./" aria-expanded="false">
                <span>
                  <iconify-icon
                    icon="solar:home-smile-bold-duotone"
                    class="fs-6"></iconify-icon>
                </span>
                <span class="hide-menu">Dashboard</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a
                class="sidebar-link"
                href="./upload-files"
                aria-expanded="false">
                <span>
                  <iconify-icon
                    icon="solar:upload-minimalistic-bold-duotone"
                    class="fs-6"></iconify-icon>
                </span>
                <span class="hide-menu">Upload Files</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="./my-files" aria-expanded="false">
                <span>
                  <iconify-icon
                    icon="solar:layers-minimalistic-bold-duotone"
                    class="fs-6"></iconify-icon>
                </span>
                <span class="hide-menu">My Files</span>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>
    <!-- Sidebar End -->
    <!-- Main wrapper -->
    <div class="body-wrapper">
      <header class="app-header">
        <nav class="navbar navbar-expand-lg navbar-light">
          <ul class="navbar-nav">
            <li class="nav-item d-block d-xl-none">
              <a
                class="nav-link sidebartoggler nav-icon-hover"
                id="headerCollapse"
                href="javascript:void(0)">
                <i class="ti ti-menu-2"></i>
              </a>
            </li>
          </ul>
        </nav>
      </header>

      <div class="container-fluid">
        <div class="row">
          <h3 class="col-lg-8"><strong> Deleting File: </strong> <span id="fileName"><?= htmlspecialchars($file_name) ?></span></h3>

          <div class="col-lg-12">
            <div class="card">
              <div class="card-body">
                <div class="text-center">
                  <iconify-icon icon="mdi:alert-circle-outline" class="display-1 text-warning"></iconify-icon>
                </div>
                <h5 class="card-title fw-semibold mb-4 text-center">
                  <strong> File ID:</strong> <span id="fileID"><?= htmlspecialchars($file_id) ?></span>
                </h5>

                <p class="mb-4 text-center">
                  Are you sure you want to delete this file? This action cannot be undone.
                </p>

                <form action="delete-file.php" method="POST" class="text-center">
                  <input type="hidden" name="file_id" value="<?= htmlspecialchars($file_id) ?>" />
                  <input type="hidden" name="file_name" value="<?= htmlspecialchars($file_name) ?>" />

                  <button type="button" onclick="window.location.href='my-files.html'" class="btn btn-dark">
                    Cancel
                  </button>
                  <button type="submit" class="btn btn-danger">
                    Delete File
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function() {
      const checkSession = () => {
        fetch("../../session/sessioncheck.php", {
            method: "GET",
            credentials: "include", // Include cookies with the request
          })
          .then((response) => response.json())
          .then((data) => {
            if (!data.sessionValid) {
              // Redirect to the logout script if the session is invalid
              window.location.href = "../../login?error=session_expired";
            }
          })
          .catch((err) => {
            console.error("Error checking session:", err);
            // Handle fetch errors if needed
          });
      };

      // Check the session every 5 minutes (300,000 milliseconds)
      setInterval(checkSession, 300000);

      // Also check the session immediately when the script is loaded
      checkSession();
    })();
  </script>
  <script src="../../../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../assets/libs/apexcharts/dist/apexcharts.min.js"></script>
  <script src="../../../assets/libs/simplebar/dist/simplebar.js"></script>
  <script src="../../../assets/js/sidebarmenu.js"></script>
  <script src="../../../assets/js/app.min.js"></script>
  <script src="../../../assets/js/dashboard.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>

</html>