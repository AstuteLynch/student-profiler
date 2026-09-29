<?php
// Edit Student Profile
// Backend/database functionality will be added later.

// Temporary placeholder data
$student = [
    "first_name" => "Lynch",
    "middle_name" => "Esplana",
    "last_name" => "",

    "student_id" => "2023-XXXXX",
    "email" => "firstname.last@cvsu.edu.ph",
    "phone" => "+63 XXX XXX XXXX",
    "birthdate" => "2000-01-01",
    "gender" => "",
    "address" => "Cavite, Philippines",

    "program" => "Bachelor of Science in Computer Science",
    "year_level" => "3rd Year",
    "section" => "",
    "college" => "College of Computing Studies",
    "campus" => "CvSU Carmona",

    "father_name" => "",
    "mother_name" => "",
    "guardian_name" => "",
    "guardian_contact" => "",

    "elementary_school" => "",
    "junior_high_school" => "",
    "senior_high_school" => ""
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

    <title>Edit Profile | StudentProfiler</title>

    <link
        rel="stylesheet"
        href="edit_profile.css"
    >

</head>

<body>


    <!--Navigation-->

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



    <!--Main Content -->

    <main class="edit-page">


        <!--Page Header-->

        <section class="page-header">

            <div>

                <span class="eyebrow">
                    MY PROFILE
                </span>

                <h1>
                    Edit Profile
                </h1>

                <p>
                    Update your personal, academic, family,
                    and educational information.
                </p>

            </div>


            <a
                href="student_profile.php"
                class="back-button"
            >
                ← Back to Profile
            </a>

        </section>



        <!--Profile Photo -->

        <section class="photo-card">

            <div class="photo-preview">

                <div class="profile-photo">
                    L
                </div>

            </div>


            <div class="photo-information">

                <span class="section-label">
                    PROFILE PHOTO
                </span>

                <h2>
                    Your Profile Photo
                </h2>

                <p>
                    Use a clear photo that represents you.
                    This photo may appear on your student profile
                    and digital portfolio.
                </p>

                <button
                    type="button"
                    class="outline-button"
                >
                    Change Photo
                </button>

            </div>

        </section>



        <!--Edit Form -->

        <form
            action=""
            method="POST"
            class="profile-form"
        >


            <!--Personal Information-->

            <section class="form-card">

                <div class="section-heading">

                    <span class="section-label">
                        PERSONAL INFORMATION
                    </span>

                    <h2>
                        Personal Details
                    </h2>

                    <p>
                        Basic information about you.
                    </p>

                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?php echo htmlspecialchars($student["first_name"]); ?>"
                            placeholder="Enter your first name"
                            autocomplete="given-name"
                            required
                        >

                    </div>



                    <div class="form-group">

                        <label for="middle_name">
                            Middle Name
                        </label>

                        <input
                            type="text"
                            id="middle_name"
                            name="middle_name"
                            value="<?php echo htmlspecialchars($student["middle_name"]); ?>"
                            placeholder="Enter your middle name"
                            autocomplete="additional-name"
                        >

                    </div>



                    <div class="form-group">

                        <label for="last_name">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?php echo htmlspecialchars($student["last_name"]); ?>"
                            placeholder="Enter your last name"
                            autocomplete="family-name"
                            required
                        >

                    </div>



                    <div class="form-group">

                        <label for="student_id">
                            Student ID
                        </label>

                        <input
                            type="text"
                            id="student_id"
                            name="student_id"
                            value="<?php echo htmlspecialchars($student["student_id"]); ?>"
                            readonly
                        >

                        <small>
                            Student ID cannot be changed here.
                        </small>

                    </div>



                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($student["email"]); ?>"
                            readonly
                        >

                        <small>
                            Your CVSU email is managed through your account.
                        </small>

                    </div>



                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?php echo htmlspecialchars($student["phone"]); ?>"
                            placeholder="+63 XXX XXX XXXX"
                            autocomplete="tel"
                        >

                    </div>



                    <div class="form-group">

                        <label for="birthdate">
                            Birthdate
                        </label>

                        <input
                            type="date"
                            id="birthdate"
                            name="birthdate"
                            value="<?php echo htmlspecialchars($student["birthdate"]); ?>"
                        >

                    </div>



                    <div class="form-group">

                        <label for="gender">
                            Gender
                        </label>

                        <select
                            id="gender"
                            name="gender"
                        >

                            <option value="">
                                Select gender
                            </option>

                            <option
                                value="Male"
                                <?php echo $student["gender"] === "Male" ? "selected" : ""; ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?php echo $student["gender"] === "Female" ? "selected" : ""; ?>
                            >
                                Female
                            </option>

                            <option
                                value="Prefer not to say"
                                <?php echo $student["gender"] === "Prefer not to say" ? "selected" : ""; ?>
                            >
                                Prefer not to say
                            </option>

                        </select>

                    </div>



                    <div class="form-group full-width">

                        <label for="address">
                            Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            placeholder="Enter your complete address"
                        ><?php echo htmlspecialchars($student["address"]); ?></textarea>

                    </div>

                </div>

            </section>



            <!--Academic Information-->

            <section class="form-card">

                <div class="section-heading">

                    <span class="section-label">
                        ACADEMIC INFORMATION
                    </span>

                    <h2>
                        Academic Details
                    </h2>

                    <p>
                        Information about your current studies.
                    </p>

                </div>


                <div class="form-grid">


                    <div class="form-group full-width">

                        <label for="program">
                            Program
                        </label>

                        <input
                            type="text"
                            id="program"
                            name="program"
                            value="<?php echo htmlspecialchars($student["program"]); ?>"
                            placeholder="Enter your degree program"
                        >

                    </div>



                    <div class="form-group">

                        <label for="year_level">
                            Year Level
                        </label>

                        <select
                            id="year_level"
                            name="year_level"
                        >

                            <option value="">
                                Select year level
                            </option>

                            <option
                                value="1st Year"
                                <?php echo $student["year_level"] === "1st Year" ? "selected" : ""; ?>
                            >
                                1st Year
                            </option>

                            <option
                                value="2nd Year"
                                <?php echo $student["year_level"] === "2nd Year" ? "selected" : ""; ?>
                            >
                                2nd Year
                            </option>

                            <option
                                value="3rd Year"
                                <?php echo $student["year_level"] === "3rd Year" ? "selected" : ""; ?>
                            >
                                3rd Year
                            </option>

                            <option
                                value="4th Year"
                                <?php echo $student["year_level"] === "4th Year" ? "selected" : ""; ?>
                            >
                                4th Year
                            </option>

                        </select>

                    </div>



                    <div class="form-group">

                        <label for="section">
                            Section
                        </label>

                        <input
                            type="text"
                            id="section"
                            name="section"
                            value="<?php echo htmlspecialchars($student["section"]); ?>"
                            placeholder="Enter your section"
                        >

                    </div>



                    <div class="form-group">

                        <label for="college">
                            College
                        </label>

                        <input
                            type="text"
                            id="college"
                            name="college"
                            value="<?php echo htmlspecialchars($student["college"]); ?>"
                            placeholder="Enter your college"
                        >

                    </div>



                    <div class="form-group">

                        <label for="campus">
                            Campus
                        </label>

                        <input
                            type="text"
                            id="campus"
                            name="campus"
                            value="<?php echo htmlspecialchars($student["campus"]); ?>"
                            placeholder="Enter your campus"
                        >

                    </div>

                </div>

            </section>



            <!--Family Information-->

            <section class="form-card">

                <div class="section-heading">

                    <span class="section-label">
                        FAMILY INFORMATION
                    </span>

                    <h2>
                        Family Details
                    </h2>

                    <p>
                        Information about your family and guardian.
                    </p>

                </div>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="father_name">
                            Father's Name
                        </label>

                        <input
                            type="text"
                            id="father_name"
                            name="father_name"
                            value="<?php echo htmlspecialchars($student["father_name"]); ?>"
                            placeholder="Enter father's name"
                        >

                    </div>



                    <div class="form-group">

                        <label for="mother_name">
                            Mother's Name
                        </label>

                        <input
                            type="text"
                            id="mother_name"
                            name="mother_name"
                            value="<?php echo htmlspecialchars($student["mother_name"]); ?>"
                            placeholder="Enter mother's name"
                        >

                    </div>



                    <div class="form-group">

                        <label for="guardian_name">
                            Guardian's Name
                        </label>

                        <input
                            type="text"
                            id="guardian_name"
                            name="guardian_name"
                            value="<?php echo htmlspecialchars($student["guardian_name"]); ?>"
                            placeholder="Enter guardian's name"
                        >

                    </div>



                    <div class="form-group">

                        <label for="guardian_contact">
                            Guardian Contact
                        </label>

                        <input
                            type="tel"
                            id="guardian_contact"
                            name="guardian_contact"
                            value="<?php echo htmlspecialchars($student["guardian_contact"]); ?>"
                            placeholder="+63 XXX XXX XXXX"
                        >

                    </div>

                </div>

            </section>



            <!--Educational Background-->

            <section class="form-card">

                <div class="section-heading">

                    <span class="section-label">
                        EDUCATIONAL BACKGROUND
                    </span>

                    <h2>
                        Previous Education
                    </h2>

                    <p>
                        Add the schools you attended before college.
                    </p>

                </div>


                <div class="education-form-list">


                    <div class="education-form-item">

                        <div class="education-number">
                            01
                        </div>

                        <div class="form-group">

                            <label for="elementary_school">
                                Elementary School
                            </label>

                            <input
                                type="text"
                                id="elementary_school"
                                name="elementary_school"
                                value="<?php echo htmlspecialchars($student["elementary_school"]); ?>"
                                placeholder="Enter elementary school"
                            >

                        </div>

                    </div>



                    <div class="education-form-item">

                        <div class="education-number">
                            02
                        </div>

                        <div class="form-group">

                            <label for="junior_high_school">
                                Junior High School
                            </label>

                            <input
                                type="text"
                                id="junior_high_school"
                                name="junior_high_school"
                                value="<?php echo htmlspecialchars($student["junior_high_school"]); ?>"
                                placeholder="Enter junior high school"
                            >

                        </div>

                    </div>



                    <div class="education-form-item">

                        <div class="education-number">
                            03
                        </div>

                        <div class="form-group">

                            <label for="senior_high_school">
                                Senior High School
                            </label>

                            <input
                                type="text"
                                id="senior_high_school"
                                name="senior_high_school"
                                value="<?php echo htmlspecialchars($student["senior_high_school"]); ?>"
                                placeholder="Enter senior high school"
                            >

                        </div>

                    </div>

                </div>

            </section>



            <!--Form Actions-->

            <div class="form-actions">

                <a
                    href="student_profile.php"
                    class="cancel-button"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="save-button"
                >
                    Save Changes
                </button>

            </div>


        </form>

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