<?php

session_start();

require_once "db.php";


/* =========================================================
   CURRENT LOGGED-IN USER
========================================================= */

$isLoggedIn = false;

$loggedInFirstName = "";

$loggedInRole = "";

$accountDashboard =
    "student_dashboard.php";


if (isset($_SESSION["user_id"])) {

    $currentUserId =
        (int) $_SESSION["user_id"];


    $currentUserStmt =
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


    if ($currentUserStmt) {

        $currentUserStmt->bind_param(
            "i",
            $currentUserId
        );


        $currentUserStmt->execute();


        $currentUser =
            $currentUserStmt
                ->get_result()
                ->fetch_assoc();


        $currentUserStmt->close();


        if (
            $currentUser &&
            $currentUser[
                "account_status"
            ] === "active" &&
            (int) $currentUser[
                "email_verified"
            ] === 1
        ) {

            $isLoggedIn = true;


            $loggedInFirstName =
                trim(
                    $currentUser[
                        "first_name"
                    ] ?? ""
                );


            if (
                $loggedInFirstName === ""
            ) {

                $loggedInFirstName =
                    "Account";
            }


            $loggedInRole =
                $currentUser[
                    "role"
                ] ?? "student";


            if (
                $loggedInRole
                === "administrator"
            ) {

                $accountDashboard =
                    "admin_dashboard.php";
            }
        }
    }
}


/* =========================================================
   STUDENT SEARCH
========================================================= */

$studentSearch =
    trim(
        $_GET[
            "student_search"
        ] ?? ""
    );


$searchResults = [];

$searchMessage = "";


/* =========================================================
   NORMALIZE SEARCH
========================================================= */

function normalizeStudentSearch(
    string $value
): string {

    $value =
        trim($value);


    if (
        function_exists(
            "mb_strtolower"
        )
    ) {

        $value =
            mb_strtolower(
                $value,
                "UTF-8"
            );

    } else {

        $value =
            strtolower($value);
    }


    $value =
        preg_replace(
            '/\s+/',
            ' ',
            $value
        );


    return trim($value);
}


/* =========================================================
   SEARCH SCORE
========================================================= */

function calculateStudentScore(
    array $student,
    string $query
): int {

    $query =
        normalizeStudentSearch(
            $query
        );


    $first =
        normalizeStudentSearch(
            $student[
                "first_name"
            ] ?? ""
        );


    $middle =
        normalizeStudentSearch(
            $student[
                "middle_name"
            ] ?? ""
        );


    $last =
        normalizeStudentSearch(
            $student[
                "last_name"
            ] ?? ""
        );


    $section =
        normalizeStudentSearch(
            $student[
                "section"
            ] ?? ""
        );


    $year =
        normalizeStudentSearch(
            $student[
                "year_level"
            ] ?? ""
        );


    $program =
        normalizeStudentSearch(
            $student[
                "search_program"
            ] ?? ""
        );


    $fullName =
        trim(
            $first .
            " " .
            $middle .
            " " .
            $last
        );


    $shortName =
        trim(
            $first .
            " " .
            $last
        );


    $score = 0;


    if (
        $query === $fullName ||
        $query === $shortName
    ) {

        $score += 150;
    }


    if (
        str_starts_with(
            $fullName,
            $query
        ) ||
        str_starts_with(
            $shortName,
            $query
        )
    ) {

        $score += 80;
    }


    if (
        str_contains(
            $fullName,
            $query
        )
    ) {

        $score += 50;
    }


    $tokens =
        preg_split(
            '/\s+/',
            $query,
            -1,
            PREG_SPLIT_NO_EMPTY
        );


    foreach (
        $tokens
        as $token
    ) {

        if ($token === "") {
            continue;
        }


        if ($first === $token) {

            $score += 35;

        } elseif (
            str_starts_with(
                $first,
                $token
            )
        ) {

            $score += 25;

        } elseif (
            str_contains(
                $first,
                $token
            )
        ) {

            $score += 15;
        }


        if ($last === $token) {

            $score += 40;

        } elseif (
            str_starts_with(
                $last,
                $token
            )
        ) {

            $score += 28;

        } elseif (
            str_contains(
                $last,
                $token
            )
        ) {

            $score += 18;
        }


        if (
            $middle !== "" &&
            str_contains(
                $middle,
                $token
            )
        ) {

            $score += 8;
        }


        if (
            $section !== "" &&
            str_contains(
                $section,
                $token
            )
        ) {

            $score += 15;
        }


        if (
            $year !== "" &&
            str_contains(
                $year,
                $token
            )
        ) {

            $score += 12;
        }


        if (
            $program !== "" &&
            str_contains(
                $program,
                $token
            )
        ) {

            $score += 12;
        }
    }


    return $score;
}


/* =========================================================
   RUN SEARCH
========================================================= */

if ($studentSearch !== "") {

    $normalizedSearch =
        normalizeStudentSearch(
            $studentSearch
        );


    if (
        strlen(
            $normalizedSearch
        ) < 2
    ) {

        $searchMessage =
            "Please enter at least 2 characters.";

    } else {

        $containsSearch =
            "%" .
            $studentSearch .
            "%";


        $startsSearch =
            $studentSearch .
            "%";


        $schoolAccess =
            $isLoggedIn
                ? 1
                : 0;


        $searchStmt =
            $conn->prepare("
                SELECT

                    u.id AS user_id,

                    sp.first_name,
                    sp.middle_name,
                    sp.last_name,

                    sp.year_level,
                    sp.section,

                    CASE

                        WHEN
                            ps.academic_visibility = 'public'

                            OR

                            (
                                ps.academic_visibility = 'school_only'
                                AND ? = 1
                            )

                        THEN sp.program

                        ELSE NULL

                    END AS public_program,


                    CASE

                        WHEN
                            ps.academic_visibility = 'public'

                            OR

                            (
                                ps.academic_visibility = 'school_only'
                                AND ? = 1
                            )

                        THEN sp.program

                        ELSE ''

                    END AS search_program,


                    sp.profile_photo,

                    ps.profile_visibility


                FROM users u


                INNER JOIN student_profiles sp
                    ON sp.user_id = u.id


                INNER JOIN privacy_settings ps
                    ON ps.user_id = u.id


                WHERE

                    u.role = 'student'

                    AND

                    u.account_status = 'active'

                    AND

                    u.email_verified = 1

                    AND

                    (
                        ps.profile_visibility = 'public'

                        OR

                        (
                            ps.profile_visibility = 'school_only'
                            AND ? = 1
                        )
                    )

                    AND

                    (
                        sp.first_name LIKE ?

                        OR

                        sp.middle_name LIKE ?

                        OR

                        sp.last_name LIKE ?

                        OR

                        CONCAT(
                            sp.first_name,
                            ' ',
                            sp.last_name
                        ) LIKE ?

                        OR

                        CONCAT(
                            sp.first_name,
                            ' ',
                            COALESCE(
                                sp.middle_name,
                                ''
                            ),
                            ' ',
                            sp.last_name
                        ) LIKE ?

                        OR

                        sp.section LIKE ?

                        OR

                        sp.year_level LIKE ?

                        OR

                        (
                            (
                                ps.academic_visibility = 'public'

                                OR

                                (
                                    ps.academic_visibility = 'school_only'
                                    AND ? = 1
                                )
                            )

                            AND

                            sp.program LIKE ?
                        )
                    )


                ORDER BY

                    CASE

                        WHEN
                            CONCAT(
                                sp.first_name,
                                ' ',
                                sp.last_name
                            ) = ?

                        THEN 1


                        WHEN
                            CONCAT(
                                sp.first_name,
                                ' ',
                                sp.last_name
                            ) LIKE ?

                        THEN 2


                        WHEN
                            sp.last_name LIKE ?

                        THEN 3


                        WHEN
                            sp.first_name LIKE ?

                        THEN 4


                        ELSE 5

                    END,

                    sp.last_name ASC,
                    sp.first_name ASC


                LIMIT 100
            ");


        if ($searchStmt) {

            $searchStmt->bind_param(
                "iiisssssssisisss",

                $schoolAccess,
                $schoolAccess,
                $schoolAccess,

                $containsSearch,
                $containsSearch,
                $containsSearch,
                $containsSearch,
                $containsSearch,
                $containsSearch,
                $containsSearch,

                $schoolAccess,

                $containsSearch,

                $studentSearch,
                $startsSearch,
                $startsSearch,
                $startsSearch
            );


            $searchStmt->execute();


            $candidateResult =
                $searchStmt
                    ->get_result();


            while (
                $candidate =
                    $candidateResult
                        ->fetch_assoc()
            ) {

                $score =
                    calculateStudentScore(
                        $candidate,
                        $studentSearch
                    );


                if ($score <= 0) {
                    continue;
                }


                $candidate["score"] =
                    $score;


                $searchResults[] =
                    $candidate;
            }


            $searchStmt->close();


            usort(
                $searchResults,

                function (
                    array $a,
                    array $b
                ): int {

                    if (
                        $a["score"]
                        ===
                        $b["score"]
                    ) {

                        $aName =
                            strtolower(
                                trim(
                                    $a[
                                        "first_name"
                                    ] .
                                    " " .
                                    $a[
                                        "last_name"
                                    ]
                                )
                            );


                        $bName =
                            strtolower(
                                trim(
                                    $b[
                                        "first_name"
                                    ] .
                                    " " .
                                    $b[
                                        "last_name"
                                    ]
                                )
                            );


                        return strcmp(
                            $aName,
                            $bName
                        );
                    }


                    return
                        $b["score"]
                        <=>
                        $a["score"];
                }
            );


            $searchResults =
                array_slice(
                    $searchResults,
                    0,
                    50
                );


            if (
                count(
                    $searchResults
                ) === 0
            ) {

                $searchMessage =
                    "No student profiles matched your search.";
            }

        } else {

            $searchMessage =
                "Student search is temporarily unavailable.";
        }
    }
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
        CVSWHO | Student Profile Platform
    </title>


    <meta
        name="description"
        content="CVSWHO is a student information and portfolio management platform for Cavite State University students."
    >


    <link
        rel="stylesheet"
        href="style.css"
    >


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >


    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        .account-button {

            gap: 9px;

            min-width: 105px;
        }


        .account-dot {

            width: 7px;
            height: 7px;

            flex-shrink: 0;

            background:
                #b7e4c7;

            border:
                2px solid
                rgba(
                    255,
                    255,
                    255,
                    0.75
                );

            border-radius: 50%;
        }


        #student-search {

            scroll-margin-top: 110px;
        }


        .student-search-area {

            max-width: 820px;

            margin:
                34px auto 45px;
        }


        .student-search-panel {

            padding: 18px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.9
                );

            border:
                1px solid
                var(--border-gray);

            border-radius: 16px;

            box-shadow:
                0 12px 35px
                rgba(
                    0,
                    55,
                    30,
                    0.07
                );
        }


        .student-search-form {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 8px;

            background:
                var(--white);

            border:
                1px solid
                var(--border-gray);

            border-radius: 12px;

            transition:
                var(--transition);
        }


        .student-search-form:focus-within {

            border-color:
                rgba(
                    0,
                    107,
                    63,
                    0.45
                );

            box-shadow:
                0 0 0 3px
                rgba(
                    0,
                    107,
                    63,
                    0.06
                );
        }


        .student-search-icon {

            width: 40px;
            height: 40px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            color:
                var(--primary-green);

            background:
                var(--light-green);

            border-radius: 9px;

            font-size: 17px;
        }


        .student-search-input {

            min-width: 0;

            flex: 1;

            padding:
                11px 6px;

            color:
                var(--text-dark);

            background:
                transparent;

            border: 0;

            outline: 0;

            font-family:
                inherit;

            font-size: 12px;
        }


        .student-search-input::placeholder {

            color:
                var(--text-light);
        }


        .student-search-button {

            padding:
                12px 21px;

            color:
                var(--white);

            background:
                var(--primary-green);

            border:
                1px solid
                var(--primary-green);

            border-radius: 9px;

            font-family:
                inherit;

            font-size: 11px;

            font-weight: 700;

            cursor: pointer;

            transition:
                var(--transition);
        }


        .student-search-button:hover {

            background:
                var(--deep-green);

            transform:
                translateY(-1px);
        }


        .search-helper {

            margin-top: 10px;

            color:
                var(--text-light);

            font-size: 9px;

            line-height: 1.6;

            text-align: center;
        }


        .student-search-results {

            margin-top: 18px;
        }


        .search-result-heading {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            padding:
                0 3px 12px;
        }


        .search-result-heading h3 {

            color:
                var(--deep-green);

            font-size: 12px;
        }


        .search-result-heading span {

            color:
                var(--text-medium);

            font-size: 9px;
        }


        .student-results-scroll {

            max-height: 230px;

            overflow-y: auto;

            overflow-x: hidden;

            padding:
                3px 8px 3px 3px;

            scrollbar-width: thin;

            scrollbar-color:
                var(--primary-green)
                var(--light-green);
        }


        .student-results-scroll::-webkit-scrollbar {

            width: 8px;
        }


        .student-results-scroll::-webkit-scrollbar-track {

            background:
                var(--light-green);

            border-radius: 10px;
        }


        .student-results-scroll::-webkit-scrollbar-thumb {

            background:
                var(--primary-green);

            border-radius: 10px;

            border:
                2px solid
                var(--light-green);
        }


        .student-result-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 10px;
        }


        .student-result-card {

            position: relative;

            display: flex;

            align-items: center;

            gap: 14px;

            min-height: 94px;

            padding: 16px;

            background:
                var(--white);

            border:
                1px solid
                var(--border-gray);

            border-radius: 12px;

            transition:
                var(--transition);
        }


        .student-result-card:hover {

            transform:
                translateY(-2px);

            border-color:
                #b9d8c7;

            box-shadow:
                0 8px 22px
                rgba(
                    0,
                    77,
                    42,
                    0.09
                );
        }


        .student-result-avatar {

            width: 50px;
            height: 50px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            overflow: hidden;

            color:
                var(--primary-green);

            background:
                var(--light-green);

            border-radius: 50%;

            font-size: 16px;

            font-weight: 800;

            background-size: cover;

            background-position: center;

            background-repeat:
                no-repeat;
        }


        .student-result-information {

            min-width: 0;

            flex: 1;
        }


        .student-result-information h4 {

            overflow: hidden;

            color:
                var(--deep-green);

            font-size: 12px;

            text-overflow:
                ellipsis;

            white-space: nowrap;
        }


        .student-result-information p {

            margin-top: 4px;

            color:
                var(--text-medium);

            font-size: 9px;

            overflow: hidden;

            text-overflow:
                ellipsis;

            white-space: nowrap;
        }


        .student-result-meta {

            display: flex;

            flex-wrap: wrap;

            gap: 5px;

            margin-top: 8px;
        }


        .student-result-meta span {

            padding:
                4px 7px;

            color:
                var(--primary-green);

            background:
                var(--light-green);

            border-radius: 20px;

            font-size: 8px;

            font-weight: 600;
        }


        .school-only-badge {

            background:
                #edf8f2 !important;

            color:
                #006b3f !important;

            border:
                1px solid #cfe5d8;
        }


        .student-result-arrow {

            flex-shrink: 0;

            color:
                var(--primary-green);

            font-size: 16px;

            transition:
                transform
                var(--transition);
        }


        .student-result-card:hover
        .student-result-arrow {

            transform:
                translateX(3px);
        }


        .student-search-empty {

            padding: 20px;

            color:
                var(--text-medium);

            background:
                var(--white);

            border:
                1px solid
                var(--border-gray);

            border-radius: 10px;

            font-size: 10px;

            text-align: center;
        }


        @media (
            max-width: 650px
        ) {

            .student-search-panel {

                padding: 13px;
            }


            .student-search-form {

                flex-wrap: wrap;
            }


            .student-search-icon {

                display: none;
            }


            .student-search-input {

                width: 100%;

                flex-basis: 100%;

                padding:
                    13px 10px;
            }


            .student-search-button {

                width: 100%;
            }


            .student-result-grid {

                grid-template-columns:
                    1fr;
            }


            .student-results-scroll {

                max-height: 520px;
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
                href="#home"
                class="nav-link active"
            >
                Home
            </a>


            <a
                href="#about"
                class="nav-link"
            >
                About
            </a>


            <a
                href="#features"
                class="nav-link"
            >
                Features
            </a>


        </nav>


        <div class="nav-actions">


            <?php if (
                $isLoggedIn
            ): ?>


                <a
                    href="<?= htmlspecialchars(
                        $accountDashboard
                    ) ?>"
                    class="
                        btn
                        btn-primary
                        account-button
                    "
                >


                    <span
                        class="account-dot"
                    ></span>


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


        <button
            class="mobile-menu-button"
            type="button"
            aria-label="Open navigation menu"
        >

            <span></span>

            <span></span>

            <span></span>

        </button>


    </div>


</header>


<main>


<section
    class="hero-section"
    id="home"
>


    <div
        class="hero-background-shape"
    ></div>


    <div
        class="
            container
            hero-container
        "
    >


        <div class="hero-content">


            <div class="hero-badge">


                <span
                    class="badge-dot"
                ></span>


                Cavite State University
                Student Platform


            </div>


            <h1>

                Your Profile.

                <span>
                    Your Achievements.
                </span>

                Your Story.

            </h1>


            <p class="hero-description">

                CVSWHO helps students organize,
                maintain, and present their
                personal information,
                accomplishments, interests,
                and activities in one
                centralized platform.

            </p>


            <div class="hero-buttons">


                <?php if (
                    $isLoggedIn
                ): ?>


                    <a
                        href="<?= htmlspecialchars(
                            $accountDashboard
                        ) ?>"
                        class="
                            btn
                            btn-primary
                            btn-large
                        "
                    >

                        Go to Dashboard

                        <span class="button-arrow">
                            →
                        </span>

                    </a>


                <?php else: ?>


                    <a
                        href="register.php"
                        class="
                            btn
                            btn-primary
                            btn-large
                        "
                    >

                        Get Started

                        <span class="button-arrow">
                            →
                        </span>

                    </a>


                <?php endif; ?>


                <a
                    href="#features"
                    class="
                        btn
                        btn-light
                        btn-large
                    "
                >
                    Explore Features
                </a>


            </div>


            <div class="hero-trust">


                <div class="trust-icon">
                    ✓
                </div>


                <div>


                    <strong>

                        Organized. Accessible.
                        Student-Centered.

                    </strong>


                    <p>
                        Designed to help you
                        manage your student profile.
                    </p>


                </div>


            </div>


        </div>


        <div class="benefits-visual">


            <div class="benefits-heading">


                <span class="section-label">
                    WHY CVSWHO?
                </span>


                <h2>

                    Designed Around

                    <span>
                        Your Needs.
                    </span>

                </h2>


                <p>

                    A simple and organized way
                    to manage your student
                    information and accomplishments.

                </p>


            </div>


            <div class="benefits-grid">


                <div class="benefit-card">


                    <div
                        class="
                            benefit-icon
                            green-icon
                        "
                    >
                        ⛨
                    </div>


                    <div>


                        <h3>
                            Information Protection
                        </h3>


                        <p>

                            Keep your personal
                            information organized
                            with privacy and
                            visibility controls.

                        </p>


                    </div>


                </div>


                <div class="benefit-card">


                    <div
                        class="
                            benefit-icon
                            blue-icon
                        "
                    >
                        ▦
                    </div>


                    <div>


                        <h3>
                            Easy Layout
                        </h3>


                        <p>

                            Access different profile
                            sections through a clear
                            interface.

                        </p>


                    </div>


                </div>


                <div class="benefit-card">


                    <div
                        class="
                            benefit-icon
                            purple-icon
                        "
                    >
                        ◈
                    </div>


                    <div>


                        <h3>
                            Organized Records
                        </h3>


                        <p>

                            Store education,
                            achievements, interests,
                            and activities together.

                        </p>


                    </div>


                </div>


                <div class="benefit-card">


                    <div
                        class="
                            benefit-icon
                            orange-icon
                        "
                    >
                        ◉
                    </div>


                    <div>


                        <h3>
                            Personal Control
                        </h3>


                        <p>

                            Maintain your profile
                            and control what you
                            present.

                        </p>


                    </div>


                </div>


            </div>


            <div class="benefits-bottom-card">


                <div class="bottom-card-icon">
                    ✓
                </div>


                <div>


                    <strong>
                        Built for Students
                    </strong>


                    <p>

                        Manage your information
                        with clarity and confidence.

                    </p>


                </div>


            </div>


        </div>


    </div>


</section>


<section
    class="introduction-section"
    id="about"
>


    <div
        class="
            container
            introduction-container
        "
    >


        <div
            class="
                section-heading
                centered-heading
            "
        >


            <span class="section-label">
                ABOUT CVSWHO
            </span>


            <h2>

                Everything About You,

                <span>
                    In One Place.
                </span>

            </h2>


            <p>

                CVSWHO provides students with
                a centralized platform where
                they can manage their personal
                information, academic background,
                accomplishments, and activities.

            </p>


        </div>


        <div
            class="student-search-area"
            id="student-search"
        >


            <div class="student-search-panel">


                <form
                    method="GET"
                    action="index.php#student-search"
                    class="student-search-form"
                >


                    <div class="student-search-icon">
                        ⌕
                    </div>


                    <input
                        type="search"
                        name="student_search"
                        class="student-search-input"
                        value="<?= htmlspecialchars(
                            $studentSearch
                        ) ?>"
                        placeholder="Search student name, section, year, or visible program..."
                        autocomplete="off"
                        aria-label="Search student profiles"
                    >


                    <button
                        type="submit"
                        class="student-search-button"
                    >
                        Search Students
                    </button>


                </form>


                <p class="search-helper">


                    Visitors can find
                    Public profiles.


                    Logged-in CVSWHO/CvSU
                    users can also find
                    School Only profiles.


                </p>


                <?php if (
                    $studentSearch !== ""
                ): ?>


                    <div class="student-search-results">


                        <?php if (
                            count(
                                $searchResults
                            ) > 0
                        ): ?>


                            <div class="search-result-heading">


                                <h3>
                                    Student Search Results
                                </h3>


                                <span>

                                    <?= count(
                                        $searchResults
                                    ) ?>

                                    result<?= count(
                                        $searchResults
                                    ) !== 1
                                        ? "s"
                                        : ""
                                    ?>

                                </span>


                            </div>


                            <div class="student-results-scroll">


                                <div class="student-result-grid">


                                    <?php foreach (
                                        $searchResults
                                        as $result
                                    ): ?>


                                        <?php

                                        $nameParts = [];


                                        foreach (
                                            [
                                                $result[
                                                    "first_name"
                                                ] ?? "",

                                                $result[
                                                    "middle_name"
                                                ] ?? "",

                                                $result[
                                                    "last_name"
                                                ] ?? ""
                                            ]
                                            as $part
                                        ) {

                                            $part =
                                                trim($part);


                                            if (
                                                $part !== ""
                                            ) {

                                                $nameParts[] =
                                                    $part;
                                            }
                                        }


                                        $resultName =
                                            trim(
                                                implode(
                                                    " ",
                                                    $nameParts
                                                )
                                            );


                                        if (
                                            $resultName === ""
                                        ) {

                                            $resultName =
                                                "Student";
                                        }


                                        $resultInitial =
                                            strtoupper(
                                                substr(
                                                    $result[
                                                        "first_name"
                                                    ]
                                                    ?: "S",

                                                    0,
                                                    1
                                                )
                                            );

                                        ?>


                                        <a
                                            href="public_student_profile.php?id=<?= (int) $result["user_id"] ?>"
                                            class="student-result-card"
                                        >


                                            <div
                                                class="student-result-avatar"

                                                <?php if (
                                                    !empty(
                                                        $result[
                                                            "profile_photo"
                                                        ]
                                                    )
                                                ): ?>

                                                    style="
                                                        background-image:
                                                        url('<?= htmlspecialchars(
                                                            $result[
                                                                "profile_photo"
                                                            ],
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ) ?>');

                                                        color:
                                                            transparent;
                                                    "

                                                <?php endif; ?>
                                            >

                                                <?= htmlspecialchars(
                                                    $resultInitial
                                                ) ?>

                                            </div>


                                            <div class="student-result-information">


                                                <h4>

                                                    <?= htmlspecialchars(
                                                        $resultName
                                                    ) ?>

                                                </h4>


                                                <?php if (
                                                    !empty(
                                                        $result[
                                                            "public_program"
                                                        ]
                                                    )
                                                ): ?>


                                                    <p>

                                                        <?= htmlspecialchars(
                                                            $result[
                                                                "public_program"
                                                            ]
                                                        ) ?>

                                                    </p>


                                                <?php else: ?>


                                                    <p>
                                                        Student profile
                                                    </p>


                                                <?php endif; ?>


                                                <div class="student-result-meta">


                                                    <?php if (
                                                        !empty(
                                                            $result[
                                                                "year_level"
                                                            ]
                                                        )
                                                    ): ?>


                                                        <span>

                                                            <?= htmlspecialchars(
                                                                $result[
                                                                    "year_level"
                                                                ]
                                                            ) ?>

                                                        </span>


                                                    <?php endif; ?>


                                                    <?php if (
                                                        !empty(
                                                            $result[
                                                                "section"
                                                            ]
                                                        )
                                                    ): ?>


                                                        <span>

                                                            <?= htmlspecialchars(
                                                                $result[
                                                                    "section"
                                                                ]
                                                            ) ?>

                                                        </span>


                                                    <?php endif; ?>


                                                    <?php if (
                                                        (
                                                            $result[
                                                                "profile_visibility"
                                                            ]
                                                            ?? ""
                                                        )
                                                        ===
                                                        "school_only"
                                                    ): ?>


                                                        <span
                                                            class="
                                                                school-only-badge
                                                            "
                                                        >
                                                            School Only
                                                        </span>


                                                    <?php endif; ?>


                                                </div>


                                            </div>


                                            <span class="student-result-arrow">
                                                →
                                            </span>


                                        </a>


                                    <?php endforeach; ?>


                                </div>


                            </div>


                        <?php else: ?>


                            <div class="student-search-empty">

                                <?= htmlspecialchars(
                                    $searchMessage
                                ) ?>

                            </div>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </div>


        </div>


        <div class="introduction-grid">


            <div class="introduction-card">


                <div class="introduction-card-icon">
                    ◈
                </div>


                <h3>
                    Organized Information
                </h3>


                <p>

                    Keep your student information
                    organized and accessible in one
                    centralized profile.

                </p>


            </div>


            <div class="introduction-card">


                <div class="introduction-card-icon">
                    ✓
                </div>


                <h3>
                    Personal Ownership
                </h3>


                <p>

                    Maintain your profile by
                    updating your information,
                    accomplishments, interests,
                    and activities.

                </p>


            </div>


            <div class="introduction-card">


                <div class="introduction-card-icon">
                    ⛨
                </div>


                <h3>
                    Privacy Focused
                </h3>


                <p>

                    Manage profile visibility
                    and control which information
                    can be viewed by others.

                </p>


            </div>


        </div>


    </div>


</section>


<section
    class="features-section"
    id="features"
>


    <div class="container">


        <div class="section-heading">


            <div>


                <span class="section-label">
                    SYSTEM FEATURES
                </span>


                <h2>

                    Everything You Need

                    <span>
                        In Your Profile.
                    </span>

                </h2>


            </div>


            <p>

                Manage different parts of your
                student profile through a simple
                and organized platform.

            </p>


        </div>


        <div class="features-grid">


            <div class="feature-card">


                <div
                    class="
                        feature-icon
                        green-feature-icon
                    "
                >
                    ◉
                </div>


                <div class="feature-card-content">


                    <h3>
                        Student Profile Management
                    </h3>


                    <p>

                        Store and manage personal,
                        academic, family, and
                        educational information.

                    </p>


                    <?php if (
                        $isLoggedIn
                    ): ?>


                        <a
                            href="<?= htmlspecialchars(
                                $accountDashboard
                            ) ?>"
                            class="feature-link"
                        >
                            Open Dashboard

                            <span>→</span>
                        </a>


                    <?php else: ?>


                        <a
                            href="register.php"
                            class="feature-link"
                        >
                            Learn More

                            <span>→</span>
                        </a>


                    <?php endif; ?>


                </div>


            </div>


            <div class="feature-card">


                <div
                    class="
                        feature-icon
                        blue-feature-icon
                    "
                >
                    ◆
                </div>


                <div class="feature-card-content">


                    <h3>
                        Accomplishment Records
                    </h3>


                    <p>

                        Record achievements,
                        competitions,
                        certifications,
                        and supporting documents.

                    </p>


                    <a
                        href="<?= $isLoggedIn
                            ? "accomplishments.php"
                            : "register.php"
                        ?>"
                        class="feature-link"
                    >

                        <?= $isLoggedIn
                            ? "View Accomplishments"
                            : "Learn More"
                        ?>

                        <span>→</span>

                    </a>


                </div>


            </div>


            <div class="feature-card">


                <div
                    class="
                        feature-icon
                        purple-feature-icon
                    "
                >
                    ▤
                </div>


                <div class="feature-card-content">


                    <h3>
                        Digital Student Portfolio
                    </h3>


                    <p>

                        Present your stored student
                        information through an
                        organized digital profile.

                    </p>


                    <a
                        href="<?= $isLoggedIn
                            ? "digital_portfolio.php"
                            : "register.php"
                        ?>"
                        class="feature-link"
                    >

                        <?= $isLoggedIn
                            ? "View Portfolio"
                            : "Learn More"
                        ?>

                        <span>→</span>

                    </a>


                </div>


            </div>


            <div class="feature-card">


                <div
                    class="
                        feature-icon
                        orange-feature-icon
                    "
                >
                    ⛨
                </div>


                <div class="feature-card-content">


                    <h3>
                        Privacy & Visibility
                    </h3>


                    <p>

                        Manage profile visibility
                        and protect sensitive
                        personal information.

                    </p>


                    <a
                        href="<?= $isLoggedIn
                            ? "privacy.php"
                            : "register.php"
                        ?>"
                        class="feature-link"
                    >

                        <?= $isLoggedIn
                            ? "Manage Privacy"
                            : "Learn More"
                        ?>

                        <span>→</span>

                    </a>


                </div>


            </div>


        </div>


    </div>


</section>


<section class="portfolio-section">


    <div
        class="
            container
            portfolio-container
        "
    >


        <div class="portfolio-content">


            <span class="section-label">
                YOUR DIGITAL PROFILE
            </span>


            <h2>

                Present Your

                <span>
                    Student Journey.
                </span>

            </h2>


            <p>

                Keep your experiences,
                organizations, interests,
                and accomplishments organized
                in a profile that represents you.

            </p>


            <div class="portfolio-checklist">


                <div class="portfolio-check-item">


                    <span>✓</span>


                    <p>
                        Manage your personal
                        and academic information.
                    </p>


                </div>


                <div class="portfolio-check-item">


                    <span>✓</span>


                    <p>
                        Organize your achievements
                        and certificates.
                    </p>


                </div>


                <div class="portfolio-check-item">


                    <span>✓</span>


                    <p>
                        Maintain your hobbies
                        and organization records.
                    </p>


                </div>


                <div class="portfolio-check-item">


                    <span>✓</span>


                    <p>
                        Control the visibility
                        of your information.
                    </p>


                </div>


            </div>


            <?php if (
                $isLoggedIn
            ): ?>


                <a
                    href="digital_portfolio.php"
                    class="
                        btn
                        btn-primary
                        btn-large
                    "
                >

                    View Your Portfolio

                    <span class="button-arrow">
                        →
                    </span>

                </a>


            <?php else: ?>


                <a
                    href="register.php"
                    class="
                        btn
                        btn-primary
                        btn-large
                    "
                >

                    Create Your Profile

                    <span class="button-arrow">
                        →
                    </span>

                </a>


            <?php endif; ?>


        </div>


        <div class="portfolio-visual">


            <div class="portfolio-paper">


                <div class="portfolio-paper-header">


                    <div class="portfolio-paper-logo">
                        C
                    </div>


                    <div>


                        <span>
                            CVSWHO
                        </span>


                        <small>
                            STUDENT PORTFOLIO
                        </small>


                    </div>


                </div>


                <div class="portfolio-paper-profile">


                    <div class="portfolio-paper-avatar">
                        S
                    </div>


                    <div>


                        <h3>
                            Student Name
                        </h3>


                        <p>
                            BS Computer Science
                        </p>


                        <span>
                            Cavite State University
                        </span>


                    </div>


                </div>


                <div class="portfolio-paper-line"></div>


                <div class="portfolio-paper-section">


                    <span>
                        ABOUT ME
                    </span>


                    <div
                        class="
                            paper-placeholder
                            long-placeholder
                        "
                    ></div>


                    <div
                        class="
                            paper-placeholder
                            medium-placeholder
                        "
                    ></div>


                </div>


                <div class="portfolio-paper-columns">


                    <div>


                        <span>
                            EDUCATION
                        </span>


                        <div
                            class="
                                paper-placeholder
                            "
                        ></div>


                        <div
                            class="
                                paper-placeholder
                                short-placeholder
                            "
                        ></div>


                    </div>


                    <div>


                        <span>
                            ACHIEVEMENTS
                        </span>


                        <div
                            class="
                                paper-placeholder
                            "
                        ></div>


                        <div
                            class="
                                paper-placeholder
                                short-placeholder
                            "
                        ></div>


                    </div>


                </div>


                <div class="portfolio-paper-footer">
                    CVSWHO Digital Student Portfolio
                </div>


            </div>


        </div>


    </div>


</section>


<section class="cta-section">


    <div class="container">


        <div class="cta-container">


            <div
                class="
                    cta-decoration
                    cta-decoration-left
                "
            ></div>


            <div
                class="
                    cta-decoration
                    cta-decoration-right
                "
            ></div>


            <div class="cta-content">


                <?php if (
                    $isLoggedIn
                ): ?>


                    <span
                        class="
                            section-label
                            cta-label
                        "
                    >
                        WELCOME BACK
                    </span>


                    <h2>

                        Continue Building Your

                        <span>
                            Student Profile.
                        </span>

                    </h2>


                    <p>

                        Your account is still
                        signed in.

                        Continue managing
                        your student profile.

                    </p>


                    <a
                        href="<?= htmlspecialchars(
                            $accountDashboard
                        ) ?>"
                        class="
                            btn
                            btn-white
                            btn-large
                        "
                    >

                        Go to Dashboard

                        <span class="button-arrow">
                            →
                        </span>

                    </a>


                <?php else: ?>


                    <span
                        class="
                            section-label
                            cta-label
                        "
                    >
                        GET STARTED WITH CVSWHO
                    </span>


                    <h2>

                        Start Building Your

                        <span>
                            Student Profile.
                        </span>

                    </h2>


                    <p>

                        Organize your information
                        and showcase your student
                        journey in one place.

                    </p>


                    <a
                        href="register.php"
                        class="
                            btn
                            btn-white
                            btn-large
                        "
                    >

                        Create Your Account

                        <span class="button-arrow">
                            →
                        </span>

                    </a>


                <?php endif; ?>


            </div>


        </div>


    </div>


</section>


</main>


<footer class="footer">


    <div class="container">


        <div class="footer-main">


            <div class="footer-brand">


                <a
                    href="index.php"
                    class="
                        brand
                        footer-brand-link
                    "
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


                <p>

                    A centralized student
                    information and portfolio
                    management platform.

                </p>


            </div>


            <div class="footer-links-group">


                <h4>
                    Platform
                </h4>


                <a href="#home">
                    Home
                </a>


                <a href="#about">
                    About
                </a>


                <a href="#features">
                    Features
                </a>


            </div>


            <div class="footer-links-group">


                <h4>
                    Social
                </h4>


                <a
                    href="https://www.facebook.com/lench.1111"
                >
                    Facebook
                </a>


                <a
                    href="https://www.instagram.com/lench._.h/"
                >
                    Instagram
                </a>


                <a
                    href="https://github.com/AstuteLynch"
                >
                    GitHub
                </a>


            </div>


            <div class="footer-links-group">


                <h4>
                    Information
                </h4>


                <a href="#about">
                    About CVSWHO
                </a>


                <a href="#features">
                    System Features
                </a>


            </div>


        </div>


        <div class="footer-bottom">


            <p>

                © 2026 CVSWHO.
                All rights reserved.

            </p>


            <p>

                Designed for Cavite State
                University Students

            </p>


        </div>


    </div>


</footer>


<script src="main.js"></script>


<?php if (
    $studentSearch !== ""
): ?>


<script>

window.addEventListener(
    "load",
    function () {

        const searchSection =
            document.getElementById(
                "student-search"
            );


        if (searchSection) {

            searchSection.scrollIntoView({

                behavior:
                    "auto",

                block:
                    "start"

            });
        }
    }
);

</script>


<?php endif; ?>


</body>

</html>