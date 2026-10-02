<?php

session_start();

require_once "db.php";


if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


$userId =
    (int) $_SESSION["user_id"];


$error = "";


/* CSRF TOKEN */

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


/* CATEGORIES */

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


/* FORM VALUES */

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


/* SAVE ACCOMPLISHMENT */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {


    /* CSRF */

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
            "Your form session expired. Please refresh the page and try again.";


    /* TITLE */

    } elseif (
        $title === ""
    ) {

        $error =
            "Please enter an accomplishment title.";


    } elseif (
        strlen($title) > 255
    ) {

        $error =
            "The accomplishment title is too long.";


    /* CATEGORY */

    } elseif (
        !array_key_exists(
            $category,
            $categories
        )
    ) {

        $error =
            "Please select a valid category.";


    /* DATE */

    } elseif (
        $dateAchieved !== "" &&
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $dateAchieved
        )
    ) {

        $error =
            "Please enter a valid date.";


    } elseif (
        strlen($organization) > 255
    ) {

        $error =
            "The organization name is too long.";
    }


    /* DOCUMENT UPLOAD */

    $documentPath = null;

    $uploadedAbsolutePath = null;


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
                "The supporting document could not be uploaded.";

        } elseif (
            $file["size"] >
            8 * 1024 * 1024
        ) {

            $error =
                "The supporting document must be 8 MB or smaller.";

        } else {

            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );


            $mime =
                $finfo->file(
                    $file["tmp_name"]
                );


            $allowedFiles = [

                "application/pdf" =>
                    "pdf",

                "image/jpeg" =>
                    "jpg",

                "image/png" =>
                    "png"
            ];


            if (
                !isset(
                    $allowedFiles[
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

                    if (
                        !mkdir(
                            $uploadDirectory,
                            0755,
                            true
                        )
                    ) {

                        $error =
                            "Unable to create the document upload folder.";
                    }
                }


                if ($error === "") {

                    $extension =
                        $allowedFiles[
                            $mime
                        ];


                    $generatedName =
                        "achievement_" .
                        $userId .
                        "_" .
                        bin2hex(
                            random_bytes(12)
                        ) .
                        "." .
                        $extension;


                    $uploadedAbsolutePath =
                        $uploadDirectory .
                        "/" .
                        $generatedName;


                    if (
                        !move_uploaded_file(
                            $file["tmp_name"],
                            $uploadedAbsolutePath
                        )
                    ) {

                        $error =
                            "Unable to save the supporting document.";

                    } else {

                        $documentPath =
                            "uploads/accomplishments/" .
                            $generatedName;
                    }
                }
            }
        }
    }


    /* INSERT */

    if ($error === "") {

        $dateForDatabase =
            $dateAchieved !== ""
                ? $dateAchieved
                : null;


        $organizationForDatabase =
            $organization !== ""
                ? $organization
                : null;


        $descriptionForDatabase =
            $description !== ""
                ? $description
                : null;


        $stmt =
            $conn->prepare("
                INSERT INTO accomplishments
                (
                    user_id,
                    title,
                    category,
                    description,
                    date_achieved,
                    organization,
                    document_path
                )

                VALUES
                (
                    ?,
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
                "Database error: " .
                $conn->error;

        } else {

            $stmt->bind_param(
                "issssss",

                $userId,
                $title,
                $category,
                $descriptionForDatabase,
                $dateForDatabase,
                $organizationForDatabase,
                $documentPath
            );


            if (
                $stmt->execute()
            ) {

                $stmt->close();


                header(
                    "Location: accomplishments.php?added=1"
                );

                exit;

            } else {

                $error =
                    "Unable to save accomplishment: " .
                    $stmt->error;


                $stmt->close();
            }
        }
    }


    /*
     * If the upload succeeded but database save failed,
     * remove the orphaned uploaded file.
     */

    if (
        $error !== "" &&
        $uploadedAbsolutePath !== null &&
        is_file(
            $uploadedAbsolutePath
        )
    ) {

        @unlink(
            $uploadedAbsolutePath
        );
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
        Add Accomplishment | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="add_accomplishment.css"
    >

    <style>

        .form-error {

            padding:
                13px 16px;

            margin-bottom: 20px;

            color:
                #a33f3f;

            background:
                #fff5f5;

            border:
                1px solid #efd0d0;

            border-radius: 9px;

            font-size: 11px;

            font-weight: 600;
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


<main class="add-page">


    <section class="page-header">


        <div>

            <span class="eyebrow">
                ACCOMPLISHMENTS
            </span>


            <h1>
                Add Accomplishment
            </h1>


            <p>
                Add a real accomplishment to
                your CVSWHO student profile.
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
        action=""
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

                <p>
                    Enter the information for
                    the accomplishment you want
                    to add.
                </p>

            </div>


            <div class="form-grid">


                <div
                    class="
                        form-group
                        full-width
                    "
                >

                    <label
                        for="achievement_title"
                    >
                        Achievement Title
                    </label>

                    <input
                        type="text"
                        id="achievement_title"
                        name="achievement_title"
                        maxlength="255"
                        placeholder="Enter accomplishment title"
                        value="<?= htmlspecialchars(
                            $title
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                    >

                        <option value="">
                            Select category
                        </option>


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

                    <label
                        for="date_achieved"
                    >
                        Date Achieved
                    </label>

                    <input
                        type="date"
                        id="date_achieved"
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

                    <label for="organization">
                        Organization
                    </label>

                    <input
                        type="text"
                        id="organization"
                        name="organization"
                        maxlength="255"
                        placeholder="School, company, organization, or institution"
                        value="<?= htmlspecialchars(
                            $organization
                        ) ?>"
                    >

                    <small>
                        Optional.
                    </small>

                </div>


                <div
                    class="
                        form-group
                        full-width
                    "
                >

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        placeholder="Describe what you achieved"
                    ><?= htmlspecialchars(
                        $description
                    ) ?></textarea>

                    <small>
                        Optional, but recommended.
                    </small>

                </div>


            </div>


        </section>


        <section class="form-card">


            <div class="section-heading">

                <span class="section-label">
                    SUPPORTING DOCUMENT
                </span>

                <h2>
                    Certificate or Document
                </h2>

                <p>
                    You can optionally upload
                    proof of your accomplishment.
                </p>

            </div>


            <div class="upload-area">


                <div class="upload-icon">
                    ↑
                </div>


                <div class="upload-content">


                    <h3>
                        Upload Supporting Document
                    </h3>


                    <p>
                        Maximum file size: 8 MB.
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
                        No file selected
                    </span>


                    <small>
                        PDF, JPG, JPEG, or PNG.
                    </small>


                </div>


            </div>


        </section>


        <section class="privacy-notice">

            <div class="privacy-icon">
                ✓
            </div>

            <div>

                <h3>
                    Privacy Reminder
                </h3>

                <p>
                    Whether accomplishments appear
                    to other users is controlled
                    by your Accomplishments
                    visibility setting.
                </p>

            </div>

        </section>


        <div class="form-actions">


            <a
                href="accomplishments.php"
                class="cancel-button"
            >
                Cancel
            </a>
    <a
        href="organizations.php"
        class="nav-link"
    >
        Organizations
    </a>


            <button
                type="submit"
                class="save-button"
            >
                Add Accomplishment
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
                    "No file selected";
            }
        }
    );

</script>


</body>

</html>