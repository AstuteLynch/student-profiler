<?php

$student = [
    "name" => "Lynch Esplana",
    "student_id" => "2023-XXXXX",
    "email" => "firstname.last@cvsu.edu.ph",
    "program" => "Bachelor of Science in Computer Science",
    "year_level" => "3rd Year",
    "section" => "BSCS 3-A",
    "campus" => "CvSU Carmona",
    "address" => "Cavite, Philippines",
    "about" => "A Computer Science student interested in programming, software development, and technology.",
    "achievement" => "Academic Achievement",
    "hobbies" => "Programming, Gaming, Music",
    "organization" => "Computer Science Organization"
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | StudentProfiler</title>
    <link rel="stylesheet" href="admin_student_profile.css">
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
            <span class="eyebrow">STUDENT RECORD</span>
            <h1>Student Profile</h1>
            <p>View student information according to your administrator permissions.</p>
        </div>

        <a href="student_records.php" class="back-button">← Back to Records</a>

    </section>

    <section class="profile-header">

        <div class="profile-photo">L</div>

        <div class="profile-info">
            <span class="status">ACTIVE STUDENT</span>
            <h2><?php echo htmlspecialchars($student["name"]); ?></h2>
            <p><?php echo htmlspecialchars($student["student_id"]); ?></p>
            <span><?php echo htmlspecialchars($student["program"]); ?></span>
        </div>

    </section>

    <div class="profile-grid">

        <section class="profile-card">

            <span class="section-label">PERSONAL INFORMATION</span>
            <h2>Personal Details</h2>

            <div class="info-list">

                <div>
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($student["email"]); ?></strong>
                </div>

                <div>
                    <span>Address</span>
                    <strong><?php echo htmlspecialchars($student["address"]); ?></strong>
                </div>

            </div>

        </section>

        <section class="profile-card">

            <span class="section-label">ACADEMIC INFORMATION</span>
            <h2>Academic Details</h2>

            <div class="info-list">

                <div>
                    <span>Program</span>
                    <strong><?php echo htmlspecialchars($student["program"]); ?></strong>
                </div>

                <div>
                    <span>Year Level</span>
                    <strong><?php echo htmlspecialchars($student["year_level"]); ?></strong>
                </div>

                <div>
                    <span>Section</span>
                    <strong><?php echo htmlspecialchars($student["section"]); ?></strong>
                </div>

                <div>
                    <span>Campus</span>
                    <strong><?php echo htmlspecialchars($student["campus"]); ?></strong>
                </div>

            </div>

        </section>

        <section class="profile-card full">

            <span class="section-label">PROFILE INFORMATION</span>
            <h2>About the Student</h2>

            <p class="about">
                <?php echo htmlspecialchars($student["about"]); ?>
            </p>

            <div class="summary-grid">

                <div>
                    <span>Achievement</span>
                    <strong><?php echo htmlspecialchars($student["achievement"]); ?></strong>
                </div>

                <div>
                    <span>Hobbies & Interests</span>
                    <strong><?php echo htmlspecialchars($student["hobbies"]); ?></strong>
                </div>

                <div>
                    <span>Organization</span>
                    <strong><?php echo htmlspecialchars($student["organization"]); ?></strong>
                </div>

            </div>

        </section>

    </div>

</main>

<footer class="footer">
    <p>StudentProfiler</p>
    <span>Administrator Portal</span>
</footer>

</body>
</html>