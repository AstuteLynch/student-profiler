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
            u.account_status,

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

            COALESCE(
                pset.show_about,
                1
            ) AS show_about,

            COALESCE(
                pset.show_education,
                1
            ) AS show_education,

            COALESCE(
                pset.show_accomplishments,
                1
            ) AS show_accomplishments,

            COALESCE(
                pset.show_hobbies,
                1
            ) AS show_hobbies,

            COALESCE(
                pset.show_organizations,
                1
            ) AS show_organizations

        FROM users u

        LEFT JOIN student_profiles sp
            ON sp.user_id = u.id

        LEFT JOIN profile_settings pset
            ON pset.user_id = u.id

        WHERE u.id = ?

        LIMIT 1
    ");


if (!$stmt) {

    die(
        "Portfolio database error: " .
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


/* NAME */

$firstName =
    trim(
        $student["first_name"]
        ?? ""
    );


$middleName =
    trim(
        $student["middle_name"]
        ?? ""
    );


$lastName =
    trim(
        $student["last_name"]
        ?? ""
    );


$suffix =
    trim(
        $student["suffix"]
        ?? ""
    );


$nameParts = [];


if ($firstName !== "") {

    $nameParts[] =
        $firstName;
}


if ($middleName !== "") {

    $nameParts[] =
        $middleName;
}


if ($lastName !== "") {

    $nameParts[] =
        $lastName;
}


if ($suffix !== "") {

    $nameParts[] =
        $suffix;
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


/* PROFILE */

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


$aboutMe =
    trim(
        $student["about_me"]
        ?? ""
    );


$profilePhoto =
    trim(
        $student["profile_photo"]
        ?? ""
    );


/* PROFILE SETTINGS */

$showAbout =
    (bool) (
        $student["show_about"]
        ?? true
    );


$showEducation =
    (bool) (
        $student["show_education"]
        ?? true
    );


$showAccomplishments =
    (bool) (
        $student[
            "show_accomplishments"
        ] ?? true
    );


$showHobbies =
    (bool) (
        $student["show_hobbies"]
        ?? true
    );


$showOrganizations =
    (bool) (
        $student[
            "show_organizations"
        ] ?? true
    );


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


/* ACCOMPLISHMENTS */

$accomplishments = [];


$accomplishmentStmt =
    $conn->prepare("
        SELECT

            id,
            title,
            category,
            description,
            date_achieved,
            organization,
            document_path

        FROM accomplishments

        WHERE user_id = ?

        ORDER BY

            CASE
                WHEN date_achieved IS NULL
                    THEN 1
                ELSE 0
            END,

            date_achieved DESC,
            id DESC
    ");


if ($accomplishmentStmt) {

    $accomplishmentStmt->bind_param(
        "i",
        $userId
    );


    $accomplishmentStmt->execute();


    $accomplishmentResult =
        $accomplishmentStmt
            ->get_result();


    while (
        $row =
            $accomplishmentResult
                ->fetch_assoc()
    ) {

        $accomplishments[] =
            $row;
    }


    $accomplishmentStmt->close();
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

        $value =
            trim(
                $row["hobby"]
                ?? ""
            );


        if ($value !== "") {

            $hobbies[] =
                $value;
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

        $value =
            trim(
                $row["interest"]
                ?? ""
            );


        if ($value !== "") {

            $interests[] =
                $value;
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


$categoryLabels = [

    "academic" =>
        "Academic",

    "non_academic" =>
        "Non-Academic",

    "competition" =>
        "Competition",

    "certification" =>
        "Certification",

    "other" =>
        "Other"
];


$organizationTypeLabels = [

    "organization" =>
        "Organization",

    "club" =>
        "Club",

    "student_government" =>
        "Student Government",

    "event" =>
        "Event"
];


/* HELPERS */

function portfolioValue(
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


function portfolioYearRange(
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
        Digital Portfolio | CVSWHO
    </title>


    <link
        rel="stylesheet"
        href="digital_portfolio.css"
    >


    <style>

        .profile-photo {

            overflow: hidden;

            background-size: cover;

            background-position: center;

            background-repeat: no-repeat;
        }


        .portfolio-empty {

            padding: 20px;

            color: #68766f;

            background: #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 10px;

            font-size: 10px;

            line-height: 1.7;

            text-align: center;
        }


        .portfolio-empty a {

            display: inline-flex;

            margin-top: 10px;

            color: #006b3f;

            font-weight: 700;

            text-decoration: none;
        }


        .portfolio-empty a:hover {

            color: #004d2a;
        }


        .accomplishment-category {

            display: inline-flex;

            width: fit-content;

            margin-bottom: 6px;

            padding:
                4px 8px;

            color: #006b3f;

            background: #edf8f2;

            border-radius: 30px;

            font-size: 8px;

            font-weight: 700;
        }


        .accomplishment-meta {

            display: flex;

            flex-wrap: wrap;

            align-items: center;

            gap: 8px;

            margin-top: 8px;

            color: #68766f;

            font-size: 9px;
        }


        .portfolio-document {

            display: inline-flex;

            margin-top: 10px;

            color: #006b3f;

            font-size: 9px;

            font-weight: 700;

            text-decoration: none;
        }


        .portfolio-document:hover {

            color: #004d2a;
        }


        .education-achievement {

            margin-top: 7px;

            color: #68766f;

            font-size: 9px;

            line-height: 1.6;
        }


        .organization-type {

            display: inline-flex;

            width: fit-content;

            margin-top: 6px;

            color: #006b3f;

            font-size: 8px;

            font-weight: 700;

            text-transform: uppercase;
        }


        .organization-date {

            display: block;

            margin-top: 5px;

            color: #68766f;

            font-size: 9px;
        }


        .organization-description {

            margin-top: 8px;

            color: #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        .contact-item p,
        .summary-item strong {

            word-break: break-word;
        }


        .portfolio-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );

            gap: 8px;

            margin-top: 15px;
        }


        .portfolio-stat {

            padding: 12px;

            text-align: center;

            background: #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 9px;
        }


        .portfolio-stat strong {

            display: block;

            color: #006b3f;

            font-size: 16px;
        }


        .portfolio-stat span {

            display: block;

            margin-top: 4px;

            color: #68766f;

            font-size: 7px;

            font-weight: 700;

            text-transform: uppercase;
        }


        @media (
            max-width: 650px
        ) {

            .portfolio-stats {

                grid-template-columns:
                    1fr;
            }

        }


        @media print {

            .navbar,
            .portfolio-actions,
            .edit-profile-button,
            .footer {

                display: none !important;
            }


            body {

                background:
                    white !important;
            }


            .portfolio-page {

                width: 100%;

                max-width: none;

                padding: 0;
            }


            .portfolio-card,
            .sidebar-card,
            .privacy-card {

                break-inside:
                    avoid;

                box-shadow:
                    none;
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
            href="digital_portfolio.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>


</header>


<!-- DIGITAL PORTFOLIO -->

<main class="portfolio-page">


    <!-- HEADER -->

    <section class="portfolio-header">


        <div class="profile-introduction">


            <div
                class="profile-photo"

                <?php if (
                    $profilePhoto !== ""
                ): ?>

                    style="
                        background-image:
                        url('<?= htmlspecialchars(
                            $profilePhoto,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>');

                        color: transparent;
                    "

                <?php endif; ?>
            >

                <?= htmlspecialchars(
                    $avatarInitial
                ) ?>

            </div>


            <div class="profile-heading">


                <span class="eyebrow">
                    DIGITAL PORTFOLIO
                </span>


                <h1>
                    <?= htmlspecialchars(
                        $fullName
                    ) ?>
                </h1>


                <?php if (
                    $program !== ""
                ): ?>

                    <p class="program">

                        <?= htmlspecialchars(
                            $program
                        ) ?>

                    </p>

                <?php endif; ?>


                <p class="student-meta">


                    <?php if (
                        $yearLevel !== ""
                    ): ?>

                        <?= htmlspecialchars(
                            $yearLevel
                        ) ?>

                    <?php endif; ?>


                    <?php if (
                        $section !== ""
                    ): ?>

                        <?php if (
                            $yearLevel !== ""
                        ): ?>

                            <span>•</span>

                        <?php endif; ?>

                        Section

                        <?= htmlspecialchars(
                            $section
                        ) ?>

                    <?php endif; ?>


                    <?php if (
                        $college !== ""
                    ): ?>

                        <?php if (
                            $yearLevel !== "" ||
                            $section !== ""
                        ): ?>

                            <span>•</span>

                        <?php endif; ?>

                        <?= htmlspecialchars(
                            $college
                        ) ?>

                    <?php endif; ?>


                    <?php if (
                        $campus !== ""
                    ): ?>

                        <?php if (
                            $yearLevel !== "" ||
                            $section !== "" ||
                            $college !== ""
                        ): ?>

                            <span>•</span>

                        <?php endif; ?>

                        <?= htmlspecialchars(
                            $campus
                        ) ?>

                    <?php endif; ?>


                </p>


            </div>


        </div>


        <div class="portfolio-actions">


            <a
                href="student_profile.php"
                class="secondary-button"
            >
                ← Back to Profile
            </a>


            <button
                type="button"
                class="primary-button"
                onclick="window.print()"
            >
                Print Portfolio
            </button>


        </div>


    </section>


    <!-- PORTFOLIO CONTENT -->

    <div class="portfolio-layout">


        <!-- MAIN CONTENT -->

        <div class="portfolio-main">


            <?php if ($showAbout): ?>


                <!-- ABOUT ME -->

                <section class="portfolio-card">


                    <div class="section-heading">


                        <span class="section-label">
                            ABOUT ME
                        </span>


                        <h2>
                            About Me
                        </h2>


                    </div>


                    <?php if (
                        $aboutMe !== ""
                    ): ?>


                        <p class="about-text">

                            <?= nl2br(
                                htmlspecialchars(
                                    $aboutMe
                                )
                            ) ?>

                        </p>


                    <?php else: ?>


                        <div class="portfolio-empty">


                            You have not added an
                            About Me description yet.


                            <br>


                            <a href="edit_profile.php">
                                Add About Me
                            </a>


                        </div>


                    <?php endif; ?>


                </section>


            <?php endif; ?>


            <?php if (
                $showEducation
            ): ?>


                <!-- EDUCATION -->

                <section class="portfolio-card">


                    <div class="section-heading">


                        <span class="section-label">
                            EDUCATION
                        </span>


                        <h2>
                            Educational Background
                        </h2>


                    </div>


                    <?php if (
                        count(
                            $education
                        ) > 0 ||
                        $program !== "" ||
                        $college !== "" ||
                        $campus !== ""
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
                                    portfolioYearRange(
                                        $school[
                                            "start_year"
                                        ] ?? "",

                                        $school[
                                            "end_year"
                                        ] ?? ""
                                    );


                                $educationAchievement =
                                    trim(
                                        $school[
                                            "achievements"
                                        ] ?? ""
                                    );

                                ?>


                                <div class="education-item">


                                    <div class="education-icon">

                                        <?= str_pad(
                                            (string) (
                                                $index + 1
                                            ),
                                            2,
                                            "0",
                                            STR_PAD_LEFT
                                        ) ?>

                                    </div>


                                    <div class="education-content">


                                        <span class="education-level">

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    $educationLabel
                                                )
                                            ) ?>

                                        </span>


                                        <h3>

                                            <?= htmlspecialchars(
                                                portfolioValue(
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
                                            $educationAchievement !== ""
                                        ): ?>


                                            <p class="education-achievement">

                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $educationAchievement
                                                    )
                                                ) ?>

                                            </p>


                                        <?php endif; ?>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                            <?php if (
                                $program !== "" ||
                                $college !== "" ||
                                $campus !== ""
                            ): ?>


                                <div
                                    class="
                                        education-item
                                        current
                                    "
                                >


                                    <div class="education-icon">

                                        <?= str_pad(
                                            (string) (
                                                count(
                                                    $education
                                                ) + 1
                                            ),
                                            2,
                                            "0",
                                            STR_PAD_LEFT
                                        ) ?>

                                    </div>


                                    <div class="education-content">


                                        <span class="education-level">
                                            CURRENT EDUCATION
                                        </span>


                                        <h3>

                                            <?= htmlspecialchars(
                                                portfolioValue(
                                                    $program
                                                )
                                            ) ?>

                                        </h3>


                                        <?php if (
                                            $college !== "" ||
                                            $campus !== ""
                                        ): ?>


                                            <p>


                                                <?php if (
                                                    $college !== ""
                                                ): ?>

                                                    <?= htmlspecialchars(
                                                        $college
                                                    ) ?>

                                                <?php endif; ?>


                                                <?php if (
                                                    $college !== "" &&
                                                    $campus !== ""
                                                ): ?>

                                                    •

                                                <?php endif; ?>


                                                <?php if (
                                                    $campus !== ""
                                                ): ?>

                                                    <?= htmlspecialchars(
                                                        $campus
                                                    ) ?>

                                                <?php endif; ?>


                                            </p>


                                        <?php endif; ?>


                                        <?php if (
                                            $yearLevel !== "" ||
                                            $section !== ""
                                        ): ?>


                                            <span class="current-status">


                                                <?php if (
                                                    $yearLevel !== ""
                                                ): ?>

                                                    <?= htmlspecialchars(
                                                        $yearLevel
                                                    ) ?>

                                                <?php endif; ?>


                                                <?php if (
                                                    $yearLevel !== "" &&
                                                    $section !== ""
                                                ): ?>

                                                    •

                                                <?php endif; ?>


                                                <?php if (
                                                    $section !== ""
                                                ): ?>

                                                    Section

                                                    <?= htmlspecialchars(
                                                        $section
                                                    ) ?>

                                                <?php endif; ?>


                                            </span>


                                        <?php endif; ?>


                                    </div>


                                </div>


                            <?php endif; ?>


                        </div>


                    <?php else: ?>


                        <div class="portfolio-empty">


                            No educational background
                            has been added yet.


                            <br>


                            <a href="edit_profile.php">
                                Add Education
                            </a>


                        </div>


                    <?php endif; ?>


                </section>


            <?php endif; ?>


            <?php if (
                $showAccomplishments
            ): ?>


                <!-- ACCOMPLISHMENTS -->

                <section class="portfolio-card">


                    <div class="section-heading">


                        <span class="section-label">
                            ACCOMPLISHMENTS
                        </span>


                        <h2>
                            Achievements & Accomplishments
                        </h2>


                    </div>


                    <?php if (
                        count(
                            $accomplishments
                        ) > 0
                    ): ?>


                        <div class="accomplishment-list">


                            <?php foreach (
                                $accomplishments
                                as $item
                            ): ?>


                                <?php

                                $categoryValue =
                                    $item[
                                        "category"
                                    ] ?? "other";


                                $categoryLabel =
                                    $categoryLabels[
                                        $categoryValue
                                    ] ?? "Other";


                                $achievementDate =
                                    !empty(
                                        $item[
                                            "date_achieved"
                                        ]
                                    )
                                        ? date(
                                            "M d, Y",
                                            strtotime(
                                                $item[
                                                    "date_achieved"
                                                ]
                                            )
                                        )
                                        : "";


                                $achievementDescription =
                                    trim(
                                        $item[
                                            "description"
                                        ] ?? ""
                                    );


                                $achievementOrganization =
                                    trim(
                                        $item[
                                            "organization"
                                        ] ?? ""
                                    );


                                $documentPath =
                                    trim(
                                        $item[
                                            "document_path"
                                        ] ?? ""
                                    );

                                ?>


                                <div class="accomplishment-item">


                                    <div class="accomplishment-icon">
                                        ✓
                                    </div>


                                    <div class="accomplishment-content">


                                        <span class="accomplishment-category">

                                            <?= htmlspecialchars(
                                                $categoryLabel
                                            ) ?>

                                        </span>


                                        <div class="item-header">


                                            <h3>

                                                <?= htmlspecialchars(
                                                    $item[
                                                        "title"
                                                    ]
                                                ) ?>

                                            </h3>


                                            <?php if (
                                                $achievementDate !== ""
                                            ): ?>


                                                <span class="item-year">

                                                    <?= htmlspecialchars(
                                                        $achievementDate
                                                    ) ?>

                                                </span>


                                            <?php endif; ?>


                                        </div>


                                        <?php if (
                                            $achievementDescription !== ""
                                        ): ?>


                                            <p>

                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $achievementDescription
                                                    )
                                                ) ?>

                                            </p>


                                        <?php endif; ?>


                                        <?php if (
                                            $achievementOrganization !== ""
                                        ): ?>


                                            <div class="accomplishment-meta">


                                                <span>

                                                    <?= htmlspecialchars(
                                                        $achievementOrganization
                                                    ) ?>

                                                </span>


                                            </div>


                                        <?php endif; ?>


                                        <?php if (
                                            $documentPath !== ""
                                        ): ?>


                                            <a
                                                href="<?= htmlspecialchars(
                                                    $documentPath,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                                class="portfolio-document"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                View Supporting Document
                                            </a>


                                        <?php endif; ?>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="portfolio-empty">


                            You have not added any
                            accomplishments yet.


                            <br>


                            <a href="add_accomplishment.php">
                                Add Accomplishment
                            </a>


                        </div>


                    <?php endif; ?>


                </section>


            <?php endif; ?>


            <?php if (
                $showHobbies
            ): ?>


                <!-- HOBBIES -->

                <section class="portfolio-card">


                    <div class="section-heading">


                        <span class="section-label">
                            HOBBIES & INTERESTS
                        </span>


                        <h2>
                            Hobbies and Interests
                        </h2>


                    </div>


                    <?php if (
                        count(
                            $hobbies
                        ) > 0 ||
                        count(
                            $interests
                        ) > 0
                    ): ?>


                        <div class="interest-list">


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


                    <?php else: ?>


                        <div class="portfolio-empty">


                            No hobbies or interests
                            have been added yet.


                            <br>


                            <a href="hobbies.php">
                                Manage Hobbies & Interests
                            </a>


                        </div>


                    <?php endif; ?>


                </section>


            <?php endif; ?>


            <?php if (
                $showOrganizations
            ): ?>


                <!-- ORGANIZATIONS -->

                <section class="portfolio-card">


                    <div class="section-heading">


                        <span class="section-label">
                            ORGANIZATIONS
                        </span>


                        <h2>
                            Organizations & Activities
                        </h2>


                    </div>


                    <?php if (
                        count(
                            $organizations
                        ) > 0
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


                                $organizationTypeLabel =
                                    $organizationTypeLabels[
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
                                    $organizationDate !== ""
                                        ? date(
                                            "M d, Y",
                                            strtotime(
                                                $organizationDate
                                            )
                                        )
                                        : "";

                                ?>


                                <div class="organization-item">


                                    <div class="organization-icon">
                                        O
                                    </div>


                                    <div class="organization-content">


                                        <h3>

                                            <?= htmlspecialchars(
                                                $organization[
                                                    "name"
                                                ]
                                            ) ?>

                                        </h3>


                                        <?php if (
                                            $organizationPosition !== ""
                                        ): ?>


                                            <p>

                                                <?= htmlspecialchars(
                                                    $organizationPosition
                                                ) ?>

                                            </p>


                                        <?php endif; ?>


                                        <span class="organization-type">

                                            <?= htmlspecialchars(
                                                $organizationTypeLabel
                                            ) ?>

                                        </span>


                                        <?php if (
                                            $organizationDateDisplay !== ""
                                        ): ?>


                                            <span class="organization-date">

                                                <?= htmlspecialchars(
                                                    $organizationDateDisplay
                                                ) ?>

                                            </span>


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


                    <?php else: ?>


                        <div class="portfolio-empty">


                            No organizations or
                            activities have been
                            added yet.


                            <br>


                            <a href="organizations.php">
                                Manage Organizations
                            </a>


                        </div>


                    <?php endif; ?>


                </section>


            <?php endif; ?>


        </div>


        <!-- SIDEBAR -->

        <aside class="portfolio-sidebar">


            <!-- CONTACT -->

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

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $email
                                )
                            ) ?>

                        </p>


                    </div>


                    <div class="contact-item">


                        <span class="contact-label">
                            PHONE
                        </span>


                        <p>

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $phone
                                )
                            ) ?>

                        </p>


                    </div>


                    <div class="contact-item">


                        <span class="contact-label">
                            LOCATION
                        </span>


                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    portfolioValue(
                                        $address
                                    )
                                )
                            ) ?>

                        </p>


                    </div>


                </div>


            </section>


            <!-- ACADEMIC PROFILE -->

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
                            Student ID
                        </span>


                        <strong>

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $studentId
                                )
                            ) ?>

                        </strong>


                    </div>


                    <div class="summary-item">


                        <span>
                            Program
                        </span>


                        <strong>

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $program
                                )
                            ) ?>

                        </strong>


                    </div>


                    <div class="summary-item">


                        <span>
                            Year Level
                        </span>


                        <strong>

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $yearLevel
                                )
                            ) ?>

                        </strong>


                    </div>


                    <div class="summary-item">


                        <span>
                            Section
                        </span>


                        <strong>

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $section
                                )
                            ) ?>

                        </strong>


                    </div>


                    <div class="summary-item">


                        <span>
                            College
                        </span>


                        <strong>

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $college
                                )
                            ) ?>

                        </strong>


                    </div>


                    <div class="summary-item">


                        <span>
                            Campus
                        </span>


                        <strong>

                            <?= htmlspecialchars(
                                portfolioValue(
                                    $campus
                                )
                            ) ?>

                        </strong>


                    </div>


                </div>


            </section>


            <!-- PORTFOLIO SUMMARY -->

            <section class="sidebar-card">


                <span class="section-label">
                    PORTFOLIO SUMMARY
                </span>


                <h2>
                    Your Records
                </h2>


                <div class="portfolio-stats">


                    <div class="portfolio-stat">


                        <strong>

                            <?= count(
                                $accomplishments
                            ) ?>

                        </strong>


                        <span>
                            Accomplishments
                        </span>


                    </div>


                    <div class="portfolio-stat">


                        <strong>

                            <?= count(
                                $hobbies
                            ) +
                            count(
                                $interests
                            ) ?>

                        </strong>


                        <span>
                            Interests
                        </span>


                    </div>


                    <div class="portfolio-stat">


                        <strong>

                            <?= count(
                                $organizations
                            ) ?>

                        </strong>


                        <span>
                            Activities
                        </span>


                    </div>


                </div>


            </section>


            <!-- PORTFOLIO INFORMATION -->

            <section class="privacy-card">


                <div class="privacy-icon">
                    ✓
                </div>


                <div>


                    <h3>
                        Your Digital Portfolio
                    </h3>


                    <p>
                        This portfolio is generated
                        from the information and
                        records saved in your
                        CVSWHO account.
                    </p>


                </div>


            </section>


            <!-- EDIT PROFILE -->

            <a
                href="edit_profile.php"
                class="edit-profile-button"
            >
                Edit Profile
            </a>


        </aside>


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


</body>

</html>