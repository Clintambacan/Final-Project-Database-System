<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Fetch all students
$students_query = "
    SELECT 
        s.student_id, 
        s.student_name, 
        s.student_number, 
        d.dept_name, 
        a.academic_year,
        s.dept_id,
        s.year_id
    FROM tbl_students s
    JOIN tbl_departments d ON s.dept_id = d.dept_id
    JOIN tbl_academic_years a ON s.year_id = a.year_id
";
$students = $conn->query($students_query);
if (!$students) {
    die("Student query failed: " . $conn->error);
}

// Fetch all departments
$departments_result = $conn->query("SELECT dept_id FROM tbl_departments");
$all_departments = [];
while ($dept = $departments_result->fetch_assoc()) {
    $all_departments[] = $dept['dept_id'];
}
$total_departments = count($all_departments);

// Summary counters
$cleared = 0;
$pending = 0;
$total_students = 0;
$student_data = [];

while ($row = $students->fetch_assoc()) {
    $student_id = $row['student_id'];
    $total_students++;

    // Fetch clearance statuses for the student
    $status_query = $conn->prepare("SELECT dept_id, status FROM tbl_clearance_status WHERE student_id = ?");
    $status_query->bind_param("i", $student_id);
    $status_query->execute();
    $result = $status_query->get_result();

    $statuses = [];
    while ($s = $result->fetch_assoc()) {
        $statuses[$s['dept_id']] = $s['status'];
    }
    $status_query->close();

    // Check if all departments have 'Cleared' status
    $isCleared = true;
    foreach ($all_departments as $dept_id) {
        if (!isset($statuses[$dept_id]) || $statuses[$dept_id] !== 'Cleared') {
            $isCleared = false;
            break;
        }
    }

    if ($isCleared && $total_departments > 0) {
        $status = "Cleared";
        $cleared++;
    } else {
        $status = "Pending";
        $pending++;
    }

    $row['status'] = $status;
    $student_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DigiClear Admin Dashboard</title>
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
            font-size: 1.5rem;
            font-weight: 600;
        }
        .stat-card {
            border-radius: 15px;
            padding: 25px;
            color: #fff;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease-in-out;
        }
        .stat-card:hover {
            transform: scale(1.03);
        }
        .bg-success { background-color: #28a745 !important; }
        .bg-warning { background-color: #ffc107 !important; color: #000 !important; }
        .bg-info { background-color: #17a2b8 !important; }
        .card {
            background-color: #ffffff15;
            border: none;
            border-radius: 20px;
            color: #fff;
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
        .btn-info:hover {
            background-color: #138496;
        }
        .search-bar {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>🎓 DigiClear</h2>
    <a href="dashboard.php" class="active">📋 Dashboard</a>
    <a href="departments.php">🏢 Departments</a>
    <a href="students.php">👨‍🎓 Students</a>
    <a href="clearance_status.php">✅ Clearance Status</a>
    <a href="logout.php" class="text-danger">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="dashboard-title">Welcome, Admin 👋</div>
        <button class="btn btn-outline-light btn-sm" onclick="toggleAutoRefresh()">🔁 Toggle Auto Refresh</button>
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

    <!-- Student Clearance Table -->
    <div class="card">
        <div class="card-body">
            <h5 class="mb-4 text-white">🧾 Student Clearance Overview</h5>
            <input type="text" id="searchInput" class="form-control search-bar" placeholder="Search by name, student number, department...">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="studentTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student No</th>
                            <th>Department</th>
                            <th>Academic Year</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><?= htmlspecialchars($row['student_number']) ?></td>
                            <td><?= htmlspecialchars($row['dept_name']) ?></td>
                            <td><?= htmlspecialchars($row['academic_year']) ?></td>
                            <td>
                                <?php
                                    $status = htmlspecialchars($row['status']);
                                    $badgeClass = $status === 'Cleared' ? 'success' : 'warning';
                                    echo "<span class='badge bg-$badgeClass'>$status</span>";
                                ?>
                                <a href="clearance_view.php?student_id=<?= $row['student_id'] ?>" class="btn btn-sm btn-info ms-2">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
let autoRefresh = false;
function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    if (autoRefresh) {
        setInterval(() => {
            location.reload();
        }, 15000); // 15 seconds
        alert("Auto-refresh enabled every 15 seconds.");
    } else {
        alert("Auto-refresh disabled.");
    }
}

document.getElementById('searchInput').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll('#studentTable tbody tr');
    rows.forEach(row => {
        const rowText = row.innerText.toLowerCase();
        row.style.display = rowText.includes(query) ? '' : 'none';
    });
});
</script>

</body>
</html>
