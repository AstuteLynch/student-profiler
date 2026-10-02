<?php

session_start();

require_once "db.php";


/* LOGIN */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


$userId =
    (int) $_SESSION["user_id"];


/* LOGOUT */

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


    header("Location: index.php");
    exit;
}


/* STUDENT */

$stmt =
    $conn->prepare("
        SELECT

            u.email,
            u.role,
            u.account_status,
            u.email_verified,
            u.created_at,

            sp.student_id,
            sp.first_name,
            sp.middle_name,
            sp.last_name,
            sp.suffix,

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
            sp.profile_completion,

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


/* ACCOUNT STATUS */

if (
    $student["account_status"] !== "active" ||
    (int) $student["email_verified"] !== 1
) {

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");
    exit;
}


/* HELPERS */

function profileValue(
    ?string $value,
    string $fallback = "Not added"
): string {

    $value =
        trim(
            (string) $value
        );


    return
        $value !== ""
            ? $value
            : $fallback;
}


function educationYearRange(
    $startYear,
    $endYear
): string {

    $startYear =
        trim(
            (string) $startYear
        );


    $endYear =
        trim(
            (string) $endYear
        );


    if (
        $startYear !== "" &&
        $endYear !== ""
    ) {

        if (
            $startYear ===
            $endYear
        ) {

            return $startYear;
        }


        return
            $startYear .
            " - " .
            $endYear;
    }


    if ($startYear !== "") {

        return
            $startYear .
            " - Present";
    }


    if ($endYear !== "") {

        return $endYear;
    }


    return "";
}


/* NAME */

$nameParts = [];


foreach (
    [
        $student["first_name"] ?? "",
        $student["middle_name"] ?? "",
        $student["last_name"] ?? "",
        $student["suffix"] ?? ""
    ]
    as $namePart
) {

    $namePart =
        trim(
            $namePart
        );


    if ($namePart !== "") {

        $nameParts[] =
            $namePart;
    }
}


$fullName =
    trim(
        implode(
            " ",
            $nameParts
        )
    );


if ($fullName === "") {

    $fullName =
        "Student";
}


$firstName =
    trim(
        $student["first_name"]
        ?? ""
    );


$avatarInitial =
    strtoupper(
        substr(
            $firstName !== ""
                ? $firstName
                : "S",
            0,
            1
        )
    );


/* PROFILE DATA */

$studentId =
    trim(
        $student["student_id"]
        ?? ""
    );


$email =
    trim(
        $student["email"]
        ?? ""
    );


$phone =
    trim(
        $student["phone"]
        ?? ""
    );


$birthdate =
    trim(
        $student["birthdate"]
        ?? ""
    );


$gender =
    trim(
        $student["gender"]
        ?? ""
    );


$address =
    trim(
        $student["address"]
        ?? ""
    );


$program =
    trim(
        $student["program"]
        ?? ""
    );


$yearLevel =
    trim(
        $student["year_level"]
        ?? ""
    );


$section =
    trim(
        $student["section"]
        ?? ""
    );


$college =
    trim(
        $student["college"]
        ?? ""
    );


$campus =
    trim(
        $student["campus"]
        ?? ""
    );


$profilePhoto =
    trim(
        $student["profile_photo"]
        ?? ""
    );


$aboutMe =
    trim(
        $student["about_me"]
        ?? ""
    );


$fatherName =
    trim(
        $student["father_name"]
        ?? ""
    );


$motherName =
    trim(
        $student["mother_name"]
        ?? ""
    );


$guardianName =
    trim(
        $student["guardian_name"]
        ?? ""
    );


$guardianContact =
    trim(
        $student["guardian_contact"]
        ?? ""
    );


/* BIRTHDATE */

$birthdateDisplay =
    "Not added";


if ($birthdate !== "") {

    $birthdateTimestamp =
        strtotime(
            $birthdate
        );


    if (
        $birthdateTimestamp !== false
    ) {

        $birthdateDisplay =
            date(
                "F j, Y",
                $birthdateTimestamp
            );
    }
}


/* EDUCATION */

$education = [];


$educationStmt =
    $conn->prepare("
        SELECT

            id,
            education_level,
            school_name,
            start_year,
            end_year,
            achievements

        FROM education

        WHERE user_id = ?

        ORDER BY

            CASE education_level

                WHEN 'elementary'
                    THEN 1

                WHEN 'junior_high'
                    THEN 2

                WHEN 'senior_high'
                    THEN 3

                WHEN 'college'
                    THEN 4

                ELSE 5

            END,

            start_year ASC,
            id ASC
    ");


if ($educationStmt) {

    $educationStmt->bind_param(
        "i",
        $userId
    );


    $educationStmt->execute();


    $educationResult =
        $educationStmt
            ->get_result();


    while (
        $row =
            $educationResult
                ->fetch_assoc()
    ) {

        $education[] =
            $row;
    }


    $educationStmt->close();
}


/* HOBBIES */

$hobbies = [];


$hobbyStmt =
    $conn->prepare("
        SELECT

            id,
            hobby

        FROM hobbies

        WHERE user_id = ?

        ORDER BY id DESC
    ");


if ($hobbyStmt) {

    $hobbyStmt->bind_param(
        "i",
        $userId
    );


    $hobbyStmt->execute();


    $hobbyResult =
        $hobbyStmt
            ->get_result();


    while (
        $row =
            $hobbyResult
                ->fetch_assoc()
    ) {

        $hobby =
            trim(
                $row["hobby"]
                ?? ""
            );


        if ($hobby !== "") {

            $hobbies[] =
                $hobby;
        }
    }


    $hobbyStmt->close();
}


/* INTERESTS */

$interests = [];


$interestStmt =
    $conn->prepare("
        SELECT

            id,
            interest

        FROM interests

        WHERE user_id = ?

        ORDER BY id DESC
    ");


if ($interestStmt) {

    $interestStmt->bind_param(
        "i",
        $userId
    );


    $interestStmt->execute();


    $interestResult =
        $interestStmt
            ->get_result();


    while (
        $row =
            $interestResult
                ->fetch_assoc()
    ) {

        $interest =
            trim(
                $row["interest"]
                ?? ""
            );


        if ($interest !== "") {

            $interests[] =
                $interest;
        }
    }


    $interestStmt->close();
}


/* ORGANIZATIONS */

$organizations = [];


$organizationStmt =
    $conn->prepare("
        SELECT

            id,
            name,
            type,
            position,
            participation_date,
            description

        FROM organizations

        WHERE user_id = ?

        ORDER BY

            CASE
                WHEN participation_date IS NULL
                    THEN 1
                ELSE 0
            END,

            participation_date DESC,
            id DESC

        LIMIT 5
    ");


if ($organizationStmt) {

    $organizationStmt->bind_param(
        "i",
        $userId
    );


    $organizationStmt->execute();


    $organizationResult =
        $organizationStmt
            ->get_result();


    while (
        $row =
            $organizationResult
                ->fetch_assoc()
    ) {

        $organizations[] =
            $row;
    }


    $organizationStmt->close();
}


/* ACCOMPLISHMENTS */

$accomplishmentCount =
    0;


$accomplishmentStmt =
    $conn->prepare("
        SELECT COUNT(*) AS total

        FROM accomplishments

        WHERE user_id = ?
    ");


if ($accomplishmentStmt) {

    $accomplishmentStmt->bind_param(
        "i",
        $userId
    );


    $accomplishmentStmt->execute();


    $accomplishmentRow =
        $accomplishmentStmt
            ->get_result()
            ->fetch_assoc();


    $accomplishmentCount =
        (int) (
            $accomplishmentRow["total"]
            ?? 0
        );


    $accomplishmentStmt->close();
}


/* PROFILE COMPLETION */

$completionFields = [

    $firstName,

    trim(
        $student["last_name"]
        ?? ""
    ),

    $studentId,

    $phone,

    $birthdate,

    $gender,

    $address,

    $program,

    $yearLevel,

    $section,

    $college,

    $campus,

    $aboutMe
];


$completedFields =
    0;


foreach (
    $completionFields
    as $field
) {

    if (
        trim(
            (string) $field
        ) !== ""
    ) {

        $completedFields++;
    }
}


$totalCompletionFields =
    count(
        $completionFields
    );


if (
    count($education) > 0
) {

    $completedFields++;
}


$totalCompletionFields++;


if (
    count($hobbies) > 0 ||
    count($interests) > 0
) {

    $completedFields++;
}


$totalCompletionFields++;


if (
    count($organizations) > 0
) {

    $completedFields++;
}


$totalCompletionFields++;


if (
    $accomplishmentCount > 0
) {

    $completedFields++;
}


$totalCompletionFields++;


$profileCompletion =
    $totalCompletionFields > 0
        ? (int) round(
            (
                $completedFields /
                $totalCompletionFields
            ) * 100
        )
        : 0;


$profileCompletion =
    max(
        0,
        min(
            100,
            $profileCompletion
        )
    );


/* LABELS */

$educationLabels = [

    "elementary" =>
        "Elementary",

    "junior_high" =>
        "Junior High School",

    "senior_high" =>
        "Senior High School",

    "college" =>
        "College",

    "other" =>
        "Other"
];


$organizationLabels = [

    "organization" =>
        "Organization",

    "club" =>
        "Club",

    "student_government" =>
        "Student Government",

    "event" =>
        "Event"
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


    <title>
        My Profile | CVSWHO
    </title>


    <link
        rel="stylesheet"
        href="student_profile.css"
    >


    <style>

        .profile-photo.real-photo {

            padding: 0;

            overflow: hidden;
        }


        .profile-photo.real-photo img {

            width: 100%;
            height: 100%;

            display: block;

            object-fit: cover;
        }


        .about-me-text {

            color: #68766f;

            font-size: 10px;

            line-height: 1.8;

            white-space: normal;
        }


        .section-heading.with-action {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;
        }


        .section-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                8px 12px;

            color: #006b3f;

            background: white;

            border:
                1px solid #cfe1d6;

            border-radius: 7px;

            font-size: 8px;

            font-weight: 700;

            text-decoration: none;

            white-space: nowrap;
        }


        .section-action:hover {

            background: #e6f3eb;
        }


        .interest-groups {

            display: flex;

            flex-direction: column;

            gap: 20px;
        }


        .interest-group h3 {

            margin-bottom: 10px;

            color: #003d24;

            font-size: 10px;

            font-weight: 700;
        }


        .interest-tags {

            display: flex;

            flex-wrap: wrap;

            gap: 8px;
        }


        .interest-tag {

            display: inline-flex;

            align-items: center;

            padding:
                7px 11px;

            color: #006b3f;

            background: #e6f3eb;

            border:
                1px solid #cfe4d7;

            border-radius: 30px;

            font-size: 9px;

            font-weight: 700;
        }


        .empty-profile-section {

            padding: 22px;

            text-align: center;

            background: #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 10px;
        }


        .empty-profile-section p {

            color: #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        .empty-profile-section a {

            display: inline-block;

            margin-top: 9px;

            color: #006b3f;

            font-size: 9px;

            font-weight: 700;

            text-decoration: none;
        }


        .education-achievement {

            margin-top: 7px;

            color: #68766f;

            font-size: 9px;

            line-height: 1.6;
        }


        .organization-list {

            display: flex;

            flex-direction: column;

            border-top:
                1px solid #e1e9e4;
        }


        .organization-item {

            display: flex;

            align-items: flex-start;

            gap: 15px;

            padding:
                17px 5px;

            border-bottom:
                1px solid #e1e9e4;
        }


        .organization-item:last-child {

            border-bottom: none;
        }


        .organization-icon {

            width: 38px;
            height: 38px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            color: #006b3f;

            background: #e6f3eb;

            border-radius: 9px;

            font-size: 9px;

            font-weight: 800;
        }


        .organization-content {

            flex: 1;
        }


        .organization-type {

            display: inline-flex;

            margin-bottom: 5px;

            color: #006b3f;

            font-size: 8px;

            font-weight: 700;

            text-transform: uppercase;
        }


        .organization-content h3 {

            color: #003d24;

            font-size: 11px;

            font-weight: 700;
        }


        .organization-meta {

            display: flex;

            flex-wrap: wrap;

            gap: 7px;

            margin-top: 5px;

            color: #68766f;

            font-size: 8px;
        }


        .organization-description {

            margin-top: 7px;

            color: #68766f;

            font-size: 9px;

            line-height: 1.6;
        }


        .profile-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 10px;

            margin-bottom: 22px;
        }


        .profile-stat {

            padding: 16px;

            background: white;

            border:
                1px solid #e1e9e4;

            border-radius: 10px;
        }


        .profile-stat span {

            display: block;

            color: #68766f;

            font-size: 7px;

            font-weight: 700;

            letter-spacing: .5px;

            text-transform: uppercase;
        }


        .profile-stat strong {

            display: block;

            margin-top: 6px;

            color: #006b3f;

            font-size: 19px;
        }


        .profile-completion-bar {

            height: 7px;

            margin-top: 9px;

            overflow: hidden;

            background: #e6ece8;

            border-radius: 20px;
        }


        .profile-completion-fill {

            height: 100%;

            background: #006b3f;

            border-radius: inherit;
        }


        @media (
            max-width: 750px
        ) {

            .profile-stats {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }

        }


        @media (
            max-width: 500px
        ) {

            .profile-stats {

                grid-template-columns:
                    1fr;
            }


            .section-heading.with-action {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .organization-item {

                align-items:
                    flex-start;
            }

        }

    </style>


</head>


<body>


<!-- NAVIGATION -->

<header class="navbar">


    <div class="nav-container">


        <a
            href="index.php"
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
                class="nav-link"
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
                href="organizations.php"
                class="nav-link"
            >
                Organizations
            </a>


            <a
                href="privacy.php"
                class="nav-link"
            >
                Privacy
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


<!-- PROFILE -->

<main class="profile-page">


    <!-- HEADER -->

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
                academic, family, educational,
                hobbies, and activity information.
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


    <!-- PROFILE HEADER -->

    <section class="profile-header-card">


        <div class="profile-photo-container">


            <?php if (
                $profilePhoto !== ""
            ): ?>


                <div
                    class="
                        profile-photo
                        real-photo
                    "
                >


                    <img
                        src="<?= htmlspecialchars(
                            $profilePhoto,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        alt="Profile photo"
                    >


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


                <?php if (
                    $yearLevel !== ""
                ): ?>


                    <span>
                        <?= htmlspecialchars(
                            $yearLevel
                        ) ?>
                    </span>


                <?php endif; ?>


                <?php if (
                    $section !== ""
                ): ?>


                    <span>
                        Section
                        <?= htmlspecialchars(
                            $section
                        ) ?>
                    </span>


                <?php endif; ?>


                <span class="profile-status">
                    Active
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


    <!-- PROFILE SUMMARY -->

    <section class="profile-stats">


        <div class="profile-stat">


            <span>
                Profile Completion
            </span>


            <strong>
                <?= $profileCompletion ?>%
            </strong>


            <div class="profile-completion-bar">


                <div
                    class="profile-completion-fill"
                    style="width: <?= $profileCompletion ?>%;"
                ></div>


            </div>


        </div>


        <div class="profile-stat">


            <span>
                Hobbies & Interests
            </span>


            <strong>

                <?= count($hobbies) +
                    count($interests)
                ?>

            </strong>


        </div>


        <div class="profile-stat">


            <span>
                Organizations
            </span>


            <strong>
                <?= count(
                    $organizations
                ) ?>
            </strong>


        </div>


        <div class="profile-stat">


            <span>
                Accomplishments
            </span>


            <strong>
                <?= $accomplishmentCount ?>
            </strong>


        </div>


    </section>


    <!-- ABOUT ME -->

    <section class="information-card">


        <div
            class="
                section-heading
                with-action
            "
        >


            <div>


                <span class="section-label">
                    ABOUT ME
                </span>


                <h2>
                    About Me
                </h2>


            </div>


            <a
                href="edit_profile.php"
                class="section-action"
            >
                Edit
            </a>


        </div>


        <?php if (
            $aboutMe !== ""
        ): ?>


            <p class="about-me-text">

                <?= nl2br(
                    htmlspecialchars(
                        $aboutMe
                    )
                ) ?>

            </p>


        <?php else: ?>


            <div class="empty-profile-section">


                <p>
                    You have not added an
                    About Me description yet.
                </p>


                <a href="edit_profile.php">
                    Add About Me →
                </a>


            </div>


        <?php endif; ?>


    </section>


    <!-- PERSONAL INFORMATION -->

    <section class="information-card">


        <div
            class="
                section-heading
                with-action
            "
        >


            <div>


                <span class="section-label">
                    PERSONAL INFORMATION
                </span>


                <h2>
                    Personal Details
                </h2>


            </div>


            <a
                href="edit_profile.php"
                class="section-action"
            >
                Edit
            </a>


        </div>


        <div class="information-grid">


            <div class="information-item">


                <span class="information-label">
                    Full Name
                </span>


                <p>
                    <?= htmlspecialchars(
                        $fullName
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
                        $birthdateDisplay
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


    <!-- ACADEMIC INFORMATION -->

    <section class="information-card">


        <div
            class="
                section-heading
                with-action
            "
        >


            <div>


                <span class="section-label">
                    ACADEMIC INFORMATION
                </span>


                <h2>
                    Current Academic Details
                </h2>


            </div>


            <a
                href="edit_profile.php"
                class="section-action"
            >
                Edit
            </a>


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


    <!-- FAMILY INFORMATION -->

    <section class="information-card">


        <div
            class="
                section-heading
                with-action
            "
        >


            <div>


                <span class="section-label">
                    FAMILY INFORMATION
                </span>


                <h2>
                    Parent & Guardian Details
                </h2>


            </div>


            <a
                href="edit_profile.php"
                class="section-action"
            >
                Edit
            </a>


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
                    Guardian Name
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


    <!-- EDUCATIONAL BACKGROUND -->

    <section class="information-card">


        <div
            class="
                section-heading
                with-action
            "
        >


            <div>


                <span class="section-label">
                    EDUCATIONAL BACKGROUND
                </span>


                <h2>
                    Education History
                </h2>


            </div>


            <a
                href="edit_profile.php"
                class="section-action"
            >
                Manage
            </a>


        </div>


        <?php if (
            count($education) > 0
        ): ?>


            <div class="education-list">


                <?php foreach (
                    $education
                    as $index => $school
                ): ?>


                    <?php

                    $educationLevel =
                        $school[
                            "education_level"
                        ] ?? "other";


                    $educationLabel =
                        $educationLabels[
                            $educationLevel
                        ] ?? "Other";


                    $educationYears =
                        educationYearRange(
                            $school[
                                "start_year"
                            ] ?? "",

                            $school[
                                "end_year"
                            ] ?? ""
                        );


                    $achievements =
                        trim(
                            $school[
                                "achievements"
                            ] ?? ""
                        );

                    ?>


                    <div class="education-item">


                        <div class="education-number">

                            <?= str_pad(
                                (string) (
                                    $index + 1
                                ),
                                2,
                                "0",
                                STR_PAD_LEFT
                            ) ?>

                        </div>


                        <div class="education-details">


                            <span>

                                <?= htmlspecialchars(
                                    strtoupper(
                                        $educationLabel
                                    )
                                ) ?>

                            </span>


                            <h3>

                                <?= htmlspecialchars(
                                    profileValue(
                                        $school[
                                            "school_name"
                                        ] ?? ""
                                    )
                                ) ?>

                            </h3>


                            <?php if (
                                $educationYears !== ""
                            ): ?>


                                <p>

                                    <?= htmlspecialchars(
                                        $educationYears
                                    ) ?>

                                </p>


                            <?php endif; ?>


                            <?php if (
                                $achievements !== ""
                            ): ?>


                                <p class="education-achievement">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $achievements
                                        )
                                    ) ?>

                                </p>


                            <?php endif; ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="empty-profile-section">


                <p>
                    No educational background
                    has been added yet.
                </p>


                <a href="edit_profile.php">
                    Add Education →
                </a>


            </div>


        <?php endif; ?>


    </section>


    <!-- HOBBIES AND INTERESTS -->

    <section class="information-card">


        <div
            class="
                section-heading
                with-action
            "
        >


            <div>


                <span class="section-label">
                    HOBBIES & INTERESTS
                </span>


                <h2>
                    Personal Interests
                </h2>


            </div>


            <a
                href="hobbies.php"
                class="section-action"
            >
                Manage
            </a>


        </div>


        <?php if (
            count($hobbies) > 0 ||
            count($interests) > 0
        ): ?>


            <div class="interest-groups">


                <?php if (
                    count($hobbies) > 0
                ): ?>


                    <div class="interest-group">


                        <h3>
                            Hobbies
                        </h3>


                        <div class="interest-tags">


                            <?php foreach (
                                $hobbies
                                as $hobby
                            ): ?>


                                <span class="interest-tag">

                                    <?= htmlspecialchars(
                                        $hobby
                                    ) ?>

                                </span>


                            <?php endforeach; ?>


                        </div>


                    </div>


                <?php endif; ?>


                <?php if (
                    count($interests) > 0
                ): ?>


                    <div class="interest-group">


                        <h3>
                            Interests
                        </h3>


                        <div class="interest-tags">


                            <?php foreach (
                                $interests
                                as $interest
                            ): ?>


                                <span class="interest-tag">

                                    <?= htmlspecialchars(
                                        $interest
                                    ) ?>

                                </span>


                            <?php endforeach; ?>


                        </div>


                    </div>


                <?php endif; ?>


            </div>


        <?php else: ?>


            <div class="empty-profile-section">


                <p>
                    No hobbies or interests
                    have been added yet.
                </p>


                <a href="hobbies.php">
                    Add Hobbies & Interests →
                </a>


            </div>


        <?php endif; ?>


    </section>


    <!-- ORGANIZATIONS -->

    <section class="information-card">


        <div
            class="
                section-heading
                with-action
            "
        >


            <div>


                <span class="section-label">
                    ORGANIZATIONS & ACTIVITIES
                </span>


                <h2>
                    Student Involvement
                </h2>


            </div>


            <a
                href="organizations.php"
                class="section-action"
            >
                Manage
            </a>


        </div>


        <?php if (
            count($organizations) > 0
        ): ?>


            <div class="organization-list">


                <?php foreach (
                    $organizations
                    as $organization
                ): ?>


                    <?php

                    $organizationType =
                        $organization[
                            "type"
                        ] ?? "organization";


                    $organizationLabel =
                        $organizationLabels[
                            $organizationType
                        ] ?? "Organization";


                    $organizationPosition =
                        trim(
                            $organization[
                                "position"
                            ] ?? ""
                        );


                    $organizationDate =
                        trim(
                            $organization[
                                "participation_date"
                            ] ?? ""
                        );


                    $organizationDescription =
                        trim(
                            $organization[
                                "description"
                            ] ?? ""
                        );


                    $organizationDateDisplay =
                        "";


                    if (
                        $organizationDate !== ""
                    ) {

                        $dateTimestamp =
                            strtotime(
                                $organizationDate
                            );


                        if (
                            $dateTimestamp !== false
                        ) {

                            $organizationDateDisplay =
                                date(
                                    "F j, Y",
                                    $dateTimestamp
                                );
                        }
                    }

                    ?>


                    <div class="organization-item">


                        <div class="organization-icon">

                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        $organizationLabel,
                                        0,
                                        1
                                    )
                                )
                            ) ?>

                        </div>


                        <div class="organization-content">


                            <span class="organization-type">

                                <?= htmlspecialchars(
                                    $organizationLabel
                                ) ?>

                            </span>


                            <h3>

                                <?= htmlspecialchars(
                                    $organization[
                                        "name"
                                    ]
                                ) ?>

                            </h3>


                            <?php if (
                                $organizationPosition !== "" ||
                                $organizationDateDisplay !== ""
                            ): ?>


                                <div class="organization-meta">


                                    <?php if (
                                        $organizationPosition !== ""
                                    ): ?>


                                        <span>

                                            <?= htmlspecialchars(
                                                $organizationPosition
                                            ) ?>

                                        </span>


                                    <?php endif; ?>


                                    <?php if (
                                        $organizationPosition !== "" &&
                                        $organizationDateDisplay !== ""
                                    ): ?>

                                        <span>•</span>

                                    <?php endif; ?>


                                    <?php if (
                                        $organizationDateDisplay !== ""
                                    ): ?>


                                        <span>

                                            <?= htmlspecialchars(
                                                $organizationDateDisplay
                                            ) ?>

                                        </span>


                                    <?php endif; ?>


                                </div>


                            <?php endif; ?>


                            <?php if (
                                $organizationDescription !== ""
                            ): ?>


                                <p class="organization-description">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $organizationDescription
                                        )
                                    ) ?>

                                </p>


                            <?php endif; ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


            <a
                href="organizations.php"
                class="section-action"
                style="margin-top: 15px;"
            >
                View All Organizations →
            </a>


        <?php else: ?>


            <div class="empty-profile-section">


                <p>
                    No organizations or activities
                    have been added yet.
                </p>


                <a href="organizations.php">
                    Add Organization or Activity →
                </a>


            </div>


        <?php endif; ?>


    </section>


    <!-- BOTTOM ACTIONS -->

    <div class="bottom-actions">


        <a
            href="edit_profile.php"
            class="primary-button"
        >
            Edit Profile
        </a>


        <a
            href="hobbies.php"
            class="secondary-button"
        >
            Manage Hobbies
        </a>


        <a
            href="organizations.php"
            class="secondary-button"
        >
            Manage Organizations
        </a>


        <a
            href="digital_portfolio.php"
            class="secondary-button"
        >
            Digital Portfolio
        </a>


    </div>


</main>


<!-- FOOTER -->

<footer class="footer">


    <p>
        CVSWHO
    </p>


    <span>
        Manage your student profile with ease.
    </span>


</footer>


<script src="main.js"></script>


</body>

</html>