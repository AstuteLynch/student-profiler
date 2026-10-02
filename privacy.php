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


/* ACCOUNT */

$stmt =
    $conn->prepare("
        SELECT
            role,
            account_status,
            email_verified

        FROM users

        WHERE id = ?

        LIMIT 1
    ");


if (!$stmt) {

    die(
        "Unable to verify your account."
    );
}


$stmt->bind_param(
    "i",
    $userId
);


$stmt->execute();


$account =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if (
    !$account ||
    $account["role"] !== "student" ||
    $account["account_status"] !== "active" ||
    (int) $account["email_verified"] !== 1
) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* LOGOUT */

if (isset($_GET["logout"])) {

    $_SESSION = [];


    if (
        ini_get(
            "session.use_cookies"
        )
    ) {

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


    header("Location: login.php");
    exit;
}


/* CSRF */

if (
    empty(
        $_SESSION[
            "privacy_csrf"
        ]
    )
) {

    $_SESSION[
        "privacy_csrf"
    ] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION[
        "privacy_csrf"
    ];


/* DEFAULTS */

$privacy = [

    "profile_visibility" =>
        "school_only",

    "contact_visibility" =>
        "private",

    "family_visibility" =>
        "private",

    "address_visibility" =>
        "private",

    "academic_visibility" =>
        "school_only",

    "achievement_visibility" =>
        "school_only",

    "hobbies_visibility" =>
        "school_only",

    "organizations_visibility" =>
        "school_only"
];


$error = "";

$success = "";


/* LOAD */

$stmt =
    $conn->prepare("
        SELECT

            profile_visibility,
            contact_visibility,
            family_visibility,
            address_visibility,
            academic_visibility,
            achievement_visibility,
            hobbies_visibility,
            organizations_visibility

        FROM privacy_settings

        WHERE user_id = ?

        LIMIT 1
    ");


if ($stmt) {

    $stmt->bind_param(
        "i",
        $userId
    );


    $stmt->execute();


    $savedPrivacy =
        $stmt
            ->get_result()
            ->fetch_assoc();


    $stmt->close();


    if ($savedPrivacy) {

        foreach (
            $privacy
            as $key => $value
        ) {

            if (
                isset(
                    $savedPrivacy[$key]
                )
            ) {

                $privacy[$key] =
                    $savedPrivacy[$key];
            }
        }
    }
}


/* SAVE */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
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
            "Your form session expired. Please refresh the page.";

    } else {

        $allowedValues = [

            "public",
            "school_only",
            "private"
        ];


        $profileVisibility =
            $_POST[
                "profile_visibility"
            ] ?? "school_only";


        $contactVisibility =
            $_POST[
                "contact_visibility"
            ] ?? "private";


        $familyVisibility =
            $_POST[
                "family_visibility"
            ] ?? "private";


        $addressVisibility =
            $_POST[
                "address_visibility"
            ] ?? "private";


        $academicVisibility =
            $_POST[
                "academic_visibility"
            ] ?? "school_only";


        $achievementVisibility =
            $_POST[
                "achievement_visibility"
            ] ?? "school_only";


        $hobbiesVisibility =
            $_POST[
                "hobbies_visibility"
            ] ?? "school_only";


        $organizationsVisibility =
            $_POST[
                "organizations_visibility"
            ] ?? "school_only";


        $values = [

            $profileVisibility,
            $contactVisibility,
            $familyVisibility,
            $addressVisibility,
            $academicVisibility,
            $achievementVisibility,
            $hobbiesVisibility,
            $organizationsVisibility
        ];


        $valid =
            true;


        foreach (
            $values
            as $value
        ) {

            if (
                !in_array(
                    $value,
                    $allowedValues,
                    true
                )
            ) {

                $valid =
                    false;

                break;
            }
        }


        if (!$valid) {

            $error =
                "Invalid privacy setting selected.";

        } else {

            $stmt =
                $conn->prepare("
                    INSERT INTO privacy_settings
                    (
                        user_id,
                        profile_visibility,
                        contact_visibility,
                        family_visibility,
                        address_visibility,
                        academic_visibility,
                        achievement_visibility,
                        hobbies_visibility,
                        organizations_visibility
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )

                    ON DUPLICATE KEY UPDATE

                        profile_visibility =
                            VALUES(
                                profile_visibility
                            ),

                        contact_visibility =
                            VALUES(
                                contact_visibility
                            ),

                        family_visibility =
                            VALUES(
                                family_visibility
                            ),

                        address_visibility =
                            VALUES(
                                address_visibility
                            ),

                        academic_visibility =
                            VALUES(
                                academic_visibility
                            ),

                        achievement_visibility =
                            VALUES(
                                achievement_visibility
                            ),

                        hobbies_visibility =
                            VALUES(
                                hobbies_visibility
                            ),

                        organizations_visibility =
                            VALUES(
                                organizations_visibility
                            )
                ");


            if (!$stmt) {

                $error =
                    "Unable to save your privacy settings.";

            } else {

                $stmt->bind_param(
                    "issssssss",

                    $userId,

                    $profileVisibility,
                    $contactVisibility,
                    $familyVisibility,
                    $addressVisibility,
                    $academicVisibility,
                    $achievementVisibility,
                    $hobbiesVisibility,
                    $organizationsVisibility
                );


                if ($stmt->execute()) {

                    $privacy[
                        "profile_visibility"
                    ] =
                        $profileVisibility;


                    $privacy[
                        "contact_visibility"
                    ] =
                        $contactVisibility;


                    $privacy[
                        "family_visibility"
                    ] =
                        $familyVisibility;


                    $privacy[
                        "address_visibility"
                    ] =
                        $addressVisibility;


                    $privacy[
                        "academic_visibility"
                    ] =
                        $academicVisibility;


                    $privacy[
                        "achievement_visibility"
                    ] =
                        $achievementVisibility;


                    $privacy[
                        "hobbies_visibility"
                    ] =
                        $hobbiesVisibility;


                    $privacy[
                        "organizations_visibility"
                    ] =
                        $organizationsVisibility;


                    $success =
                        "Privacy settings saved successfully.";

                } else {

                    $error =
                        "Unable to save your privacy settings.";
                }


                $stmt->close();
            }
        }
    }
}


/* OPTION */

function privacyChecked(
    array $privacy,
    string $field,
    string $value
): string {

    return
        (
            $privacy[$field]
            ?? ""
        ) === $value
            ? "checked"
            : "";
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
        Privacy & Visibility | CVSWHO
    </title>


    <link
        rel="stylesheet"
        href="privacy.css"
    >


    <style>

        .privacy-message {

            margin-bottom: 18px;

            padding: 12px 15px;

            border-radius: 9px;

            font-size: 9px;
        }


        .privacy-message.success {

            color: #18794e;

            background: #eaf7ef;

            border:
                1px solid #cee8d9;
        }


        .privacy-message.error {

            color: #a23939;

            background: #fff0f0;

            border:
                1px solid #efcccc;
        }


        .visibility-row {

            padding: 20px 0;

            border-bottom:
                1px solid #e1e9e4;
        }


        .visibility-row:last-child {

            border-bottom: none;
        }


        .visibility-row-header {

            margin-bottom: 13px;
        }


        .visibility-row-header h3 {

            color: #003d24;

            font-size: 11px;

            font-weight: 700;
        }


        .visibility-row-header p {

            margin-top: 4px;

            color: #68766f;

            font-size: 9px;

            line-height: 1.6;
        }


        .visibility-choice-group {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 9px;
        }


        .visibility-choice {

            position: relative;

            display: block;

            cursor: pointer;
        }


        .visibility-choice input {

            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .visibility-choice-content {

            height: 100%;

            padding: 13px;

            background: white;

            border:
                1px solid #dce7e0;

            border-radius: 9px;

            transition:
                border-color .2s ease,
                background .2s ease,
                box-shadow .2s ease;
        }


        .visibility-choice-content strong {

            display: block;

            color: #003d24;

            font-size: 9px;
        }


        .visibility-choice-content span {

            display: block;

            margin-top: 4px;

            color: #68766f;

            font-size: 8px;

            line-height: 1.5;
        }


        .visibility-choice input:checked
        + .visibility-choice-content {

            background: #eff8f3;

            border-color: #006b3f;

            box-shadow:
                0 0 0 2px
                rgba(
                    0,
                    107,
                    63,
                    .06
                );
        }


        .save-row {

            display: flex;

            align-items: center;

            justify-content:
                flex-end;

            margin-top: 22px;
        }


        .save-button {

            padding:
                11px 17px;

            color: white;

            background: #006b3f;

            border: none;

            border-radius: 8px;

            font: inherit;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }


        .save-button:hover {

            background: #004d2a;
        }


        .privacy-explanation {

            margin-top: 20px;

            padding: 16px;

            color: #68766f;

            background: #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 9px;

            font-size: 9px;

            line-height: 1.7;
        }


        .privacy-explanation strong {

            color: #003d24;
        }


        @media (
            max-width: 700px
        ) {

            .visibility-choice-group {

                grid-template-columns:
                    1fr;
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
                class="nav-link active"
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
            href="privacy.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>


</header>


<!-- PRIVACY -->

<main class="page">


    <section class="page-header">


        <span class="eyebrow">
            SETTINGS & PRIVACY
        </span>


        <h1>
            Privacy & Visibility
        </h1>


        <p>
            Control who can view your profile
            and each section of your student information.
        </p>


    </section>


    <?php if (
        $success !== ""
    ): ?>


        <div class="privacy-message success">

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>


    <?php endif; ?>


    <?php if (
        $error !== ""
    ): ?>


        <div class="privacy-message error">

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>


    <?php endif; ?>


    <form
        method="POST"
        action="privacy.php"
    >


        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $csrfToken
            ) ?>"
        >


        <!-- PROFILE -->

        <section class="privacy-card">


            <div class="section-heading">


                <span class="section-label">
                    PROFILE VISIBILITY
                </span>


                <h2>
                    Who can view your profile?
                </h2>


                <p>
                    This controls whether another
                    person may open your profile at all.
                </p>


            </div>


            <div class="visibility-choice-group">


                <label class="visibility-choice">


                    <input
                        type="radio"
                        name="profile_visibility"
                        value="public"
                        <?= privacyChecked(
                            $privacy,
                            "profile_visibility",
                            "public"
                        ) ?>
                    >


                    <div class="visibility-choice-content">

                        <strong>
                            Public
                        </strong>

                        <span>
                            Anyone can open your profile.
                        </span>

                    </div>


                </label>


                <label class="visibility-choice">


                    <input
                        type="radio"
                        name="profile_visibility"
                        value="school_only"
                        <?= privacyChecked(
                            $privacy,
                            "profile_visibility",
                            "school_only"
                        ) ?>
                    >


                    <div class="visibility-choice-content">

                        <strong>
                            School Only
                        </strong>

                        <span>
                            Only authenticated CVSWHO
                            school users can open your profile.
                        </span>

                    </div>


                </label>


                <label class="visibility-choice">


                    <input
                        type="radio"
                        name="profile_visibility"
                        value="private"
                        <?= privacyChecked(
                            $privacy,
                            "profile_visibility",
                            "private"
                        ) ?>
                    >


                    <div class="visibility-choice-content">

                        <strong>
                            Private
                        </strong>

                        <span>
                            Other users cannot open your profile.
                        </span>

                    </div>


                </label>


            </div>


        </section>


        <!-- SECTION VISIBILITY -->

        <section class="privacy-card">


            <div class="section-heading">


                <span class="section-label">
                    INFORMATION VISIBILITY
                </span>


                <h2>
                    Profile Sections
                </h2>


                <p>
                    Each section has its own independent
                    visibility setting.
                </p>


            </div>


            <!-- CONTACT -->

            <div class="visibility-row">


                <div class="visibility-row-header">

                    <h3>
                        Contact Information
                    </h3>

                    <p>
                        Email address and phone number.
                    </p>

                </div>


                <div class="visibility-choice-group">

                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="contact_visibility"
                            value="public"
                            <?= privacyChecked(
                                $privacy,
                                "contact_visibility",
                                "public"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Public</strong>
                            <span>Visible to anyone.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="contact_visibility"
                            value="school_only"
                            <?= privacyChecked(
                                $privacy,
                                "contact_visibility",
                                "school_only"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>School Only</strong>
                            <span>Visible to school users.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="contact_visibility"
                            value="private"
                            <?= privacyChecked(
                                $privacy,
                                "contact_visibility",
                                "private"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Private</strong>
                            <span>Hidden from other users.</span>
                        </div>

                    </label>

                </div>


            </div>


            <!-- FAMILY -->

            <div class="visibility-row">


                <div class="visibility-row-header">

                    <h3>
                        Family Information
                    </h3>

                    <p>
                        Parent and guardian information.
                    </p>

                </div>


                <div class="visibility-choice-group">

                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="family_visibility"
                            value="public"
                            <?= privacyChecked(
                                $privacy,
                                "family_visibility",
                                "public"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Public</strong>
                            <span>Visible to anyone.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="family_visibility"
                            value="school_only"
                            <?= privacyChecked(
                                $privacy,
                                "family_visibility",
                                "school_only"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>School Only</strong>
                            <span>Visible to school users.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="family_visibility"
                            value="private"
                            <?= privacyChecked(
                                $privacy,
                                "family_visibility",
                                "private"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Private</strong>
                            <span>Hidden from other users.</span>
                        </div>

                    </label>

                </div>


            </div>


            <!-- ADDRESS -->

            <div class="visibility-row">


                <div class="visibility-row-header">

                    <h3>
                        Address
                    </h3>

                    <p>
                        Your residential address.
                    </p>

                </div>


                <div class="visibility-choice-group">

                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="address_visibility"
                            value="public"
                            <?= privacyChecked(
                                $privacy,
                                "address_visibility",
                                "public"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Public</strong>
                            <span>Visible to anyone.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="address_visibility"
                            value="school_only"
                            <?= privacyChecked(
                                $privacy,
                                "address_visibility",
                                "school_only"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>School Only</strong>
                            <span>Visible to school users.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="address_visibility"
                            value="private"
                            <?= privacyChecked(
                                $privacy,
                                "address_visibility",
                                "private"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Private</strong>
                            <span>Hidden from other users.</span>
                        </div>

                    </label>

                </div>


            </div>


            <!-- ACADEMIC -->

            <div class="visibility-row">


                <div class="visibility-row-header">

                    <h3>
                        Academic Information
                    </h3>

                    <p>
                        Program, college, campus,
                        year level, section, and education.
                    </p>

                </div>


                <div class="visibility-choice-group">

                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="academic_visibility"
                            value="public"
                            <?= privacyChecked(
                                $privacy,
                                "academic_visibility",
                                "public"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Public</strong>
                            <span>Visible to anyone.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="academic_visibility"
                            value="school_only"
                            <?= privacyChecked(
                                $privacy,
                                "academic_visibility",
                                "school_only"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>School Only</strong>
                            <span>Visible to school users.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="academic_visibility"
                            value="private"
                            <?= privacyChecked(
                                $privacy,
                                "academic_visibility",
                                "private"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Private</strong>
                            <span>Hidden from other users.</span>
                        </div>

                    </label>

                </div>


            </div>


            <!-- ACHIEVEMENTS -->

            <div class="visibility-row">


                <div class="visibility-row-header">

                    <h3>
                        Achievements
                    </h3>

                    <p>
                        Accomplishments, awards,
                        competitions, and certifications.
                    </p>

                </div>


                <div class="visibility-choice-group">

                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="achievement_visibility"
                            value="public"
                            <?= privacyChecked(
                                $privacy,
                                "achievement_visibility",
                                "public"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Public</strong>
                            <span>Visible to anyone.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="achievement_visibility"
                            value="school_only"
                            <?= privacyChecked(
                                $privacy,
                                "achievement_visibility",
                                "school_only"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>School Only</strong>
                            <span>Visible to school users.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="achievement_visibility"
                            value="private"
                            <?= privacyChecked(
                                $privacy,
                                "achievement_visibility",
                                "private"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Private</strong>
                            <span>Hidden from other users.</span>
                        </div>

                    </label>

                </div>


            </div>


            <!-- HOBBIES -->

            <div class="visibility-row">


                <div class="visibility-row-header">

                    <h3>
                        Hobbies & Interests
                    </h3>

                    <p>
                        Your hobbies and personal interests.
                    </p>

                </div>


                <div class="visibility-choice-group">

                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="hobbies_visibility"
                            value="public"
                            <?= privacyChecked(
                                $privacy,
                                "hobbies_visibility",
                                "public"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Public</strong>
                            <span>Visible to anyone.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="hobbies_visibility"
                            value="school_only"
                            <?= privacyChecked(
                                $privacy,
                                "hobbies_visibility",
                                "school_only"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>School Only</strong>
                            <span>Visible to school users.</span>
                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="hobbies_visibility"
                            value="private"
                            <?= privacyChecked(
                                $privacy,
                                "hobbies_visibility",
                                "private"
                            ) ?>
                        >

                        <div class="visibility-choice-content">
                            <strong>Private</strong>
                            <span>Hidden from other users.</span>
                        </div>

                    </label>

                </div>


            </div>


            <!-- ORGANIZATIONS -->

            <div class="visibility-row">


                <div class="visibility-row-header">

                    <h3>
                        Organizations & Activities
                    </h3>

                    <p>
                        Organizations, clubs,
                        student government roles,
                        and event participation.
                    </p>

                </div>


                <div class="visibility-choice-group">

                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="organizations_visibility"
                            value="public"
                            <?= privacyChecked(
                                $privacy,
                                "organizations_visibility",
                                "public"
                            ) ?>
                        >

                        <div class="visibility-choice-content">

                            <strong>
                                Public
                            </strong>

                            <span>
                                Visible to anyone.
                            </span>

                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="organizations_visibility"
                            value="school_only"
                            <?= privacyChecked(
                                $privacy,
                                "organizations_visibility",
                                "school_only"
                            ) ?>
                        >

                        <div class="visibility-choice-content">

                            <strong>
                                School Only
                            </strong>

                            <span>
                                Visible to authenticated school users.
                            </span>

                        </div>

                    </label>


                    <label class="visibility-choice">

                        <input
                            type="radio"
                            name="organizations_visibility"
                            value="private"
                            <?= privacyChecked(
                                $privacy,
                                "organizations_visibility",
                                "private"
                            ) ?>
                        >

                        <div class="visibility-choice-content">

                            <strong>
                                Private
                            </strong>

                            <span>
                                Hidden from other users.
                            </span>

                        </div>

                    </label>

                </div>


            </div>


            <div class="privacy-explanation">

                <strong>
                    How privacy works:
                </strong>

                Public information can be viewed by anyone.
                School Only information requires an authenticated,
                active and verified CVSWHO school account.
                Private information is not shown to other users.

            </div>


        </section>


        <div class="save-row">

            <button
                type="submit"
                class="save-button"
            >
                Save Privacy Settings
            </button>

        </div>


    </form>


</main>


<!-- FOOTER -->

<footer class="footer">

    <p>
        CVSWHO
    </p>

    <span>
        Manage your student profile with ease.
    </span>

</footer>


<script src="main.js"></script>


</body>

</html>