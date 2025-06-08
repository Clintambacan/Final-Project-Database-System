<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Handle search
$searchTerm = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $students = $conn->prepare("
        SELECT s.student_id, s.student_name, s.student_number, d.dept_name, a.academic_year 
        FROM tbl_students s
        JOIN tbl_departments d ON s.dept_id = d.dept_id
        JOIN tbl_academic_years a ON s.year_id = a.year_id
        WHERE s.student_name LIKE ? OR s.student_number LIKE ?
        ORDER BY s.student_name ASC
    ");
    $like = '%' . $searchTerm . '%';
    $students->bind_param("ss", $like, $like);
    $students->execute();
    $students = $students->get_result();
} else {
    $students = $conn->query("
        SELECT s.student_id, s.student_name, s.student_number, d.dept_name, a.academic_year 
        FROM tbl_students s
        JOIN tbl_departments d ON s.dept_id = d.dept_id
        JOIN tbl_academic_years a ON s.year_id = a.year_id
        ORDER BY s.student_name ASC
    ");
}

// Fetch departments
$departments = [];
$dept_result = $conn->query("SELECT dept_id, dept_name FROM tbl_departments ORDER BY dept_name ASC");
while ($dept = $dept_result->fetch_assoc()) {
    $departments[$dept['dept_id']] = $dept['dept_name'];
}

// Optional department display name mapping
$nameMap = [
    'IBM' => 'Billing',
    'ICS' => 'SAS',
    'ITE' => 'Registrar'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clearance Status - DigiClear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
            font-family: 'Outfit', sans-serif;
            color: #fff;
            margin: 0;
        }
        .sidebar {
            width: 250px;
            position: fixed;
            height: 100%;
            background-color: #1c1c1c;
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
        }
        .sidebar h2 {
            color: #fff;
            margin-bottom: 30px;
            font-size: 1.5rem;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 10px;
            color: #ddd;
            font-weight: 500;
            text-decoration: none;
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
        h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #e0f7fa;
            text-shadow: 0 0 6px rgba(23, 162, 184, 0.7);
        }
        .search-bar {
            margin-bottom: 30px;
        }
        .search-bar input {
            border-radius: 8px;
            border: 2px solid #17a2b8;
            padding: 10px;
            width: 300px;
            font-weight: 500;
        }
        .search-bar button {
            margin-left: 10px;
            border-radius: 8px;
            background-color: #17a2b8;
            border: none;
            padding: 10px 15px;
            font-weight: bold;
            color: white;
        }
        .card {
            background-color: #ffffff15;
            border-radius: 20px;
            margin-bottom: 30px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
            border: none;
        }
        .student-header h5 {
            font-size: 1.5rem;
            color: #e0f7fa;
            margin-bottom: 0;
        }
        .student-header p {
            font-size: 1rem;
            color: #b0d9e8;
            margin-bottom: 15px;
        }
        .dept-clearance {
            border-left: 3px solid #17a2b8;
            padding-left: 15px;
        }
        .dept-clearance div {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-weight: 600;
            font-size: 1rem;
            color: #d9f0fb;
            border-bottom: 1px solid #17a2b8;
        }
        .dept-clearance div:last-child {
            border-bottom: none;
        }
        .form-select {
            width: 160px;
            background: #1a1a1a;
            color: #fff;
            border: 2px solid #17a2b8;
            border-radius: 8px;
            font-weight: 600;
        }
        .select-pending {
            background-color: #3a3a3a;
            color: #ffc107 !important;
        }
        .select-cleared {
            background-color: #2b422e;
            color: #28a745 !important;
        }
        .btn-update {
            margin-top: 20px;
            background-color: #17a2b8;
            color: #fff;
            font-weight: bold;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
        }
        .btn-update:hover {
            background-color: #138496;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>🎓 DigiClear</h2>
    <a href="dashboard.php">📋 Dashboard</a>
    <a href="departments.php">🏢 Departments</a>
    <a href="students.php">👨‍🎓 Students</a>
    <a href="clearance_status.php" class="active">✅ Clearance Status</a>
    <a href="logout.php" class="text-danger">🚪 Logout</a>
</div>

<div class="main-content">
    <h3>✅ Clearance Status</h3>

    <form method="GET" class="search-bar">
        <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search by name or ID number...">
        <button type="submit">🔍 Search</button>
    </form>

    <?php if ($students->num_rows > 0): ?>
        <?php while ($student = $students->fetch_assoc()): ?>
            <div class="card">
                <div class="student-header">
                    <h5><?= htmlspecialchars($student['student_name']) ?> (<?= htmlspecialchars($student['student_number']) ?>)</h5>
                    <p><?= htmlspecialchars($student['dept_name']) ?> | <?= htmlspecialchars($student['academic_year']) ?></p>
                </div>
                <form method="POST" action="update_status.php" class="dept-clearance">
                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                    <?php foreach ($departments as $dept_id => $dept_name): 
                        $stmt = $conn->prepare("SELECT status FROM tbl_clearance_status WHERE student_id = ? AND dept_id = ?");
                        $stmt->bind_param("ii", $student['student_id'], $dept_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $status = $result->fetch_assoc()['status'] ?? 'Pending';
                        $stmt->close();

                        $displayName = $nameMap[$dept_name] ?? $dept_name;
                    ?>
                    <div>
                        <span><?= htmlspecialchars($displayName) ?></span>
                        <select name="status[<?= $dept_id ?>]" required class="form-select <?= $status === 'Cleared' ? 'select-cleared' : 'select-pending' ?>">
                            <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Cleared" <?= $status === 'Cleared' ? 'selected' : '' ?>>Cleared</option>
                        </select>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn-update">Update Status</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-light">No students found for the search keyword.</p>
    <?php endif; ?>
</div>

<script>
    document.querySelectorAll('select.form-select').forEach(select => {
        const updateColor = () => {
            if (select.value === 'Pending') {
                select.classList.add('select-pending');
                select.classList.remove('select-cleared');
            } else {
                select.classList.add('select-cleared');
                select.classList.remove('select-pending');
            }
        };
        updateColor();
        select.addEventListener('change', updateColor);
    });
</script>

</body>
</html>
