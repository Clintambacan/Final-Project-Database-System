<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Handle student deletion
if (isset($_GET['delete'])) {
    $studentId = intval($_GET['delete']);
    $conn->query("DELETE FROM tbl_students WHERE student_id = $studentId");
    header("Location: students.php");
    exit;
}

// Handle search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $sql = "
        SELECT 
            s.student_id, 
            s.student_name, 
            s.student_number, 
            d.dept_name, 
            a.academic_year,
            CASE 
                WHEN MAX(IFNULL(cs.status, '') != 'Cleared') = 1 THEN 'Pending'
                ELSE 'Cleared'
            END AS overall_status
        FROM tbl_students s
        JOIN tbl_departments d ON s.dept_id = d.dept_id
        JOIN tbl_academic_years a ON s.year_id = a.year_id
        LEFT JOIN tbl_clearance_status cs ON s.student_id = cs.student_id
        WHERE s.student_name LIKE ? OR s.student_number LIKE ?
        GROUP BY s.student_id, s.student_name, s.student_number, d.dept_name, a.academic_year
    ";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $students = $stmt->get_result();
} else {
    $sql = "
        SELECT 
            s.student_id, 
            s.student_name, 
            s.student_number, 
            d.dept_name, 
            a.academic_year,
            CASE 
                WHEN MAX(IFNULL(cs.status, '') != 'Cleared') = 1 THEN 'Pending'
                ELSE 'Cleared'
            END AS overall_status
        FROM tbl_students s
        JOIN tbl_departments d ON s.dept_id = d.dept_id
        JOIN tbl_academic_years a ON s.year_id = a.year_id
        LEFT JOIN tbl_clearance_status cs ON s.student_id = cs.student_id
        GROUP BY s.student_id, s.student_name, s.student_number, d.dept_name, a.academic_year
    ";
    $students = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DigiClear - Students</title>
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
        }
        .table {
            color: #fff;
        }
        .table th {
            background-color: #004e92;
            color: white;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #ffffff10;
        }
        .btn-info {
            border-radius: 8px;
            background-color: #17a2b8;
            border: none;
        }
        .btn-danger {
            border-radius: 8px;
            border: none;
        }
        .btn-warning {
            border-radius: 8px;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-success {
            border-radius: 8px;
            background-color: #28a745;
            border: none;
        }
        .btn-secondary {
            border-radius: 8px;
            background-color: #6c757d;
            border: none;
        }
        .card {
            background-color: #ffffff15;
            border: none;
            border-radius: 20px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🎓 DigiClear</h2>
    <a href="dashboard.php">📋 Dashboard</a>
    <a href="departments.php">🏢 Departments</a>
    <a href="students.php" class="active">👨‍🎓 Students</a>
    <a href="clearance_status.php">✅ Clearance Status</a>
    <a href="logout.php" class="text-danger">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-title mb-4">Student Records 📚</div>

    <!-- Search Form -->
    <form method="get" class="mb-3 d-flex justify-content-end">
        <input type="text" name="search" class="form-control w-25 me-2" placeholder="Search by name or number" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-info">Search</button>
    </form>

    <!-- Add Student Button -->
    <div class="mb-4 text-end">
        <a href="add_student.php" class="btn btn-info">➕ Add Student</a>
    </div>

    <!-- Student Table -->
    <div class="card">
        <div class="card-body">
            <h5 class="mb-4 text-white">👨‍🎓 List of Registered Students</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student Number</th>
                            <th>Department</th>
                            <th>Academic Year</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><?= htmlspecialchars($row['student_number']) ?></td>
                                    <td><?= htmlspecialchars($row['dept_name']) ?></td>
                                    <td><?= htmlspecialchars($row['academic_year']) ?></td>
                                    <td>
                                        <a href="clearance_view.php?id=<?= $row['student_id'] ?>" 
                                           class="btn btn-sm <?= $row['overall_status'] === 'Cleared' ? 'btn-success' : 'btn-secondary' ?>">
                                           <?= $row['overall_status'] === 'Cleared' ? '✅ Cleared' : '❌ Pending' ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="edit_student.php?id=<?= $row['student_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="students.php?delete=<?= $row['student_id'] ?>" onclick="return confirm('Are you sure you want to delete this student?');" class="btn btn-sm btn-danger">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-light">No student records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
