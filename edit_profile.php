<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$error = "";


/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET["logout"])) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

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

    header("Location: login.php");
    exit;
}


/* =========================================================
   EDUCATION SAVE HELPER
========================================================= */

function saveEducation(
    mysqli $conn,
    int $userId,
    string $level,
    string $schoolName
): void {

    $schoolName = trim($schoolName);

    $findStmt = $conn->prepare("
        SELECT id
        FROM education
        WHERE user_id = ?
        AND education_level = ?
        ORDER BY id ASC
        LIMIT 1
    ");

    if (!$findStmt) {
        throw new Exception($conn->error);
    }

    $findStmt->bind_param(
        "is",
        $userId,
        $level
    );

    $findStmt->execute();

    $existing = $findStmt
        ->get_result()
        ->fetch_assoc();

    $findStmt->close();


    if ($existing) {

        $educationId = (int) $existing["id"];

        if ($schoolName === "") {

            $deleteStmt = $conn->prepare("
                DELETE FROM education
                WHERE id = ?
                AND user_id = ?
            ");

            if (!$deleteStmt) {
                throw new Exception($conn->error);
            }

            $deleteStmt->bind_param(
                "ii",
                $educationId,
                $userId
            );

            $deleteStmt->execute();
            $deleteStmt->close();

            return;
        }


        $updateStmt = $conn->prepare("
            UPDATE education
            SET school_name = ?
            WHERE id = ?
            AND user_id = ?
        ");

        if (!$updateStmt) {
            throw new Exception($conn->error);
        }

        $updateStmt->bind_param(
            "sii",
            $schoolName,
            $educationId,
            $userId
        );

        $updateStmt->execute();
        $updateStmt->close();

        return;
    }


    if ($schoolName !== "") {

        $insertStmt = $conn->prepare("
            INSERT INTO education
            (
                user_id,
                education_level,
                school_name
            )
            VALUES (?, ?, ?)
        ");

        if (!$insertStmt) {
            throw new Exception($conn->error);
        }

        $insertStmt->bind_param(
            "iss",
            $userId,
            $level,
            $schoolName
        );

        $insertStmt->execute();
        $insertStmt->close();
    }
}


/* =========================================================
   GET OLD PHOTO BEFORE UPDATE
========================================================= */

$currentPhoto = "";

$photoCheck = $conn->prepare("
    SELECT profile_photo
    FROM student_profiles
    WHERE user_id = ?
    LIMIT 1
");

if ($photoCheck) {

    $photoCheck->bind_param(
        "i",
        $userId
    );

    $photoCheck->execute();

    $photoRow = $photoCheck
        ->get_result()
        ->fetch_assoc();

    $currentPhoto = trim(
        $photoRow["profile_photo"] ?? ""
    );

    $photoCheck->close();
}


/* =========================================================
   SAVE PROFILE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["first_name"] ?? "");
    $middleName = trim($_POST["middle_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");

    $studentId = trim($_POST["student_id"] ?? "");

    $phone = trim($_POST["phone"] ?? "");
    $birthdate = trim($_POST["birthdate"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $address = trim($_POST["address"] ?? "");

    $aboutMe = trim($_POST["about_me"] ?? "");

    $program = trim($_POST["program"] ?? "");
    $yearLevel = trim($_POST["year_level"] ?? "");
    $section = trim($_POST["section"] ?? "");
    $college = trim($_POST["college"] ?? "");
    $campus = trim($_POST["campus"] ?? "");

    $fatherName = trim($_POST["father_name"] ?? "");
    $motherName = trim($_POST["mother_name"] ?? "");
    $guardianName = trim($_POST["guardian_name"] ?? "");
    $guardianContact = trim($_POST["guardian_contact"] ?? "");

    $elementarySchool = trim(
        $_POST["elementary_school"] ?? ""
    );

    $juniorHighSchool = trim(
        $_POST["junior_high_school"] ?? ""
    );

    $seniorHighSchool = trim(
        $_POST["senior_high_school"] ?? ""
    );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($firstName === "" || $lastName === "") {

        $error =
            "First name and last name are required.";

    } elseif (
        $studentId !== "" &&
        !preg_match('/^[A-Za-z0-9\-]+$/', $studentId)
    ) {

        $error =
            "Student ID can only contain letters, numbers, and hyphens.";

    } elseif (
        $birthdate !== "" &&
        strtotime($birthdate) === false
    ) {

        $error =
            "Please enter a valid birthdate.";
    }


    /* =====================================================
       PROFILE PHOTO
    ===================================================== */

    $newProfilePhoto = $currentPhoto;
    $newUploadedFile = null;

    if (
        $error === "" &&
        isset($_FILES["profile_photo"]) &&
        $_FILES["profile_photo"]["error"]
            !== UPLOAD_ERR_NO_FILE
    ) {

        $photo = $_FILES["profile_photo"];


        if ($photo["error"] !== UPLOAD_ERR_OK) {

            $error =
                "There was a problem uploading your profile photo.";

        } elseif ($photo["size"] > 5 * 1024 * 1024) {

            $error =
                "Profile photo must be 5 MB or smaller.";

        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mimeType = $finfo->file(
                $photo["tmp_name"]
            );

            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];


            if (!isset($allowedTypes[$mimeType])) {

                $error =
                    "Profile photo must be JPG, PNG, or WEBP.";

            } else {

                $uploadDirectory =
                    __DIR__ .
                    "/uploads/profile_photos";


                if (
                    !is_dir($uploadDirectory) &&
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                ) {

                    $error =
                        "Unable to create the profile photo folder.";

                } else {

                    try {

                        $randomName =
                            bin2hex(
                                random_bytes(16)
                            );

                    } catch (Throwable $exception) {

                        $randomName =
                            uniqid(
                                "profile_",
                                true
                            );
                    }


                    $extension =
                        $allowedTypes[$mimeType];


                    $fileName =
                        "user_" .
                        $userId .
                        "_" .
                        $randomName .
                        "." .
                        $extension;


                    $absolutePath =
                        $uploadDirectory .
                        "/" .
                        $fileName;


                    $databasePath =
                        "uploads/profile_photos/" .
                        $fileName;


                    if (
                        move_uploaded_file(
                            $photo["tmp_name"],
                            $absolutePath
                        )
                    ) {

                        $newProfilePhoto =
                            $databasePath;

                        $newUploadedFile =
                            $absolutePath;

                    } else {

                        $error =
                            "Unable to save the uploaded profile photo.";
                    }
                }
            }
        }
    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === "") {

        try {

            $conn->begin_transaction();


            /* =============================================
               STUDENT PROFILE
            ============================================= */

            $profileStmt = $conn->prepare("
                INSERT INTO student_profiles
                (
                    user_id,
                    student_id,

                    first_name,
                    middle_name,
                    last_name,

                    phone,
                    birthdate,
                    gender,
                    address,

                    program,
                    year_level,
                    section,
                    college,
                    campus,

                    profile_photo,
                    about_me
                )

                VALUES
                (
                    ?,
                    NULLIF(?, ''),

                    ?,
                    NULLIF(?, ''),
                    ?,

                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    NULLIF(?, ''),

                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    NULLIF(?, ''),

                    NULLIF(?, ''),
                    NULLIF(?, '')
                )

                ON DUPLICATE KEY UPDATE

                    student_id =
                        VALUES(student_id),

                    first_name =
                        VALUES(first_name),

                    middle_name =
                        VALUES(middle_name),

                    last_name =
                        VALUES(last_name),

                    phone =
                        VALUES(phone),

                    birthdate =
                        VALUES(birthdate),

                    gender =
                        VALUES(gender),

                    address =
                        VALUES(address),

                    program =
                        VALUES(program),

                    year_level =
                        VALUES(year_level),

                    section =
                        VALUES(section),

                    college =
                        VALUES(college),

                    campus =
                        VALUES(campus),

                    profile_photo =
                        VALUES(profile_photo),

                    about_me =
                        VALUES(about_me)
            ");


            if (!$profileStmt) {
                throw new Exception($conn->error);
            }


            $profileStmt->bind_param(
                "isssssssssssssss",
                $userId,
                $studentId,

                $firstName,
                $middleName,
                $lastName,

                $phone,
                $birthdate,
                $gender,
                $address,

                $program,
                $yearLevel,
                $section,
                $college,
                $campus,

                $newProfilePhoto,
                $aboutMe
            );


            if (!$profileStmt->execute()) {
                throw new Exception(
                    $profileStmt->error
                );
            }

            $profileStmt->close();


            /* =============================================
               FAMILY
            ============================================= */

            $familyStmt = $conn->prepare("
                INSERT INTO family_information
                (
                    user_id,
                    father_name,
                    mother_name,
                    guardian_name,
                    guardian_contact
                )

                VALUES
                (
                    ?,
                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    NULLIF(?, '')
                )

                ON DUPLICATE KEY UPDATE

                    father_name =
                        VALUES(father_name),

                    mother_name =
                        VALUES(mother_name),

                    guardian_name =
                        VALUES(guardian_name),

                    guardian_contact =
                        VALUES(guardian_contact)
            ");


            if (!$familyStmt) {
                throw new Exception($conn->error);
            }


            $familyStmt->bind_param(
                "issss",
                $userId,
                $fatherName,
                $motherName,
                $guardianName,
                $guardianContact
            );


            if (!$familyStmt->execute()) {
                throw new Exception(
                    $familyStmt->error
                );
            }

            $familyStmt->close();


            /* =============================================
               EDUCATION
            ============================================= */

            saveEducation(
                $conn,
                $userId,
                "elementary",
                $elementarySchool
            );

            saveEducation(
                $conn,
                $userId,
                "junior_high",
                $juniorHighSchool
            );

            saveEducation(
                $conn,
                $userId,
                "senior_high",
                $seniorHighSchool
            );


            $conn->commit();


            /* =============================================
               DELETE OLD PROFILE PHOTO
            ============================================= */

            if (
                $newUploadedFile !== null &&
                $currentPhoto !== "" &&
                str_starts_with(
                    $currentPhoto,
                    "uploads/profile_photos/"
                ) &&
                $currentPhoto !== $newProfilePhoto
            ) {

                $oldAbsolutePath =
                    __DIR__ .
                    "/" .
                    $currentPhoto;


                if (is_file($oldAbsolutePath)) {
                    @unlink($oldAbsolutePath);
                }
            }


            header(
                "Location: student_profile.php?updated=1"
            );

            exit;


        } catch (Throwable $exception) {

            $conn->rollback();


            if (
                $newUploadedFile !== null &&
                is_file($newUploadedFile)
            ) {
                @unlink($newUploadedFile);
            }


            if (
                str_contains(
                    strtolower(
                        $exception->getMessage()
                    ),
                    "duplicate"
                )
            ) {

                $error =
                    "That Student ID is already being used by another account.";

            } else {

                $error =
                    "Unable to save your profile. " .
                    $exception->getMessage();
            }
        }
    }
}


/* =========================================================
   LOAD PROFILE
========================================================= */

$stmt = $conn->prepare("
    SELECT

        u.email,

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

        fi.father_name,
        fi.mother_name,
        fi.guardian_name,
        fi.guardian_contact

    FROM users u

    LEFT JOIN student_profiles sp
        ON sp.user_id = u.id

    LEFT JOIN family_information fi
        ON fi.user_id = u.id

    WHERE u.id = ?

    LIMIT 1
");


if (!$stmt) {

    die(
        "Profile database error: " .
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

$student = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


if (!$student) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================================================
   LOAD EDUCATION
========================================================= */

$education = [
    "elementary" => "",
    "junior_high" => "",
    "senior_high" => ""
];


$educationStmt = $conn->prepare("
    SELECT
        education_level,
        school_name

    FROM education

    WHERE user_id = ?

    AND education_level IN
    (
        'elementary',
        'junior_high',
        'senior_high'
    )

    ORDER BY id ASC
");


if ($educationStmt) {

    $educationStmt->bind_param(
        "i",
        $userId
    );

    $educationStmt->execute();

    $educationResult =
        $educationStmt->get_result();


    while (
        $educationRow =
        $educationResult->fetch_assoc()
    ) {

        $level =
            $educationRow[
                "education_level"
            ];

        if (
            array_key_exists(
                $level,
                $education
            )
        ) {

            $education[$level] =
                trim(
                    $educationRow[
                        "school_name"
                    ] ?? ""
                );
        }
    }

    $educationStmt->close();
}


/* =========================================================
   NORMALIZE
========================================================= */

$fields = [
    "first_name",
    "middle_name",
    "last_name",
    "student_id",
    "email",
    "phone",
    "birthdate",
    "gender",
    "address",
    "program",
    "year_level",
    "section",
    "college",
    "campus",
    "profile_photo",
    "about_me",
    "father_name",
    "mother_name",
    "guardian_name",
    "guardian_contact"
];


foreach ($fields as $field) {

    $student[$field] =
        trim(
            $student[$field] ?? ""
        );
}


$student["elementary_school"] =
    $education["elementary"];

$student["junior_high_school"] =
    $education["junior_high"];

$student["senior_high_school"] =
    $education["senior_high"];


$avatarSource =
    $student["first_name"] !== ""
        ? $student["first_name"]
        : "Student";


$avatarInitial =
    strtoupper(
        substr(
            $avatarSource,
            0,
            1
        )
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
        Edit Profile | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="edit_profile.css"
    >


    <style>

        .photo-upload-area {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .photo-upload-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            color: var(--primary-green);
            background: var(--white);
            border: 1px solid var(--primary-green);
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
        }

        .photo-upload-label:hover {
            color: var(--white);
            background: var(--primary-green);
        }

        .photo-upload-input {
            display: none;
        }

        .photo-file-name {
            color: var(--text-medium);
            font-size: 10px;
        }

        .profile-photo {
            overflow: hidden;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .form-message-error {
            padding: 15px 18px;
            margin-bottom: 20px;
            color: #a33f3f;
            background: #fff5f5;
            border: 1px solid #efd0d0;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

    </style>

</head>


<body>


<header class="navbar">

    <div class="nav-container">

        <a
            href="student_dashboard.php"
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
                href="settings.php"
                class="nav-link"
            >
                Settings
            </a>

        </nav>


        <a
            href="edit_profile.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>

    </div>

</header>


<main class="edit-page">


    <section class="page-header">

        <div>

            <span class="eyebrow">
                MY PROFILE
            </span>

            <h1>
                Edit Profile
            </h1>

            <p>
                Update your personal, academic,
                family, educational information,
                and profile photo.
            </p>

        </div>


        <a
            href="student_profile.php"
            class="back-button"
        >
            ← Back to Profile
        </a>

    </section>


    <?php if ($error !== ""): ?>

        <div class="form-message-error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action=""
        enctype="multipart/form-data"
        class="profile-form"
    >


        <!-- PROFILE PHOTO -->

        <section class="photo-card">

            <div class="photo-preview">


                <div
                    class="profile-photo"
                    id="profilePhotoPreview"
                    <?php if (
                        $student["profile_photo"] !== ""
                    ): ?>
                        style="
                            background-image:
                            url('<?= htmlspecialchars(
                                $student["profile_photo"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>');
                        "
                    <?php endif; ?>
                >

                    <?php if (
                        $student["profile_photo"] === ""
                    ): ?>

                        <span id="profileInitial">
                            <?= htmlspecialchars(
                                $avatarInitial
                            ) ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <div class="photo-information">

                <span class="section-label">
                    PROFILE PHOTO
                </span>

                <h2>
                    Your Profile Photo
                </h2>

                <p>
                    Upload a clear JPG, PNG, or WEBP
                    image. Maximum file size is 5 MB.
                </p>


                <div class="photo-upload-area">

                    <label
                        for="profile_photo"
                        class="photo-upload-label"
                    >
                        Choose Photo
                    </label>


                    <input
                        type="file"
                        id="profile_photo"
                        name="profile_photo"
                        class="photo-upload-input"
                        accept="image/jpeg,image/png,image/webp"
                    >


                    <span
                        class="photo-file-name"
                        id="photoFileName"
                    >
                        No new photo selected
                    </span>

                </div>

            </div>

        </section>


        <!-- ABOUT ME -->

        <section class="form-card">

            <div class="section-heading">

                <span class="section-label">
                    ABOUT ME
                </span>

                <h2>
                    Profile Introduction
                </h2>

                <p>
                    Write a short introduction
                    about yourself.
                </p>

            </div>


            <div class="form-grid">

                <div class="form-group full-width">

                    <label for="about_me">
                        About Me
                    </label>

                    <textarea
                        id="about_me"
                        name="about_me"
                        rows="5"
                        maxlength="2000"
                        placeholder="Tell others about yourself, your interests, goals, or studies."
                    ><?= htmlspecialchars(
                        $student["about_me"]
                    ) ?></textarea>

                </div>

            </div>

        </section>


        <!-- PERSONAL INFORMATION -->

        <section class="form-card">

            <div class="section-heading">

                <span class="section-label">
                    PERSONAL INFORMATION
                </span>

                <h2>
                    Personal Details
                </h2>

                <p>
                    Basic information about you.
                </p>

            </div>


            <div class="form-grid">


                <div class="form-group">

                    <label for="first_name">
                        First Name
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= htmlspecialchars(
                            $student["first_name"]
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="middle_name">
                        Middle Name
                    </label>

                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        value="<?= htmlspecialchars(
                            $student["middle_name"]
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="last_name">
                        Last Name
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= htmlspecialchars(
                            $student["last_name"]
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="student_id">
                        Student ID
                    </label>

                    <input
                        type="text"
                        id="student_id"
                        name="student_id"
                        value="<?= htmlspecialchars(
                            $student["student_id"]
                        ) ?>"
                        placeholder="Example: 2023-12345"
                    >

                    <small>
                        Student ID must be unique.
                    </small>

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        value="<?= htmlspecialchars(
                            $student["email"]
                        ) ?>"
                        readonly
                    >

                    <small>
                        Your CvSU email is managed
                        through your account.
                    </small>

                </div>


                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars(
                            $student["phone"]
                        ) ?>"
                        placeholder="+63 XXX XXX XXXX"
                    >

                </div>


                <div class="form-group">

                    <label for="birthdate">
                        Birthdate
                    </label>

                    <input
                        type="date"
                        id="birthdate"
                        name="birthdate"
                        value="<?= htmlspecialchars(
                            $student["birthdate"]
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="gender">
                        Gender
                    </label>

                    <select
                        id="gender"
                        name="gender"
                    >

                        <option value="">
                            Select gender
                        </option>

                        <option
                            value="Male"
                            <?= $student["gender"]
                                === "Male"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Male
                        </option>

                        <option
                            value="Female"
                            <?= $student["gender"]
                                === "Female"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Female
                        </option>

                        <option
                            value="Prefer not to say"
                            <?= $student["gender"]
                                === "Prefer not to say"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Prefer not to say
                        </option>

                    </select>

                </div>


                <div class="form-group full-width">

                    <label for="address">
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        placeholder="Enter your complete address"
                    ><?= htmlspecialchars(
                        $student["address"]
                    ) ?></textarea>

                </div>

            </div>

        </section>


        <!-- ACADEMIC -->

        <section class="form-card">

            <div class="section-heading">

                <span class="section-label">
                    ACADEMIC INFORMATION
                </span>

                <h2>
                    Academic Details
                </h2>

                <p>
                    Information about your
                    current studies.
                </p>

            </div>


            <div class="form-grid">


                <div class="form-group full-width">

                    <label for="program">
                        Program
                    </label>

                    <input
                        type="text"
                        id="program"
                        name="program"
                        value="<?= htmlspecialchars(
                            $student["program"]
                        ) ?>"
                        placeholder="Enter your degree program"
                    >

                </div>


                <div class="form-group">

                    <label for="year_level">
                        Year Level
                    </label>

                    <select
                        id="year_level"
                        name="year_level"
                    >

                        <option value="">
                            Select year level
                        </option>

                        <?php

                        $yearOptions = [
                            "1st Year",
                            "2nd Year",
                            "3rd Year",
                            "4th Year",
                            "5th Year"
                        ];

                        foreach (
                            $yearOptions as $year
                        ):

                        ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $year
                                ) ?>"
                                <?= $student["year_level"]
                                    === $year
                                    ? "selected"
                                    : ""
                                ?>
                            >
                                <?= htmlspecialchars(
                                    $year
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="section">
                        Section
                    </label>

                    <input
                        type="text"
                        id="section"
                        name="section"
                        value="<?= htmlspecialchars(
                            $student["section"]
                        ) ?>"
                        placeholder="Enter your section"
                    >

                </div>


                <div class="form-group">

                    <label for="college">
                        College
                    </label>

                    <input
                        type="text"
                        id="college"
                        name="college"
                        value="<?= htmlspecialchars(
                            $student["college"]
                        ) ?>"
                        placeholder="Enter your college"
                    >

                </div>


                <div class="form-group">

                    <label for="campus">
                        Campus
                    </label>

                    <input
                        type="text"
                        id="campus"
                        name="campus"
                        value="<?= htmlspecialchars(
                            $student["campus"]
                        ) ?>"
                        placeholder="Enter your campus"
                    >

                </div>

            </div>

        </section>


        <!-- FAMILY -->

        <section class="form-card">

            <div class="section-heading">

                <span class="section-label">
                    FAMILY INFORMATION
                </span>

                <h2>
                    Family Details
                </h2>

                <p>
                    Family information is private
                    by default.
                </p>

            </div>


            <div class="form-grid">


                <div class="form-group">

                    <label for="father_name">
                        Father's Name
                    </label>

                    <input
                        type="text"
                        id="father_name"
                        name="father_name"
                        value="<?= htmlspecialchars(
                            $student["father_name"]
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="mother_name">
                        Mother's Name
                    </label>

                    <input
                        type="text"
                        id="mother_name"
                        name="mother_name"
                        value="<?= htmlspecialchars(
                            $student["mother_name"]
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="guardian_name">
                        Guardian's Name
                    </label>

                    <input
                        type="text"
                        id="guardian_name"
                        name="guardian_name"
                        value="<?= htmlspecialchars(
                            $student["guardian_name"]
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="guardian_contact">
                        Guardian Contact
                    </label>

                    <input
                        type="tel"
                        id="guardian_contact"
                        name="guardian_contact"
                        value="<?= htmlspecialchars(
                            $student["guardian_contact"]
                        ) ?>"
                    >

                </div>

            </div>

        </section>


        <!-- EDUCATION -->

        <section class="form-card">

            <div class="section-heading">

                <span class="section-label">
                    EDUCATIONAL BACKGROUND
                </span>

                <h2>
                    Previous Education
                </h2>

                <p>
                    Add the schools you attended
                    before college.
                </p>

            </div>


            <div class="education-form-list">


                <div class="education-form-item">

                    <div class="education-number">
                        01
                    </div>

                    <div class="form-group">

                        <label>
                            Elementary School
                        </label>

                        <input
                            type="text"
                            name="elementary_school"
                            value="<?= htmlspecialchars(
                                $student[
                                    "elementary_school"
                                ]
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="education-form-item">

                    <div class="education-number">
                        02
                    </div>

                    <div class="form-group">

                        <label>
                            Junior High School
                        </label>

                        <input
                            type="text"
                            name="junior_high_school"
                            value="<?= htmlspecialchars(
                                $student[
                                    "junior_high_school"
                                ]
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="education-form-item">

                    <div class="education-number">
                        03
                    </div>

                    <div class="form-group">

                        <label>
                            Senior High School
                        </label>

                        <input
                            type="text"
                            name="senior_high_school"
                            value="<?= htmlspecialchars(
                                $student[
                                    "senior_high_school"
                                ]
                            ) ?>"
                        >

                    </div>

                </div>

            </div>

        </section>


        <div class="form-actions">

            <a
                href="student_profile.php"
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

const photoInput =
    document.getElementById(
        "profile_photo"
    );

const photoPreview =
    document.getElementById(
        "profilePhotoPreview"
    );

const fileName =
    document.getElementById(
        "photoFileName"
    );


photoInput.addEventListener(
    "change",
    function () {

        const file =
            this.files[0];

        if (!file) {
            return;
        }

        fileName.textContent =
            file.name;


        const reader =
            new FileReader();


        reader.onload =
            function (event) {

                photoPreview.style.backgroundImage =
                    `url("${event.target.result}")`;

                photoPreview.style.backgroundSize =
                    "cover";

                photoPreview.style.backgroundPosition =
                    "center";

                photoPreview.innerHTML =
                    "";
            };


        reader.readAsDataURL(file);
    }
);

</script>


</body>

</html>