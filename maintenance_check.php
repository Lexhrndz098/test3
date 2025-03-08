<?php
// Include database connection and settings functions if not already included
if (!function_exists('is_maintenance_mode')) {
    require_once "db_connect.php";
    require_once "settings_functions.php";
}

// Check if maintenance mode is enabled
if (is_maintenance_mode()) {
    // Allow admins to bypass maintenance mode
    $is_admin = false;
    
    // Check if user is logged in and is an admin
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["id"])) {
        // Check if user is an admin
        $sql = "SELECT is_admin FROM users WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $_SESSION["id"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($admin_status);
                    $stmt->fetch();
                    $is_admin = $admin_status == 1;
                }
            }
            $stmt->close();
        }
    }
    
    // If not an admin or not logged in as admin, show maintenance page
    if (!$is_admin && (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true)) {
        // Only redirect if not already on the maintenance page
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'maintenance.php') {
            header("location: maintenance.php");
            exit;
        }
    }
}
?>