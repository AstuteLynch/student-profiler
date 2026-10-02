<?php

session_start();

require_once "db.php";
require_once "admin_student_functions.php";

/* ADMIN */

$admin =
    requireAdministrator($conn);

$adminUserId =
    (int) $admin["id"];

$csrfToken =
    getAdminCsrfToken();

purgeExpiredStudentAccounts($conn);


/* MESSAGE */

$error = "";
$success = "";

if (isset($_GET["removed"])) {
    $success =
        "Student account moved to Trash. It will be permanently deleted after 30 days unless restored.";
}


/* REMOVE */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "remove_student"
) {
    if (
        !verifyAdminCsrfToken(
            $_POST["csrf_token"] ?? ""
        )
    ) {
        $error =
            "Your session expired. Please refresh the page.";

    } else {
        $studentUserId =
            (int) ($_POST["student_user_id"] ?? 0);

        if (
            moveStudentToTrash(
                $conn,
                $studentUserId,
                $adminUserId
            )
        ) {
            header(
                "Location: student_records.php?removed=1"
            );
            exit;
        }

        $error =
            "Unable to move the student account to Trash.";
    }
}


/* FILTERS */

$search =
    trim($_GET["search"] ?? "");

$status =
    trim($_GET["status"] ?? "");

$program =
    trim($_GET["program"] ?? "");


/* PROGRAMS */

$programs = [];

$programResult =
    $conn->query("
        SELECT DISTINCT program
        FROM student_profiles
        WHERE
            program IS NOT NULL
            AND TRIM(program) <> ''
        ORDER BY program ASC
    ");

if ($programResult) {
    while (
        $row =
            $programResult->fetch_assoc()
    ) {
        $programs[] =
            $row["program"];
    }
}


/* STUDENTS */

$sql = "
    SELECT
        u.id AS user_id,
        u.email,
        u.account_status,
        u.email_verified,
        u.created_at,

        sp.student_id,
        sp.first_name,
        sp.middle_name,
        sp.last_name,
        sp.program,
        sp.year_level,
        sp.section,
        sp.campus,
        sp.profile_photo

    FROM users u

    LEFT JOIN student_profiles sp
        ON sp.user_id = u.id

    LEFT JOIN deleted_student_accounts d
        ON d.user_id = u.id

    WHERE
        u.role = 'student'
        AND d.user_id IS NULL
";

$params = [];
$types = "";

if ($search !== "") {
    $sql .= "
        AND (
            sp.first_name LIKE ?
            OR sp.middle_name LIKE ?
            OR sp.last_name LIKE ?
            OR sp.student_id LIKE ?
            OR u.email LIKE ?
            OR CONCAT_WS(
                ' ',
                sp.first_name,
                sp.middle_name,
                sp.last_name
            ) LIKE ?
        )
    ";

    $searchLike =
        "%" . $search . "%";

    for ($i = 0; $i < 6; $i++) {
        $params[] =
            $searchLike;
        $types .= "s";
    }
}

if ($status !== "") {
    $allowedStatuses = [
        "pending",
        "active",
        "archived",
        "deactivated",
        "suspended"
    ];

    if (
        in_array(
            $status,
            $allowedStatuses,
            true
        )
    ) {
        $sql .= "
            AND u.account_status = ?
        ";

        $params[] = $status;
        $types .= "s";
    }
}

if ($program !== "") {
    $sql .= "
        AND sp.program = ?
    ";

    $params[] = $program;
    $types .= "s";
}

$sql .= "
    ORDER BY
        sp.last_name ASC,
        sp.first_name ASC,
        u.id DESC
";

$stmt =
    $conn->prepare($sql);

if (!$stmt) {
    die(
        "Unable to load student records."
    );
}

if (!empty($params)) {
    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$students =
    $stmt
        ->get_result();

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
        .message {
            margin-bottom: 18px;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 10px;
        }

        .message.success {
            color: #18794e;
            background: #eaf7ef;
            border: 1px solid #cee8d9;
        }

        .message.error {
            color: #a23939;
            background: #fff0f0;
            border: 1px solid #efcccc;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .trash-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            color: #7a3131;
            background: #fff4f4;
            border: 1px solid #efcccc;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 700;
            text-decoration: none;
        }

        .trash-button:hover {
            background: #ffeaea;
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .remove-button {
            padding: 7px 10px;
            color: #a23939;
            background: #fff;
            border: 1px solid #e8bcbc;
            border-radius: 7px;
            font-size: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .remove-button:hover {
            background: #fff0f0;
        }

        .empty-row {
            padding: 35px;
            color: #68766f;
            text-align: center;
        }
    </style>

</head>

<body>

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
                href="admin_trash.php"
                class="nav-link"
            >
                Trash
            </a>

            <a
                href="admin_settings.php"
                class="nav-link"
            >
                Settings
            </a>

        </nav>

        <a
            href="logout.php"
            class="logout-button"
        >
            Log out
        </a>

    </div>

</header>


<main class="page">

    <section class="page-header">

        <div>

            <span class="eyebrow">
                ADMINISTRATION
            </span>

            <h1>
                Manage Student Records
            </h1>

            <p>
                Search and manage registered student accounts.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="admin_trash.php"
                class="trash-button"
            >
                Trash
            </a>

        </div>

    </section>


    <?php if ($success !== ""): ?>

        <div class="message success">

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="message error">

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>

    <?php endif; ?>


    <section class="records-card">

        <form
            method="GET"
            action="student_records.php"
            class="filter-bar"
        >

            <div class="search-box">

                <input
                    type="search"
                    name="search"
                    placeholder="Search student name, ID, or email"
                    value="<?= htmlspecialchars($search) ?>"
                >

            </div>

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
                            : "" ?>
                    >

                        <?= htmlspecialchars(
                            $programOption
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <select name="status">

                <option value="">
                    All Status
                </option>

                <option
                    value="active"
                    <?= $status === "active"
                        ? "selected"
                        : "" ?>
                >
                    Active
                </option>

                <option
                    value="pending"
                    <?= $status === "pending"
                        ? "selected"
                        : "" ?>
                >
                    Pending
                </option>

                <option
                    value="suspended"
                    <?= $status === "suspended"
                        ? "selected"
                        : "" ?>
                >
                    Suspended
                </option>

                <option
                    value="deactivated"
                    <?= $status === "deactivated"
                        ? "selected"
                        : "" ?>
                >
                    Deactivated
                </option>

                <option
                    value="archived"
                    <?= $status === "archived"
                        ? "selected"
                        : "" ?>
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

        </form>


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
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (
                    $students->num_rows === 0
                ): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="empty-row"
                        >
                            No student records found.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php while (
                        $student =
                            $students->fetch_assoc()
                    ): ?>

                        <?php

                        $nameParts = [
                            trim(
                                $student[
                                    "first_name"
                                ] ?? ""
                            ),
                            trim(
                                $student[
                                    "middle_name"
                                ] ?? ""
                            ),
                            trim(
                                $student[
                                    "last_name"
                                ] ?? ""
                            )
                        ];

                        $nameParts =
                            array_filter(
                                $nameParts
                            );

                        $studentName =
                            trim(
                                implode(
                                    " ",
                                    $nameParts
                                )
                            );

                        if ($studentName === "") {
                            $studentName =
                                $student["email"];
                        }

                        ?>

                        <tr>

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $studentName
                                    ) ?>

                                </strong>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $student[
                                        "student_id"
                                    ] ?? "Not added"
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $student[
                                        "program"
                                    ] ?? "Not added"
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $student[
                                        "year_level"
                                    ] ?? "Not added"
                                ) ?>

                            </td>

                            <td>

                                <span class="status">

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $student[
                                                "account_status"
                                            ]
                                        )
                                    ) ?>

                                </span>

                            </td>

                            <td>

                                <div class="action-group">

                                    <a
                                        href="admin_student_profile.php?id=<?= (int) $student["user_id"] ?>"
                                        class="view-button"
                                    >
                                        Open Profile
                                    </a>

                                    <form
                                        method="POST"
                                        action="student_records.php"
                                        onsubmit="return confirm('Move this student account to Trash? It can be restored for 30 days.');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="remove_student"
                                        >

                                        <input
                                            type="hidden"
                                            name="student_user_id"
                                            value="<?= (int) $student["user_id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="remove-button"
                                        >
                                            Remove
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>


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

<?php

$stmt->close();

?>