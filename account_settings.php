<?php

$account = [
    "name" => "Lynch Esplana",
    "student_id" => "2023-XXXXX",
    "email" => "firstname.last@cvsu.edu.ph",
    "status" => "Active"
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | StudentProfiler</title>
    <link rel="stylesheet" href="account_settings.css">
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
        <span class="eyebrow">SETTINGS</span>
        <h1>Account Settings</h1>
        <p>Manage your account information, password, email, and account status.</p>
    </section>

    <section class="settings-card">

        <span class="section-label">ACCOUNT INFORMATION</span>
        <h2>Account Details</h2>

        <div class="account-grid">

            <div>
                <span>Name</span>
                <strong><?php echo htmlspecialchars($account["name"]); ?></strong>
            </div>

            <div>
                <span>Student ID</span>
                <strong><?php echo htmlspecialchars($account["student_id"]); ?></strong>
            </div>

            <div>
                <span>Email</span>
                <strong><?php echo htmlspecialchars($account["email"]); ?></strong>
            </div>

            <div>
                <span>Status</span>
                <strong class="status"><?php echo htmlspecialchars($account["status"]); ?></strong>
            </div>

        </div>

    </section>

    <section class="settings-card">

        <span class="section-label">SECURITY</span>
        <h2>Password & Email</h2>

        <a href="change_password.php" class="setting-row">
            <div>
                <h3>Change Password</h3>
                <p>Update the password used to access your account.</p>
            </div>
            <span>→</span>
        </a>

        <a href="update_email.php" class="setting-row">
            <div>
                <h3>Update Email</h3>
                <p>Change your account email address.</p>
            </div>
            <span>→</span>
        </a>

    </section>

    <section class="settings-card danger-card">

        <span class="section-label">ACCOUNT MANAGEMENT</span>
        <h2>Account Actions</h2>

        <div class="danger-actions">

            <a href="login.php" class="logout-action">
                Log out
            </a>

            <button class="deactivate-action">
                Deactivate Account
            </button>

        </div>

        <p class="warning">
            Account deactivation may restrict access to your StudentProfiler account.
        </p>

    </section>

</main>

<footer class="footer">
    <p>StudentProfiler</p>
    <span>Manage your student profile with ease.</span>
</footer>

</body>
</html>