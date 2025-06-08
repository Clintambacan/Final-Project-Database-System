<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $statuses = $_POST['status'] ?? [];

    if ($student_id && is_array($statuses)) {
        foreach ($statuses as $dept_id => $status) {
            // Validate input: only 'Pending' or 'Cleared' allowed
            $status = ($status === 'Cleared') ? 'Cleared' : 'Pending';
            $dept_id = (int)$dept_id;

            // Check if record exists for this student and department
            $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM tbl_clearance_status WHERE student_id = ? AND dept_id = ?");
            $stmt_check->bind_param("ii", $student_id, $dept_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            $exists = ($result->fetch_assoc()['count'] > 0);
            $stmt_check->close();

            if ($exists) {
                // Update existing record
                $stmt_update = $conn->prepare("UPDATE tbl_clearance_status SET status = ? WHERE student_id = ? AND dept_id = ?");
                $stmt_update->bind_param("sii", $status, $student_id, $dept_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Insert new record
                $stmt_insert = $conn->prepare("INSERT INTO tbl_clearance_status (student_id, dept_id, status) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iis", $student_id, $dept_id, $status);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
    }
}

header("Location: clearance_status.php");
exit();
?>
