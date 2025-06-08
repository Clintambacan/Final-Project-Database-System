<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['student_id'])) {
    echo "Student ID not provided.";
    exit();
}

$student_id = intval($_GET['student_id']);

// Fetch student details
$student_stmt = $conn->prepare("
    SELECT s.student_name, s.student_number, d.dept_name AS student_dept, a.academic_year
    FROM tbl_students s
    JOIN tbl_departments d ON s.dept_id = d.dept_id
    JOIN tbl_academic_years a ON s.year_id = a.year_id
    WHERE s.student_id = ?
");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    echo "Student not found.";
    exit();
}

// Fetch all departments
$departments = $conn->query("SELECT * FROM tbl_departments");

// Fetch existing clearance statuses
$status_stmt = $conn->prepare("SELECT dept_id, status FROM tbl_clearance_status WHERE student_id = ?");
$status_stmt->bind_param("i", $student_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();

$statuses = [];
while ($row = $status_result->fetch_assoc()) {
    $statuses[$row['dept_id']] = $row['status'];
}

// Friendly names for clearance offices
$nameMap = [
    'IBM' => 'Billing',
    'ICS' => 'SAS',
    'ITE' => 'Registrar',
    'Library' => 'Library',
    'Clinic' => 'Clinic'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clearance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f2f6fc;
            padding: 40px;
        }
        .report {
            background: #fff;
            padding: 50px;
            border-radius: 16px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        h3 {
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(to right, #007bff, #00c6ff);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h3::before {
            content: "🧾";
            font-size: 28px;
        }
        .info p {
            font-size: 16px;
            margin: 4px 0;
        }
        table {
            font-size: 15px;
        }
        table thead {
            background-color: #007bff;
            color: white;
        }
        table tbody tr:nth-child(even) {
            background-color: #f0f4fa;
        }
        .signature {
            margin-top: 60px;
            text-align: right;
        }
        .signature-line {
            display: inline-block;
            border-top: 1px solid #000;
            width: 250px;
            margin-top: 30px;
        }
        .top-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .report {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 30px;
            }
            .top-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="report">
    <div class="top-buttons">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
    </div>

    <h3>Clearance Report</h3>
    <div class="info mb-4">
        <p><strong>Name:</strong> <?= htmlspecialchars($student['student_name']) ?></p>
        <p><strong>Student Number:</strong> <?= htmlspecialchars($student['student_number']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($student['student_dept']) ?></p>
        <p><strong>Academic Year:</strong> <?= htmlspecialchars($student['academic_year']) ?></p>
    </div>

    <h5 class="mt-4 mb-3 fw-semibold">Departmental Clearance Status</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Offices</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($dept = $departments->fetch_assoc()): ?>
                <?php $displayName = $nameMap[$dept['dept_name']] ?? $dept['dept_name']; ?>
                <tr>
                    <td><?= htmlspecialchars($displayName) ?></td>
                    <td><?= htmlspecialchars($statuses[$dept['dept_id']] ?? 'Pending') ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="signature">
        <p>Verified by:</p>
        <div class="signature-line"></div>
        <p><strong>Admin Signature</strong></p>
    </div>
</div>

</body>
</html>
