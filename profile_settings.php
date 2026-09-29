<?php

$settings = [
    "profile_photo" => true,
    "display_name" => true,
    "about_me" => true,
    "education" => true,
    "accomplishments" => true,
    "hobbies" => true,
    "organizations" => true,
    "experiences" => true
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | StudentProfiler</title>
    <link rel="stylesheet" href="profile_settings.css">
</head>

<body>

<header class="navbar">
    <div class="nav-container">

        <a href="student_dashboard.php" class="brand">
            <div class="brand-mark">SP</div>
            <div class="brand-text">
                <span class="brand-name">StudentProfiler</span>
                <span class="brand-subtitle">Student Profile Management</span>
            </div>
        </a>

        <nav class="desktop-nav">
            <a href="student_dashboard.php" class="nav-link">Dashboard</a>
            <a href="student_profile.php" class="nav-link">Profile</a>
            <a href="accomplishments.php" class="nav-link">Accomplishments</a>
            <a href="settings.php" class="nav-link active">Settings</a>
        </nav>

        <a href="login.php" class="logout-button">Log out</a>

    </div>
</header>

<main class="page">

    <section class="page-header">

        <div>
            <span class="eyebrow">SETTINGS</span>
            <h1>Profile Settings</h1>
            <p>Manage how your student profile is displayed and organized.</p>
        </div>

    </section>

    <section class="settings-card">

        <div class="settings-heading">
            <span class="section-label">PROFILE MANAGEMENT</span>
            <h2>Profile Controls</h2>
            <p>Manage your profile information and display preferences.</p>
        </div>

        <div class="settings-list">

            <a href="edit_profile.php" class="setting-item">

                <div class="setting-icon">P</div>

                <div class="setting-content">
                    <h3>Edit Profile</h3>
                    <p>Update your personal, academic, family, and educational information.</p>
                </div>

                <span class="arrow">→</span>

            </a>

            <div class="setting-item">

                <div class="setting-icon">I</div>

                <div class="setting-content">
                    <h3>Profile Photo</h3>
                    <p>Change the photo displayed on your student profile.</p>
                </div>

                <button class="outline-button">Change Photo</button>

            </div>

            <div class="setting-item">

                <div class="setting-icon">D</div>

                <div class="setting-content">
                    <h3>Profile Display Settings</h3>
                    <p>Control the information shown on your profile.</p>
                </div>

                <span class="arrow">→</span>

            </div>

        </div>

    </section>

    <section class="settings-card">

        <div class="settings-heading">
            <span class="section-label">PROFILE SECTIONS</span>
            <h2>Manage Profile Sections</h2>
            <p>Choose which sections are available on your profile.</p>
        </div>

        <div class="toggle-list">

            <?php foreach ($settings as $section => $enabled): ?>

            <div class="toggle-item">

                <div>
                    <h3><?php echo ucwords(str_replace("_", " ", $section)); ?></h3>
                    <p>Include this section on your profile.</p>
                </div>

                <label class="switch">
                    <input type="checkbox" <?php echo $enabled ? "checked" : ""; ?>>
                    <span class="slider"></span>
                </label>

            </div>

            <?php endforeach; ?>

        </div>

    </section>

    <section class="settings-card">

        <div class="settings-heading">
            <span class="section-label">COMPLETION</span>
            <h2>Profile Completion Preferences</h2>
            <p>Control how profile completion information is presented.</p>
        </div>

        <div class="completion-box">

            <div class="completion-circle">
                80%
            </div>

            <div>
                <h3>Profile Completion</h3>
                <p>Complete more sections to provide a more complete student profile.</p>
            </div>

        </div>

    </section>

</main>

<footer class="footer">
    <p>StudentProfiler</p>
    <span>Manage your student profile with ease.</span>
</footer>

</body>
</html>