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
        $_SESSION["hobbies_csrf"]
    )
) {

    $_SESSION["hobbies_csrf"] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION["hobbies_csrf"];


/* MESSAGE */

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


/* DUPLICATE CHECK */

function entryExists(
    mysqli $conn,
    int $userId,
    string $type,
    string $name,
    int $excludeId = 0
): bool {

    if ($type === "hobby") {

        if ($excludeId > 0) {

            $stmt =
                $conn->prepare("
                    SELECT id

                    FROM hobbies

                    WHERE
                        user_id = ?
                        AND LOWER(hobby) = LOWER(?)
                        AND id != ?

                    LIMIT 1
                ");


            $stmt->bind_param(
                "isi",
                $userId,
                $name,
                $excludeId
            );

        } else {

            $stmt =
                $conn->prepare("
                    SELECT id

                    FROM hobbies

                    WHERE
                        user_id = ?
                        AND LOWER(hobby) = LOWER(?)

                    LIMIT 1
                ");


            $stmt->bind_param(
                "is",
                $userId,
                $name
            );
        }

    } else {

        if ($excludeId > 0) {

            $stmt =
                $conn->prepare("
                    SELECT id

                    FROM interests

                    WHERE
                        user_id = ?
                        AND LOWER(interest) = LOWER(?)
                        AND id != ?

                    LIMIT 1
                ");


            $stmt->bind_param(
                "isi",
                $userId,
                $name,
                $excludeId
            );

        } else {

            $stmt =
                $conn->prepare("
                    SELECT id

                    FROM interests

                    WHERE
                        user_id = ?
                        AND LOWER(interest) = LOWER(?)

                    LIMIT 1
                ");


            $stmt->bind_param(
                "is",
                $userId,
                $name
            );
        }
    }


    $stmt->execute();


    $exists =
        $stmt
            ->get_result()
            ->num_rows > 0;


    $stmt->close();


    return $exists;
}


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
        strtolower(
            trim(
                $_POST["type"]
                ?? ""
            )
        );


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $error =
            "Your form session expired. Please refresh the page.";

    } elseif ($name === "") {

        $error =
            "Please enter a hobby or interest.";

    } elseif (
        strlen($name) > 150
    ) {

        $error =
            "The name must be 150 characters or fewer.";

    } elseif (
        !in_array(
            $type,
            [
                "hobby",
                "interest"
            ],
            true
        )
    ) {

        $error =
            "Please select a valid entry type.";

    } elseif (
        entryExists(
            $conn,
            $userId,
            $type,
            $name
        )
    ) {

        $error =
            "You already added this " .
            $type .
            ".";

    } else {

        if ($type === "hobby") {

            $stmt =
                $conn->prepare("
                    INSERT INTO hobbies
                    (
                        user_id,
                        hobby
                    )

                    VALUES
                    (
                        ?,
                        ?
                    )
                ");

        } else {

            $stmt =
                $conn->prepare("
                    INSERT INTO interests
                    (
                        user_id,
                        interest
                    )

                    VALUES
                    (
                        ?,
                        ?
                    )
                ");
        }


        if (!$stmt) {

            $error =
                "Unable to save the entry.";

        } else {

            $stmt->bind_param(
                "is",
                $userId,
                $name
            );


            if ($stmt->execute()) {

                $stmt->close();


                header(
                    "Location: hobbies.php?added=1"
                );

                exit;

            } else {

                $error =
                    "Unable to save the entry.";


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


    $entryId =
        (int) (
            $_POST["entry_id"]
            ?? 0
        );


    $originalType =
        strtolower(
            trim(
                $_POST["original_type"]
                ?? ""
            )
        );


    $type =
        strtolower(
            trim(
                $_POST["type"]
                ?? ""
            )
        );


    $name =
        trim(
            $_POST["name"]
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
        $entryId <= 0
    ) {

        $error =
            "Invalid entry.";

    } elseif (
        !in_array(
            $originalType,
            [
                "hobby",
                "interest"
            ],
            true
        )
    ) {

        $error =
            "Invalid original entry type.";

    } elseif (
        !in_array(
            $type,
            [
                "hobby",
                "interest"
            ],
            true
        )
    ) {

        $error =
            "Please select a valid entry type.";

    } elseif ($name === "") {

        $error =
            "Please enter a hobby or interest.";

    } elseif (
        strlen($name) > 150
    ) {

        $error =
            "The name must be 150 characters or fewer.";

    } else {

        $excludeId =
            $originalType === $type
                ? $entryId
                : 0;


        if (
            entryExists(
                $conn,
                $userId,
                $type,
                $name,
                $excludeId
            )
        ) {

            $error =
                "You already added this " .
                $type .
                ".";

        } else {

            /* SAME TYPE */

            if (
                $originalType === $type
            ) {

                if ($type === "hobby") {

                    $stmt =
                        $conn->prepare("
                            UPDATE hobbies

                            SET hobby = ?

                            WHERE
                                id = ?
                                AND user_id = ?

                            LIMIT 1
                        ");

                } else {

                    $stmt =
                        $conn->prepare("
                            UPDATE interests

                            SET interest = ?

                            WHERE
                                id = ?
                                AND user_id = ?

                            LIMIT 1
                        ");
                }


                if (!$stmt) {

                    $error =
                        "Unable to update the entry.";

                } else {

                    $stmt->bind_param(
                        "sii",
                        $name,
                        $entryId,
                        $userId
                    );


                    if (
                        $stmt->execute() &&
                        $stmt->affected_rows >= 0
                    ) {

                        $stmt->close();


                        header(
                            "Location: hobbies.php?updated=1"
                        );

                        exit;

                    } else {

                        $error =
                            "Unable to update the entry.";


                        $stmt->close();
                    }
                }

            /* CHANGE TYPE */

            } else {

                $conn->begin_transaction();


                try {

                    if (
                        $originalType === "hobby"
                    ) {

                        $deleteStmt =
                            $conn->prepare("
                                DELETE FROM hobbies

                                WHERE
                                    id = ?
                                    AND user_id = ?

                                LIMIT 1
                            ");

                    } else {

                        $deleteStmt =
                            $conn->prepare("
                                DELETE FROM interests

                                WHERE
                                    id = ?
                                    AND user_id = ?

                                LIMIT 1
                            ");
                    }


                    if (!$deleteStmt) {

                        throw new Exception(
                            "Unable to update the entry."
                        );
                    }


                    $deleteStmt->bind_param(
                        "ii",
                        $entryId,
                        $userId
                    );


                    $deleteStmt->execute();


                    if (
                        $deleteStmt->affected_rows !== 1
                    ) {

                        $deleteStmt->close();


                        throw new Exception(
                            "Entry could not be found."
                        );
                    }


                    $deleteStmt->close();


                    if ($type === "hobby") {

                        $insertStmt =
                            $conn->prepare("
                                INSERT INTO hobbies
                                (
                                    user_id,
                                    hobby
                                )

                                VALUES
                                (
                                    ?,
                                    ?
                                )
                            ");

                    } else {

                        $insertStmt =
                            $conn->prepare("
                                INSERT INTO interests
                                (
                                    user_id,
                                    interest
                                )

                                VALUES
                                (
                                    ?,
                                    ?
                                )
                            ");
                    }


                    if (!$insertStmt) {

                        throw new Exception(
                            "Unable to update the entry."
                        );
                    }


                    $insertStmt->bind_param(
                        "is",
                        $userId,
                        $name
                    );


                    if (
                        !$insertStmt->execute()
                    ) {

                        $insertStmt->close();


                        throw new Exception(
                            "Unable to update the entry."
                        );
                    }


                    $insertStmt->close();


                    $conn->commit();


                    header(
                        "Location: hobbies.php?updated=1"
                    );

                    exit;

                } catch (Throwable $exception) {

                    $conn->rollback();


                    $error =
                        $exception->getMessage();
                }
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


    $entryId =
        (int) (
            $_POST["entry_id"]
            ?? 0
        );


    $type =
        strtolower(
            trim(
                $_POST["type"]
                ?? ""
            )
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
        $entryId <= 0
    ) {

        $error =
            "Invalid entry.";

    } elseif (
        !in_array(
            $type,
            [
                "hobby",
                "interest"
            ],
            true
        )
    ) {

        $error =
            "Invalid entry type.";

    } else {

        if ($type === "hobby") {

            $stmt =
                $conn->prepare("
                    DELETE FROM hobbies

                    WHERE
                        id = ?
                        AND user_id = ?

                    LIMIT 1
                ");

        } else {

            $stmt =
                $conn->prepare("
                    DELETE FROM interests

                    WHERE
                        id = ?
                        AND user_id = ?

                    LIMIT 1
                ");
        }


        if (!$stmt) {

            $error =
                "Unable to remove the entry.";

        } else {

            $stmt->bind_param(
                "ii",
                $entryId,
                $userId
            );


            if ($stmt->execute()) {

                $stmt->close();


                header(
                    "Location: hobbies.php?deleted=1"
                );

                exit;

            } else {

                $error =
                    "Unable to remove the entry.";


                $stmt->close();
            }
        }
    }
}


/* FLASH MESSAGE */

if (isset($_GET["added"])) {

    $success =
        "Entry added successfully.";

} elseif (
    isset($_GET["updated"])
) {

    $success =
        "Entry updated successfully.";

} elseif (
    isset($_GET["deleted"])
) {

    $success =
        "Entry removed successfully.";
}


/* HOBBIES */

$hobbies = [];


$hobbyStmt =
    $conn->prepare("
        SELECT

            id,
            hobby,
            created_at,
            updated_at

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

        $hobbies[] = [

            "id" =>
                (int) $row["id"],

            "name" =>
                $row["hobby"],

            "type" =>
                "hobby",

            "label" =>
                "Hobby",

            "created_at" =>
                $row["created_at"]
        ];
    }


    $hobbyStmt->close();
}


/* INTERESTS */

$interests = [];


$interestStmt =
    $conn->prepare("
        SELECT

            id,
            interest,
            created_at,
            updated_at

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

        $interests[] = [

            "id" =>
                (int) $row["id"],

            "name" =>
                $row["interest"],

            "type" =>
                "interest",

            "label" =>
                "Interest",

            "created_at" =>
                $row["created_at"]
        ];
    }


    $interestStmt->close();
}


/* ENTRIES */

$entries =
    array_merge(
        $hobbies,
        $interests
    );


usort(
    $entries,

    function (
        array $a,
        array $b
    ): int {

        return strcmp(
            strtolower(
                $a["name"]
            ),
            strtolower(
                $b["name"]
            )
        );
    }
);


$totalEntries =
    count($entries);


$totalHobbies =
    count($hobbies);


$totalInterests =
    count($interests);

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
        Hobbies & Interests | CVSWHO
    </title>


    <link
        rel="stylesheet"
        href="hobbies.css"
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
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 12px;

            margin-bottom: 20px;
        }


        .summary-card {

            padding: 17px;

            background: white;

            border:
                1px solid #e1e9e4;

            border-radius: 11px;
        }


        .summary-card span {

            color: #68766f;

            font-size: 8px;

            font-weight: 700;

            letter-spacing: .6px;

            text-transform: uppercase;
        }


        .summary-card strong {

            display: block;

            margin-top: 7px;

            color: #006b3f;

            font-size: 22px;
        }


        .entry-type.hobby {

            color: #006b3f;

            background: #e6f3eb;
        }


        .entry-type.interest {

            color: #365d90;

            background: #edf3fb;
        }


        .entry-icon.interest {

            color: #365d90;

            background: #edf3fb;
        }


        .empty-state {

            padding:
                42px 20px;

            text-align: center;
        }


        .empty-state h3 {

            color: #003d24;

            font-size: 14px;
        }


        .empty-state p {

            max-width: 430px;

            margin:
                7px auto 0;

            color: #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        .empty-state button {

            margin-top: 15px;
        }


        .entry-actions form {

            display: inline;
        }


        .remove-action {

            cursor: pointer;
        }


        .delete-modal-text {

            color: #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        .danger-button {

            padding:
                10px 14px;

            color: white;

            background: #b84242;

            border:
                1px solid #b84242;

            border-radius: 7px;

            font-family: inherit;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;
        }


        .danger-button:hover {

            background: #913131;
        }


        @media (
            max-width: 650px
        ) {

            .summary-grid {

                grid-template-columns:
                    1fr;
            }


            .entry {

                align-items:
                    flex-start;

                flex-wrap: wrap;
            }


            .entry-actions {

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
            href="hobbies.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>


</header>


<!-- HOBBIES AND INTERESTS -->

<main class="page">


    <!-- HEADER -->

    <section class="page-header">


        <div>


            <span class="eyebrow">
                PERSONAL PROFILE
            </span>


            <h1>
                Hobbies & Interests
            </h1>


            <p>
                Add and manage the hobbies and
                interests connected to your
                CVSWHO student profile.
            </p>


        </div>


        <button
            type="button"
            class="primary-button"
            onclick="openAddForm()"
        >
            + Add Entry
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
                Total Entries
            </span>


            <strong>
                <?= $totalEntries ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Hobbies
            </span>


            <strong>
                <?= $totalHobbies ?>
            </strong>


        </div>


        <div class="summary-card">


            <span>
                Interests
            </span>


            <strong>
                <?= $totalInterests ?>
            </strong>


        </div>


    </section>


    <!-- ENTRIES -->

    <section class="entries-card">


        <div class="card-heading">


            <div>


                <span class="section-label">
                    MY ENTRIES
                </span>


                <h2>
                    Hobbies and Interests
                </h2>


            </div>


            <span class="entry-count">

                <?= $totalEntries ?>

                <?= $totalEntries === 1
                    ? "Entry"
                    : "Entries"
                ?>

            </span>


        </div>


        <?php if (
            $totalEntries > 0
        ): ?>


            <div class="entries-list">


                <?php foreach (
                    $entries
                    as $entry
                ): ?>


                    <div class="entry">


                        <div
                            class="
                                entry-icon
                                <?= htmlspecialchars(
                                    $entry["type"]
                                ) ?>
                            "
                        >

                            <?= $entry["type"] === "hobby"
                                ? "H"
                                : "I"
                            ?>

                        </div>


                        <div class="entry-content">


                            <div class="entry-title-row">


                                <h3>

                                    <?= htmlspecialchars(
                                        $entry["name"]
                                    ) ?>

                                </h3>


                                <span
                                    class="
                                        entry-type
                                        <?= htmlspecialchars(
                                            $entry["type"]
                                        ) ?>
                                    "
                                >

                                    <?= htmlspecialchars(
                                        $entry["label"]
                                    ) ?>

                                </span>


                            </div>


                            <p>

                                <?= $entry["type"]
                                    === "hobby"
                                        ? "Added as a hobby on your student profile."
                                        : "Added as a personal interest on your student profile."
                                ?>

                            </p>


                        </div>


                        <div class="entry-actions">


                            <button
                                type="button"
                                class="edit-action"
                                onclick='openEditForm(
                                    <?= (int) $entry["id"] ?>,
                                    <?= json_encode(
                                        $entry["name"]
                                    ) ?>,
                                    <?= json_encode(
                                        $entry["type"]
                                    ) ?>
                                )'
                            >
                                Edit
                            </button>


                            <button
                                type="button"
                                class="remove-action"
                                onclick='openDeleteForm(
                                    <?= (int) $entry["id"] ?>,
                                    <?= json_encode(
                                        $entry["name"]
                                    ) ?>,
                                    <?= json_encode(
                                        $entry["type"]
                                    ) ?>
                                )'
                            >
                                Remove
                            </button>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="empty-state">


                <h3>
                    No hobbies or interests yet
                </h3>


                <p>
                    Add hobbies and interests to
                    personalize your student profile
                    and Digital Portfolio.
                </p>


                <button
                    type="button"
                    class="primary-button"
                    onclick="openAddForm()"
                >
                    + Add Your First Entry
                </button>


            </div>


        <?php endif; ?>


    </section>


</main>


<!-- ADD ENTRY -->

<div
    class="modal"
    id="addModal"
>


    <div class="modal-box">


        <div class="modal-header">


            <div>


                <span class="section-label">
                    NEW ENTRY
                </span>


                <h2>
                    Add Hobby or Interest
                </h2>


            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeAddForm()"
            >
                ×
            </button>


        </div>


        <form
            method="POST"
            action="hobbies.php"
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


            <div class="form-group">


                <label for="add_name">
                    Name
                </label>


                <input
                    type="text"
                    id="add_name"
                    name="name"
                    maxlength="150"
                    placeholder="Example: Photography"
                    autocomplete="off"
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


                    <option value="hobby">
                        Hobby
                    </option>


                    <option value="interest">
                        Interest
                    </option>


                </select>


            </div>


            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeAddForm()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="save-button"
                >
                    Add Entry
                </button>


            </div>


        </form>


    </div>


</div>


<!-- EDIT ENTRY -->

<div
    class="modal"
    id="editModal"
>


    <div class="modal-box">


        <div class="modal-header">


            <div>


                <span class="section-label">
                    EDIT ENTRY
                </span>


                <h2>
                    Edit Hobby or Interest
                </h2>


            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeEditForm()"
            >
                ×
            </button>


        </div>


        <form
            method="POST"
            action="hobbies.php"
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
                name="entry_id"
                id="edit_entry_id"
            >


            <input
                type="hidden"
                name="original_type"
                id="edit_original_type"
            >


            <div class="form-group">


                <label for="edit_name">
                    Name
                </label>


                <input
                    type="text"
                    id="edit_name"
                    name="name"
                    maxlength="150"
                    autocomplete="off"
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


                    <option value="hobby">
                        Hobby
                    </option>


                    <option value="interest">
                        Interest
                    </option>


                </select>


            </div>


            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeEditForm()"
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


<!-- DELETE ENTRY -->

<div
    class="modal"
    id="deleteModal"
>


    <div class="modal-box">


        <div class="modal-header">


            <div>


                <span class="section-label">
                    REMOVE ENTRY
                </span>


                <h2>
                    Remove Hobby or Interest
                </h2>


            </div>


            <button
                type="button"
                class="close-button"
                onclick="closeDeleteForm()"
            >
                ×
            </button>


        </div>


        <p class="delete-modal-text">

            Are you sure you want to remove

            <strong id="deleteEntryName"></strong>

            from your profile?

        </p>


        <form
            method="POST"
            action="hobbies.php"
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
                name="entry_id"
                id="delete_entry_id"
            >


            <input
                type="hidden"
                name="type"
                id="delete_entry_type"
            >


            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeDeleteForm()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="danger-button"
                >
                    Remove Entry
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


    function openAddForm() {

        document.getElementById(
            "add_name"
        ).value = "";


        document.getElementById(
            "add_type"
        ).value = "hobby";


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


    function closeAddForm() {

        addModal.classList.remove(
            "show"
        );
    }


    function openEditForm(
        id,
        name,
        type
    ) {

        document.getElementById(
            "edit_entry_id"
        ).value = id;


        document.getElementById(
            "edit_original_type"
        ).value = type;


        document.getElementById(
            "edit_name"
        ).value = name;


        document.getElementById(
            "edit_type"
        ).value = type;


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


    function closeEditForm() {

        editModal.classList.remove(
            "show"
        );
    }


    function openDeleteForm(
        id,
        name,
        type
    ) {

        document.getElementById(
            "delete_entry_id"
        ).value = id;


        document.getElementById(
            "delete_entry_type"
        ).value = type;


        document.getElementById(
            "deleteEntryName"
        ).textContent = name;


        deleteModal.classList.add(
            "show"
        );
    }


    function closeDeleteForm() {

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

                closeAddForm();
            }


            if (
                event.target ===
                editModal
            ) {

                closeEditForm();
            }


            if (
                event.target ===
                deleteModal
            ) {

                closeDeleteForm();
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

                closeAddForm();

                closeEditForm();

                closeDeleteForm();
            }
        }
    );

</script>


</body>

</html>