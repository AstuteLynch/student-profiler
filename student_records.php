<?php

$students = [
    [
        "id" => "2023-00123",
        "name" => "Lynch Esplana",
        "program" => "BS Computer Science",
        "year" => "3rd Year",
        "status" => "Active"
    ],
    [
        "id" => "2023-00124",
        "name" => "Juan Dela Cruz",
        "program" => "BS Information Technology",
        "year" => "3rd Year",
        "status" => "Active"
    ],
    [
        "id" => "2022-00451",
        "name" => "Maria Santos",
        "program" => "BS Computer Science",
        "year" => "4th Year",
        "status" => "Archived"
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student Records | StudentProfiler</title>
    <link rel="stylesheet" href="student_records.css">
</head>

<body>

<header class="navbar">

    <div class="nav-container">

        <a href="admin_dashboard.php" class="brand">
            <div class="brand-mark">SP</div>
            <div class="brand-text">
                <span class="brand-name">StudentProfiler</span>
                <span class="brand-subtitle">Administrator Portal</span>
            </div>
        </a>

        <nav class="desktop-nav">
            <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
            <a href="student_records.php" class="nav-link active">Student Records</a>
            <a href="admin_settings.php" class="nav-link">Settings</a>
        </nav>

        <a href="login.php" class="logout-button">Log out</a>

    </div>

</header>

<main class="page">

    <section class="page-header">

        <div>
            <span class="eyebrow">ADMINISTRATION</span>
            <h1>Manage Student Records</h1>
            <p>Search, update, archive, restore, and manage student account records.</p>
        </div>

    </section>

    <section class="records-card">

        <div class="filter-bar">

            <div class="search-box">
                <input type="search" placeholder="Search student name or ID">
            </div>

            <select>
                <option>All Programs</option>
                <option>BS Computer Science</option>
                <option>BS Information Technology</option>
            </select>

            <select>
                <option>All Status</option>
                <option>Active</option>
                <option>Archived</option>
            </select>

            <button class="filter-button">Filter</button>

        </div>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($students as $student): ?>

                    <tr>

                        <td>
                            <strong><?php echo htmlspecialchars($student["name"]); ?></strong>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($student["id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($student["program"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($student["year"]); ?>
                        </td>

                        <td>
                            <span class="status <?php echo strtolower($student["status"]); ?>">
                                <?php echo htmlspecialchars($student["status"]); ?>
                            </span>
                        </td>

                        <td>
                            <a href="admin_student_profile.php" class="view-button">
                                Open Profile
                            </a>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<footer class="footer">
    <p>StudentProfiler</p>
    <span>Administrator Portal</span>
</footer>

</body>
</html>