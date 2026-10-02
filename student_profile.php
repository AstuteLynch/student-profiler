<?php

session_start();

require_once "db.php";


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


$userId = (int) $_SESSION["user_id"];


/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET["logout"])) {

    $_SESSION = [];


    if (ini_get("session.use_cookies")) {

        $params =
            session_get_cookie_params();


        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }


    session_destroy();


    header("Location: login.php");
    exit;
}


/* =========================================================
   GET STUDENT PROFILE

   Main student information comes from:

   users
   student_profiles
   family_information
========================================================= */

$stmt = $conn->prepare("
    SELECT

        u.email,
        u.account_status,

        sp.first_name,
        sp.middle_name,
        sp.last_name,
        sp.student_id,
        sp.phone,
        sp.birthdate,
        sp.gender,
        sp.address,

        sp.program,
        sp.year_level,
        sp.section,
        sp.college,
        sp.campus,

        sp.profile_photo,
        sp.about_me,

        fi.father_name,
        fi.mother_name,
        fi.guardian_name,
        fi.guardian_contact

    FROM users u

    LEFT JOIN student_profiles sp
        ON sp.user_id = u.id

    LEFT JOIN family_information fi
        ON fi.user_id = u.id

    WHERE u.id = ?

    LIMIT 1
");


if (!$stmt) {

    die(
        "Profile database error: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


$stmt->bind_param(
    "i",
    $userId
);


$stmt->execute();


$student =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (!$student) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================================================
   GET EDUCATIONAL BACKGROUND
========================================================= */

$education = [

    "elementary" => "",
    "junior_high" => "",
    "senior_high" => ""
];


$educationStmt = $conn->prepare("
    SELECT
        education_level,
        school_name

    FROM education

    WHERE user_id = ?

    AND education_level IN (
        'elementary',
        'junior_high',
        'senior_high'
    )

    ORDER BY id ASC
");


if ($educationStmt) {

    $educationStmt->bind_param(
        "i",
        $userId
    );


    $educationStmt->execute();


    $educationResult =
        $educationStmt->get_result();


    while (
        $educationRow =
            $educationResult->fetch_assoc()
    ) {

        $level =
            $educationRow[
                "education_level"
            ];


        if (
            array_key_exists(
                $level,
                $education
            )
        ) {

            $education[$level] =
                trim(
                    $educationRow[
                        "school_name"
                    ] ?? ""
                );
        }
    }


    $educationStmt->close();
}


/* =========================================================
   PROFILE VALUES
========================================================= */

$firstName =
    trim(
        $student["first_name"] ?? ""
    );


$middleName =
    trim(
        $student["middle_name"] ?? ""
    );


$lastName =
    trim(
        $student["last_name"] ?? ""
    );


$studentId =
    trim(
        $student["student_id"] ?? ""
    );


$email =
    trim(
        $student["email"] ?? ""
    );


$phone =
    trim(
        $student["phone"] ?? ""
    );


$birthdate =
    trim(
        $student["birthdate"] ?? ""
    );


$gender =
    trim(
        $student["gender"] ?? ""
    );


$address =
    trim(
        $student["address"] ?? ""
    );


$program =
    trim(
        $student["program"] ?? ""
    );


$yearLevel =
    trim(
        $student["year_level"] ?? ""
    );


$section =
    trim(
        $student["section"] ?? ""
    );


$college =
    trim(
        $student["college"] ?? ""
    );


$campus =
    trim(
        $student["campus"] ?? ""
    );


$profilePhoto =
    trim(
        $student["profile_photo"] ?? ""
    );


$aboutMe =
    trim(
        $student["about_me"] ?? ""
    );


$fatherName =
    trim(
        $student["father_name"] ?? ""
    );


$motherName =
    trim(
        $student["mother_name"] ?? ""
    );


$guardianName =
    trim(
        $student["guardian_name"] ?? ""
    );


$guardianContact =
    trim(
        $student["guardian_contact"] ?? ""
    );


$elementarySchool =
    trim(
        $education["elementary"]
    );


$juniorHighSchool =
    trim(
        $education["junior_high"]
    );


$seniorHighSchool =
    trim(
        $education["senior_high"]
    );


/* =========================================================
   FULL NAME
========================================================= */

$nameParts = [];


if ($firstName !== "") {
    $nameParts[] = $firstName;
}


if ($middleName !== "") {
    $nameParts[] = $middleName;
}


if ($lastName !== "") {
    $nameParts[] = $lastName;
}


$fullName =
    trim(
        implode(
            " ",
            $nameParts
        )
    );


if ($fullName === "") {
    $fullName = "Student";
}


/* =========================================================
   AVATAR INITIAL
========================================================= */

$avatarSource =
    $firstName !== ""
        ? $firstName
        : $fullName;


$avatarInitial =
    strtoupper(
        substr(
            $avatarSource,
            0,
            1
        )
    );


/* =========================================================
   ACCOUNT STATUS
========================================================= */

$accountStatus =
    strtolower(
        trim(
            $student[
                "account_status"
            ] ?? "pending"
        )
    );


if ($accountStatus === "active") {

    $accountStatusDisplay =
        "Profile Active";

} elseif ($accountStatus === "pending") {

    $accountStatusDisplay =
        "Profile Pending";

} elseif ($accountStatus === "suspended") {

    $accountStatusDisplay =
        "Profile Suspended";

} elseif ($accountStatus === "archived") {

    $accountStatusDisplay =
        "Profile Archived";

} elseif ($accountStatus === "deactivated") {

    $accountStatusDisplay =
        "Profile Deactivated";

} else {

    $accountStatusDisplay =
        ucfirst(
            $accountStatus
        );
}


/* =========================================================
   BIRTHDATE DISPLAY
========================================================= */

$birthdateDisplay = "";


if ($birthdate !== "") {

    $birthTimestamp =
        strtotime(
            $birthdate
        );


    if ($birthTimestamp !== false) {

        $birthdateDisplay =
            date(
                "F j, Y",
                $birthTimestamp
            );
    }
}


/* =========================================================
   DISPLAY HELPER
========================================================= */

function profileValue(
    ?string $value,
    string $fallback = "Not added"
): string {

    $value =
        trim(
            (string) $value
        );


    return $value !== ""
        ? $value
        : $fallback;
}

?>

<!DOCTYPE html>

<html lang="en">


<head>

    <meta charset="UTF-8">


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <title>
        My Profile | CVSWHO
    </title>


    <link
        rel="stylesheet"
        href="student_profile.css"
    >

</head>


<body>


<!-- =================================
     NAVIGATION
================================= -->

<header class="navbar">


    <div class="nav-container">


        <a
            href="student_dashboard.php"
            class="brand"
        >


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
            href="student_profile.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>


</header>


<!-- =================================
     MAIN PROFILE
================================= -->

<main class="profile-page">


    <!-- =================================
         PAGE HEADER
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
                View and manage your personal,
                academic, family, and educational
                information.
            </p>


        </div>


        <div class="header-actions">


            <a
                href="edit_profile.php"
                class="primary-button"
            >
                Edit Profile
            </a>


            <a
                href="privacy.php"
                class="secondary-button"
            >
                Manage Visibility
            </a>


        </div>


    </section>


    <!-- =================================
         PROFILE HEADER
    ================================= -->

    <section class="profile-header-card">


        <div class="profile-photo-container">


            <?php if ($profilePhoto !== ""): ?>


                <div
                    class="profile-photo"
                    style="
                        background-image:
                            url('<?= htmlspecialchars(
                                $profilePhoto,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>');

                        background-size: cover;
                        background-position: center;

                        color: transparent;
                    "
                >
                    <?= htmlspecialchars(
                        $avatarInitial
                    ) ?>
                </div>


            <?php else: ?>


                <div class="profile-photo">

                    <span>
                        <?= htmlspecialchars(
                            $avatarInitial
                        ) ?>
                    </span>

                </div>


            <?php endif; ?>


            <a
                href="edit_profile.php"
                class="photo-button"
            >
                Edit Profile
            </a>


        </div>


        <div class="profile-summary">


            <span class="section-label">
                STUDENT PROFILE
            </span>


            <h2>
                <?= htmlspecialchars(
                    $fullName
                ) ?>
            </h2>


            <p>
                <?= htmlspecialchars(
                    profileValue(
                        $program,
                        "Program not added"
                    )
                ) ?>
            </p>


            <div class="summary-details">


                <span>
                    <?= htmlspecialchars(
                        profileValue(
                            $studentId,
                            "Student ID not added"
                        )
                    ) ?>
                </span>


                <span>
                    <?= htmlspecialchars(
                        profileValue(
                            $yearLevel,
                            "Year level not added"
                        )
                    ) ?>
                </span>


                <span class="profile-status">
                    <?= htmlspecialchars(
                        $accountStatusDisplay
                    ) ?>
                </span>


            </div>


        </div>


        <div class="portfolio-action">


            <a
                href="digital_portfolio.php"
                class="portfolio-button"
            >

                View Digital Portfolio

                <span>
                    →
                </span>

            </a>


        </div>


    </section>


    <!-- =================================
         ABOUT ME
    ================================= -->

    <section class="information-card">


        <div class="section-heading">


            <div>


                <span class="section-label">
                    ABOUT ME
                </span>


                <h2>
                    Profile Introduction
                </h2>


            </div>


        </div>


        <div class="information-grid">


            <div
                class="
                    information-item
                    full-width
                "
            >


                <span class="information-label">
                    About Me
                </span>


                <p>
                    <?= nl2br(
                        htmlspecialchars(
                            profileValue(
                                $aboutMe,
                                "No introduction added yet."
                            )
                        )
                    ) ?>
                </p>


            </div>


        </div>


    </section>


    <!-- =================================
         PERSONAL INFORMATION
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
                    <?= htmlspecialchars(
                        profileValue(
                            $firstName
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Middle Name
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $middleName
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Last Name
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $lastName
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Student ID
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $studentId
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Email Address
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $email
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Phone Number
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $phone
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Birthdate
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $birthdateDisplay
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Gender
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $gender
                        )
                    ) ?>
                </p>


            </div>


            <div
                class="
                    information-item
                    full-width
                "
            >


                <span class="information-label">
                    Address
                </span>


                <p>
                    <?= nl2br(
                        htmlspecialchars(
                            profileValue(
                                $address
                            )
                        )
                    ) ?>
                </p>


            </div>


        </div>


    </section>


    <!-- =================================
         ACADEMIC INFORMATION
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


            <div
                class="
                    information-item
                    full-width
                "
            >


                <span class="information-label">
                    Program
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $program
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Year Level
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $yearLevel
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Section
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $section
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    College
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $college
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Campus
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $campus
                        )
                    ) ?>
                </p>


            </div>


        </div>


    </section>


    <!-- =================================
         FAMILY INFORMATION
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
                    <?= htmlspecialchars(
                        profileValue(
                            $fatherName
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Mother's Name
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $motherName
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Guardian's Name
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $guardianName
                        )
                    ) ?>
                </p>


            </div>


            <div class="information-item">


                <span class="information-label">
                    Guardian Contact
                </span>


                <p>
                    <?= htmlspecialchars(
                        profileValue(
                            $guardianContact
                        )
                    ) ?>
                </p>


            </div>


        </div>


    </section>


    <!-- =================================
         EDUCATIONAL BACKGROUND
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
                        <?= htmlspecialchars(
                            profileValue(
                                $elementarySchool
                            )
                        ) ?>
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
                        <?= htmlspecialchars(
                            profileValue(
                                $juniorHighSchool
                            )
                        ) ?>
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
                        <?= htmlspecialchars(
                            profileValue(
                                $seniorHighSchool
                            )
                        ) ?>
                    </h3>


                </div>


            </div>


        </div>


    </section>


    <!-- =================================
         BOTTOM ACTIONS
    ================================= -->

    <section class="bottom-actions">


        <a
            href="edit_profile.php"
            class="primary-button"
        >
            Edit Profile
        </a>


        <a
            href="privacy.php"
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


<!-- =================================
     FOOTER
================================= -->

<footer class="footer">


    <p>
        CVSWHO
    </p>


    <span>
        Manage your student profile with ease.
    </span>


</footer>


</body>

</html>