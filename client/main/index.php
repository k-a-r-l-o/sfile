<?php
session_start();
// Check if session variables are set
if (isset($_SESSION['client_role'], $_SESSION['client_token'], $_SESSION['client_user_id'])) {
    $role = $_SESSION['client_role'];
    if ($role == 'Employee') {
        header("Location: ./employee");
    } else if ($role == 'Head') {
        header("Location: ./head");
    } else {
        header("Location: ../login");
    }
} else {
    header("Location: ../login");
}
exit();
