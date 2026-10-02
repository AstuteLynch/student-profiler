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


$userId =
    (int) $_SESSION["user_id"];


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


    header("Location: index.php");
    exit;
}


/* =========================================================
   CATEGORY LABELS

   These values match the accomplishments database.
========================================================= */

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


/* =========================================================
   LOAD CURRENT USER'S ACCOMPLISHMENTS
========================================================= */

$accomplishments = [];


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


if (!$stmt) {

    die(
        "Accomplishments database error: " .
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


$result =
    $stmt->get_result();


while (
    $row =
        $result->fetch_assoc()
) {

    $accomplishments[] =
        $row;
}


$stmt->close();


/* =========================================================
   SUMMARY COUNTS
========================================================= */

$totalAccomplishments =
    count($accomplishments);


$documentCount = 0;

$usedCategories = [];


foreach (
    $accomplishments
    as $accomplishment
) {

    if (
        !empty(
            trim(
                $accomplishment[
                    "document_path"
                ] ?? ""
            )
        )
    ) {

        $documentCount++;
    }


    $category =
        $accomplishment[
            "category"
        ] ?? "";


    if ($category !== "") {

        $usedCategories[
            $category
        ] = true;
    }
}


$categoryCount =
    count($usedCategories);


/* =========================================================
   FLASH MESSAGE
========================================================= */

$message = "";

$messageType = "";


if (isset($_GET["added"])) {

    $message =
        "Accomplishment added successfully.";

    $messageType =
        "success";

} elseif (
    isset($_GET["updated"])
) {

    $message =
        "Accomplishment updated successfully.";

    $messageType =
        "success";

} elseif (
    isset($_GET["deleted"])
) {

    $message =
        "Accomplishment deleted successfully.";

    $messageType =
        "success";
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
        Accomplishments | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="accomplishments.css"
    >

    <style>

        .page-message {

            margin-bottom: 20px;

            padding:
                13px 16px;

            border-radius: 9px;

            font-size: 11px;

            font-weight: 600;
        }


        .page-message.success {

            color:
                #18794e;

            background:
                #eefaf3;

            border:
                1px solid #cae7d7;
        }


        .accomplishment-card {

            position: relative;
        }


        .card-actions {

            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }


        .details-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                8px 12px;

            color:
                #006b3f;

            background:
                #edf8f2;

            border:
                1px solid #cae3d5;

            border-radius: 7px;

            font-size: 9px;

            font-weight: 700;

            text-decoration: none;
        }


        .details-button:hover {

            background:
                #ddefe5;
        }


        .certificate-button.disabled {

            pointer-events: none;

            opacity: .45;
        }


        .empty-state.initial-empty {

            display: flex;
        }


        .empty-add-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            margin-top: 15px;

            padding:
                10px 14px;

            color: white;

            background:
                #006b3f;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;

            text-decoration: none;
        }


        .empty-add-button:hover {

            background:
                #004d2a;
        }

    </style>

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
            class="nav-link active"
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
            href="accomplishments.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>

</header>


<main class="accomplishments-page">


    <section class="page-header">


        <div>

            <span class="eyebrow">
                MY PROFILE
            </span>


            <h1>
                Accomplishments
            </h1>


            <p>
                Add and manage your achievements,
                competitions, certifications,
                academic records, and other
                accomplishments.
            </p>

        </div>


        <a
            href="add_accomplishment.php"
            class="add-button"
        >
            + Add Accomplishment
        </a>


    </section>


    <?php if (
        $message !== ""
    ): ?>

        <div
            class="
                page-message
                <?= htmlspecialchars(
                    $messageType
                ) ?>
            "
        >
            <?= htmlspecialchars(
                $message
            ) ?>
        </div>

    <?php endif; ?>


    <!-- =================================================
         SUMMARY
    ================================================== -->

    <section class="summary-row">


        <div class="summary-card">

            <div class="summary-icon">
                A
            </div>

            <div>

                <span>
                    TOTAL ACCOMPLISHMENTS
                </span>

                <strong>
                    <?= $totalAccomplishments ?>
                </strong>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon">
                C
            </div>

            <div>

                <span>
                    CATEGORIES USED
                </span>

                <strong>
                    <?= $categoryCount ?>
                </strong>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon">
                D
            </div>

            <div>

                <span>
                    DOCUMENTS
                </span>

                <strong>
                    <?= $documentCount ?>
                </strong>

            </div>

        </div>


    </section>


    <!-- =================================================
         FILTER
    ================================================== -->

    <section class="filter-card">


        <div class="filter-heading">

            <span class="section-label">
                FILTER
            </span>

            <h2>
                Browse Accomplishments
            </h2>

        </div>


        <div class="category-filters">


            <button
                type="button"
                class="filter-button active"
                data-category="all"
            >
                All
            </button>


            <?php foreach (
                $categoryLabels
                as $value => $label
            ): ?>

                <button
                    type="button"
                    class="filter-button"
                    data-category="<?= htmlspecialchars(
                        $value
                    ) ?>"
                >
                    <?= htmlspecialchars(
                        $label
                    ) ?>
                </button>

            <?php endforeach; ?>


        </div>


    </section>


    <!-- =================================================
         ACCOMPLISHMENT LIST
    ================================================== -->

    <section class="accomplishments-section">


        <div class="section-top">


            <div>

                <span class="section-label">
                    ACHIEVEMENTS
                </span>

                <h2>
                    Your Accomplishments
                </h2>

            </div>


            <span
                class="result-count"
                id="resultCount"
            >
                <?= $totalAccomplishments ?>

                record<?= $totalAccomplishments !== 1
                    ? "s"
                    : ""
                ?>
            </span>


        </div>


        <div
            class="accomplishment-list"
            id="accomplishmentList"
        >


            <?php foreach (
                $accomplishments
                as $accomplishment
            ): ?>


                <?php

                $categoryValue =
                    $accomplishment[
                        "category"
                    ] ?? "other";


                $categoryLabel =
                    $categoryLabels[
                        $categoryValue
                    ] ?? "Other";


                $dateDisplay =
                    !empty(
                        $accomplishment[
                            "date_achieved"
                        ]
                    )
                        ? date(
                            "M d, Y",
                            strtotime(
                                $accomplishment[
                                    "date_achieved"
                                ]
                            )
                        )
                        : "Date not added";


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


                $documentPath =
                    trim(
                        $accomplishment[
                            "document_path"
                        ] ?? ""
                    );

                ?>


                <article
                    class="accomplishment-card"
                    data-category="<?= htmlspecialchars(
                        $categoryValue
                    ) ?>"
                >


                    <div class="accomplishment-icon">
                        ✓
                    </div>


                    <div class="accomplishment-content">


                        <div class="accomplishment-header">


                            <div>

                                <span class="category-label">
                                    <?= htmlspecialchars(
                                        $categoryLabel
                                    ) ?>
                                </span>


                                <h3>
                                    <?= htmlspecialchars(
                                        $accomplishment[
                                            "title"
                                        ]
                                    ) ?>
                                </h3>

                            </div>


                            <span class="achievement-date">
                                <?= htmlspecialchars(
                                    $dateDisplay
                                ) ?>
                            </span>


                        </div>


                        <?php if (
                            $descriptionDisplay !== ""
                        ): ?>

                            <p class="description">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $descriptionDisplay
                                    )
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <div class="accomplishment-footer">


                            <div class="organization">


                                <span class="footer-label">
                                    ORGANIZATION
                                </span>


                                <span>
                                    <?= htmlspecialchars(
                                        $organizationDisplay
                                    ) ?>
                                </span>


                            </div>


                            <div class="card-actions">


                                <a
                                    href="accomplishment_details.php?id=<?= (int) $accomplishment["id"] ?>"
                                    class="details-button"
                                >
                                    View Details
                                </a>


                                <?php if (
                                    $documentPath !== ""
                                ): ?>

                                    <a
                                        href="<?= htmlspecialchars(
                                            $documentPath,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        class="certificate-button"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        View Document
                                    </a>

                                <?php endif; ?>


                            </div>


                        </div>


                    </div>


                </article>


            <?php endforeach; ?>


        </div>


        <!-- =================================================
             EMPTY DATABASE STATE
        ================================================== -->

        <?php if (
            $totalAccomplishments === 0
        ): ?>

            <div
                class="
                    empty-state
                    initial-empty
                "
                id="emptyState"
            >

                <div class="empty-icon">
                    +
                </div>


                <h3>
                    No accomplishments yet
                </h3>


                <p>
                    Add your first accomplishment
                    to start building your student
                    achievement record.
                </p>


                <a
                    href="add_accomplishment.php"
                    class="empty-add-button"
                >
                    + Add Accomplishment
                </a>

            </div>

        <?php else: ?>

            <div
                class="empty-state"
                id="emptyState"
            >

                <div class="empty-icon">
                    —
                </div>


                <h3>
                    No accomplishments found
                </h3>


                <p>
                    You do not have an accomplishment
                    in this category.
                </p>

            </div>

        <?php endif; ?>


    </section>


</main>


<footer class="footer">

    <p>
        CVSWHO
    </p>

    <span>
        Manage your student profile with ease.
    </span>

</footer>


<script>

    const filterButtons =
        document.querySelectorAll(
            ".filter-button"
        );


    const accomplishmentCards =
        document.querySelectorAll(
            ".accomplishment-card"
        );


    const emptyState =
        document.getElementById(
            "emptyState"
        );


    const resultCount =
        document.getElementById(
            "resultCount"
        );


    filterButtons.forEach(
        button => {

            button.addEventListener(
                "click",
                () => {

                    filterButtons.forEach(
                        item => {

                            item.classList.remove(
                                "active"
                            );
                        }
                    );


                    button.classList.add(
                        "active"
                    );


                    const selectedCategory =
                        button.dataset.category;


                    let visibleCount = 0;


                    accomplishmentCards.forEach(
                        card => {

                            const cardCategory =
                                card.dataset.category;


                            if (
                                selectedCategory
                                    === "all" ||
                                cardCategory
                                    === selectedCategory
                            ) {

                                card.style.display =
                                    "flex";

                                visibleCount++;

                            } else {

                                card.style.display =
                                    "none";
                            }
                        }
                    );


                    if (resultCount) {

                        resultCount.textContent =
                            visibleCount +
                            (
                                visibleCount === 1
                                    ? " record"
                                    : " records"
                            );
                    }


                    if (emptyState) {

                        emptyState.style.display =
                            visibleCount === 0
                                ? "flex"
                                : "none";
                    }
                }
            );
        }
    );

</script>


</body>

</html>