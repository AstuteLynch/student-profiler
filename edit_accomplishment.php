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


$error = "";


/* =========================================================
   CSRF
========================================================= */

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


/* =========================================================
   CATEGORIES
========================================================= */

$categories = [

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
   LOAD ACCOMPLISHMENT
========================================================= */

$stmt =
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

        WHERE
            id = ?
            AND user_id = ?

        LIMIT 1
    ");


if (!$stmt) {

    die(
        "Database error."
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


/* =========================================================
   FORM VALUES
========================================================= */

$title =
    trim(
        $accomplishment[
            "title"
        ] ?? ""
    );


$category =
    trim(
        $accomplishment[
            "category"
        ] ?? ""
    );


$dateAchieved =
    trim(
        $accomplishment[
            "date_achieved"
        ] ?? ""
    );


$organization =
    trim(
        $accomplishment[
            "organization"
        ] ?? ""
    );


$description =
    trim(
        $accomplishment[
            "description"
        ] ?? ""
    );


$existingDocument =
    trim(
        $accomplishment[
            "document_path"
        ] ?? ""
    );


/* =========================================================
   UPDATE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
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

        $error =
            "Your form session expired.";

    } else {

        $title =
            trim(
                $_POST[
                    "achievement_title"
                ] ?? ""
            );


        $category =
            trim(
                $_POST[
                    "category"
                ] ?? ""
            );


        $dateAchieved =
            trim(
                $_POST[
                    "date_achieved"
                ] ?? ""
            );


        $organization =
            trim(
                $_POST[
                    "organization"
                ] ?? ""
            );


        $description =
            trim(
                $_POST[
                    "description"
                ] ?? ""
            );


        $removeDocument =
            isset(
                $_POST[
                    "remove_document"
                ]
            );


        if ($title === "") {

            $error =
                "Please enter an accomplishment title.";

        } elseif (
            strlen($title) > 255
        ) {

            $error =
                "The title is too long.";

        } elseif (
            !array_key_exists(
                $category,
                $categories
            )
        ) {

            $error =
                "Please select a valid category.";
        }


        $newDocument =
            $existingDocument;


        $newUploadedAbsolute =
            null;


        /* REMOVE CURRENT */

        if (
            $error === "" &&
            $removeDocument
        ) {

            $newDocument =
                null;
        }


        /* REPLACE DOCUMENT */

        if (
            $error === "" &&
            isset(
                $_FILES[
                    "supporting_document"
                ]
            ) &&
            $_FILES[
                "supporting_document"
            ]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            $file =
                $_FILES[
                    "supporting_document"
                ];


            if (
                $file["error"]
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    "Unable to upload the new document.";

            } elseif (
                $file["size"] >
                8 * 1024 * 1024
            ) {

                $error =
                    "The document must be 8 MB or smaller.";

            } else {

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );


                $mime =
                    $finfo->file(
                        $file["tmp_name"]
                    );


                $allowed = [

                    "application/pdf" =>
                        "pdf",

                    "image/jpeg" =>
                        "jpg",

                    "image/png" =>
                        "png"
                ];


                if (
                    !isset(
                        $allowed[
                            $mime
                        ]
                    )
                ) {

                    $error =
                        "Only PDF, JPG, JPEG, and PNG files are allowed.";

                } else {

                    $uploadDirectory =
                        __DIR__ .
                        "/uploads/accomplishments";


                    if (
                        !is_dir(
                            $uploadDirectory
                        )
                    ) {

                        mkdir(
                            $uploadDirectory,
                            0755,
                            true
                        );
                    }


                    $generated =
                        "achievement_" .
                        $userId .
                        "_" .
                        bin2hex(
                            random_bytes(12)
                        ) .
                        "." .
                        $allowed[$mime];


                    $newUploadedAbsolute =
                        $uploadDirectory .
                        "/" .
                        $generated;


                    if (
                        !move_uploaded_file(
                            $file["tmp_name"],
                            $newUploadedAbsolute
                        )
                    ) {

                        $error =
                            "Unable to save the new document.";

                    } else {

                        $newDocument =
                            "uploads/accomplishments/" .
                            $generated;
                    }
                }
            }
        }


        if ($error === "") {

            $dateForDatabase =
                $dateAchieved !== ""
                    ? $dateAchieved
                    : null;


            $descriptionForDatabase =
                $description !== ""
                    ? $description
                    : null;


            $organizationForDatabase =
                $organization !== ""
                    ? $organization
                    : null;


            $updateStmt =
                $conn->prepare("
                    UPDATE accomplishments

                    SET
                        title = ?,
                        category = ?,
                        description = ?,
                        date_achieved = ?,
                        organization = ?,
                        document_path = ?

                    WHERE
                        id = ?
                        AND user_id = ?

                    LIMIT 1
                ");


            if (!$updateStmt) {

                $error =
                    "Unable to update accomplishment.";

            } else {

                $updateStmt->bind_param(
                    "ssssssii",

                    $title,
                    $category,
                    $descriptionForDatabase,
                    $dateForDatabase,
                    $organizationForDatabase,
                    $newDocument,

                    $accomplishmentId,
                    $userId
                );


                if (
                    $updateStmt->execute()
                ) {

                    $updateStmt->close();


                    /*
                     * Delete old file only AFTER
                     * database update succeeds.
                     */

                    if (
                        $existingDocument !== "" &&
                        $existingDocument !==
                            $newDocument &&
                        str_starts_with(
                            $existingDocument,
                            "uploads/accomplishments/"
                        )
                    ) {

                        $oldAbsolute =
                            __DIR__ .
                            "/" .
                            $existingDocument;


                        if (
                            is_file(
                                $oldAbsolute
                            )
                        ) {

                            @unlink(
                                $oldAbsolute
                            );
                        }
                    }


                    header(
                        "Location: accomplishment_details.php?id=" .
                        $accomplishmentId .
                        "&updated=1"
                    );

                    exit;

                } else {

                    $error =
                        "Unable to update accomplishment.";


                    $updateStmt->close();
                }
            }
        }


        if (
            $error !== "" &&
            $newUploadedAbsolute !== null &&
            is_file(
                $newUploadedAbsolute
            )
        ) {

            @unlink(
                $newUploadedAbsolute
            );
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
        Edit Accomplishment | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="add_accomplishment.css"
    >

    <style>

        .form-error {

            padding: 13px 16px;

            margin-bottom: 20px;

            color: #a33f3f;

            background: #fff5f5;

            border:
                1px solid #efd0d0;

            border-radius: 9px;
        }


        .current-document {

            margin-top: 15px;

            padding: 14px;

            background: #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 9px;
        }


        .current-document strong {

            display: block;

            margin-bottom: 6px;
        }


        .remove-document {

            display: flex;

            align-items: center;

            gap: 7px;

            margin-top: 12px;

            font-size: 10px;
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
            href="student_dashboard.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>

</header>


<main class="add-page">


    <section class="page-header">


        <div>

            <span class="eyebrow">
                ACCOMPLISHMENTS
            </span>

            <h1>
                Edit Accomplishment
            </h1>

            <p>
                Update this accomplishment record.
            </p>

        </div>


        <a
            href="accomplishment_details.php?id=<?= $accomplishmentId ?>"
            class="back-button"
        >
            ← Back to Details
        </a>


    </section>


    <?php if (
        $error !== ""
    ): ?>

        <div class="form-error">
            <?= htmlspecialchars(
                $error
            ) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
        class="accomplishment-form"
    >


        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $csrfToken
            ) ?>"
        >


        <section class="form-card">


            <div class="section-heading">

                <span class="section-label">
                    ACHIEVEMENT DETAILS
                </span>

                <h2>
                    Accomplishment Information
                </h2>

            </div>


            <div class="form-grid">


                <div
                    class="
                        form-group
                        full-width
                    "
                >

                    <label>
                        Achievement Title
                    </label>

                    <input
                        type="text"
                        name="achievement_title"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $title
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Category
                    </label>

                    <select
                        name="category"
                        required
                    >

                        <?php foreach (
                            $categories
                            as $value => $label
                        ): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $value
                                ) ?>"
                                <?= $category === $value
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                <?= htmlspecialchars(
                                    $label
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Date Achieved
                    </label>

                    <input
                        type="date"
                        name="date_achieved"
                        value="<?= htmlspecialchars(
                            $dateAchieved
                        ) ?>"
                    >

                </div>


                <div
                    class="
                        form-group
                        full-width
                    "
                >

                    <label>
                        Organization
                    </label>

                    <input
                        type="text"
                        name="organization"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $organization
                        ) ?>"
                    >

                </div>


                <div
                    class="
                        form-group
                        full-width
                    "
                >

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="6"
                    ><?= htmlspecialchars(
                        $description
                    ) ?></textarea>

                </div>


            </div>


        </section>


        <section class="form-card">


            <div class="section-heading">

                <span class="section-label">
                    SUPPORTING DOCUMENT
                </span>

                <h2>
                    Document
                </h2>

            </div>


            <?php if (
                $existingDocument !== ""
            ): ?>

                <div class="current-document">

                    <strong>
                        Current document
                    </strong>

                    <a
                        href="<?= htmlspecialchars(
                            $existingDocument,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        <?= htmlspecialchars(
                            basename(
                                $existingDocument
                            )
                        ) ?>
                    </a>


                    <label class="remove-document">

                        <input
                            type="checkbox"
                            name="remove_document"
                            value="1"
                        >

                        Remove current document

                    </label>

                </div>

            <?php endif; ?>


            <div class="upload-area">


                <div class="upload-icon">
                    ↑
                </div>


                <div class="upload-content">

                    <h3>
                        Replace Document
                    </h3>

                    <p>
                        Leave empty to keep
                        the current document.
                    </p>

                    <label
                        for="supporting_document"
                        class="upload-button"
                    >
                        Choose File
                    </label>

                    <input
                        type="file"
                        id="supporting_document"
                        name="supporting_document"
                        accept=".pdf,.jpg,.jpeg,.png"
                    >

                    <span
                        class="file-name"
                        id="fileName"
                    >
                        No new file selected
                    </span>

                </div>


            </div>


        </section>


        <div class="form-actions">


            <a
                href="accomplishment_details.php?id=<?= $accomplishmentId ?>"
                class="cancel-button"
            >
                Cancel
            </a>


            <button
                type="submit"
                class="save-button"
            >
                Save Changes
            </button>


        </div>


    </form>


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

    const fileInput =
        document.getElementById(
            "supporting_document"
        );


    const fileName =
        document.getElementById(
            "fileName"
        );


    fileInput.addEventListener(
        "change",
        function () {

            if (
                this.files.length > 0
            ) {

                fileName.textContent =
                    this.files[0].name;

            } else {

                fileName.textContent =
                    "No new file selected";
            }
        }
    );

</script>

</body>

</html>