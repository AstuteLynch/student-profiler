<?php
//
// Digital Portfolio
// Backend/database functionality will be added later.
// Privacy settings will determine which sections
// are visible on the student's public portfolio.
//

// Temporary student data
$student = [
    "first_name" => "Lynch",
    "middle_name" => "Esplana",
    "last_name" => "",
    "program" => "Bachelor of Science in Computer Science",
    "year_level" => "3rd Year",
    "college" => "College of Computing Studies",
    "campus" => "CvSU Carmona",
    "address" => "Cavite, Philippines",
    "email" => "firstname.last@cvsu.edu.ph",
    "phone" => "+63 XXX XXX XXXX",

    "about_me" => "I am a Computer Science student interested in technology, programming, software development, and learning new things. I enjoy exploring different areas of computing and developing projects that allow me to improve my skills.",

    "elementary_school" => "Sample Elementary School",
    "junior_high_school" => "Sample Junior High School",
    "senior_high_school" => "Sample Senior High School"
];

// Temporary privacy settings
// true = visible
// false = hidden
$privacy = [
    "about_me" => true,
    "education" => true,
    "accomplishments" => true,
    "hobbies" => true,
    "organizations" => true,
    "experiences" => true
];

// Temporary accomplishments
$accomplishments = [
    [
        "title" => "Academic Achievement",
        "description" => "Recognized for academic performance and participation in academic activities.",
        "year" => "2025"
    ],
    [
        "title" => "Programming Project",
        "description" => "Developed a student-focused web application as part of an academic project.",
        "year" => "2026"
    ]
];

// Temporary hobbies and interests
$hobbies = [
    "Programming",
    "Web Development",
    "Technology",
    "Game Development",
    "Learning New Skills"
];

// Temporary organizations
$organizations = [
    [
        "name" => "Student Organization",
        "role" => "Member",
        "year" => "2025 - Present"
    ]
];

// Temporary experiences
$experiences = [
    [
        "title" => "Student Project",
        "organization" => "Cavite State University",
        "description" => "Participated in the planning, development, and testing of a student profile management system.",
        "year" => "2026"
    ]
];

$full_name = trim(
    $student["first_name"] . " " .
    $student["middle_name"] . " " .
    $student["last_name"]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Digital Portfolio | StudentProfiler</title>

    <link rel="stylesheet" href="digital_portfolio.css">
</head>

<body>

    <!--  Navigation -->

    <header class="navbar">

        <div class="nav-container">

            <a href="student_dashboard.php" class="brand">

                <div class="brand-mark">
                    SP
                </div>

                <div class="brand-text">

                    <span class="brand-name">
                        StudentProfiler
                    </span>

                    <span class="brand-subtitle">
                        Student Profile Management
                    </span>

                </div>

            </a>

            <nav class="desktop-nav">

                <a href="student_dashboard.php" class="nav-link">
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


    <!--Main Portfolio -->

    <main class="portfolio-page">

        <!-- Portfolio Header -->

        <section class="portfolio-header">

            <div class="profile-introduction">

                <div class="profile-photo">
                    L
                </div>

                <div class="profile-heading">

                    <span class="eyebrow">
                        DIGITAL PORTFOLIO
                    </span>

                    <h1>
                        <?php echo htmlspecialchars($full_name); ?>
                    </h1>

                    <p class="program">
                        <?php echo htmlspecialchars($student["program"]); ?>
                    </p>

                    <p class="student-meta">
                        <?php echo htmlspecialchars($student["year_level"]); ?>
                        <span>•</span>
                        <?php echo htmlspecialchars($student["college"]); ?>
                        <span>•</span>
                        <?php echo htmlspecialchars($student["campus"]); ?>
                    </p>

                </div>

            </div>

            <div class="portfolio-actions">

                <a href="student_profile.php" class="secondary-button">
                    ← Back to Profile
                </a>

                <button type="button" class="primary-button" onclick="window.print()">
                    Print Portfolio
                </button>

            </div>

        </section>


        <!-- Portfolio Content -->

        <div class="portfolio-layout">

            <!-- Main Content -->

            <div class="portfolio-main">


                <!--About Me-->

                <?php if ($privacy["about_me"]): ?>

                    <section class="portfolio-card">

                        <div class="section-heading">

                            <span class="section-label">
                                ABOUT ME
                            </span>

                            <h2>
                                About Me
                            </h2>

                        </div>

                        <p class="about-text">
                            <?php echo htmlspecialchars($student["about_me"]); ?>
                        </p>

                    </section>

                <?php endif; ?>


                <!-- Education-->

                <?php if ($privacy["education"]): ?>

                    <section class="portfolio-card">

                        <div class="section-heading">

                            <span class="section-label">
                                EDUCATION
                            </span>

                            <h2>
                                Educational Background
                            </h2>

                        </div>

                        <div class="education-list">

                            <div class="education-item">

                                <div class="education-icon">
                                    01
                                </div>

                                <div class="education-content">

                                    <span class="education-level">
                                        ELEMENTARY
                                    </span>

                                    <h3>
                                        <?php echo htmlspecialchars($student["elementary_school"]); ?>
                                    </h3>

                                    <p>
                                        Elementary Education
                                    </p>

                                </div>

                            </div>


                            <div class="education-item">

                                <div class="education-icon">
                                    02
                                </div>

                                <div class="education-content">

                                    <span class="education-level">
                                        JUNIOR HIGH SCHOOL
                                    </span>

                                    <h3>
                                        <?php echo htmlspecialchars($student["junior_high_school"]); ?>
                                    </h3>

                                    <p>
                                        Junior High School Education
                                    </p>

                                </div>

                            </div>


                            <div class="education-item">

                                <div class="education-icon">
                                    03
                                </div>

                                <div class="education-content">

                                    <span class="education-level">
                                        SENIOR HIGH SCHOOL
                                    </span>

                                    <h3>
                                        <?php echo htmlspecialchars($student["senior_high_school"]); ?>
                                    </h3>

                                    <p>
                                        Senior High School Education
                                    </p>

                                </div>

                            </div>


                            <div class="education-item current">

                                <div class="education-icon">
                                    04
                                </div>

                                <div class="education-content">

                                    <span class="education-level">
                                        CURRENT EDUCATION
                                    </span>

                                    <h3>
                                        <?php echo htmlspecialchars($student["program"]); ?>
                                    </h3>

                                    <p>
                                        <?php echo htmlspecialchars($student["college"]); ?>
                                        •
                                        <?php echo htmlspecialchars($student["campus"]); ?>
                                    </p>

                                    <span class="current-status">
                                        <?php echo htmlspecialchars($student["year_level"]); ?>
                                    </span>

                                </div>

                            </div>

                        </div>

                    </section>

                <?php endif; ?>


                <!--Accomplishments-->

                <?php if ($privacy["accomplishments"]): ?>

                    <section class="portfolio-card">

                        <div class="section-heading">

                            <span class="section-label">
                                ACCOMPLISHMENTS
                            </span>

                            <h2>
                                Achievements & Accomplishments
                            </h2>

                        </div>

                        <div class="accomplishment-list">

                            <?php foreach ($accomplishments as $item): ?>

                                <div class="accomplishment-item">

                                    <div class="accomplishment-icon">
                                        ✓
                                    </div>

                                    <div class="accomplishment-content">

                                        <div class="item-header">

                                            <h3>
                                                <?php echo htmlspecialchars($item["title"]); ?>
                                            </h3>

                                            <span class="item-year">
                                                <?php echo htmlspecialchars($item["year"]); ?>
                                            </span>

                                        </div>

                                        <p>
                                            <?php echo htmlspecialchars($item["description"]); ?>
                                        </p>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endif; ?>


                <!-- hobbies & Interests-->

                <?php if ($privacy["hobbies"]): ?>

                    <section class="portfolio-card">

                        <div class="section-heading">

                            <span class="section-label">
                                HOBBIES & INTERESTS
                            </span>

                            <h2>
                                Hobbies and Interests
                            </h2>

                        </div>

                        <div class="interest-list">

                            <?php foreach ($hobbies as $hobby): ?>

                                <span class="interest-tag">
                                    <?php echo htmlspecialchars($hobby); ?>
                                </span>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endif; ?>


                <!--Organizations-->

                <?php if ($privacy["organizations"]): ?>

                    <section class="portfolio-card">

                        <div class="section-heading">

                            <span class="section-label">
                                ORGANIZATIONS
                            </span>

                            <h2>
                                Organizations & Activities
                            </h2>

                        </div>

                        <div class="organization-list">

                            <?php foreach ($organizations as $organization): ?>

                                <div class="organization-item">

                                    <div class="organization-icon">
                                        O
                                    </div>

                                    <div class="organization-content">

                                        <h3>
                                            <?php echo htmlspecialchars($organization["name"]); ?>
                                        </h3>

                                        <p>
                                            <?php echo htmlspecialchars($organization["role"]); ?>
                                        </p>

                                        <span>
                                            <?php echo htmlspecialchars($organization["year"]); ?>
                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endif; ?>


                <!--Experiences-->

                <?php if ($privacy["experiences"]): ?>

                    <section class="portfolio-card">

                        <div class="section-heading">

                            <span class="section-label">
                                EXPERIENCES
                            </span>

                            <h2>
                                Experiences
                            </h2>

                        </div>

                        <div class="experience-list">

                            <?php foreach ($experiences as $experience): ?>

                                <div class="experience-item">

                                    <div class="experience-marker">
                                    </div>

                                    <div class="experience-content">

                                        <div class="experience-header">

                                            <div>

                                                <h3>
                                                    <?php echo htmlspecialchars($experience["title"]); ?>
                                                </h3>

                                                <p class="experience-organization">
                                                    <?php echo htmlspecialchars($experience["organization"]); ?>
                                                </p>

                                            </div>

                                            <span class="item-year">
                                                <?php echo htmlspecialchars($experience["year"]); ?>
                                            </span>

                                        </div>

                                        <p class="experience-description">
                                            <?php echo htmlspecialchars($experience["description"]); ?>
                                        </p>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </section>

                <?php endif; ?>


            </div>


            <!--Sidebar-->

            <aside class="portfolio-sidebar">


                <!-- Contact -->

                <section class="sidebar-card">

                    <span class="section-label">
                        CONTACT
                    </span>

                    <h2>
                        Contact Information
                    </h2>

                    <div class="contact-list">

                        <div class="contact-item">

                            <span class="contact-label">
                                EMAIL
                            </span>

                            <p>
                                <?php echo htmlspecialchars($student["email"]); ?>
                            </p>

                        </div>


                        <div class="contact-item">

                            <span class="contact-label">
                                PHONE
                            </span>

                            <p>
                                <?php echo htmlspecialchars($student["phone"]); ?>
                            </p>

                        </div>


                        <div class="contact-item">

                            <span class="contact-label">
                                LOCATION
                            </span>

                            <p>
                                <?php echo htmlspecialchars($student["address"]); ?>
                            </p>

                        </div>

                    </div>

                </section>


                <!-- Academic Summary -->

                <section class="sidebar-card">

                    <span class="section-label">
                        ACADEMIC PROFILE
                    </span>

                    <h2>
                        Academic Summary
                    </h2>

                    <div class="summary-list">

                        <div class="summary-item">

                            <span>
                                Program
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($student["program"]); ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                Year Level
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($student["year_level"]); ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                College
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($student["college"]); ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                Campus
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($student["campus"]); ?>
                            </strong>

                        </div>

                    </div>

                </section>


                <!-- Privacy Notice -->

                <section class="privacy-card">

                    <div class="privacy-icon">
                        ✓
                    </div>

                    <div>

                        <h3>
                            Privacy Protected
                        </h3>

                        <p>
                            This portfolio only displays information that is permitted by the student's privacy settings.
                        </p>

                    </div>

                </section>


                <!-- Edit Profile -->

                <a href="edit_profile.php" class="edit-profile-button">
                    Edit Profile
                </a>

            </aside>

        </div>

    </main>


    <!--Footer-->

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