<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_name = trim($_POST['student_name']);
    $student_number = trim($_POST['student_number']);
    $dept_id = intval($_POST['dept_id']);
    $year_id = intval($_POST['year_id']);

    if ($student_name && $student_number && $dept_id && $year_id) {
        // Insert new student
        $stmt = $conn->prepare("INSERT INTO tbl_students (student_name, student_number, dept_id, year_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $student_name, $student_number, $dept_id, $year_id);
        if ($stmt->execute()) {
            $new_student_id = $stmt->insert_id; // Get the new student's ID

            // Assign "Pending" status only for the student's own department
            $conn->query("INSERT INTO tbl_clearance_status (student_id, dept_id, status) VALUES ($new_student_id, $dept_id, 'Pending')");

            header("Location: students.php");
            exit();
        } else {
            $error = "Error adding student.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}

// Fetch departments and academic years for dropdowns
$departments = $conn->query("SELECT * FROM tbl_departments");
$years = $conn->query("SELECT * FROM tbl_academic_years");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student - DigiClear</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
            font-family: 'Outfit', sans-serif;
            color: #fff;
        }
        .container {
            max-width: 600px;
            margin-top: 60px;
            background-color: #ffffff15;
            border-radius: 20px;
            padding: 30px;
        }
        label {
            font-weight: 600;
        }
        .form-control {
            border-radius: 10px;
        }
        .btn-primary {
            background-color: #17a2b8;
            border: none;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background-color: #138496;
        }
        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
        }
        a.back-link {
            color: #ccc;
            text-decoration: none;
        }
        a.back-link:hover {
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="form-title">➕ Add New Student</div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label for="student_name" class="form-label">Full Name</label>
            <input type="text" name="student_name" id="student_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="student_number" class="form-label">Student Number</label>
            <input type="text" name="student_number" id="student_number" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="dept_id" class="form-label">Department</label>
            <select name="dept_id" id="dept_id" class="form-select" required>
                <option value="">-- Select Department --</option>
                <?php while ($row = $departments->fetch_assoc()): ?>
                    <?php
                        $excluded = ['Clinic', 'Library'];
                        if (in_array($row['dept_name'], $excluded)) continue;
                    ?>
                    <option value="<?= (int)$row['dept_id'] ?>"><?= htmlspecialchars($row['dept_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-4">
            <label for="year_id" class="form-label">Academic Year</label>
            <select name="year_id" id="year_id" class="form-select" required>
                <option value="">-- Select Year --</option>
                <?php while ($row = $years->fetch_assoc()): ?>
                    <option value="<?= (int)$row['year_id'] ?>"><?= htmlspecialchars($row['academic_year']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Save Student</button>
        <div class="mt-3 text-center">
            <a href="students.php" class="back-link">← Back to Student List</a>
        </div>
    </form>
</div>

</body>
</html>
