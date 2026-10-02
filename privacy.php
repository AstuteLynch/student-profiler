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
$success = "";


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
   DEFAULT VISIBILITY
========================================================= */

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
        "school_only"
];


$allowedValues = [

    "public",
    "school_only",
    "private"
];


/* =========================================================
   SAVE SETTINGS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    foreach (
        array_keys($privacy)
        as $key
    ) {

        $value =
            $_POST[$key]
            ?? $privacy[$key];


        if (
            !in_array(
                $value,
                $allowedValues,
                true
            )
        ) {

            $error =
                "Invalid privacy setting.";

            break;
        }


        $privacy[$key] =
            $value;
    }


    if ($error === "") {

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
                    hobbies_visibility
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
                    ?
                )

                ON DUPLICATE KEY UPDATE

                    profile_visibility =
                        VALUES(profile_visibility),

                    contact_visibility =
                        VALUES(contact_visibility),

                    family_visibility =
                        VALUES(family_visibility),

                    address_visibility =
                        VALUES(address_visibility),

                    academic_visibility =
                        VALUES(academic_visibility),

                    achievement_visibility =
                        VALUES(achievement_visibility),

                    hobbies_visibility =
                        VALUES(hobbies_visibility)
            ");


        if (!$stmt) {

            $error =
                "Privacy database error: " .
                $conn->error;

        } else {

            $stmt->bind_param(
                "isssssss",

                $userId,

                $privacy[
                    "profile_visibility"
                ],

                $privacy[
                    "contact_visibility"
                ],

                $privacy[
                    "family_visibility"
                ],

                $privacy[
                    "address_visibility"
                ],

                $privacy[
                    "academic_visibility"
                ],

                $privacy[
                    "achievement_visibility"
                ],

                $privacy[
                    "hobbies_visibility"
                ]
            );


            if (
                $stmt->execute()
            ) {

                $success =
                    "Privacy settings saved successfully.";

            } else {

                $error =
                    "Unable to save privacy settings: " .
                    $stmt->error;
            }


            $stmt->close();
        }
    }
}


/* =========================================================
   LOAD CURRENT SETTINGS
========================================================= */

$stmt =
    $conn->prepare("
        SELECT

            profile_visibility,
            contact_visibility,
            family_visibility,
            address_visibility,

            academic_visibility,
            achievement_visibility,
            hobbies_visibility

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


    $saved =
        $stmt
            ->get_result()
            ->fetch_assoc();


    $stmt->close();


    if ($saved) {

        $privacy =
            array_merge(
                $privacy,
                $saved
            );
    }
}


/* =========================================================
   RADIO OPTIONS
========================================================= */

function privacyOptions(
    string $name,
    string $current
): string {

    $options = [

        "public" => [
            "Public",
            "Visible to everyone."
        ],

        "school_only" => [
            "School Only",
            "Visible only to authenticated CVSWHO/CvSU users."
        ],

        "private" => [
            "Private",
            "Hidden from other users."
        ]
    ];


    $html =
        '<div class="privacy-choice-grid">';


    foreach (
        $options
        as $value => $info
    ) {

        $checked =
            $current === $value
                ? "checked"
                : "";


        $html .= '

            <label class="privacy-choice">

                <input
                    type="radio"
                    name="' .
                        htmlspecialchars(
                            $name
                        ) .
                    '"
                    value="' .
                        htmlspecialchars(
                            $value
                        ) .
                    '"
                    ' .
                        $checked .
                    '
                >

                <span class="privacy-choice-box">

                    <strong>' .
                        htmlspecialchars(
                            $info[0]
                        ) .
                    '</strong>

                    <span>' .
                        htmlspecialchars(
                            $info[1]
                        ) .
                    '</span>

                </span>

            </label>
        ';
    }


    $html .= '</div>';


    return $html;
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

        .privacy-notice {

            padding:
                18px 20px;

            margin-bottom: 22px;

            background:
                #edf8f2;

            border:
                1px solid #cfe5d8;

            border-radius: 12px;
        }


        .privacy-notice strong {

            display: block;

            color:
                #003d24;

            font-size: 11px;
        }


        .privacy-notice p {

            margin-top: 6px;

            color:
                #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        .privacy-message {

            padding:
                14px 18px;

            margin-bottom: 20px;

            border-radius: 10px;

            font-size: 11px;

            font-weight: 600;
        }


        .privacy-success {

            color:
                #18794e;

            background:
                #eefaf3;

            border:
                1px solid #cae7d7;
        }


        .privacy-error {

            color:
                #a33f3f;

            background:
                #fff5f5;

            border:
                1px solid #efd0d0;
        }


        .privacy-section-row {

            padding:
                20px 0;

            border-top:
                1px solid #e1e9e4;
        }


        .privacy-section-row:first-child {

            border-top: 0;
        }


        .privacy-section-header {

            margin-bottom: 13px;
        }


        .privacy-section-header h3 {

            color:
                #003d24;

            font-size: 11px;
        }


        .privacy-section-header p {

            margin-top: 5px;

            color:
                #68766f;

            font-size: 9px;

            line-height: 1.6;
        }


        .privacy-choice-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );

            gap: 9px;
        }


        .privacy-choice {

            position: relative;

            cursor: pointer;
        }


        .privacy-choice input {

            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .privacy-choice-box {

            display: block;

            height: 100%;

            padding: 13px;

            background:
                #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 9px;

            transition:
                .2s ease;
        }


        .privacy-choice-box strong {

            display: block;

            color:
                #003d24;

            font-size: 10px;
        }


        .privacy-choice-box span {

            display: block;

            margin-top: 4px;

            color:
                #68766f;

            font-size: 8px;

            line-height: 1.5;
        }


        .privacy-choice input:checked
        + .privacy-choice-box {

            background:
                #ddefe5;

            border-color:
                #006b3f;

            box-shadow:
                0 0 0 1px
                rgba(
                    0,
                    107,
                    63,
                    .05
                );
        }


        .always-visible {

            padding: 16px;

            margin-top: 18px;

            background:
                #f5f8f6;

            border:
                1px solid #e1e9e4;

            border-radius: 10px;
        }


        .always-visible strong {

            display: block;

            color:
                #006b3f;

            font-size: 10px;
        }


        .always-visible p {

            margin-top: 5px;

            color:
                #68766f;

            font-size: 9px;

            line-height: 1.6;
        }


        .privacy-actions {

            display: flex;

            justify-content:
                flex-end;

            gap: 10px;

            margin-top: 22px;
        }


        .save-privacy-button {

            padding:
                12px 20px;

            color:
                white;

            background:
                #006b3f;

            border:
                1px solid #006b3f;

            border-radius: 8px;

            font-family:
                inherit;

            font-size: 11px;

            font-weight: 600;

            cursor: pointer;
        }


        .save-privacy-button:hover {

            background:
                #004d2a;
        }


        @media (
            max-width: 700px
        ) {

            .privacy-choice-grid {

                grid-template-columns:
                    1fr;
            }
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


<main class="page">


    <section class="page-header">


        <span class="eyebrow">
            SETTINGS & PRIVACY
        </span>


        <h1>
            Privacy & Visibility
        </h1>


        <p>
            Choose whether each part of your
            profile is public, school-only,
            or private.
        </p>


    </section>


    <?php if (
        $success !== ""
    ): ?>


        <div
            class="
                privacy-message
                privacy-success
            "
        >

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>


    <?php endif; ?>


    <?php if (
        $error !== ""
    ): ?>


        <div
            class="
                privacy-message
                privacy-error
            "
        >

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>


    <?php endif; ?>


    <div class="privacy-notice">


        <strong>
            How visibility works
        </strong>


        <p>

            Public information can be viewed
            by anyone.

            School Only information can be
            viewed only by authenticated
            CVSWHO/CvSU users.

            Private information stays hidden
            from other users.

        </p>


    </div>


    <form method="POST">


        <section class="privacy-card">


            <div class="section-heading">


                <span class="section-label">
                    PROFILE VISIBILITY
                </span>


                <h2>
                    Who can find and open
                    your profile?
                </h2>


                <p>
                    This setting controls
                    access to the profile itself.
                </p>


            </div>


            <?= privacyOptions(
                "profile_visibility",
                $privacy[
                    "profile_visibility"
                ]
            ) ?>


            <div class="always-visible">


                <strong>
                    Basic profile identity
                </strong>


                <p>

                    When someone is allowed to
                    view the profile, the
                    student's name, year level,
                    and section remain basic
                    identifying information.

                </p>


            </div>


        </section>


        <section class="privacy-card">


            <div class="section-heading">


                <span class="section-label">
                    INFORMATION VISIBILITY
                </span>


                <h2>
                    Profile Sections
                </h2>


                <p>
                    Set visibility separately
                    for each type of information.
                </p>


            </div>


            <div class="privacy-section-row">


                <div class="privacy-section-header">


                    <h3>
                        Contact Information
                    </h3>


                    <p>
                        Email address and
                        phone number.
                    </p>


                </div>


                <?= privacyOptions(
                    "contact_visibility",
                    $privacy[
                        "contact_visibility"
                    ]
                ) ?>


            </div>


            <div class="privacy-section-row">


                <div class="privacy-section-header">


                    <h3>
                        Family Information
                    </h3>


                    <p>
                        Parent, guardian, and
                        guardian contact information.
                    </p>


                </div>


                <?= privacyOptions(
                    "family_visibility",
                    $privacy[
                        "family_visibility"
                    ]
                ) ?>


            </div>


            <div class="privacy-section-row">


                <div class="privacy-section-header">


                    <h3>
                        Address
                    </h3>


                    <p>
                        Residential address.
                    </p>


                </div>


                <?= privacyOptions(
                    "address_visibility",
                    $privacy[
                        "address_visibility"
                    ]
                ) ?>


            </div>


            <div class="privacy-section-row">


                <div class="privacy-section-header">


                    <h3>
                        Academic Details
                        & School Background
                    </h3>


                    <p>

                        Program, college, campus,
                        and previous schools.

                        Year level and section
                        remain part of the
                        basic profile.

                    </p>


                </div>


                <?= privacyOptions(
                    "academic_visibility",
                    $privacy[
                        "academic_visibility"
                    ]
                ) ?>


            </div>


            <div class="privacy-section-row">


                <div class="privacy-section-header">


                    <h3>
                        Accomplishments
                    </h3>


                    <p>
                        Achievements,
                        competitions,
                        certificates,
                        and projects.
                    </p>


                </div>


                <?= privacyOptions(
                    "achievement_visibility",
                    $privacy[
                        "achievement_visibility"
                    ]
                ) ?>


            </div>


            <div class="privacy-section-row">


                <div class="privacy-section-header">


                    <h3>
                        Hobbies & Interests
                    </h3>


                    <p>
                        Personal hobbies
                        and interests.
                    </p>


                </div>


                <?= privacyOptions(
                    "hobbies_visibility",
                    $privacy[
                        "hobbies_visibility"
                    ]
                ) ?>


            </div>


            <div class="privacy-actions">


                <button
                    type="submit"
                    class="save-privacy-button"
                >
                    Save Privacy Settings
                </button>


            </div>


        </section>


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


</body>

</html>