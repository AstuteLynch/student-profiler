<?php

$administrator = [
    "name" => "Administrator",
    "email" => "admin@cvsu.edu.ph",
    "role" => "System Administrator",
    "status" => "Active"
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Settings | StudentProfiler</title>
    <link rel="stylesheet" href="admin_settings.css">
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
            <a href="student_records.php" class="nav-link">Student Records</a>
            <a href="admin_settings.php" class="nav-link active">Settings</a>
        </nav>

        <a href="login.php" class="logout-button">Log out</a>

    </div>

</header>

<main class="page">

    <section class="page-header">
        <span class="eyebrow">ADMINISTRATION</span>
        <h1>Administrator Account Settings</h1>
        <p>Manage your administrator account and account security.</p>
    </section>

    <section class="settings-card">

        <span class="section-label">ADMINISTRATOR ACCOUNT</span>
        <h2>Account Information</h2>

        <div class="account-profile">

            <div class="profile-icon">
                A
            </div>

            <div>
                <h3><?php echo htmlspecialchars($administrator["name"]); ?></h3>
                <p><?php echo htmlspecialchars($administrator["email"]); ?></p>
                <span><?php echo htmlspecialchars($administrator["role"]); ?></span>
            </div>

        </div>

        <div class="information-grid">

            <div>
                <span>Name</span>
                <strong><?php echo htmlspecialchars($administrator["name"]); ?></strong>
            </div>

            <div>
                <span>Email</span>
                <strong><?php echo htmlspecialchars($administrator["email"]); ?></strong>
            </div>

            <div>
                <span>Role</span>
                <strong><?php echo htmlspecialchars($administrator["role"]); ?></strong>
            </div>

            <div>
                <span>Status</span>
                <strong class="active">Active</strong>
            </div>

        </div>

    </section>

    <section class="settings-card">

        <span class="section-label">SECURITY</span>
        <h2>Password Management</h2>

        <a href="admin_change_password.php" class="setting-row">

            <div>
                <h3>Change Password</h3>
                <p>Update the password used to access the administrator account.</p>
            </div>

            <span>→</span>

        </a>

    </section>

    <section class="settings-card">

        <span class="section-label">PERMISSIONS</span>
        <h2>Administrator Permissions</h2>

        <div class="permission-list">

            <div class="permission">
                <div>
                    <h3>View Student Records</h3>
                    <p>View student records according to assigned permissions.</p>
                </div>
                <span class="enabled">Enabled</span>
            </div>

            <div class="permission">
                <div>
                    <h3>Manage Student Records</h3>
                    <p>Update and manage student records.</p>
                </div>
                <span class="enabled">Enabled</span>
            </div>

            <div class="permission">
                <div>
                    <h3>Manage Accounts</h3>
                    <p>Manage student account status when permitted.</p>
                </div>
                <span class="enabled">Enabled</span>
            </div>

        </div>

    </section>

    <div class="logout-section">
        <a href="login.php" class="logout-action">Log out</a>
    </div>

</main>

<footer class="footer">
    <p>StudentProfiler</p>
    <span>Administrator Portal</span>
</footer>

</body>
</html>