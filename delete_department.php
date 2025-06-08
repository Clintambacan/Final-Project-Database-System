<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Check if the department exists first (optional, for safety)
    $check = $conn->prepare("SELECT * FROM tbl_departments WHERE dept_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM tbl_departments WHERE dept_id = ?");
        $delete->bind_param("i", $id);
        if ($delete->execute()) {
            $_SESSION['success'] = "✅ Department deleted successfully.";
        } else {
            $_SESSION['error'] = "❌ Failed to delete department.";
        }
        $delete->close();
    } else {
        $_SESSION['error'] = "⚠️ Department not found.";
    }

    $check->close();
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: departments.php");
exit();
?>
