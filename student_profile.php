<?php
// Student Profile
// Backend/database functionality will be added later.

// Temporary placeholder data
$student = [
    "first_name" => "Lynch",
    "middle_name" => "Esplana",
    "last_name" => "",
    "student_id" => "2023-XXXXX",
    "email" => "firstname.last@cvsu.edu.ph",
    "phone" => "+63 XXX XXX XXXX",
    "birthdate" => "January 1, 2000",
    "gender" => "Not specified",
    "address" => "Cavite, Philippines",

    "program" => "Bachelor of Science in Computer Science",
    "year_level" => "3rd Year",
    "section" => "Not specified",
    "college" => "College of Computing Studies",
    "campus" => "CvSU Carmona",

    "father_name" => "Not specified",
    "mother_name" => "Not specified",
    "guardian_name" => "Not specified",
    "guardian_contact" => "Not specified",

    "elementary_school" => "Not specified",
    "junior_high_school" => "Not specified",
    "senior_high_school" => "Not specified"
];
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile | StudentProfiler</title>

    <link
        rel="stylesheet"
        href="student_profile.css"
    >

</head>

<body>


    <!-- ================================
         Navigation
    ================================= -->

    <header class="navbar">

        <div class="nav-container">


            <a
                href="student_dashboard.php"
                class="brand"
            >

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

                <a
                    href="student_dashboard.php"
                    class="nav-link"
                >
                    Dashboard
                </a>

                <a
                    href="student_profile.php"
                    class="nav-link active"
                >
                    Profile
                </a>

                <a
                    href="accomplishments.php"
                    class="nav-link"
                >
                    Accomplishments
                </a>

                <a
                    href="settings.php"
                    class="nav-link"
                >
                    Settings
                </a>

            </nav>


            <a
                href="login.php"
                class="logout-button"
            >
                Log out
            </a>

        </div>

    </header>



    <!-- ================================
         Main Profile
    ================================= -->

    <main class="profile-page">


        <!-- ================================
             Page Header
        ================================= -->

        <section class="page-header">

            <div>

                <span class="eyebrow">
                    MY PROFILE
                </span>

                <h1>
                    Student Profile
                </h1>

                <p>
                    View and manage your personal, academic,
                    family, and educational information.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="student_profile.php?edit=1"
                    class="primary-button"
                >
                    Edit Profile
                </a>

                <a
                    href="settings.php#visibility"
                    class="secondary-button"
                >
                    Manage Visibility
                </a>

            </div>

        </section>



        <!-- ================================
             Profile Header Card
        ================================= -->

        <section class="profile-header-card">


            <div class="profile-photo-container">

                <div class="profile-photo">

                    <span>
                        L
                    </span>

                </div>


                <button
                    type="button"
                    class="photo-button"
                >
                    Change Photo
                </button>

            </div>



            <div class="profile-summary">

                <span class="section-label">
                    STUDENT PROFILE
                </span>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $student["first_name"] . " " .
                        $student["middle_name"]
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    echo htmlspecialchars($student["program"]);
                    ?>
                </p>

                <div class="summary-details">

                    <span>
                        <?php
                        echo htmlspecialchars($student["student_id"]);
                        ?>
                    </span>

                    <span>
                        <?php
                        echo htmlspecialchars($student["year_level"]);
                        ?>
                    </span>

                    <span class="profile-status">
                        Profile Active
                    </span>

                </div>

            </div>



            <div class="portfolio-action">

                <a
                    href="digital_portfolio.php"
                    class="portfolio-button"
                >
                    View Digital Portfolio
                    <span>→</span>
                </a>

            </div>

        </section>



        <!-- ================================
             Personal Information
        ================================= -->

        <section class="information-card">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        PERSONAL INFORMATION
                    </span>

                    <h2>
                        Personal Details
                    </h2>

                </div>

            </div>


            <div class="information-grid">


                <div class="information-item">

                    <span class="information-label">
                        First Name
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["first_name"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Middle Name
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["middle_name"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Last Name
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["last_name"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Student ID
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["student_id"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Email Address
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["email"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Phone Number
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["phone"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Birthdate
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["birthdate"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Gender
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["gender"]);
                        ?>
                    </p>

                </div>


                <div class="information-item full-width">

                    <span class="information-label">
                        Address
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["address"]);
                        ?>
                    </p>

                </div>

            </div>

        </section>



        <!-- ================================
             Academic Information
        ================================= -->

        <section class="information-card">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        ACADEMIC INFORMATION
                    </span>

                    <h2>
                        Current Academic Details
                    </h2>

                </div>

            </div>


            <div class="information-grid">


                <div class="information-item full-width">

                    <span class="information-label">
                        Program
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["program"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Year Level
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["year_level"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Section
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["section"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        College
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["college"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Campus
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["campus"]);
                        ?>
                    </p>

                </div>

            </div>

        </section>



        <!-- ================================
             Family Information
        ================================= -->

        <section class="information-card">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        FAMILY INFORMATION
                    </span>

                    <h2>
                        Family Details
                    </h2>

                </div>

            </div>


            <div class="information-grid">


                <div class="information-item">

                    <span class="information-label">
                        Father's Name
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["father_name"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Mother's Name
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["mother_name"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Guardian's Name
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["guardian_name"]);
                        ?>
                    </p>

                </div>


                <div class="information-item">

                    <span class="information-label">
                        Guardian Contact
                    </span>

                    <p>
                        <?php
                        echo htmlspecialchars($student["guardian_contact"]);
                        ?>
                    </p>

                </div>

            </div>

        </section>



        <!-- ================================
             Educational Background
        ================================= -->

        <section class="information-card">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        EDUCATIONAL BACKGROUND
                    </span>

                    <h2>
                        Previous Education
                    </h2>

                </div>

            </div>


            <div class="education-list">


                <div class="education-item">

                    <div class="education-number">
                        01
                    </div>

                    <div class="education-details">

                        <span>
                            ELEMENTARY
                        </span>

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $student["elementary_school"]
                            );
                            ?>
                        </h3>

                    </div>

                </div>



                <div class="education-item">

                    <div class="education-number">
                        02
                    </div>

                    <div class="education-details">

                        <span>
                            JUNIOR HIGH SCHOOL
                        </span>

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $student["junior_high_school"]
                            );
                            ?>
                        </h3>

                    </div>

                </div>



                <div class="education-item">

                    <div class="education-number">
                        03
                    </div>

                    <div class="education-details">

                        <span>
                            SENIOR HIGH SCHOOL
                        </span>

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $student["senior_high_school"]
                            );
                            ?>
                        </h3>

                    </div>

                </div>

            </div>

        </section>



        <!-- ================================
             Bottom Actions
        ================================= -->

        <section class="bottom-actions">

            <a
                href="student_profile.php?edit=1"
                class="primary-button"
            >
                Edit Profile
            </a>

            <a
                href="settings.php#visibility"
                class="secondary-button"
            >
                Manage Visibility
            </a>

            <a
                href="digital_portfolio.php"
                class="portfolio-button"
            >
                View Digital Portfolio →
            </a>

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