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


/* CSRF */

if (
    empty(
        $_SESSION["organizations_csrf"]
    )
) {

    $_SESSION["organizations_csrf"] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION["organizations_csrf"];


/* MESSAGES */

$success = "";

$error = "";


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


/* TYPES */

$typeLabels = [

    "organization" =>
        "Organization",

    "club" =>
        "Club",

    "student_government" =>
        "Student Government",

    "event" =>
        "Event"
];


$typeIcons = [

    "organization" =>
        "O",

    "club" =>
        "C",

    "student_government" =>
        "G",

    "event" =>
        "E"
];


/* ADD */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "add"
) {

    $submittedToken =
        $_POST["csrf_token"]
        ?? "";


    $name =
        trim(
            $_POST["name"]
            ?? ""
        );


    $type =
        trim(
            $_POST["type"]
            ?? ""
        );


    $position =
        trim(
            $_POST["position"]
            ?? ""
        );


    $participationDate =
        trim(
            $_POST["participation_date"]
            ?? ""
        );


    $description =
        trim(
            $_POST["description"]
            ?? ""
        );


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $error =
            "Your form session expired. Please refresh the page.";

    } elseif (
        $name === ""
    ) {

        $error =
            "Please enter the organization or activity name.";

    } elseif (
        strlen($name) > 255
    ) {

        $error =
            "The organization name must be 255 characters or fewer.";

    } elseif (
        !array_key_exists(
            $type,
            $typeLabels
        )
    ) {

        $error =
            "Please select a valid activity type.";

    } elseif (
        strlen($position) > 150
    ) {

        $error =
            "The position must be 150 characters or fewer.";

    } elseif (
        $participationDate !== "" &&
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $participationDate
        )
    ) {

        $error =
            "Please enter a valid participation date.";

    } else {

        $dateForDatabase =
            $participationDate !== ""
                ? $participationDate
                : null;


        $positionForDatabase =
            $position !== ""
                ? $position
                : null;


        $descriptionForDatabase =
            $description !== ""
                ? $description
                : null;


        $stmt =
            $conn->prepare("
                INSERT INTO organizations
                (
                    user_id,
                    name,
                    type,
                    position,
                    participation_date,
                    description
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


        if (!$stmt) {

            $error =
                "Unable to save the activity.";

        } else {

            $stmt->bind_param(
                "isssss",

                $userId,
                $name,
                $type,
                $positionForDatabase,
                $dateForDatabase,
                $descriptionForDatabase
            );


            if ($stmt->execute()) {

                $stmt->close();


                header(
                    "Location: organizations.php?added=1"
                );

                exit;

            } else {

                $error =
                    "Unable to save the activity.";


                $stmt->close();
            }
        }
    }
}


/* UPDATE */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "update"
) {

    $submittedToken =
        $_POST["csrf_token"]
        ?? "";


    $organizationId =
        (int) (
            $_POST["organization_id"]
            ?? 0
        );


    $name =
        trim(
            $_POST["name"]
            ?? ""
        );


    $type =
        trim(
            $_POST["type"]
            ?? ""
        );


    $position =
        trim(
            $_POST["position"]
            ?? ""
        );


    $participationDate =
        trim(
            $_POST["participation_date"]
            ?? ""
        );


    $description =
        trim(
            $_POST["description"]
            ?? ""
        );


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $error =
            "Your form session expired. Please refresh the page.";

    } elseif (
        $organizationId <= 0
    ) {

        $error =
            "Invalid organization record.";

    } elseif (
        $name === ""
    ) {

        $error =
            "Please enter the organization or activity name.";

    } elseif (
        strlen($name) > 255
    ) {

        $error =
            "The organization name must be 255 characters or fewer.";

    } elseif (
        !array_key_exists(
            $type,
            $typeLabels
        )
    ) {

        $error =
            "Please select a valid activity type.";

    } elseif (
        strlen($position) > 150
    ) {

        $error =
            "The position must be 150 characters or fewer.";

    } elseif (
        $participationDate !== "" &&
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $participationDate
        )
    ) {

        $error =
            "Please enter a valid participation date.";

    } else {

        $dateForDatabase =
            $participationDate !== ""
                ? $participationDate
                : null;


        $positionForDatabase =
            $position !== ""
                ? $position
                : null;


        $descriptionForDatabase =
            $description !== ""
                ? $description
                : null;


        $stmt =
            $conn->prepare("
                UPDATE organizations

                SET
                    name = ?,
                    type = ?,
                    position = ?,
                    participation_date = ?,
                    description = ?

                WHERE
                    id = ?
                    AND user_id = ?

                LIMIT 1
            ");


        if (!$stmt) {

            $error =
                "Unable to update the activity.";

        } else {

            $stmt->bind_param(
                "sssssii",

                $name,
                $type,
                $positionForDatabase,
                $dateForDatabase,
                $descriptionForDatabase,

                $organizationId,
                $userId
            );


            if ($stmt->execute()) {

                $stmt->close();


                header(
                    "Location: organizations.php?updated=1"
                );

                exit;

            } else {

                $error =
                    "Unable to update the activity.";


                $stmt->close();
            }
        }
    }
}


/* DELETE */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "delete"
) {

    $submittedToken =
        $_POST["csrf_token"]
        ?? "";


    $organizationId =
        (int) (
            $_POST["organization_id"]
            ?? 0
        );


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $error =
            "Your form session expired. Please refresh the page.";

    } elseif (
        $organizationId <= 0
    ) {

        $error =
            "Invalid organization record.";

    } else {

        $stmt =
            $conn->prepare("
                DELETE FROM organizations

                WHERE
                    id = ?
                    AND user_id = ?

                LIMIT 1
            ");


        if (!$stmt) {

            $error =
                "Unable to remove the activity.";

        } else {

            $stmt->bind_param(
                "ii",
                $organizationId,
                $userId
            );


            if ($stmt->execute()) {

                $stmt->close();


                header(
                    "Location: organizations.php?deleted=1"
                );

                exit;

            } else {

                $error =
                    "Unable to remove the activity.";


                $stmt->close();
            }
        }
    }
}


/* FLASH MESSAGE */

if (isset($_GET["added"])) {

    $success =
        "Activity added successfully.";

} elseif (
    isset($_GET["updated"])
) {

    $success =
        "Activity updated successfully.";

} elseif (
    isset($_GET["deleted"])
) {

    $success =
        "Activity removed successfully.";
}


/* TAB */

$tab =
    strtolower(
        trim(
            $_GET["tab"]
            ?? "all"
        )
    );


$allowedTabs = [

    "all",
    "organization",
    "club",
    "student_government",
    "event"
];


if (
    !in_array(
        $tab,
        $allowedTabs,
        true
    )
) {

    $tab =
        "all";
}


/* ORGANIZATIONS */

$organizations = [];


$stmt =
    $conn->prepare("
        SELECT

            id,
            name,
            type,
            position,
            participation_date,
            description,
            created_at,
            updated_at

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


if (!$stmt) {

    die(
        "Organizations database error: " .
        htmlspecialchars(
            $conn->error
        )
    );
}


$stmt->bind_param(
    "i",
    $userId
);


$stmt->execute();


$result =
    $stmt->get_result();


while (
    $row =
        $result->fetch_assoc()
) {

    $organizations[] =
        $row;
}


$stmt->close();


/* COUNTS */

$totalOrganizations =
    count($organizations);


$typeCounts = [

    "organization" => 0,

    "club" => 0,

    "student_government" => 0,

    "event" => 0
];


foreach (
    $organizations
    as $organization
) {

    $type =
        $organization["type"]
        ?? "";


    if (
        isset(
            $typeCounts[$type]
        )
    ) {

        $typeCounts[$type]++;
    }
}


/* FILTER */

$filteredOrganizations =
    array_filter(
        $organizations,

        function (
            array $organization
        ) use ($tab): bool {

            if (
                $tab === "all"
            ) {

                return true;
            }


            return
                ($organization["type"] ?? "")
                === $tab;
        }
    );


$filteredOrganizations =
    array_values(
        $filteredOrganizations
    );


/* TAB TITLE */

$tabTitle =
    $tab === "all"
        ? "All Activities"
        : (
            $typeLabels[$tab]
            ?? "Activities"
        );

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
        Organizations & Activities | CVSWHO
    </title>


    <link
        rel="stylesheet"
        href="organizations.css"
    >


    <style>

        .page-message {

            margin-bottom: 20px;

            padding:
                13px 16px;

            border-radius: 9px;

            font-size: 10px;

            font-weight: 600;
        }


        .page-message.success {

            color: #18794e;

            background: #eefaf3;

            border:
                1px solid #cae7d7;
        }


        .page-message.error {

            color: #a33f3f;

            background: #fff5f5;

            border:
                1px solid #efd0d0;
        }


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

            padding: 15px;

            background: white;

            border:
                1px solid #e1e9e4;

            border-radius: 10px;
        }


        .summary-card span {

            display: block;

            color: #68766f;

            font-size: 7px;

            font-weight: 700;

            letter-spacing: .6px;

            text-transform: uppercase;
        }


        .summary-card strong {

            display: block;

            margin-top: 6px;

            color: #006b3f;

            font-size: 20px;
        }


        .primary-button {

            border: none;

            cursor: pointer;
        }


        .activity-type {

            display: inline-flex;

            width: fit-content;

            margin-bottom: 6px;

            padding:
                4px 8px;

            color: #006b3f;

            background: #e6f3eb;

            border-radius: 30px;

            font-size: 8px;

            font-weight: 700;

            text-transform: uppercase;
        }


        .activity-date {

            color: #68766f;

            font-size: 9px;
        }


        .activity-description {

            margin-top: 8px;

            line-height: 1.7;
        }


        .activity-actions {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .edit-button {

            cursor: pointer;
        }


        .delete-button {

            cursor: pointer;
        }


        .empty-state {

            padding:
                45px 20px;

            text-align: center;
        }


        .empty-state h3 {

            color: #003d24;

            font-size: 14px;
        }


        .empty-state p {

            max-width: 430px;

            margin:
                8px auto 0;

            color: #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        .empty-state button {

            margin-top: 15px;
        }


        .modal {

            position: fixed;

            inset: 0;

            z-index: 500;

            display: none;

            align-items: center;

            justify-content: center;

            padding: 20px;

            background:
                rgba(
                    5,
                    20,
                    12,
                    .58
                );
        }


        .modal.show {

            display: flex;
        }


        .modal-box {

            width:
                min(
                    540px,
                    100%
                );

            max-height:
                calc(
                    100vh - 40px
                );

            overflow-y: auto;

            padding: 25px;

            background: white;

            border-radius: 14px;

            box-shadow:
                0 20px 60px
                rgba(
                    0,
                    0,
                    0,
                    .18
                );
        }


        .modal-header {

            display: flex;

            align-items: flex-start;

            justify-content:
                space-between;

            gap: 20px;

            margin-bottom: 20px;
        }


        .modal-header h2 {

            margin-top: 5px;

            color: #003d24;

            font-size: 17px;
        }


        .close-button {

            width: 32px;
            height: 32px;

            display: grid;

            place-items: center;

            color: #68766f;

            background: #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 8px;

            font-size: 18px;

            cursor: pointer;
        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 14px;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 6px;
        }


        .form-group.full-width {

            grid-column:
                1 / -1;
        }


        .form-group label {

            color: #003d24;

            font-size: 9px;

            font-weight: 700;
        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            padding:
                11px 12px;

            color: #1d2a23;

            background: white;

            border:
                1px solid #e1e9e4;

            border-radius: 8px;

            outline: none;

            font: inherit;

            font-size: 10px;
        }


        .form-group textarea {

            min-height: 110px;

            resize: vertical;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

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


        .form-group small {

            color: #8c9891;

            font-size: 8px;

            line-height: 1.5;
        }


        .modal-actions {

            display: flex;

            justify-content:
                flex-end;

            gap: 8px;

            margin-top: 20px;
        }


        .cancel-button,
        .save-button,
        .danger-button {

            padding:
                10px 14px;

            border-radius: 8px;

            font-family: inherit;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }


        .cancel-button {

            color: #68766f;

            background: white;

            border:
                1px solid #e1e9e4;
        }


        .save-button {

            color: white;

            background: #006b3f;

            border:
                1px solid #006b3f;
        }


        .save-button:hover {

            background: #004d2a;
        }


        .danger-button {

            color: white;

            background: #b84242;

            border:
                1px solid #b84242;
        }


        .danger-button:hover {

            background: #913131;
        }


        .delete-text {

            color: #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        @media (
            max-width: 850px
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

        }


        @media (
            max-width: 650px
        ) {

            .summary-grid {

                grid-template-columns:
                    1fr;
            }


            .form-grid {

                grid-template-columns:
                    1fr;
            }


            .form-group.full-width {

                grid-column: auto;
            }


            .activity {

                align-items: flex-start;

                flex-wrap: wrap;
            }


            .activity-actions {

                width: 100%;

                padding-left: 49px;
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
            href="organizations.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>


</header>


<!-- ORGANIZATIONS AND ACTIVITIES -->

<main class="page">


    <!-- HEADER -->

    <section class="page-header">


        <div>


            <span class="eyebrow">
                ACTIVITIES
            </span>


            <h1>
                Organizations & Activities
            </h1>


            <p>
                Add and manage your organizations,
                clubs, student government roles,
                and event participation.
            </p>


        </div>


        <button
            type="button"
            class="primary-button"
            onclick="openAddModal()"
        >
            + Add Activity
        </button>


    </section>


    <?php if (
        $success !== ""
    ): ?>


        <div class="page-message success">

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>


    <?php endif; ?>


    <?php if (
        $error !== ""
    ): ?>


        <div class="page-message error">

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>


    <?php endif; ?>


    <!-- SUMMARY -->

    <section class="summary-grid">


        <div class="summary-card">


            <span>
                Total Activities
            </span>


            <strong>
                <?= $totalOrganizations ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Organizations
            </span>


            <strong>
                <?= $typeCounts[
                    "organization"
                ] ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Clubs
            </span>


            <strong>
                <?= $typeCounts[
                    "club"
                ] ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Student Government
            </span>


            <strong>
                <?= $typeCounts[
                    "student_government"
                ] ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Events
            </span>


            <strong>
                <?= $typeCounts[
                    "event"
                ] ?>
            </strong>


        </div>


    </section>


    <!-- ACTIVITIES -->

    <section class="content-card">


        <!-- TABS -->

        <nav class="tabs">


            <a
                href="organizations.php?tab=all"
                class="tab <?= $tab === "all"
                    ? "active"
                    : ""
                ?>"
            >
                All
            </a>


            <a
                href="organizations.php?tab=organization"
                class="tab <?= $tab === "organization"
                    ? "active"
                    : ""
                ?>"
            >
                Organizations
            </a>


            <a
                href="organizations.php?tab=club"
                class="tab <?= $tab === "club"
                    ? "active"
                    : ""
                ?>"
            >
                Clubs
            </a>


            <a
                href="organizations.php?tab=student_government"
                class="tab <?= $tab === "student_government"
                    ? "active"
                    : ""
                ?>"
            >
                Student Government
            </a>


            <a
                href="organizations.php?tab=event"
                class="tab <?= $tab === "event"
                    ? "active"
                    : ""
                ?>"
            >
                Events
            </a>


        </nav>


        <div class="section-heading">


            <span class="section-label">
                MY ACTIVITIES
            </span>


            <h2>
                <?= htmlspecialchars(
                    $tabTitle
                ) ?>
            </h2>


        </div>


        <?php if (
            count(
                $filteredOrganizations
            ) > 0
        ): ?>


            <div class="activity-list">


                <?php foreach (
                    $filteredOrganizations
                    as $organization
                ): ?>


                    <?php

                    $organizationType =
                        $organization["type"]
                        ?? "organization";


                    $typeLabel =
                        $typeLabels[
                            $organizationType
                        ]
                        ?? "Organization";


                    $icon =
                        $typeIcons[
                            $organizationType
                        ]
                        ?? "O";


                    $position =
                        trim(
                            $organization[
                                "position"
                            ]
                            ?? ""
                        );


                    $description =
                        trim(
                            $organization[
                                "description"
                            ]
                            ?? ""
                        );


                    $participationDate =
                        trim(
                            $organization[
                                "participation_date"
                            ]
                            ?? ""
                        );


                    $dateDisplay =
                        $participationDate !== ""
                            ? date(
                                "F d, Y",
                                strtotime(
                                    $participationDate
                                )
                            )
                            : "Date not specified";

                    ?>


                    <article class="activity">


                        <div class="activity-icon">

                            <?= htmlspecialchars(
                                $icon
                            ) ?>

                        </div>


                        <div class="activity-content">


                            <span class="activity-type">

                                <?= htmlspecialchars(
                                    $typeLabel
                                ) ?>

                            </span>


                            <h3>

                                <?= htmlspecialchars(
                                    $organization[
                                        "name"
                                    ]
                                ) ?>

                            </h3>


                            <div class="activity-meta">


                                <?php if (
                                    $position !== ""
                                ): ?>


                                    <span>

                                        <?= htmlspecialchars(
                                            $position
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                                <span class="activity-date">

                                    <?= htmlspecialchars(
                                        $dateDisplay
                                    ) ?>

                                </span>


                            </div>


                            <?php if (
                                $description !== ""
                            ): ?>


                                <p class="activity-description">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $description
                                        )
                                    ) ?>

                                </p>


                            <?php endif; ?>


                        </div>


                        <div class="activity-actions">


                            <button
                                type="button"
                                class="edit-button"
                                onclick='openEditModal(
                                    <?= (int) $organization["id"] ?>,
                                    <?= json_encode(
                                        $organization["name"]
                                    ) ?>,
                                    <?= json_encode(
                                        $organization["type"]
                                    ) ?>,
                                    <?= json_encode(
                                        $organization["position"]
                                        ?? ""
                                    ) ?>,
                                    <?= json_encode(
                                        $organization["participation_date"]
                                        ?? ""
                                    ) ?>,
                                    <?= json_encode(
                                        $organization["description"]
                                        ?? ""
                                    ) ?>
                                )'
                            >
                                Edit
                            </button>


                            <button
                                type="button"
                                class="delete-button"
                                onclick='openDeleteModal(
                                    <?= (int) $organization["id"] ?>,
                                    <?= json_encode(
                                        $organization["name"]
                                    ) ?>
                                )'
                            >
                                Remove
                            </button>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="empty-state">


                <h3>
                    No activities found
                </h3>


                <p>

                    <?php if (
                        $tab === "all"
                    ): ?>

                        You have not added any
                        organizations or activities yet.

                    <?php else: ?>

                        You do not have any records
                        under this activity type yet.

                    <?php endif; ?>

                </p>


                <button
                    type="button"
                    class="primary-button"
                    onclick="openAddModal()"
                >
                    + Add Activity
                </button>


            </div>


        <?php endif; ?>


    </section>


</main>


<!-- ADD ACTIVITY -->

<div
    class="modal"
    id="addModal"
>


    <div class="modal-box">


        <div class="modal-header">


            <div>


                <span class="section-label">
                    NEW ACTIVITY
                </span>


                <h2>
                    Add Organization or Activity
                </h2>


            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeAddModal()"
            >
                ×
            </button>


        </div>


        <form
            method="POST"
            action="organizations.php"
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
                value="add"
            >


            <div class="form-grid">


                <div class="form-group full-width">


                    <label for="add_name">
                        Name
                    </label>


                    <input
                        type="text"
                        id="add_name"
                        name="name"
                        maxlength="255"
                        placeholder="Organization, club, or event name"
                        required
                    >


                </div>


                <div class="form-group">


                    <label for="add_type">
                        Type
                    </label>


                    <select
                        id="add_type"
                        name="type"
                        required
                    >


                        <option value="organization">
                            Organization
                        </option>


                        <option value="club">
                            Club
                        </option>


                        <option value="student_government">
                            Student Government
                        </option>


                        <option value="event">
                            Event
                        </option>


                    </select>


                </div>


                <div class="form-group">


                    <label for="add_date">
                        Participation Date
                    </label>


                    <input
                        type="date"
                        id="add_date"
                        name="participation_date"
                    >


                    <small>
                        Optional.
                    </small>


                </div>


                <div class="form-group full-width">


                    <label for="add_position">
                        Position or Role
                    </label>


                    <input
                        type="text"
                        id="add_position"
                        name="position"
                        maxlength="150"
                        placeholder="Example: Member, President, Volunteer"
                    >


                    <small>
                        Optional.
                    </small>


                </div>


                <div class="form-group full-width">


                    <label for="add_description">
                        Description
                    </label>


                    <textarea
                        id="add_description"
                        name="description"
                        placeholder="Describe your participation or role"
                    ></textarea>


                    <small>
                        Optional.
                    </small>


                </div>


            </div>


            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeAddModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="save-button"
                >
                    Add Activity
                </button>


            </div>


        </form>


    </div>


</div>


<!-- EDIT ACTIVITY -->

<div
    class="modal"
    id="editModal"
>


    <div class="modal-box">


        <div class="modal-header">


            <div>


                <span class="section-label">
                    EDIT ACTIVITY
                </span>


                <h2>
                    Update Organization or Activity
                </h2>


            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeEditModal()"
            >
                ×
            </button>


        </div>


        <form
            method="POST"
            action="organizations.php"
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
                value="update"
            >


            <input
                type="hidden"
                name="organization_id"
                id="edit_id"
            >


            <div class="form-grid">


                <div class="form-group full-width">


                    <label for="edit_name">
                        Name
                    </label>


                    <input
                        type="text"
                        id="edit_name"
                        name="name"
                        maxlength="255"
                        required
                    >


                </div>


                <div class="form-group">


                    <label for="edit_type">
                        Type
                    </label>


                    <select
                        id="edit_type"
                        name="type"
                        required
                    >


                        <option value="organization">
                            Organization
                        </option>


                        <option value="club">
                            Club
                        </option>


                        <option value="student_government">
                            Student Government
                        </option>


                        <option value="event">
                            Event
                        </option>


                    </select>


                </div>


                <div class="form-group">


                    <label for="edit_date">
                        Participation Date
                    </label>


                    <input
                        type="date"
                        id="edit_date"
                        name="participation_date"
                    >


                </div>


                <div class="form-group full-width">


                    <label for="edit_position">
                        Position or Role
                    </label>


                    <input
                        type="text"
                        id="edit_position"
                        name="position"
                        maxlength="150"
                    >


                </div>


                <div class="form-group full-width">


                    <label for="edit_description">
                        Description
                    </label>


                    <textarea
                        id="edit_description"
                        name="description"
                    ></textarea>


                </div>


            </div>


            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="save-button"
                >
                    Save Changes
                </button>


            </div>


        </form>


    </div>


</div>


<!-- DELETE ACTIVITY -->

<div
    class="modal"
    id="deleteModal"
>


    <div class="modal-box">


        <div class="modal-header">


            <div>


                <span class="section-label">
                    REMOVE ACTIVITY
                </span>


                <h2>
                    Remove Activity
                </h2>


            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeDeleteModal()"
            >
                ×
            </button>


        </div>


        <p class="delete-text">

            Are you sure you want to remove

            <strong id="deleteActivityName"></strong>

            from your profile?

        </p>


        <form
            method="POST"
            action="organizations.php"
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
                value="delete"
            >


            <input
                type="hidden"
                name="organization_id"
                id="delete_id"
            >


            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeDeleteModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="danger-button"
                >
                    Remove Activity
                </button>


            </div>


        </form>


    </div>


</div>


<!-- FOOTER -->

<footer class="footer">


    <p>
        CVSWHO
    </p>


    <span>
        Manage your student profile with ease.
    </span>


</footer>


<script>

    const addModal =
        document.getElementById(
            "addModal"
        );


    const editModal =
        document.getElementById(
            "editModal"
        );


    const deleteModal =
        document.getElementById(
            "deleteModal"
        );


    function openAddModal() {

        document.getElementById(
            "add_name"
        ).value = "";


        document.getElementById(
            "add_type"
        ).value =
            "organization";


        document.getElementById(
            "add_date"
        ).value = "";


        document.getElementById(
            "add_position"
        ).value = "";


        document.getElementById(
            "add_description"
        ).value = "";


        addModal.classList.add(
            "show"
        );


        setTimeout(
            function () {

                document.getElementById(
                    "add_name"
                ).focus();

            },
            100
        );
    }


    function closeAddModal() {

        addModal.classList.remove(
            "show"
        );
    }


    function openEditModal(
        id,
        name,
        type,
        position,
        participationDate,
        description
    ) {

        document.getElementById(
            "edit_id"
        ).value = id;


        document.getElementById(
            "edit_name"
        ).value =
            name || "";


        document.getElementById(
            "edit_type"
        ).value =
            type || "organization";


        document.getElementById(
            "edit_position"
        ).value =
            position || "";


        document.getElementById(
            "edit_date"
        ).value =
            participationDate || "";


        document.getElementById(
            "edit_description"
        ).value =
            description || "";


        editModal.classList.add(
            "show"
        );


        setTimeout(
            function () {

                document.getElementById(
                    "edit_name"
                ).focus();

            },
            100
        );
    }


    function closeEditModal() {

        editModal.classList.remove(
            "show"
        );
    }


    function openDeleteModal(
        id,
        name
    ) {

        document.getElementById(
            "delete_id"
        ).value = id;


        document.getElementById(
            "deleteActivityName"
        ).textContent =
            name;


        deleteModal.classList.add(
            "show"
        );
    }


    function closeDeleteModal() {

        deleteModal.classList.remove(
            "show"
        );
    }


    window.addEventListener(
        "click",
        function (event) {

            if (
                event.target ===
                addModal
            ) {

                closeAddModal();
            }


            if (
                event.target ===
                editModal
            ) {

                closeEditModal();
            }


            if (
                event.target ===
                deleteModal
            ) {

                closeDeleteModal();
            }
        }
    );


    window.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key ===
                "Escape"
            ) {

                closeAddModal();

                closeEditModal();

                closeDeleteModal();
            }
        }
    );

</script>

<script src="main.js"></script>


</body>

</html>