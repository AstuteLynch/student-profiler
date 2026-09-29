<?php

$organizations = [
    [
        "name" => "Computer Science Organization",
        "role" => "Member",
        "date" => "2025 - Present",
        "description" => "Participates in organization activities and student events."
    ],
    [
        "name" => "Student Government",
        "role" => "Volunteer",
        "date" => "2025 - 2026",
        "description" => "Assisted with student activities and campus events."
    ]
];

$active_tab = $_GET["tab"] ?? "organizations";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizations & Activities | StudentProfiler</title>
    <link rel="stylesheet" href="organizations.css">
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
            <a href="student_profile.php" class="nav-link active">Profile</a>
            <a href="accomplishments.php" class="nav-link">Accomplishments</a>
            <a href="settings.php" class="nav-link">Settings</a>
        </nav>

        <a href="login.php" class="logout-button">Log out</a>

    </div>

</header>

<main class="page">

    <section class="page-header">

        <div>
            <span class="eyebrow">ACTIVITIES</span>
            <h1>Organizations & Activities</h1>
            <p>Manage your organizations, clubs, student government participation, and events.</p>
        </div>

        <a href="add_organization.php" class="primary-button">+ Add Activity</a>

    </section>

    <section class="content-card">

        <nav class="tabs">

            <a href="organizations.php?tab=organizations"
               class="tab <?php echo $active_tab === 'organizations' ? 'active' : ''; ?>">
                Organizations
            </a>

            <a href="organizations.php?tab=clubs"
               class="tab <?php echo $active_tab === 'clubs' ? 'active' : ''; ?>">
                Clubs
            </a>

            <a href="organizations.php?tab=government"
               class="tab <?php echo $active_tab === 'government' ? 'active' : ''; ?>">
                Student Government
            </a>

            <a href="organizations.php?tab=events"
               class="tab <?php echo $active_tab === 'events' ? 'active' : ''; ?>">
                Events
            </a>

        </nav>

        <div class="section-heading">
            <span class="section-label">MY ACTIVITIES</span>
            <h2><?php echo ucfirst(str_replace("-", " ", $active_tab)); ?></h2>
        </div>

        <div class="activity-list">

            <?php foreach ($organizations as $organization): ?>

            <article class="activity">

                <div class="activity-icon">
                    O
                </div>

                <div class="activity-content">

                    <h3>
                        <?php echo htmlspecialchars($organization["name"]); ?>
                    </h3>

                    <div class="activity-meta">

                        <span>
                            <?php echo htmlspecialchars($organization["role"]); ?>
                        </span>

                        <span>
                            <?php echo htmlspecialchars($organization["date"]); ?>
                        </span>

                    </div>

                    <p>
                        <?php echo htmlspecialchars($organization["description"]); ?>
                    </p>

                </div>

                <div class="activity-actions">
                    <a href="edit_organization.php" class="edit-button">Edit</a>
                    <button class="delete-button">Remove</button>
                </div>

            </article>

            <?php endforeach; ?>

        </div>

    </section>

</main>

<footer class="footer">
    <p>StudentProfiler</p>
    <span>Manage your student profile with ease.</span>
</footer>

</body>
</html>