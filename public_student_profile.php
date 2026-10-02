<?php
session_start();
require_once "db.php";
require_once "visibility_helpers.php";
/* REQUESTED PROFILE */
$profileUserId =
    isset($_GET["id"])
        ? (int) $_GET["id"]
        : 0;
if ($profileUserId <= 0) {
    header(
        "Location: index.php#student-search"
    );
    exit;
}
/* CURRENT VIEWER */
$viewerIsSchoolUser = false;
$viewerUserId = 0;
$loggedInFirstName = "";
$accountDashboard =
    "student_dashboard.php";
if (isset($_SESSION["user_id"])) {
    $viewerUserId =
        (int) $_SESSION["user_id"];
    $viewerStmt =
        $conn->prepare("
            SELECT
                u.role,
                u.account_status,
                u.email_verified,
                sp.first_name
            FROM users u
            LEFT JOIN student_profiles sp
                ON sp.user_id = u.id
            WHERE u.id = ?
            LIMIT 1
        ");
    if ($viewerStmt) {
        $viewerStmt->bind_param(
            "i",
            $viewerUserId
        );
        $viewerStmt->execute();
        $viewer =
            $viewerStmt
                ->get_result()
                ->fetch_assoc();
        $viewerStmt->close();
        if (
            $viewer &&
            $viewer[
                "account_status"
            ] === "active" &&
            (int) $viewer[
                "email_verified"
            ] === 1 &&
            in_array(
                $viewer["role"] ?? "",
                ["student", "administrator"],
                true
            )
        ) {
            $viewerIsSchoolUser = true;
            $loggedInFirstName =
                trim(
                    $viewer[
                        "first_name"
                    ] ?? ""
                );
            if (
                $loggedInFirstName === ""
            ) {
                $loggedInFirstName =
                    "Account";
            }
            if (
                ($viewer["role"] ?? "")
                === "administrator"
            ) {
                $accountDashboard =
                    "admin_dashboard.php";
            }
        }
    }
}
/* LOAD STUDENT */
$stmt =
    $conn->prepare("
        SELECT
            u.id AS user_id,
            u.email,
            u.account_status,
            u.email_verified,
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
            COALESCE(
                ps.profile_visibility,
                'school_only'
            ) AS profile_visibility,
            COALESCE(
                ps.contact_visibility,
                'private'
            ) AS contact_visibility,
            COALESCE(
                ps.family_visibility,
                'private'
            ) AS family_visibility,
            COALESCE(
                ps.address_visibility,
                'private'
            ) AS address_visibility,
            COALESCE(
                ps.academic_visibility,
                'school_only'
            ) AS academic_visibility,
            COALESCE(
                ps.achievement_visibility,
                'school_only'
            ) AS achievement_visibility,
            COALESCE(
                ps.hobbies_visibility,
                'school_only'
            ) AS hobbies_visibility,
            COALESCE(
                ps.organizations_visibility,
                'school_only'
            ) AS organizations_visibility
        FROM users u
        INNER JOIN student_profiles sp
            ON sp.user_id = u.id
        LEFT JOIN privacy_settings ps
            ON ps.user_id = u.id
        WHERE
            u.id = ?
            AND
            u.role = 'student'
            AND
            u.account_status = 'active'
            AND
            u.email_verified = 1
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
    $profileUserId
);
$stmt->execute();
$student =
    $stmt
        ->get_result()
        ->fetch_assoc();
$stmt->close();
/* PROFILE ACCESS */
$profileUnavailable =
    false;
if (!$student) {
    $profileUnavailable =
        true;
} else {
    $canOpenProfile =
        canViewerOpenProfile(
            $student[
                "profile_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
    if (!$canOpenProfile) {
        $profileUnavailable =
            true;
    }
}
/* DEFAULT DATA */
$family = [
    "father_name" => "",
    "mother_name" => "",
    "guardian_name" => "",
    "guardian_contact" => ""
];
$education = [];
$accomplishments = [];
$hobbies = [];
$interests = [];
$organizations = [];
$showContact = false;
$showFamily = false;
$showAddress = false;
$showAcademic = false;
$showAchievements = false;
$showHobbies = false;
$showOrganizations = false;
/* SECTION VISIBILITY */
if (!$profileUnavailable) {
    $showContact =
        canViewerSeeVisibility(
            $student[
                "contact_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
    $showFamily =
        canViewerSeeVisibility(
            $student[
                "family_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
    $showAddress =
        canViewerSeeVisibility(
            $student[
                "address_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
    $showAcademic =
        canViewerSeeVisibility(
            $student[
                "academic_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
    $showAchievements =
        canViewerSeeVisibility(
            $student[
                "achievement_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
    $showHobbies =
        canViewerSeeVisibility(
            $student[
                "hobbies_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
    $showOrganizations =
        canViewerSeeVisibility(
            $student[
                "organizations_visibility"
            ] ?? "private",
            $viewerIsSchoolUser
        );
/* FAMILY */
    if ($showFamily) {
        $familyStmt =
            $conn->prepare("
                SELECT
                    father_name,
                    mother_name,
                    guardian_name,
                    guardian_contact
                FROM family_information
                WHERE user_id = ?
                LIMIT 1
            ");
        if ($familyStmt) {
            $familyStmt->bind_param(
                "i",
                $profileUserId
            );
            $familyStmt->execute();
            $familyRow =
                $familyStmt
                    ->get_result()
                    ->fetch_assoc();
            $familyStmt->close();
            if ($familyRow) {
                $family =
                    array_merge(
                        $family,
                        $familyRow
                    );
            }
        }
    }
    /* EDUCATION */
    if ($showAcademic) {
        $educationStmt =
            $conn->prepare("
                SELECT
                    education_level,
                    school_name
                FROM education
                WHERE user_id = ?
                ORDER BY id ASC
            ");
        if ($educationStmt) {
            $educationStmt->bind_param(
                "i",
                $profileUserId
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
    }
    /* ACCOMPLISHMENTS */
    if ($showAchievements) {
        $achievementStmt =
            $conn->prepare("
                SELECT
                    title,
                    category,
                    date_achieved
                FROM accomplishments
                WHERE user_id = ?
                ORDER BY
                    date_achieved DESC,
                    id DESC
                LIMIT 25
            ");
        if ($achievementStmt) {
            $achievementStmt->bind_param(
                "i",
                $profileUserId
            );
            $achievementStmt->execute();
            $achievementResult =
                $achievementStmt
                    ->get_result();
            while (
                $row =
                    $achievementResult
                        ->fetch_assoc()
            ) {
                $accomplishments[] =
                    $row;
            }
            $achievementStmt->close();
        }
    }
    /* HOBBIES */
    if ($showHobbies) {
        $hobbyStmt =
            $conn->prepare("
                SELECT hobby
                FROM hobbies
                WHERE user_id = ?
                ORDER BY id DESC
                LIMIT 30
            ");
        if ($hobbyStmt) {
            $hobbyStmt->bind_param(
                "i",
                $profileUserId
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
        $interestStmt =
            $conn->prepare("
                SELECT interest
                FROM interests
                WHERE user_id = ?
                ORDER BY id DESC
                LIMIT 30
            ");
        if ($interestStmt) {
            $interestStmt->bind_param(
                "i",
                $profileUserId
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
    }
    /* ORGANIZATIONS */
    if ($showOrganizations) {
        $organizationStmt =
            $conn->prepare("
                SELECT
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
                LIMIT 30
            ");
        if ($organizationStmt) {
            $organizationStmt->bind_param(
                "i",
                $profileUserId
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
    }
}
/* DISPLAY HELPERS */
function publicValue(
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
/* NAME */
if (!$profileUnavailable) {
    $nameParts = [];
    foreach (
        [
            $student["first_name"]
                ?? "",
            $student["middle_name"]
                ?? "",
            $student["last_name"]
                ?? ""
        ]
        as $part
    ) {
        $part =
            trim($part);
        if ($part !== "") {
            $nameParts[] =
                $part;
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
    $avatarInitial =
        strtoupper(
            substr(
                trim(
                    $student[
                        "first_name"
                    ] ?? ""
                ) !== ""
                    ? $student[
                        "first_name"
                    ]
                    : "S",
                0,
                1
            )
        );
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

        Student Profile | CVSWHO

    </title>

    <link

        rel="stylesheet"

        href="style.css"

    >

    <link

        rel="preconnect"

        href="https\://fonts.googleapis.com"

    >

    <link

        rel="preconnect"

        href="https\://fonts.gstatic.com"

        crossorigin

    >

    <link

        href="https\://fonts.googleapis.com/css2?family=Inter:wght\@400;500;600;700;800&display=swap"

        rel="stylesheet"

    >

    <style>

        body {

            background:

                var(--very-light-green);

        }

        .public-profile-page {

            width:

                min(

                    960px,

                    calc(100% - 40px)

                );

            margin: 0 auto;

            padding:

                55px 0 80px;

        }

        .public-back {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            margin-bottom: 24px;

            color:

                var(--primary-green);

            font-size: 11px;

            font-weight: 700;

        }

        .profile-hero-card {

            display: flex;

            align-items: center;

            gap: 25px;

            padding: 30px;

            background:

                var(--white);

            border:

                1px solid

                var(--border-gray);

            border-radius: 18px;

            box-shadow:

                var(--shadow-medium);

        }

        .profile-avatar {

            width: 96px;

            height: 96px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            overflow: hidden;

            color:

                var(--primary-green);

            background:

                var(--light-green);

            border-radius: 50%;

            font-size: 31px;

            font-weight: 800;

            background-size: cover;

            background-position: center;

            background-repeat:

                no-repeat;

        }

        .profile-main {

            min-width: 0;

            flex: 1;

        }

        .profile-main h1 {

            color:

                var(--dark-green);

            font-size:

                clamp(

                    25px,

                    4vw,

                    36px

                );

        }

        .profile-program {

            margin-top: 8px;

            color:

                var(--text-medium);

            font-size: 12px;

        }

        .basic-meta {

            display: flex;

            flex-wrap: wrap;

            gap: 7px;

            margin-top: 14px;

        }

        .basic-meta span {

            padding:

                6px 10px;

            color:

                var(--primary-green);

            background:

                var(--light-green);

            border-radius: 30px;

            font-size: 9px;

            font-weight: 700;

        }

        .visibility-badge {

            padding:

                7px 11px;

            color:

                var(--primary-green);

            background:

                #edf8f2;

            border:

                1px solid #cce5d7;

            border-radius: 30px;

            font-size: 9px;

            font-weight: 700;

        }

        .profile-section {

            margin-top: 20px;

            padding: 27px;

            background:

                var(--white);

            border:

                1px solid

                var(--border-gray);

            border-radius: 14px;

            box-shadow:

                var(--shadow-small);

        }

        .profile-section-header {

            margin-bottom: 20px;

        }

        .profile-section-header h2 {

            margin-top: 7px;

            color:

                var(--dark-green);

            font-size: 18px;

        }

        .about-text {

            color:

                var(--text-medium);

            font-size: 12px;

            line-height: 1.9;

            white-space: pre-line;

        }

        .detail-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    2,

                    minmax(0, 1fr)

                );

            gap: 12px;

        }

        .detail-item {

            padding: 15px;

            background:

                var(--very-light-green);

            border:

                1px solid

                var(--border-gray);

            border-radius: 10px;

        }

        .detail-item.full {

            grid-column:

                1 / -1;

        }

        .detail-label {

            display: block;

            color:

                var(--text-light);

            font-size: 8px;

            font-weight: 700;

            letter-spacing: .6px;

            text-transform: uppercase;

        }

        .detail-item strong,

        .detail-item p {

            display: block;

            margin-top: 6px;

            color:

                var(--dark-green);

            font-size: 11px;

            line-height: 1.6;

        }

        .list {

            display: flex;

            flex-direction: column;

            gap: 10px;

        }

        .list-item {

            display: flex;

            align-items: center;

            gap: 14px;

            padding: 15px;

            background:

                var(--very-light-green);

            border:

                1px solid

                var(--border-gray);

            border-radius: 10px;

        }

        .list-icon {

            width: 36px;

            height: 36px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            color:

                var(--primary-green);

            background:

                var(--light-green);

            border-radius: 9px;

            font-size: 9px;

            font-weight: 800;

        }

        .list-content {

            flex: 1;

        }

        .list-content span {

            color:

                var(--primary-green);

            font-size: 8px;

            font-weight: 700;

            text-transform: uppercase;

        }

        .list-content h3 {

            margin-top: 4px;

            color:

                var(--dark-green);

            font-size: 11px;

        }

        .list-content p {

            margin-top: 4px;

            color:

                var(--text-medium);

            font-size: 9px;

        }

        .tag-list {

            display: flex;

            flex-wrap: wrap;

            gap: 8px;

        }

        .tag {

            padding:

                7px 10px;

            color:

                var(--primary-green);

            background:

                var(--light-green);

            border:

                1px solid #cfe6d8;

            border-radius: 30px;

            font-size: 9px;

            font-weight: 600;

        }

        .empty-state {

            padding: 18px;

            color:

                var(--text-medium);

            background:

                var(--very-light-green);

            border:

                1px solid

                var(--border-gray);

            border-radius: 10px;

            font-size: 10px;

            text-align: center;

        }

        .unavailable-card {

            max-width: 650px;

            margin:

                50px auto;

            padding:

                45px 30px;

            text-align: center;

            background:

                var(--white);

            border:

                1px solid

                var(--border-gray);

            border-radius: 18px;

            box-shadow:

                var(--shadow-medium);

        }

        .unavailable-card h1 {

            color:

                var(--dark-green);

        }

        .unavailable-card p {

            margin:

                10px auto 20px;

            color:

                var(--text-medium);

            font-size: 11px;

        }

        @media (

            max-width: 650px

        ) {

            .public-profile-page {

                width:

                    calc(

                        100% - 28px

                    );

            }

            .profile-hero-card {

                align-items:

                    flex-start;

                flex-direction:

                    column;

            }

            .detail-grid {

                grid-template-columns:

                    1fr;

            }

            .detail-item.full {

                grid-column:

                    auto;

            }

        }

    </style>

</head>

<body>

<header class="navbar">

    <div

        class="

            container

            nav-container

        "

    >

        <a

            href="index.php"

            class="brand"

        >

            <div class="brand-logo">

                C

            </div>

            <div class="brand-text">

                <span class="brand-name">

                    CVSWHO

                </span>

                <span class="brand-subtitle">

                    Student Profile Platform

                </span>

            </div>

        </a>

        <nav class="desktop-nav">

            <a

                href="index.php"

                class="nav-link"

            >

                Home

            </a>

            <a

                href="index.php#student-search"

                class="nav-link"

            >

                Search Students

            </a>

            <a

                href="index.php#features"

                class="nav-link"

            >

                Features

            </a>

        </nav>

        <div class="nav-actions">

            <?php if (
                $viewerIsSchoolUser
            ): ?>

                <a

                    href="<?= htmlspecialchars(

                        $accountDashboard

                    ) ?>"

                    class="

                        btn

                        btn-primary

                    "

                >

                    <?= htmlspecialchars(

                        $loggedInFirstName

                    ) ?>

                    <span class="button-arrow">

                        →

                    </span>

                </a>

            <?php else: ?>

                <a

                    href="login.php"

                    class="

                        btn

                        btn-outline

                    "

                >

                    Login

                </a>

                <a

                    href="register.php"

                    class="

                        btn

                        btn-primary

                    "

                >

                    Register

                </a>

            <?php endif; ?>

        </div>

    </div>

</header>

<main class="public-profile-page">

    <a

        href="index.php#student-search"

        class="public-back"

    >

        ← Back to Student Search

    </a>

    <?php if (
        $profileUnavailable
    ): ?>

        <section class="unavailable-card">

            <h1>

                Profile unavailable

            </h1>

            <p>

                This profile is private,

                school-only, or unavailable

                for your current account.

            </p>

            <a

                href="index.php#student-search"

                class="

                    btn

                    btn-primary

                "

            >

                Back to Search

            </a>

        </section>

    <?php else: ?>

        <section class="profile-hero-card">

            <div

                class="profile-avatar"

                <?php if (
                    !empty(
                        $student[
                            "profile_photo"
                        ]
                    )
                ): ?>

                    style="

                        background-image:

                        url('<?= htmlspecialchars(

                            $student[

                                "profile_photo"

                            ],

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

            <div class="profile-main">

                <span class="section-label">

                    STUDENT PROFILE

                </span>

                <h1>

                    <?= htmlspecialchars(

                        $fullName

                    ) ?>

                </h1>

                <?php if (
                    $showAcademic &&
                    !empty(
                        $student[
                            "program"
                        ]
                    )
                ): ?>

                    <p class="profile-program">

                        <?= htmlspecialchars(

                            $student[

                                "program"

                            ]

                        ) ?>

                    </p>

                <?php endif; ?>

                <div class="basic-meta">

                    <?php if (
                        !empty(
                            $student[
                                "year_level"
                            ]
                        )
                    ): ?>

                        <span>

                            <?= htmlspecialchars(

                                $student[

                                    "year_level"

                                ]

                            ) ?>

                        </span>

                    <?php endif; ?>

                    <?php if (
                        !empty(
                            $student[
                                "section"
                            ]
                        )
                    ): ?>

                        <span>

                            Section

                            <?= htmlspecialchars(

                                $student[

                                    "section"

                                ]

                            ) ?>

                        </span>

                    <?php endif; ?>

                </div>

            </div>

            <div class="visibility-badge">

                <?php if (
                    $student[
                        "profile_visibility"
                    ] === "school_only"
                ): ?>

                    School Only

                <?php else: ?>

                    Public Profile

                <?php endif; ?>

            </div>

        </section>

        <?php if (
            trim(
                $student[
                    "about_me"
                ] ?? ""
            ) !== ""
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

                    <span class="section-label">

                        ABOUT ME

                    </span>

                    <h2>

                        Introduction

                    </h2>

                </div>

                <p class="about-text">

                    <?= htmlspecialchars(

                        $student[

                            "about_me"

                        ]

                    ) ?>

                </p>

            </section>

        <?php endif; ?>

        <?php if (
            $showAcademic
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

                    <span class="section-label">

                        ACADEMIC INFORMATION

                    </span>

                    <h2>

                        Academic Details

                    </h2>

                </div>

                <div class="detail-grid">

                    <div

                        class="

                            detail-item

                            full

                        "

                    >

                        <span class="detail-label">

                            Program

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $student[

                                        "program"

                                    ] ?? ""

                                )

                            ) ?>

                        </strong>

                    </div>

                    <div class="detail-item">

                        <span class="detail-label">

                            College

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $student[

                                        "college"

                                    ] ?? ""

                                )

                            ) ?>

                        </strong>

                    </div>

                    <div class="detail-item">

                        <span class="detail-label">

                            Campus

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $student[

                                        "campus"

                                    ] ?? ""

                                )

                            ) ?>

                        </strong>

                    </div>

                </div>

            </section>

            <?php if (
                count(
                    $education
                ) > 0
            ): ?>

                <section class="profile-section">

                    <div class="profile-section-header">

                        <span class="section-label">

                            EDUCATIONAL BACKGROUND

                        </span>

                        <h2>

                            Previous Education

                        </h2>

                    </div>

                    <div class="list">

                        <?php foreach (
                            $education
                            as $index => $school
                        ): ?>

                            <div class="list-item">

                                <div class="list-icon">

                                    <?= str_pad(

                                        (string) (

                                            $index + 1

                                        ),

                                        2,

                                        "0",

                                        STR_PAD_LEFT

                                    ) ?>

                                </div>

                                <div class="list-content">

                                    <span>

                                        <?= htmlspecialchars(

                                            str_replace(

                                                "_",

                                                " ",

                                                $school[

                                                    "education_level"

                                                ]

                                            )

                                        ) ?>

                                    </span>

                                    <h3>

                                        <?= htmlspecialchars(

                                            $school[

                                                "school_name"

                                            ]

                                        ) ?>

                                    </h3>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endif; ?>

        <?php endif; ?>

        <?php if (
            $showContact
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

                    <span class="section-label">

                        CONTACT

                    </span>

                    <h2>

                        Contact Information

                    </h2>

                </div>

                <div class="detail-grid">

                    <div class="detail-item">

                        <span class="detail-label">

                            Email

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $student[

                                        "email"

                                    ] ?? ""

                                )

                            ) ?>

                        </strong>

                    </div>

                    <div class="detail-item">

                        <span class="detail-label">

                            Phone

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $student[

                                        "phone"

                                    ] ?? ""

                                )

                            ) ?>

                        </strong>

                    </div>

                </div>

            </section>

        <?php endif; ?>

        <?php if (
            $showAddress
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

                    <span class="section-label">

                        ADDRESS

                    </span>

                    <h2>

                        Address

                    </h2>

                </div>

                <div

                    class="

                        detail-item

                        full

                    "

                >

                    <p>

                        <?= nl2br(

                            htmlspecialchars(

                                publicValue(

                                    $student[

                                        "address"

                                    ] ?? ""

                                )

                            )

                        ) ?>

                    </p>

                </div>

            </section>

        <?php endif; ?>

        <?php if (
            $showFamily
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

                    <span class="section-label">

                        FAMILY INFORMATION

                    </span>

                    <h2>

                        Family Details

                    </h2>

                </div>

                <div class="detail-grid">

                    <div class="detail-item">

                        <span class="detail-label">

                            Father's Name

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $family[

                                        "father_name"

                                    ]

                                )

                            ) ?>

                        </strong>

                    </div>

                    <div class="detail-item">

                        <span class="detail-label">

                            Mother's Name

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $family[

                                        "mother_name"

                                    ]

                                )

                            ) ?>

                        </strong>

                    </div>

                    <div class="detail-item">

                        <span class="detail-label">

                            Guardian

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $family[

                                        "guardian_name"

                                    ]

                                )

                            ) ?>

                        </strong>

                    </div>

                    <div class="detail-item">

                        <span class="detail-label">

                            Guardian Contact

                        </span>

                        <strong>

                            <?= htmlspecialchars(

                                publicValue(

                                    $family[

                                        "guardian_contact"

                                    ]

                                )

                            ) ?>

                        </strong>

                    </div>

                </div>

            </section>

        <?php endif; ?>

        <?php if (
            $showAchievements
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

                    <span class="section-label">

                        ACCOMPLISHMENTS

                    </span>

                    <h2>

                        Achievements

                    </h2>

                </div>

                <?php if (
                    count(
                        $accomplishments
                    ) > 0
                ): ?>

                    <div class="list">

                        <?php foreach (
                            $accomplishments
                            as $item
                        ): ?>

                            <div class="list-item">

                                <div class="list-icon">

                                    ✓

                                </div>

                                <div class="list-content">

                                    <span>

                                        <?= htmlspecialchars(

                                            $item[

                                                "category"

                                            ] ?? ""

                                        ) ?>

                                    </span>

                                    <h3>

                                        <?= htmlspecialchars(

                                            $item[

                                                "title"

                                            ] ?? ""

                                        ) ?>

                                    </h3>

                                    <?php if (
                                        !empty(
                                            $item[
                                                "date_achieved"
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <?= htmlspecialchars(

                                                date(

                                                    "M d, Y",

                                                    strtotime(

                                                        $item[

                                                            "date_achieved"

                                                        ]

                                                    )

                                                )

                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        No accomplishments

                        added yet.

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <?php if (
            $showHobbies
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

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

                    <div class="tag-list">

                        <?php foreach (
                            $hobbies
                            as $hobby
                        ): ?>

                            <span class="tag">

                                <?= htmlspecialchars(

                                    $hobby

                                ) ?>

                            </span>

                        <?php endforeach; ?>

                        <?php foreach (
                            $interests
                            as $interest
                        ): ?>

                            <span class="tag">

                                <?= htmlspecialchars(

                                    $interest

                                ) ?>

                            </span>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        No hobbies or interests

                        added yet.

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <?php if (
            $showOrganizations
        ): ?>

            <section class="profile-section">

                <div class="profile-section-header">

                    <span class="section-label">

                        ORGANIZATIONS & ACTIVITIES

                    </span>

                    <h2>

                        Organizations and Activities

                    </h2>

                </div>

                <?php if (
                    count(
                        $organizations
                    ) > 0
                ): ?>

                    <div class="list">

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
                                match (
                                    $organizationType
                                ) {
                                    "club" =>
                                        "Club",
                                    "student_government" =>
                                        "Student Government",
                                    "event" =>
                                        "Event",
                                    default =>
                                        "Organization"
                                };
                            ?>

                            <div class="list-item">

                                <div class="list-icon">

                                    O

                                </div>

                                <div class="list-content">

                                    <span>

                                        <?= htmlspecialchars(

                                            $organizationTypeLabel

                                        ) ?>

                                    </span>

                                    <h3>

                                        <?= htmlspecialchars(

                                            $organization[
                                                "name"
                                            ] ?? ""

                                        ) ?>

                                    </h3>

                                    <?php if (
                                        !empty(
                                            $organization[
                                                "position"
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <?= htmlspecialchars(

                                                $organization[
                                                    "position"
                                                ]

                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $organization[
                                                "participation_date"
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <?= htmlspecialchars(

                                                date(

                                                    "M d, Y",

                                                    strtotime(

                                                        $organization[
                                                            "participation_date"
                                                        ]

                                                    )

                                                )

                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $organization[
                                                "description"
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <?= nl2br(

                                                htmlspecialchars(

                                                    $organization[
                                                        "description"
                                                    ]

                                                )

                                            ) ?>

                                        </p>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        No organizations or activities

                        added yet.

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    <?php endif; ?>

</main>

<footer class="footer">

    <div class="container">

        <div class="footer-bottom">

            <p>

                © 2026 CVSWHO.

            </p>

            <p>

                Student Profile Platform

            </p>

        </div>

    </div>

</footer>

</body>

</html>
