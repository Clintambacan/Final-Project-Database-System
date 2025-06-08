<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch departments
$departments = $conn->query("SELECT * FROM tbl_departments");

// Summary Data
$total_students = $conn->query("SELECT COUNT(*) AS total FROM tbl_students")->fetch_assoc()['total'];
$cleared = $conn->query("
    SELECT COUNT(*) AS cleared FROM (
        SELECT s.student_id, IF(SUM(cs.status = 'Pending') = 0, 1, 0) AS is_cleared
        FROM tbl_students s
        LEFT JOIN tbl_clearance_status cs ON s.student_id = cs.student_id
        GROUP BY s.student_id
        HAVING is_cleared = 1
    ) cleared_students
")->fetch_assoc()['cleared'];
$pending = $total_students - $cleared;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DigiClear - Departments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
            font-family: 'Outfit', sans-serif;
            color: #fff;
        }
        .sidebar {
            width: 250px;
            position: fixed;
            top: 0; left: 0;
            height: 100%;
            background-color: #1c1c1c;
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
        }
        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 30px;
            color: #fff;
        }
        .sidebar a {
            display: block;
            color: #ddd;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 10px;
            text-decoration: none;
            font-weight: 500;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #17a2b8;
            color: #fff;
        }
        .main-content {
            margin-left: 270px;
            padding: 40px 30px;
        }
        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ccc;
        }
        .card {
            background-color: #ffffff15;
            border: none;
            border-radius: 20px;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #004e92;
            color: white;
            cursor: pointer;
            padding: 15px 20px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        .table {
            color: #fff;
        }
        .table th {
            background-color: #004e92;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #ffffff10;
        }
        .stat-card {
            padding: 20px;
            border-radius: 20px;
            text-align: center;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🎓 DigiClear</h2>
    <a href="dashboard.php">📋 Dashboard</a>
    <a href="departments.php" class="active">🏢 Departments</a>
    <a href="students.php">👨‍🎓 Students</a>
    <a href="clearance_status.php">✅ Clearance Status</a>
    <a href="logout.php" class="text-danger">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-end mb-4">
        <div class="dashboard-title">Welcome, Admin 👋</div>
    </div>

    <!-- Clearance Summary -->
    <div class="mb-5">
        <h5 class="text-white mb-3">📊 Clearance Summary</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card bg-success">
                    ✅ Cleared Students
                    <h3><?= $cleared ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-warning text-dark">
                    ⏳ Pending Clearances
                    <h3><?= $pending ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-info">
                    🎓 Total Students
                    <h3><?= $total_students ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Students Table -->
    <h3 class="mb-4">🏢 Departments & Students</h3>

    <?php while ($dept = $departments->fetch_assoc()): ?>
        <?php
            // Skip Clinic and Library
            if (in_array(strtolower($dept['dept_name']), ['clinic', 'library'])) {
                continue;
            }

            $dept_id = $dept['dept_id'];
            $dept_name = htmlspecialchars($dept['dept_name']);
            $stmt = $conn->prepare("
                SELECT s.student_id, s.student_name, s.student_number,
                       IF(SUM(cs.status = 'Pending') > 0, 'Pending', 'Cleared') AS overall_status
                FROM tbl_students s
                LEFT JOIN tbl_clearance_status cs ON s.student_id = cs.student_id
                WHERE s.dept_id = ?
                GROUP BY s.student_id
            ");
            $stmt->bind_param("i", $dept_id);
            $stmt->execute();
            $students = $stmt->get_result();
        ?>

        <div class="card">
            <div class="card-header" data-bs-toggle="collapse" data-bs-target="#dept<?= $dept_id ?>" aria-expanded="false">
                <h5 class="mb-0"><?= $dept_name ?> Department</h5>
            </div>
            <div id="dept<?= $dept_id ?>" class="collapse">
                <div class="card-body">
                    <?php if ($students->num_rows > 0): ?>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Student Number</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['student_name']) ?></td>
                                        <td><?= htmlspecialchars($student['student_number']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $student['overall_status'] === 'Cleared' ? 'success' : 'secondary' ?>">
                                                <?= $student['overall_status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-light">No students found in this department.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
