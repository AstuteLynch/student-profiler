<?php

$privacy = [
    "profile_visibility" => "School-only",
    "contact_information" => true,
    "family_information" => false,
    "address" => false,
    "academic_information" => true,
    "achievements" => true,
    "hobbies_interests" => true
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy & Visibility | StudentProfiler</title>
    <link rel="stylesheet" href="privacy.css">
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
        <span class="eyebrow">SETTINGS & PRIVACY</span>
        <h1>Privacy & Visibility</h1>
        <p>Control which parts of your student profile can be viewed by others.</p>
    </section>

    <section class="privacy-card">

        <div class="section-heading">
            <span class="section-label">PROFILE VISIBILITY</span>
            <h2>Who can view your profile?</h2>
            <p>Select the default visibility level for your student profile.</p>
        </div>

        <div class="visibility-options">

            <label class="visibility-option">
                <input type="radio" name="visibility" value="public">
                <div>
                    <strong>Public Profile</strong>
                    <span>Your profile can be viewed publicly.</span>
                </div>
            </label>

            <label class="visibility-option active">
                <input type="radio" name="visibility" value="school" checked>
                <div>
                    <strong>School-only Profile</strong>
                    <span>Your profile is visible only within the school.</span>
                </div>
            </label>

            <label class="visibility-option">
                <input type="radio" name="visibility" value="private">
                <div>
                    <strong>Private Profile</strong>
                    <span>Your profile is not publicly visible.</span>
                </div>
            </label>

        </div>

    </section>

    <section class="privacy-card">

        <div class="section-heading">
            <span class="section-label">INFORMATION VISIBILITY</span>
            <h2>Profile Sections</h2>
            <p>Choose which information can appear on your profile.</p>
        </div>

        <div class="privacy-list">

            <div class="privacy-item">
                <div>
                    <h3>Contact Information</h3>
                    <p>Phone number and email information.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>

            <div class="privacy-item">
                <div>
                    <h3>Family Information</h3>
                    <p>Parent, guardian, and family information.</p>
                </div>
                <label class="switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="privacy-item">
                <div>
                    <h3>Address</h3>
                    <p>Your residential address.</p>
                </div>
                <label class="switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="privacy-item">
                <div>
                    <h3>Academic Information</h3>
                    <p>Program, year level, section, and academic background.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>

            <div class="privacy-item">
                <div>
                    <h3>Achievements</h3>
                    <p>Accomplishments, awards, and certificates.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>

            <div class="privacy-item">
                <div>
                    <h3>Hobbies and Interests</h3>
                    <p>Your personal hobbies and interests.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
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