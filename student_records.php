<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


/* ADMIN */

requireAdmin($conn);


$administratorId =
    (int) $_SESSION["user_id"];


/* LOGOUT */

if (isset($_GET["logout"])) {

    logoutUser(
        "login.php"
    );
}


/* FILTERS */

$search =
    trim(
        $_GET["search"]
        ?? ""
    );


$program =
    trim(
        $_GET["program"]
        ?? ""
    );


$status =
    trim(
        $_GET["status"]
        ?? ""
    );


$yearLevel =
    trim(
        $_GET["year_level"]
        ?? ""
    );


$allowedStatuses = [

    "pending",
    "active",
    "archived",
    "deactivated",
    "suspended"
];


if (
    $status !== "" &&
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    $status = "";
}


/* PAGINATION */

$perPage = 15;


$page =
    max(
        1,
        (int) (
            $_GET["page"]
            ?? 1
        )
    );


$offset =
    ($page - 1) *
    $perPage;


/* SEARCH VALUES */

$searchLike =
    "%" .
    $search .
    "%";


/* TOTAL RECORDS */

$countStmt =
    $conn->prepare("
        SELECT
            COUNT(*) AS total

        FROM users u

        LEFT JOIN student_profiles sp
            ON sp.user_id = u.id

        WHERE
            u.role = 'student'

            AND
            (
                ? = ''

                OR CONCAT_WS(
                    ' ',
                    sp.first_name,
                    sp.middle_name,
                    sp.last_name,
                    sp.suffix
                ) LIKE ?

                OR sp.student_id LIKE ?

                OR u.email LIKE ?
            )

            AND
            (
                ? = ''
                OR sp.program = ?
            )

            AND
            (
                ? = ''
                OR u.account_status = ?
            )

            AND
            (
                ? = ''
                OR sp.year_level = ?
            )
    ");


if (!$countStmt) {

    die(
        "Unable to load student records."
    );
}


$countStmt->bind_param(
    "ssssssssss",

    $search,
    $searchLike,
    $searchLike,
    $searchLike,

    $program,
    $program,

    $status,
    $status,

    $yearLevel,
    $yearLevel
);


$countStmt->execute();


$countResult =
    $countStmt
        ->get_result()
        ->fetch_assoc();


$totalRecords =
    (int) (
        $countResult["total"]
        ?? 0
    );


$countStmt->close();


$totalPages =
    max(
        1,
        (int) ceil(
            $totalRecords /
            $perPage
        )
    );


if (
    $page > $totalPages
) {

    $page =
        $totalPages;


    $offset =
        ($page - 1) *
        $perPage;
}


/* STUDENTS */

$students = [];


$stmt =
    $conn->prepare("
        SELECT

            u.id AS user_id,
            u.email,
            u.account_status,
            u.email_verified,
            u.last_login_at,
            u.created_at,

            sp.student_id,
            sp.first_name,
            sp.middle_name,
            sp.last_name,
            sp.suffix,
            sp.program,
            sp.year_level,
            sp.section,
            sp.college,
            sp.campus,
            sp.profile_photo,
            sp.profile_completion

        FROM users u

        LEFT JOIN student_profiles sp
            ON sp.user_id = u.id

        WHERE
            u.role = 'student'

            AND
            (
                ? = ''

                OR CONCAT_WS(
                    ' ',
                    sp.first_name,
                    sp.middle_name,
                    sp.last_name,
                    sp.suffix
                ) LIKE ?

                OR sp.student_id LIKE ?

                OR u.email LIKE ?
            )

            AND
            (
                ? = ''
                OR sp.program = ?
            )

            AND
            (
                ? = ''
                OR u.account_status = ?
            )

            AND
            (
                ? = ''
                OR sp.year_level = ?
            )

        ORDER BY

            CASE u.account_status

                WHEN 'active'
                    THEN 1

                WHEN 'pending'
                    THEN 2

                WHEN 'suspended'
                    THEN 3

                WHEN 'deactivated'
                    THEN 4

                WHEN 'archived'
                    THEN 5

                ELSE 6

            END,

            sp.last_name ASC,
            sp.first_name ASC,
            u.id DESC

        LIMIT ?
        OFFSET ?
    ");


if (!$stmt) {

    die(
        "Unable to load student records."
    );
}


$stmt->bind_param(
    "ssssssssssii",

    $search,
    $searchLike,
    $searchLike,
    $searchLike,

    $program,
    $program,

    $status,
    $status,

    $yearLevel,
    $yearLevel,

    $perPage,
    $offset
);


$stmt->execute();


$result =
    $stmt->get_result();


while (
    $row =
        $result->fetch_assoc()
) {

    $students[] =
        $row;
}


$stmt->close();


/* PROGRAM FILTER */

$programs = [];


$programStmt =
    $conn->prepare("
        SELECT DISTINCT
            sp.program

        FROM users u

        INNER JOIN student_profiles sp
            ON sp.user_id = u.id

        WHERE
            u.role = 'student'
            AND sp.program IS NOT NULL
            AND TRIM(sp.program) <> ''

        ORDER BY sp.program ASC
    ");


if ($programStmt) {

    $programStmt->execute();


    $programResult =
        $programStmt
            ->get_result();


    while (
        $row =
            $programResult
                ->fetch_assoc()
    ) {

        $programs[] =
            $row["program"];
    }


    $programStmt->close();
}


/* YEAR FILTER */

$yearLevels = [];


$yearStmt =
    $conn->prepare("
        SELECT DISTINCT
            sp.year_level

        FROM users u

        INNER JOIN student_profiles sp
            ON sp.user_id = u.id

        WHERE
            u.role = 'student'
            AND sp.year_level IS NOT NULL
            AND TRIM(sp.year_level) <> ''

        ORDER BY sp.year_level ASC
    ");


if ($yearStmt) {

    $yearStmt->execute();


    $yearResult =
        $yearStmt
            ->get_result();


    while (
        $row =
            $yearResult
                ->fetch_assoc()
    ) {

        $yearLevels[] =
            $row["year_level"];
    }


    $yearStmt->close();
}


/* SUMMARY */

$summary = [

    "total" => 0,
    "active" => 0,
    "pending" => 0,
    "suspended" => 0,
    "deactivated" => 0,
    "archived" => 0
];


$summaryStmt =
    $conn->prepare("
        SELECT

            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN account_status = 'active'
                    THEN 1
                    ELSE 0
                END
            ) AS active,

            SUM(
                CASE
                    WHEN account_status = 'pending'
                    THEN 1
                    ELSE 0
                END
            ) AS pending,

            SUM(
                CASE
                    WHEN account_status = 'suspended'
                    THEN 1
                    ELSE 0
                END
            ) AS suspended,

            SUM(
                CASE
                    WHEN account_status = 'deactivated'
                    THEN 1
                    ELSE 0
                END
            ) AS deactivated,

            SUM(
                CASE
                    WHEN account_status = 'archived'
                    THEN 1
                    ELSE 0
                END
            ) AS archived

        FROM users

        WHERE role = 'student'
    ");


if ($summaryStmt) {

    $summaryStmt->execute();


    $summaryRow =
        $summaryStmt
            ->get_result()
            ->fetch_assoc();


    $summaryStmt->close();


    if ($summaryRow) {

        foreach (
            $summary
            as $key => $value
        ) {

            $summary[$key] =
                (int) (
                    $summaryRow[$key]
                    ?? 0
                );
        }
    }
}


/* HELPERS */

function displayValue(
    ?string $value,
    string $fallback = "Not provided"
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


function studentFullName(
    array $student
): string {

    $parts = [];


    foreach (
        [
            $student["first_name"]
                ?? "",

            $student["middle_name"]
                ?? "",

            $student["last_name"]
                ?? "",

            $student["suffix"]
                ?? ""
        ]
        as $part
    ) {

        $part =
            trim(
                (string) $part
            );


        if ($part !== "") {

            $parts[] =
                $part;
        }
    }


    $name =
        implode(
            " ",
            $parts
        );


    return
        $name !== ""
            ? $name
            : "Student";
}


function statusLabel(
    string $status
): string {

    return match ($status) {

        "active" =>
            "Active",

        "pending" =>
            "Pending",

        "archived" =>
            "Archived",

        "deactivated" =>
            "Deactivated",

        "suspended" =>
            "Suspended",

        default =>
            ucfirst($status)
    };
}


function buildRecordsUrl(
    int $page,
    string $search,
    string $program,
    string $status,
    string $yearLevel
): string {

    $query = [

        "page" =>
            $page
    ];


    if ($search !== "") {

        $query["search"] =
            $search;
    }


    if ($program !== "") {

        $query["program"] =
            $program;
    }


    if ($status !== "") {

        $query["status"] =
            $status;
    }


    if ($yearLevel !== "") {

        $query["year_level"] =
            $yearLevel;
    }


    return
        "student_records.php?" .
        http_build_query(
            $query
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
        Student Records | CVSWHO
    </title>


    <link
        rel="stylesheet"
        href="student_records.css"
    >


    <style>

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    5,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 10px;

            margin-bottom: 20px;
        }


        .summary-card {

            padding: 16px;

            background: white;

            border:
                1px solid #e1e9e4;

            border-radius: 10px;
        }


        .summary-card span {

            display: block;

            color: #68766f;

            font-size: 8px;

            font-weight: 700;

            letter-spacing: .5px;

            text-transform: uppercase;
        }


        .summary-card strong {

            display: block;

            margin-top: 6px;

            color: #006b3f;

            font-size: 21px;
        }


        .filter-form {

            width: 100%;

            display: grid;

            grid-template-columns:
                minmax(
                    220px,
                    2fr
                )
                repeat(
                    3,
                    minmax(
                        135px,
                        1fr
                    )
                )
                auto
                auto;

            gap: 9px;
        }


        .filter-form input,
        .filter-form select {

            width: 100%;

            min-height: 39px;

            padding:
                0 11px;

            color: #25342c;

            background: white;

            border:
                1px solid #dce6e0;

            border-radius: 8px;

            outline: none;

            font: inherit;

            font-size: 9px;
        }


        .filter-form input:focus,
        .filter-form select:focus {

            border-color: #006b3f;

            box-shadow:
                0 0 0 3px
                rgba(
                    0,
                    107,
                    63,
                    .06
                );
        }


        .filter-button {

            min-height: 39px;

            padding:
                0 15px;

            color: white;

            background: #006b3f;

            border: none;

            border-radius: 8px;

            font: inherit;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }


        .filter-button:hover {

            background: #004d2a;
        }


        .clear-button {

            min-height: 39px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0 15px;

            color: #526159;

            background: white;

            border:
                1px solid #dce6e0;

            border-radius: 8px;

            font-size: 9px;

            font-weight: 700;

            text-decoration: none;
        }


        .table-heading {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 15px;

            margin:
                25px 0 13px;
        }


        .table-heading h2 {

            color: #003d24;

            font-size: 16px;
        }


        .result-count {

            color: #68766f;

            font-size: 9px;
        }


        .student-cell {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 190px;
        }


        .student-avatar {

            width: 34px;
            height: 34px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            overflow: hidden;

            color: white;

            background: #006b3f;

            border-radius: 50%;

            font-size: 10px;

            font-weight: 800;
        }


        .student-avatar img {

            width: 100%;
            height: 100%;

            display: block;

            object-fit: cover;
        }


        .student-cell strong {

            display: block;

            color: #1f2e26;

            font-size: 9px;
        }


        .student-email {

            display: block;

            margin-top: 3px;

            color: #7c8982;

            font-size: 8px;
        }


        .status {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                5px 8px;

            border-radius: 30px;

            font-size: 7px;

            font-weight: 800;

            text-transform: uppercase;
        }


        .status.active {

            color: #18794e;

            background: #eaf7ef;
        }


        .status.pending {

            color: #8a6915;

            background: #fff8df;
        }


        .status.suspended {

            color: #a23939;

            background: #fff0f0;
        }


        .status.deactivated {

            color: #626d67;

            background: #edf0ee;
        }


        .status.archived {

            color: #5f607f;

            background: #f0f0f8;
        }


        .verified {

            display: block;

            margin-top: 4px;

            color: #18794e;

            font-size: 7px;
        }


        .not-verified {

            color: #a23939;
        }


        .view-button {

            white-space: nowrap;
        }


        .empty-state {

            padding:
                40px 20px;

            text-align: center;
        }


        .empty-state h3 {

            color: #003d24;

            font-size: 14px;
        }


        .empty-state p {

            margin-top: 6px;

            color: #68766f;

            font-size: 9px;

            line-height: 1.7;
        }


        .pagination {

            display: flex;

            align-items: center;

            justify-content: center;

            flex-wrap: wrap;

            gap: 6px;

            margin-top: 20px;
        }


        .pagination a,
        .pagination span {

            min-width: 34px;
            height: 34px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0 9px;

            color: #526159;

            background: white;

            border:
                1px solid #dce6e0;

            border-radius: 7px;

            font-size: 8px;

            font-weight: 700;

            text-decoration: none;
        }


        .pagination .active {

            color: white;

            background: #006b3f;

            border-color: #006b3f;
        }


        .pagination .disabled {

            opacity: .45;
        }


        @media (
            max-width: 1000px
        ) {

            .summary-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }


            .filter-form {

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
            max-width: 600px
        ) {

            .summary-grid,
            .filter-form {

                grid-template-columns:
                    1fr;
            }


            .table-heading {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }

        }

    </style>


</head>


<body>


<!-- NAVIGATION -->

<header class="navbar">


    <div class="nav-container">


        <a
            href="admin_dashboard.php"
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
                    Administrator Portal
                </span>


            </div>


        </a>


        <nav class="desktop-nav">


            <a
                href="admin_dashboard.php"
                class="nav-link"
            >
                Dashboard
            </a>


            <a
                href="student_records.php"
                class="nav-link active"
            >
                Student Records
            </a>


            <a
                href="admin_settings.php"
                class="nav-link"
            >
                Settings
            </a>


        </nav>


        <a
            href="student_records.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>


</header>


<!-- STUDENT RECORDS -->

<main class="page">


    <!-- HEADER -->

    <section class="page-header">


        <div>


            <span class="eyebrow">
                ADMINISTRATION
            </span>


            <h1>
                Student Records
            </h1>


            <p>
                Search and review registered
                student accounts and profiles.
            </p>


        </div>


    </section>


    <!-- SUMMARY -->

    <section class="summary-grid">


        <div class="summary-card">


            <span>
                Total Students
            </span>


            <strong>
                <?= $summary["total"] ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Active
            </span>


            <strong>
                <?= $summary["active"] ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Pending
            </span>


            <strong>
                <?= $summary["pending"] ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Suspended
            </span>


            <strong>
                <?= $summary["suspended"] ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Inactive
            </span>


            <strong>

                <?= $summary["deactivated"] +
                    $summary["archived"]
                ?>

            </strong>


        </div>


    </section>


    <!-- RECORDS -->

    <section class="records-card">


        <!-- FILTERS -->

        <form
            method="GET"
            action="student_records.php"
            class="filter-form"
        >


            <input
                type="search"
                name="search"
                value="<?= htmlspecialchars(
                    $search
                ) ?>"
                placeholder="Search name, student ID, or email"
                autocomplete="off"
            >


            <select name="program">


                <option value="">
                    All Programs
                </option>


                <?php foreach (
                    $programs
                    as $programOption
                ): ?>


                    <option
                        value="<?= htmlspecialchars(
                            $programOption
                        ) ?>"
                        <?= $program === $programOption
                            ? "selected"
                            : ""
                        ?>
                    >

                        <?= htmlspecialchars(
                            $programOption
                        ) ?>

                    </option>


                <?php endforeach; ?>


            </select>


            <select name="year_level">


                <option value="">
                    All Year Levels
                </option>


                <?php foreach (
                    $yearLevels
                    as $yearOption
                ): ?>


                    <option
                        value="<?= htmlspecialchars(
                            $yearOption
                        ) ?>"
                        <?= $yearLevel === $yearOption
                            ? "selected"
                            : ""
                        ?>
                    >

                        <?= htmlspecialchars(
                            $yearOption
                        ) ?>

                    </option>


                <?php endforeach; ?>


            </select>


            <select name="status">


                <option value="">
                    All Statuses
                </option>


                <option
                    value="active"
                    <?= $status === "active"
                        ? "selected"
                        : ""
                    ?>
                >
                    Active
                </option>


                <option
                    value="pending"
                    <?= $status === "pending"
                        ? "selected"
                        : ""
                    ?>
                >
                    Pending
                </option>


                <option
                    value="suspended"
                    <?= $status === "suspended"
                        ? "selected"
                        : ""
                    ?>
                >
                    Suspended
                </option>


                <option
                    value="deactivated"
                    <?= $status === "deactivated"
                        ? "selected"
                        : ""
                    ?>
                >
                    Deactivated
                </option>


                <option
                    value="archived"
                    <?= $status === "archived"
                        ? "selected"
                        : ""
                    ?>
                >
                    Archived
                </option>


            </select>


            <button
                type="submit"
                class="filter-button"
            >
                Filter
            </button>


            <a
                href="student_records.php"
                class="clear-button"
            >
                Clear
            </a>


        </form>


        <!-- HEADING -->

        <div class="table-heading">


            <h2>
                Student Accounts
            </h2>


            <span class="result-count">

                <?= $totalRecords ?>

                <?= $totalRecords === 1
                    ? "student"
                    : "students"
                ?>

                found

            </span>


        </div>


        <!-- TABLE -->

        <?php if (
            count($students) > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <thead>


                        <tr>

                            <th>
                                Student
                            </th>

                            <th>
                                Student ID
                            </th>

                            <th>
                                Program
                            </th>

                            <th>
                                Year
                            </th>

                            <th>
                                Section
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>


                    </thead>


                    <tbody>


                        <?php foreach (
                            $students
                            as $student
                        ): ?>


                            <?php

                            $studentName =
                                studentFullName(
                                    $student
                                );


                            $firstName =
                                trim(
                                    $student[
                                        "first_name"
                                    ] ?? ""
                                );


                            $initial =
                                strtoupper(
                                    substr(
                                        $firstName !== ""
                                            ? $firstName
                                            : $studentName,
                                        0,
                                        1
                                    )
                                );


                            $profilePhoto =
                                trim(
                                    $student[
                                        "profile_photo"
                                    ] ?? ""
                                );


                            $studentStatus =
                                $student[
                                    "account_status"
                                ] ?? "pending";

                            ?>


                            <tr>


                                <td>


                                    <div class="student-cell">


                                        <div class="student-avatar">


                                            <?php if (
                                                $profilePhoto !== ""
                                            ): ?>


                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $profilePhoto
                                                    ) ?>"
                                                    alt=""
                                                >


                                            <?php else: ?>


                                                <?= htmlspecialchars(
                                                    $initial
                                                ) ?>


                                            <?php endif; ?>


                                        </div>


                                        <div>


                                            <strong>

                                                <?= htmlspecialchars(
                                                    $studentName
                                                ) ?>

                                            </strong>


                                            <span class="student-email">

                                                <?= htmlspecialchars(
                                                    $student["email"]
                                                ) ?>

                                            </span>


                                        </div>


                                    </div>


                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        displayValue(
                                            $student[
                                                "student_id"
                                            ] ?? ""
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        displayValue(
                                            $student[
                                                "program"
                                            ] ?? ""
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        displayValue(
                                            $student[
                                                "year_level"
                                            ] ?? ""
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        displayValue(
                                            $student[
                                                "section"
                                            ] ?? ""
                                        )
                                    ) ?>

                                </td>


                                <td>


                                    <span
                                        class="status <?= htmlspecialchars(
                                            $studentStatus
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            statusLabel(
                                                $studentStatus
                                            )
                                        ) ?>

                                    </span>


                                    <span
                                        class="<?= (int) $student[
                                            "email_verified"
                                        ] === 1
                                            ? "verified"
                                            : "verified not-verified"
                                        ?>"
                                    >

                                        <?= (int) $student[
                                            "email_verified"
                                        ] === 1
                                            ? "Verified"
                                            : "Not verified"
                                        ?>

                                    </span>


                                </td>


                                <td>


                                    <a
                                        href="admin_student_profile.php?id=<?= (int) $student["user_id"] ?>"
                                        class="view-button"
                                    >
                                        Open Profile
                                    </a>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty-state">


                <h3>
                    No student records found
                </h3>


                <p>
                    No students matched your
                    current search and filters.
                </p>


                <a
                    href="student_records.php"
                    class="clear-button"
                    style="margin-top: 12px;"
                >
                    Clear Filters
                </a>


            </div>


        <?php endif; ?>


        <!-- PAGINATION -->

        <?php if (
            $totalPages > 1
        ): ?>


            <nav
                class="pagination"
                aria-label="Student records pages"
            >


                <?php if (
                    $page > 1
                ): ?>


                    <a
                        href="<?= htmlspecialchars(
                            buildRecordsUrl(
                                $page - 1,
                                $search,
                                $program,
                                $status,
                                $yearLevel
                            )
                        ) ?>"
                    >
                        ← Previous
                    </a>


                <?php else: ?>


                    <span class="disabled">
                        ← Previous
                    </span>


                <?php endif; ?>


                <?php

                $startPage =
                    max(
                        1,
                        $page - 2
                    );


                $endPage =
                    min(
                        $totalPages,
                        $page + 2
                    );

                ?>


                <?php for (
                    $pageNumber = $startPage;
                    $pageNumber <= $endPage;
                    $pageNumber++
                ): ?>


                    <?php if (
                        $pageNumber === $page
                    ): ?>


                        <span class="active">

                            <?= $pageNumber ?>

                        </span>


                    <?php else: ?>


                        <a
                            href="<?= htmlspecialchars(
                                buildRecordsUrl(
                                    $pageNumber,
                                    $search,
                                    $program,
                                    $status,
                                    $yearLevel
                                )
                            ) ?>"
                        >

                            <?= $pageNumber ?>

                        </a>


                    <?php endif; ?>


                <?php endfor; ?>


                <?php if (
                    $page < $totalPages
                ): ?>


                    <a
                        href="<?= htmlspecialchars(
                            buildRecordsUrl(
                                $page + 1,
                                $search,
                                $program,
                                $status,
                                $yearLevel
                            )
                        ) ?>"
                    >
                        Next →
                    </a>


                <?php else: ?>


                    <span class="disabled">
                        Next →
                    </span>


                <?php endif; ?>


            </nav>


        <?php endif; ?>


    </section>


</main>


<!-- FOOTER -->

<footer class="footer">


    <p>
        CVSWHO
    </p>


    <span>
        Administrator Portal
    </span>


</footer>


</body>

</html>