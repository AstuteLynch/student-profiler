<?php

session_start();

require_once "db.php";


if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


$userId =
    (int) $_SESSION["user_id"];


$accomplishmentId =
    isset($_GET["id"])
        ? (int) $_GET["id"]
        : 0;


if ($accomplishmentId <= 0) {

    header(
        "Location: accomplishments.php"
    );

    exit;
}


/* CSRF */

if (
    empty(
        $_SESSION[
            "accomplishment_csrf"
        ]
    )
) {

    $_SESSION[
        "accomplishment_csrf"
    ] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION[
        "accomplishment_csrf"
    ];


/* LOAD RECORD OWNED BY CURRENT USER */

$stmt =
    $conn->prepare("
        SELECT

            id,
            title,
            category,
            description,
            date_achieved,
            organization,
            document_path,
            created_at,
            updated_at

        FROM accomplishments

        WHERE
            id = ?
            AND user_id = ?

        LIMIT 1
    ");


if (!$stmt) {

    die(
        "Database error: " .
        htmlspecialchars(
            $conn->error
        )
    );
}


$stmt->bind_param(
    "ii",
    $accomplishmentId,
    $userId
);


$stmt->execute();


$accomplishment =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (!$accomplishment) {

    http_response_code(404);

    die(
        "Accomplishment not found."
    );
}


/* DELETE */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST" &&
    ($_POST["action"] ?? "")
        === "delete"
) {

    $submittedToken =
        $_POST["csrf_token"]
        ?? "";


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        die(
            "Invalid request."
        );
    }


    $documentPath =
        trim(
            $accomplishment[
                "document_path"
            ] ?? ""
        );


    $deleteStmt =
        $conn->prepare("
            DELETE FROM accomplishments

            WHERE
                id = ?
                AND user_id = ?

            LIMIT 1
        ");


    if (!$deleteStmt) {

        die(
            "Unable to delete accomplishment."
        );
    }


    $deleteStmt->bind_param(
        "ii",
        $accomplishmentId,
        $userId
    );


    if (
        $deleteStmt->execute()
    ) {

        $deleteStmt->close();


        if (
            $documentPath !== "" &&
            str_starts_with(
                $documentPath,
                "uploads/accomplishments/"
            )
        ) {

            $absolutePath =
                __DIR__ .
                "/" .
                $documentPath;


            if (
                is_file(
                    $absolutePath
                )
            ) {

                @unlink(
                    $absolutePath
                );
            }
        }


        header(
            "Location: accomplishments.php?deleted=1"
        );

        exit;
    }


    $deleteStmt->close();
}


/* LABELS */

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


$categoryLabel =
    $categoryLabels[
        $accomplishment[
            "category"
        ]
    ] ?? "Other";


$dateDisplay =
    !empty(
        $accomplishment[
            "date_achieved"
        ]
    )
        ? date(
            "F d, Y",
            strtotime(
                $accomplishment[
                    "date_achieved"
                ]
            )
        )
        : "Not specified";


$organizationDisplay =
    trim(
        $accomplishment[
            "organization"
        ] ?? ""
    );


if (
    $organizationDisplay === ""
) {

    $organizationDisplay =
        "Not specified";
}


$descriptionDisplay =
    trim(
        $accomplishment[
            "description"
        ] ?? ""
    );


if (
    $descriptionDisplay === ""
) {

    $descriptionDisplay =
        "No description was provided.";
}


$documentPath =
    trim(
        $accomplishment[
            "document_path"
        ] ?? ""
    );


$documentName =
    $documentPath !== ""
        ? basename(
            $documentPath
        )
        : "";

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
        Accomplishment Details | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="accomplishment_details.css"
    >

</head>


<body>


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
            href="student_dashboard.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>

</header>


<main class="details-page">


    <section class="page-header">


        <div>

            <span class="eyebrow">
                ACCOMPLISHMENTS
            </span>

            <h1>
                Accomplishment Details
            </h1>

            <p>
                View and manage this accomplishment.
            </p>

        </div>


        <a
            href="accomplishments.php"
            class="back-button"
        >
            ← Back to Accomplishments
        </a>
    <a
        href="organizations.php"
        class="nav-link"
    >
        Organizations
    </a>


    </section>


    <div class="details-layout">


        <div class="details-main">


            <section class="details-card">


                <div class="achievement-heading">


                    <div class="achievement-icon">
                        ✓
                    </div>


                    <div class="achievement-title">


                        <span class="category-label">
                            <?= htmlspecialchars(
                                $categoryLabel
                            ) ?>
                        </span>


                        <h2>
                            <?= htmlspecialchars(
                                $accomplishment[
                                    "title"
                                ]
                            ) ?>
                        </h2>


                        <p>
                            <?= htmlspecialchars(
                                $dateDisplay
                            ) ?>
                        </p>


                    </div>


                </div>


                <div class="information-section">


                    <span class="section-label">
                        ACHIEVEMENT INFORMATION
                    </span>


                    <h3>
                        Description
                    </h3>


                    <p class="description">
                        <?= nl2br(
                            htmlspecialchars(
                                $descriptionDisplay
                            )
                        ) ?>
                    </p>


                </div>


                <div class="information-grid">


                    <div class="information-item">

                        <span>
                            CATEGORY
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $categoryLabel
                            ) ?>
                        </strong>

                    </div>


                    <div class="information-item">

                        <span>
                            DATE ACHIEVED
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $dateDisplay
                            ) ?>
                        </strong>

                    </div>


                    <div
                        class="
                            information-item
                            full-width
                        "
                    >

                        <span>
                            ORGANIZATION
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $organizationDisplay
                            ) ?>
                        </strong>

                    </div>


                </div>


            </section>


            <section class="certificate-card">


                <div class="section-heading">

                    <span class="section-label">
                        SUPPORTING DOCUMENT
                    </span>

                    <h2>
                        Certificate or Document
                    </h2>

                </div>


                <?php if (
                    $documentPath !== ""
                ): ?>


                    <div class="document-preview">


                        <div class="document-icon">
                            FILE
                        </div>


                        <div class="document-information">

                            <h3>
                                <?= htmlspecialchars(
                                    $documentName
                                ) ?>
                            </h3>

                            <p>
                                Supporting document
                            </p>

                        </div>


                        <a
                            href="<?= htmlspecialchars(
                                $documentPath,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            class="view-document-button"
                            target="_blank"
                            rel="noopener"
                        >
                            View Document
                        </a>


                    </div>


                <?php else: ?>


                    <div class="document-preview">


                        <div class="document-icon">
                            —
                        </div>


                        <div class="document-information">

                            <h3>
                                No document uploaded
                            </h3>

                            <p>
                                This accomplishment
                                does not have a
                                supporting document.
                            </p>

                        </div>


                    </div>


                <?php endif; ?>


            </section>


        </div>


        <aside class="details-sidebar">


            <section class="action-card">


                <span class="section-label">
                    MANAGE
                </span>


                <h2>
                    Accomplishment Actions
                </h2>


                <div class="action-list">


                    <a
                        href="edit_accomplishment.php?id=<?= $accomplishmentId ?>"
                        class="edit-button"
                    >
                        Edit Accomplishment
                    </a>


                    <button
                        type="button"
                        class="delete-button"
                        onclick="showDeleteConfirmation()"
                    >
                        Delete Accomplishment
                    </button>


                </div>


            </section>


            <section class="summary-card">


                <span class="section-label">
                    SUMMARY
                </span>


                <h2>
                    Record Information
                </h2>


                <div class="summary-list">


                    <div class="summary-item">

                        <span>
                            Category
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $categoryLabel
                            ) ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            Date
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $dateDisplay
                            ) ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            Organization
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $organizationDisplay
                            ) ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            Document
                        </span>

                        <strong>
                            <?= $documentPath !== ""
                                ? "Available"
                                : "None"
                            ?>
                        </strong>

                    </div>


                </div>


            </section>


            <section class="privacy-card">

                <div class="privacy-icon">
                    ✓
                </div>

                <div>

                    <h3>
                        Portfolio Visibility
                    </h3>

                    <p>
                        Visibility is controlled by
                        your Accomplishments setting
                        in Privacy.
                    </p>

                </div>

            </section>


        </aside>


    </div>


</main>


<!-- DELETE CONFIRMATION -->

<div
    class="delete-overlay"
    id="deleteOverlay"
>

    <div class="delete-modal">


        <div class="delete-icon">
            !
        </div>


        <h2>
            Delete Accomplishment?
        </h2>


        <p>
            This permanently deletes this
            accomplishment and its uploaded
            document.
        </p>


        <div class="delete-actions">


            <button
                type="button"
                class="cancel-delete"
                onclick="hideDeleteConfirmation()"
            >
                Cancel
            </button>


            <form
                method="POST"
                action="accomplishment_details.php?id=<?= $accomplishmentId ?>"
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


                <button
                    type="submit"
                    class="confirm-delete"
                >
                    Delete
                </button>

            </form>


        </div>


    </div>

</div>


<footer class="footer">

    <p>
        CVSWHO
    </p>

    <span>
        Manage your student profile with ease.
    </span>

</footer>


<script>

    const deleteOverlay =
        document.getElementById(
            "deleteOverlay"
        );


    function showDeleteConfirmation() {

        deleteOverlay.classList.add(
            "show"
        );
    }


    function hideDeleteConfirmation() {

        deleteOverlay.classList.remove(
            "show"
        );
    }


    deleteOverlay.addEventListener(
        "click",
        function (event) {

            if (
                event.target ===
                deleteOverlay
            ) {

                hideDeleteConfirmation();
            }
        }
    );

</script>


</body>

</html>