<?php
// Student Dashboard
// Backend/database functionality will be added later.

// Temporary placeholder data
$studentName = "Juan";
$profileCompletion = 80;

$recentAccomplishments = [
    [
        "title" => "Programming Competition",
        "type" => "Competition",
        "date" => "Recently added"
    ],
    [
        "title" => "Certificate of Participation",
        "type" => "Certificate",
        "date" => "Recently added"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | CVSWHO</title>

    <link rel="stylesheet" href="student_dashboard.css">
</head>

<body>

    <!-- Navigation -->

    <header class="navbar">

        <div class="nav-container">

            <a href="student_dashboard.php" class="brand">

                <div class="brand-mark">
                    C
                </div>

                <div class="brand-text">

                    <span class="brand-name">
                        CVSWHO
                    </span>

                    <span class="brand-subtitle">
                        Student Profile Management
                    </span>

                </div>

            </a>


            <nav class="desktop-nav">

                <a href="student_dashboard.php" class="nav-link active">
                    Dashboard
                </a>

                <a href="student_profile.php" class="nav-link">
                    Profile
                </a>

                <a href="accomplishments.php" class="nav-link">
                    Accomplishments
                </a>

                <a href="settings.php" class="nav-link">
                    Settings
                </a>

            </nav>


            <a href="login.php" class="logout-button">
                Log out
            </a>

        </div>

    </header>


    <!--Main Dashboard-->

    <main class="dashboard">


        <!--Welcome-->

        <section class="welcome-section">

            <div>

                <span class="eyebrow">
                    STUDENT DASHBOARD
                </span>

                <h1>
                    Welcome, <?php echo htmlspecialchars($studentName); ?>!
                </h1>

                <p>
                    Manage your student profile, accomplishments, and
                    activities in one place.
                </p>

            </div>


            <a href="student_profile.php" class="primary-button">
                View Profile
            </a>

        </section>



        <!--Profile Completion-->

        <section class="completion-card">

            <div class="completion-content">

                <div>

                    <span class="section-label">
                        PROFILE COMPLETION
                    </span>

                    <h2>
                        <?php echo $profileCompletion; ?>% complete
                    </h2>

                    <p>
                        Keep your profile updated by adding your personal
                        information, academic background, accomplishments,
                        and activities.
                    </p>

                </div>


                <div
                    class="progress-circle"
                    style="--progress: <?php echo $profileCompletion; ?>%;"
                >

                    <div class="progress-circle-inner">

                        <strong>
                            <?php echo $profileCompletion; ?>%
                        </strong>

                        <span>
                            Complete
                        </span>

                    </div>

                </div>

            </div>


            <div class="progress-bar">

                <div
                    class="progress-fill"
                    style="width: <?php echo $profileCompletion; ?>%;"
                ></div>

            </div>

        </section>



        <!--Profile Overview-->

        <section class="overview-grid">


            <!-- Profile -->

            <div class="overview-card profile-overview">

                <div class="card-header">

                    <div>

                        <span class="section-label">
                            PROFILE OVERVIEW
                        </span>

                        <h2>
                            Your Profile
                        </h2>

                    </div>

                    <span class="status-badge">
                        Active
                    </span>

                </div>


                <div class="profile-details">

                    <div class="avatar">
                        L
                    </div>

                    <div>

                        <h3>
                            <?php echo htmlspecialchars($studentName); ?> Dead
                        </h3>

                        <p>
                            BS Computer Science
                        </p>

                        <span>
                            3rd Year Student
                        </span>

                    </div>

                </div>


                <a href="student_profile.php" class="text-link">
                    View full profile →
                </a>

            </div>



            <!-- Visibility -->

            <div class="overview-card visibility-card">

                <div class="card-header">

                    <div>

                        <span class="section-label">
                            PROFILE VISIBILITY
                        </span>

                        <h2>
                            School Only
                        </h2>

                    </div>

                    <div class="visibility-icon">
                        ✓
                    </div>

                </div>


                <p>
                    Your profile is currently visible to authorized
                    users within the school.
                </p>


                <a href="settings.php" class="text-link">
                    Manage privacy settings →
                </a>

            </div>

        </section>



        <!-- ================================
             Quick Actions
        ================================= -->

        <section class="quick-actions">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        QUICK ACTIONS
                    </span>

                    <h2>
                        What would you like to do?
                    </h2>

                </div>

            </div>


            <div class="action-grid">


                <!-- View Profile -->

                <a href="student_profile.php" class="action-card">

                    <div class="action-icon">
                        01
                    </div>

                    <div>

                        <h3>
                            View Profile
                        </h3>

                        <p>
                            Review your student information.
                        </p>

                    </div>

                    <span class="action-arrow">
                        →
                    </span>

                </a>



                <!-- Edit Profile -->

                <a
                    href="student_profile.php?edit=1"
                    class="action-card"
                >

                    <div class="action-icon">
                        02
                    </div>

                    <div>

                        <h3>
                            Edit Profile
                        </h3>

                        <p>
                            Update your personal and academic details.
                        </p>

                    </div>

                    <span class="action-arrow">
                        →
                    </span>

                </a>



                <!-- Add Achievement -->

                <a
                    href="accomplishments.php?action=add"
                    class="action-card"
                >

                    <div class="action-icon">
                        03
                    </div>

                    <div>

                        <h3>
                            Add Achievement
                        </h3>

                        <p>
                            Add a new accomplishment or certificate.
                        </p>

                    </div>

                    <span class="action-arrow">
                        →
                    </span>

                </a>

            </div>

        </section>



        <!-- ================================
             Recent Accomplishments
        ================================= -->

        <section class="accomplishments-section">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        RECENT ACCOMPLISHMENTS
                    </span>

                    <h2>
                        Your latest achievements
                    </h2>

                </div>


                <a href="accomplishments.php" class="text-link">
                    View all →
                </a>

            </div>


            <div class="accomplishment-list">

                <?php foreach ($recentAccomplishments as $accomplishment): ?>

                    <div class="accomplishment-item">

                        <div class="achievement-icon">
                            ✓
                        </div>


                        <div class="achievement-info">

                            <h3>
                                <?php echo htmlspecialchars($accomplishment["title"]); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($accomplishment["type"]); ?>
                            </p>

                        </div>


                        <span class="achievement-date">

                            <?php echo htmlspecialchars($accomplishment["date"]); ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    </main>



    <!-- ================================
         Footer
    ================================= -->

    <footer class="footer">

        <p>
            StudentProfiler
        </p>

        <span>
            Manage your student profile with ease.
        </span>

    </footer>


</body>
</html>