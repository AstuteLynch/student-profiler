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
        $_SESSION["settings_csrf"]
    )
) {

    $_SESSION["settings_csrf"] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION["settings_csrf"];


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


/* USER */

$userStmt =
    $conn->prepare("
        SELECT

            u.id,
            u.email,
            u.password_hash,
            u.role,
            u.account_status,
            u.email_verified,
            u.created_at,
            u.last_login_at,

            sp.student_id,
            sp.first_name,
            sp.middle_name,
            sp.last_name,
            sp.suffix,
            sp.program,
            sp.year_level,
            sp.section,
            sp.profile_photo

        FROM users u

        LEFT JOIN student_profiles sp
            ON sp.user_id = u.id

        WHERE u.id = ?

        LIMIT 1
    ");


if (!$userStmt) {

    die(
        "Settings database error: " .
        htmlspecialchars(
            $conn->error
        )
    );
}


$userStmt->bind_param(
    "i",
    $userId
);


$userStmt->execute();


$user =
    $userStmt
        ->get_result()
        ->fetch_assoc();


$userStmt->close();


if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* NAME */

$nameParts = [];


foreach (
    [
        $user["first_name"] ?? "",
        $user["middle_name"] ?? "",
        $user["last_name"] ?? "",
        $user["suffix"] ?? ""
    ]
    as $namePart
) {

    $namePart =
        trim($namePart);


    if ($namePart !== "") {

        $nameParts[] =
            $namePart;
    }
}


$fullName =
    trim(
        implode(
            " ",
            $nameParts
        )
    );


if ($fullName === "") {

    $fullName =
        "Student";
}


$avatarInitial =
    strtoupper(
        substr(
            trim(
                $user["first_name"]
                ?? ""
            ) !== ""
                ? $user["first_name"]
                : "S",
            0,
            1
        )
    );


$profilePhoto =
    trim(
        $user["profile_photo"]
        ?? ""
    );


/* PROFILE SETTINGS */

$profileSettings = [

    "show_about" => 1,

    "show_education" => 1,

    "show_accomplishments" => 1,

    "show_hobbies" => 1,

    "show_organizations" => 1
];


$profileSettingsStmt =
    $conn->prepare("
        SELECT

            show_about,
            show_education,
            show_accomplishments,
            show_hobbies,
            show_organizations

        FROM profile_settings

        WHERE user_id = ?

        LIMIT 1
    ");


if ($profileSettingsStmt) {

    $profileSettingsStmt->bind_param(
        "i",
        $userId
    );


    $profileSettingsStmt->execute();


    $savedProfileSettings =
        $profileSettingsStmt
            ->get_result()
            ->fetch_assoc();


    $profileSettingsStmt->close();


    if ($savedProfileSettings) {

        $profileSettings =
            array_merge(
                $profileSettings,
                $savedProfileSettings
            );
    }
}


/* PRIVACY */

$privacyVisibility =
    "school_only";


$privacyStmt =
    $conn->prepare("
        SELECT profile_visibility

        FROM privacy_settings

        WHERE user_id = ?

        LIMIT 1
    ");


if ($privacyStmt) {

    $privacyStmt->bind_param(
        "i",
        $userId
    );


    $privacyStmt->execute();


    $privacyRow =
        $privacyStmt
            ->get_result()
            ->fetch_assoc();


    $privacyStmt->close();


    if ($privacyRow) {

        $privacyVisibility =
            $privacyRow[
                "profile_visibility"
            ] ?? "school_only";
    }
}


/* SAVE PORTFOLIO SETTINGS */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST" &&
    ($_POST["action"] ?? "")
        === "save_portfolio_settings"
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
            "Your settings session expired. Please refresh the page.";

    } else {

        $showAbout =
            isset(
                $_POST["show_about"]
            )
                ? 1
                : 0;


        $showEducation =
            isset(
                $_POST["show_education"]
            )
                ? 1
                : 0;


        $showAccomplishments =
            isset(
                $_POST[
                    "show_accomplishments"
                ]
            )
                ? 1
                : 0;


        $showHobbies =
            isset(
                $_POST["show_hobbies"]
            )
                ? 1
                : 0;


        $showOrganizations =
            isset(
                $_POST[
                    "show_organizations"
                ]
            )
                ? 1
                : 0;


        $saveSettingsStmt =
            $conn->prepare("
                INSERT INTO profile_settings
                (
                    user_id,
                    show_about,
                    show_education,
                    show_accomplishments,
                    show_hobbies,
                    show_organizations
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

                ON DUPLICATE KEY UPDATE

                    show_about =
                        VALUES(show_about),

                    show_education =
                        VALUES(show_education),

                    show_accomplishments =
                        VALUES(show_accomplishments),

                    show_hobbies =
                        VALUES(show_hobbies),

                    show_organizations =
                        VALUES(show_organizations)
            ");


        if (!$saveSettingsStmt) {

            $error =
                "Unable to save portfolio settings.";

        } else {

            $saveSettingsStmt->bind_param(
                "iiiiii",

                $userId,
                $showAbout,
                $showEducation,
                $showAccomplishments,
                $showHobbies,
                $showOrganizations
            );


            if (
                $saveSettingsStmt->execute()
            ) {

                $profileSettings[
                    "show_about"
                ] =
                    $showAbout;


                $profileSettings[
                    "show_education"
                ] =
                    $showEducation;


                $profileSettings[
                    "show_accomplishments"
                ] =
                    $showAccomplishments;


                $profileSettings[
                    "show_hobbies"
                ] =
                    $showHobbies;


                $profileSettings[
                    "show_organizations"
                ] =
                    $showOrganizations;


                $success =
                    "Digital portfolio settings saved successfully.";

            } else {

                $error =
                    "Unable to save portfolio settings.";
            }


            $saveSettingsStmt->close();
        }
    }
}


/* CHANGE PASSWORD */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST" &&
    ($_POST["action"] ?? "")
        === "change_password"
) {

    $submittedToken =
        $_POST["csrf_token"]
        ?? "";


    $currentPassword =
        $_POST["current_password"]
        ?? "";


    $newPassword =
        $_POST["new_password"]
        ?? "";


    $confirmPassword =
        $_POST["confirm_password"]
        ?? "";


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $error =
            "Your settings session expired. Please refresh the page.";

    } elseif (
        $currentPassword === "" ||
        $newPassword === "" ||
        $confirmPassword === ""
    ) {

        $error =
            "Please complete all password fields.";

    } elseif (
        !password_verify(
            $currentPassword,
            $user["password_hash"]
        )
    ) {

        $error =
            "Your current password is incorrect.";

    } elseif (
        strlen($newPassword) < 8
    ) {

        $error =
            "Your new password must contain at least 8 characters.";

    } elseif (
        !preg_match(
            '/[A-Z]/',
            $newPassword
        )
    ) {

        $error =
            "Your new password must contain at least one uppercase letter.";

    } elseif (
        !preg_match(
            '/[a-z]/',
            $newPassword
        )
    ) {

        $error =
            "Your new password must contain at least one lowercase letter.";

    } elseif (
        !preg_match(
            '/[0-9]/',
            $newPassword
        )
    ) {

        $error =
            "Your new password must contain at least one number.";

    } elseif (
        $newPassword !==
        $confirmPassword
    ) {

        $error =
            "Your new passwords do not match.";

    } elseif (
        password_verify(
            $newPassword,
            $user["password_hash"]
        )
    ) {

        $error =
            "Your new password must be different from your current password.";

    } else {

        $newPasswordHash =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        $passwordStmt =
            $conn->prepare("
                UPDATE users

                SET password_hash = ?

                WHERE id = ?

                LIMIT 1
            ");


        if (!$passwordStmt) {

            $error =
                "Unable to update your password.";

        } else {

            $passwordStmt->bind_param(
                "si",
                $newPasswordHash,
                $userId
            );


            if (
                $passwordStmt->execute()
            ) {

                $success =
                    "Your password has been changed successfully.";


                $user["password_hash"] =
                    $newPasswordHash;


                session_regenerate_id(
                    true
                );

            } else {

                $error =
                    "Unable to update your password.";
            }


            $passwordStmt->close();
        }
    }
}


/* DEACTIVATE ACCOUNT */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST" &&
    ($_POST["action"] ?? "")
        === "deactivate_account"
) {

    $submittedToken =
        $_POST["csrf_token"]
        ?? "";


    $deactivatePassword =
        $_POST[
            "deactivate_password"
        ] ?? "";


    $confirmation =
        trim(
            $_POST[
                "deactivate_confirmation"
            ] ?? ""
        );


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $error =
            "Your settings session expired. Please refresh the page.";

    } elseif (
        !password_verify(
            $deactivatePassword,
            $user["password_hash"]
        )
    ) {

        $error =
            "Your password is incorrect.";

    } elseif (
        strtoupper(
            $confirmation
        ) !== "DEACTIVATE"
    ) {

        $error =
            "Type DEACTIVATE to confirm account deactivation.";

    } else {

        $deactivateStmt =
            $conn->prepare("
                UPDATE users

                SET account_status =
                    'deactivated'

                WHERE id = ?

                LIMIT 1
            ");


        if (!$deactivateStmt) {

            $error =
                "Unable to deactivate your account.";

        } else {

            $deactivateStmt->bind_param(
                "i",
                $userId
            );


            if (
                $deactivateStmt->execute()
            ) {

                $deactivateStmt->close();


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


                header(
                    "Location: index.php?account_deactivated=1"
                );

                exit;
            }


            $deactivateStmt->close();


            $error =
                "Unable to deactivate your account.";
        }
    }
}


/* DISPLAY */

$studentIdDisplay =
    trim(
        $user["student_id"]
        ?? ""
    );


if (
    $studentIdDisplay === ""
) {

    $studentIdDisplay =
        "Not added";
}


$programDisplay =
    trim(
        $user["program"]
        ?? ""
    );


if (
    $programDisplay === ""
) {

    $programDisplay =
        "Not added";
}


$yearDisplay =
    trim(
        $user["year_level"]
        ?? ""
    );


if (
    $yearDisplay === ""
) {

    $yearDisplay =
        "Not added";
}


$sectionDisplay =
    trim(
        $user["section"]
        ?? ""
    );


if (
    $sectionDisplay === ""
) {

    $sectionDisplay =
        "Not added";
}


$accountStatusDisplay =
    ucwords(
        str_replace(
            "_",
            " ",
            $user[
                "account_status"
            ] ?? "unknown"
        )
    );


$visibilityLabels = [

    "public" =>
        "Public",

    "school_only" =>
        "School Only",

    "private" =>
        "Private"
];


$visibilityDisplay =
    $visibilityLabels[
        $privacyVisibility
    ] ?? "School Only";


$memberSince =
    !empty(
        $user["created_at"]
    )
        ? date(
            "F Y",
            strtotime(
                $user["created_at"]
            )
        )
        : "Unknown";

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
        Settings | CVSWHO
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >


    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        * {

            margin: 0;

            padding: 0;

            box-sizing:
                border-box;
        }


        :root {

            --primary-green:
                #006b3f;

            --deep-green:
                #004d2a;

            --dark-green:
                #003d24;

            --light-green:
                #e6f3eb;

            --very-light-green:
                #f5f8f6;

            --white:
                #ffffff;

            --text-dark:
                #1d2a23;

            --text-medium:
                #68766f;

            --text-light:
                #8c9891;

            --border-color:
                #e1e9e4;

            --danger:
                #b84242;

            --danger-dark:
                #913131;

            --danger-light:
                #fff3f3;

            --transition:
                .2s ease;
        }


        body {

            min-height: 100vh;

            color:
                var(--text-dark);

            background:
                var(--very-light-green);

            font-family:
                "Inter",
                sans-serif;
        }


        a {

            color: inherit;

            text-decoration: none;
        }


        button,
        input {

            font-family:
                inherit;
        }


        .navbar {

            position: sticky;

            top: 0;

            z-index: 100;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .96
                );

            border-bottom:
                1px solid
                var(--border-color);

            backdrop-filter:
                blur(12px);
        }


        .nav-container {

            width:
                min(
                    1120px,
                    calc(
                        100% - 40px
                    )
                );

            min-height: 72px;

            margin: 0 auto;

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 25px;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .brand-mark {

            width: 37px;
            height: 37px;

            display: grid;

            place-items: center;

            color:
                white;

            background:
                var(--primary-green);

            border-radius: 9px;

            font-size: 14px;

            font-weight: 800;
        }


        .brand-text {

            display: flex;

            flex-direction:
                column;
        }


        .brand-name {

            color:
                var(--dark-green);

            font-size: 13px;

            font-weight: 800;
        }


        .brand-subtitle {

            margin-top: 2px;

            color:
                var(--text-light);

            font-size: 8px;
        }


        .desktop-nav {

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .nav-link {

            padding:
                9px 11px;

            color:
                var(--text-medium);

            border-radius: 7px;

            font-size: 10px;

            font-weight: 600;
        }


        .nav-link:hover,
        .nav-link.active {

            color:
                var(--primary-green);

            background:
                var(--light-green);
        }


        .logout-button {

            padding:
                9px 13px;

            color:
                var(--primary-green);

            border:
                1px solid
                var(--border-color);

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;
        }


        .logout-button:hover {

            background:
                var(--light-green);
        }


        .settings-page {

            width:
                min(
                    1050px,
                    calc(
                        100% - 40px
                    )
                );

            margin: 0 auto;

            padding:
                50px 0 80px;
        }


        .page-header {

            display: flex;

            align-items:
                flex-end;

            justify-content:
                space-between;

            gap: 25px;

            margin-bottom: 28px;
        }


        .eyebrow,
        .section-label {

            color:
                var(--primary-green);

            font-size: 8px;

            font-weight: 800;

            letter-spacing:
                1px;

            text-transform:
                uppercase;
        }


        .page-header h1 {

            margin-top: 7px;

            color:
                var(--dark-green);

            font-size:
                clamp(
                    28px,
                    5vw,
                    38px
                );
        }


        .page-header p {

            max-width: 610px;

            margin-top: 9px;

            color:
                var(--text-medium);

            font-size: 11px;

            line-height: 1.7;
        }


        .view-portfolio-button {

            padding:
                11px 16px;

            color: white;

            background:
                var(--primary-green);

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;

            white-space:
                nowrap;
        }


        .view-portfolio-button:hover {

            background:
                var(--deep-green);
        }


        .message {

            margin-bottom: 20px;

            padding:
                14px 17px;

            border-radius: 9px;

            font-size: 10px;

            font-weight: 600;
        }


        .message.success {

            color:
                #18794e;

            background:
                #eefaf3;

            border:
                1px solid
                #cae7d7;
        }


        .message.error {

            color:
                #a33f3f;

            background:
                #fff5f5;

            border:
                1px solid
                #efd0d0;
        }


        .settings-layout {

            display: grid;

            grid-template-columns:
                minmax(
                    0,
                    1fr
                )
                310px;

            gap: 20px;

            align-items: start;
        }


        .settings-main {

            display: flex;

            flex-direction:
                column;

            gap: 20px;
        }


        .settings-sidebar {

            display: flex;

            flex-direction:
                column;

            gap: 20px;
        }


        .settings-card {

            padding: 25px;

            background:
                var(--white);

            border:
                1px solid
                var(--border-color);

            border-radius: 14px;

            box-shadow:
                0 6px 20px
                rgba(
                    0,
                    55,
                    30,
                    .035
                );
        }


        .settings-heading {

            margin-bottom: 19px;
        }


        .settings-heading h2 {

            margin-top: 6px;

            color:
                var(--dark-green);

            font-size: 17px;
        }


        .settings-heading p {

            margin-top: 6px;

            color:
                var(--text-medium);

            font-size: 9px;

            line-height: 1.6;
        }


        /* ACCOUNT */

        .account-profile {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;
        }


        .account-avatar {

            width: 60px;
            height: 60px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            overflow: hidden;

            color:
                var(--primary-green);

            background:
                var(--light-green);

            border-radius: 50%;

            font-size: 21px;

            font-weight: 800;

            background-size: cover;

            background-position: center;

            background-repeat:
                no-repeat;
        }


        .account-profile h2 {

            color:
                var(--dark-green);

            font-size: 15px;
        }


        .account-profile p {

            margin-top: 4px;

            color:
                var(--text-medium);

            font-size: 9px;
        }


        .account-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 10px;
        }


        .account-item {

            padding: 13px;

            background:
                var(--very-light-green);

            border:
                1px solid
                var(--border-color);

            border-radius: 9px;
        }


        .account-item span {

            display: block;

            color:
                var(--text-light);

            font-size: 7px;

            font-weight: 700;

            letter-spacing:
                .6px;

            text-transform:
                uppercase;
        }


        .account-item strong {

            display: block;

            margin-top: 6px;

            color:
                var(--dark-green);

            font-size: 10px;

            word-break:
                break-word;
        }


        .status-active {

            color:
                var(--primary-green)
                !important;
        }


        /* SHORTCUTS */

        .settings-links {

            display: flex;

            flex-direction:
                column;

            gap: 9px;
        }


        .setting-link {

            display: flex;

            align-items: center;

            gap: 13px;

            padding: 14px;

            background:
                var(--very-light-green);

            border:
                1px solid
                var(--border-color);

            border-radius: 9px;

            transition:
                var(--transition);
        }


        .setting-link:hover {

            transform:
                translateY(-1px);

            border-color:
                #bdd5c7;

            background:
                #f0f7f3;
        }


        .setting-link-icon {

            width: 34px;
            height: 34px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            color:
                var(--primary-green);

            background:
                var(--light-green);

            border-radius: 8px;

            font-size: 10px;

            font-weight: 800;
        }


        .setting-link-content {

            flex: 1;
        }


        .setting-link-content h3 {

            color:
                var(--dark-green);

            font-size: 10px;
        }


        .setting-link-content p {

            margin-top: 4px;

            color:
                var(--text-medium);

            font-size: 8px;

            line-height: 1.5;
        }


        .setting-arrow {

            color:
                var(--primary-green);

            font-size: 14px;
        }


        /* TOGGLES */

        .toggle-list {

            display: flex;

            flex-direction:
                column;
        }


        .toggle-item {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            padding:
                16px 0;

            border-bottom:
                1px solid
                var(--border-color);
        }


        .toggle-item:first-child {

            padding-top: 0;
        }


        .toggle-item:last-child {

            padding-bottom: 0;

            border-bottom: 0;
        }


        .toggle-content h3 {

            color:
                var(--dark-green);

            font-size: 10px;
        }


        .toggle-content p {

            margin-top: 4px;

            color:
                var(--text-medium);

            font-size: 8px;

            line-height: 1.5;
        }


        .switch {

            position: relative;

            width: 42px;
            height: 23px;

            flex-shrink: 0;
        }


        .switch input {

            width: 0;
            height: 0;

            opacity: 0;
        }


        .slider {

            position: absolute;

            inset: 0;

            background:
                #ccd6d0;

            border-radius: 30px;

            cursor: pointer;

            transition:
                var(--transition);
        }


        .slider::before {

            content: "";

            position: absolute;

            width: 17px;
            height: 17px;

            left: 3px;
            top: 3px;

            background:
                white;

            border-radius: 50%;

            box-shadow:
                0 2px 5px
                rgba(
                    0,
                    0,
                    0,
                    .12
                );

            transition:
                var(--transition);
        }


        .switch input:checked
        + .slider {

            background:
                var(--primary-green);
        }


        .switch input:checked
        + .slider::before {

            transform:
                translateX(19px);
        }


        .save-button {

            margin-top: 20px;

            padding:
                11px 17px;

            color: white;

            background:
                var(--primary-green);

            border:
                1px solid
                var(--primary-green);

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;
        }


        .save-button:hover {

            background:
                var(--deep-green);
        }


        /* PASSWORD */

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

            gap: 13px;
        }


        .form-group {

            display: flex;

            flex-direction:
                column;

            gap: 6px;
        }


        .form-group.full {

            grid-column:
                1 / -1;
        }


        .form-group label {

            color:
                var(--dark-green);

            font-size: 9px;

            font-weight: 700;
        }


        .form-group input {

            width: 100%;

            padding:
                11px 12px;

            color:
                var(--text-dark);

            background:
                var(--white);

            border:
                1px solid
                var(--border-color);

            border-radius: 8px;

            outline: none;

            font-size: 10px;
        }


        .form-group input:focus {

            border-color:
                var(--primary-green);

            box-shadow:
                0 0 0 3px
                rgba(
                    0,
                    107,
                    63,
                    .06
                );
        }


        .password-note {

            margin-top: 12px;

            color:
                var(--text-light);

            font-size: 8px;

            line-height: 1.6;
        }


        /* PRIVACY */

        .visibility-box {

            padding: 15px;

            background:
                var(--very-light-green);

            border:
                1px solid
                var(--border-color);

            border-radius: 10px;
        }


        .visibility-box span {

            color:
                var(--text-light);

            font-size: 8px;

            font-weight: 700;

            text-transform:
                uppercase;
        }


        .visibility-box strong {

            display: block;

            margin-top: 6px;

            color:
                var(--primary-green);

            font-size: 15px;
        }


        .visibility-box p {

            margin-top: 6px;

            color:
                var(--text-medium);

            font-size: 8px;

            line-height: 1.6;
        }


        .secondary-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            margin-top: 12px;

            padding:
                10px 13px;

            color:
                var(--primary-green);

            background:
                white;

            border:
                1px solid
                #bdd5c7;

            border-radius: 8px;

            font-size: 9px;

            font-weight: 700;
        }


        .secondary-button:hover {

            background:
                var(--light-green);
        }


        /* DANGER */

        .danger-card {

            border-color:
                #efd3d3;
        }


        .danger-card
        .section-label {

            color:
                var(--danger);
        }


        .danger-warning {

            padding: 13px;

            margin-bottom: 15px;

            color:
                #7b4747;

            background:
                var(--danger-light);

            border:
                1px solid #efd3d3;

            border-radius: 8px;

            font-size: 8px;

            line-height: 1.6;
        }


        .danger-button {

            padding:
                11px 15px;

            color: white;

            background:
                var(--danger);

            border:
                1px solid
                var(--danger);

            border-radius: 8px;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }


        .danger-button:hover {

            background:
                var(--danger-dark);
        }


        /* MODAL */

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


        .modal-card {

            width:
                min(
                    460px,
                    100%
                );

            padding: 25px;

            background:
                white;

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


        .modal-card h2 {

            color:
                var(--dark-green);

            font-size: 17px;
        }


        .modal-card > p {

            margin:
                8px 0 18px;

            color:
                var(--text-medium);

            font-size: 9px;

            line-height: 1.6;
        }


        .modal-actions {

            display: flex;

            justify-content:
                flex-end;

            gap: 8px;

            margin-top: 18px;
        }


        .cancel-button {

            padding:
                10px 14px;

            color:
                var(--text-medium);

            background:
                white;

            border:
                1px solid
                var(--border-color);

            border-radius: 8px;

            font-size: 9px;

            font-weight: 700;

            cursor: pointer;
        }


        /* FOOTER */

        .footer {

            padding:
                25px 20px;

            text-align: center;

            background:
                white;

            border-top:
                1px solid
                var(--border-color);
        }


        .footer p {

            color:
                var(--dark-green);

            font-size: 12px;

            font-weight: 800;
        }


        .footer span {

            display: block;

            margin-top: 5px;

            color:
                var(--text-medium);

            font-size: 9px;
        }


        @media (
            max-width: 850px
        ) {

            .desktop-nav {

                display: none;
            }


            .settings-layout {

                grid-template-columns:
                    1fr;
            }


            .settings-sidebar {

                order: -1;
            }

        }


        @media (
            max-width: 600px
        ) {

            .nav-container,
            .settings-page {

                width:
                    calc(
                        100% - 28px
                    );
            }


            .brand-subtitle {

                display: none;
            }


            .page-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .account-grid,
            .form-grid {

                grid-template-columns:
                    1fr;
            }


            .form-group.full {

                grid-column:
                    auto;
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
                href="privacy.php"
                class="nav-link"
            >
                Privacy
            </a>

            <a
                href="settings.php"
                class="nav-link active"
            >
                Settings
            </a>

        </nav>


        <a
            href="settings.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>


    </div>


</header>


<!-- SETTINGS -->

<main class="settings-page">


    <!-- HEADER -->

    <section class="page-header">


        <div>


            <span class="eyebrow">
                ACCOUNT SETTINGS
            </span>


            <h1>
                Settings
            </h1>


            <p>
                Manage your student profile,
                digital portfolio, privacy,
                password, and account.
            </p>


        </div>


        <a
            href="digital_portfolio.php"
            class="view-portfolio-button"
        >
            View Digital Portfolio →
        </a>


    </section>


    <?php if (
        $success !== ""
    ): ?>


        <div class="message success">

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>


    <?php endif; ?>


    <?php if (
        $error !== ""
    ): ?>


        <div class="message error">

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>


    <?php endif; ?>


    <div class="settings-layout">


        <div class="settings-main">


            <!-- PROFILE MANAGEMENT -->

            <section class="settings-card">


                <div class="settings-heading">


                    <span class="section-label">
                        PROFILE MANAGEMENT
                    </span>


                    <h2>
                        Manage Your Profile
                    </h2>


                    <p>
                        Update the information
                        and records connected
                        to your student profile.
                    </p>


                </div>


                <div class="settings-links">


                    <a
                        href="edit_profile.php"
                        class="setting-link"
                    >


                        <div class="setting-link-icon">
                            P
                        </div>


                        <div class="setting-link-content">


                            <h3>
                                Edit Student Profile
                            </h3>


                            <p>
                                Update your personal
                                information, photo,
                                academics, family,
                                and educational
                                background.
                            </p>


                        </div>


                        <span class="setting-arrow">
                            →
                        </span>


                    </a>


                    <a
                        href="accomplishments.php"
                        class="setting-link"
                    >


                        <div class="setting-link-icon">
                            A
                        </div>


                        <div class="setting-link-content">


                            <h3>
                                Manage Accomplishments
                            </h3>


                            <p>
                                Add, edit, view,
                                or remove your
                                accomplishments
                                and certificates.
                            </p>


                        </div>


                        <span class="setting-arrow">
                            →
                        </span>


                    </a>


                    <a
                        href="hobbies.php"
                        class="setting-link"
                    >


                        <div class="setting-link-icon">
                            H
                        </div>


                        <div class="setting-link-content">


                            <h3>
                                Hobbies & Interests
                            </h3>


                            <p>
                                Manage the hobbies
                                and interests shown
                                on your student
                                portfolio.
                            </p>


                        </div>


                        <span class="setting-arrow">
                            →
                        </span>


                    </a>


                    <a
                        href="organizations.php"
                        class="setting-link"
                    >


                        <div class="setting-link-icon">
                            O
                        </div>


                        <div class="setting-link-content">


                            <h3>
                                Organizations & Activities
                            </h3>


                            <p>
                                Manage organizations,
                                clubs, events, and
                                student activities.
                            </p>


                        </div>


                        <span class="setting-arrow">
                            →
                        </span>


                    </a>


                </div>


            </section>


            <!-- DIGITAL PORTFOLIO -->

            <section class="settings-card">


                <div class="settings-heading">


                    <span class="section-label">
                        DIGITAL PORTFOLIO
                    </span>


                    <h2>
                        Portfolio Sections
                    </h2>


                    <p>
                        Choose which sections are
                        included when you view
                        your Digital Portfolio.
                    </p>


                </div>


                <form
                    method="POST"
                    action="settings.php"
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
                        value="save_portfolio_settings"
                    >


                    <div class="toggle-list">


                        <div class="toggle-item">


                            <div class="toggle-content">


                                <h3>
                                    About Me
                                </h3>


                                <p>
                                    Show your About Me
                                    description in your
                                    Digital Portfolio.
                                </p>


                            </div>


                            <label class="switch">


                                <input
                                    type="checkbox"
                                    name="show_about"
                                    value="1"
                                    <?= (int) $profileSettings[
                                        "show_about"
                                    ] === 1
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span class="slider"></span>


                            </label>


                        </div>


                        <div class="toggle-item">


                            <div class="toggle-content">


                                <h3>
                                    Education
                                </h3>


                                <p>
                                    Show your educational
                                    background and current
                                    academic information.
                                </p>


                            </div>


                            <label class="switch">


                                <input
                                    type="checkbox"
                                    name="show_education"
                                    value="1"
                                    <?= (int) $profileSettings[
                                        "show_education"
                                    ] === 1
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span class="slider"></span>


                            </label>


                        </div>


                        <div class="toggle-item">


                            <div class="toggle-content">


                                <h3>
                                    Accomplishments
                                </h3>


                                <p>
                                    Show achievements,
                                    certifications, and
                                    accomplishment records.
                                </p>


                            </div>


                            <label class="switch">


                                <input
                                    type="checkbox"
                                    name="show_accomplishments"
                                    value="1"
                                    <?= (int) $profileSettings[
                                        "show_accomplishments"
                                    ] === 1
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span class="slider"></span>


                            </label>


                        </div>


                        <div class="toggle-item">


                            <div class="toggle-content">


                                <h3>
                                    Hobbies & Interests
                                </h3>


                                <p>
                                    Show your hobbies and
                                    personal interests.
                                </p>


                            </div>


                            <label class="switch">


                                <input
                                    type="checkbox"
                                    name="show_hobbies"
                                    value="1"
                                    <?= (int) $profileSettings[
                                        "show_hobbies"
                                    ] === 1
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span class="slider"></span>


                            </label>


                        </div>


                        <div class="toggle-item">


                            <div class="toggle-content">


                                <h3>
                                    Organizations & Activities
                                </h3>


                                <p>
                                    Show your organization,
                                    club, government, and
                                    event participation.
                                </p>


                            </div>


                            <label class="switch">


                                <input
                                    type="checkbox"
                                    name="show_organizations"
                                    value="1"
                                    <?= (int) $profileSettings[
                                        "show_organizations"
                                    ] === 1
                                        ? "checked"
                                        : ""
                                    ?>
                                >


                                <span class="slider"></span>


                            </label>


                        </div>


                    </div>


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Save Portfolio Settings
                    </button>


                </form>


            </section>


            <!-- SECURITY -->

            <section class="settings-card">


                <div class="settings-heading">


                    <span class="section-label">
                        SECURITY
                    </span>


                    <h2>
                        Change Password
                    </h2>


                    <p>
                        Update the password used
                        to access your CVSWHO
                        account.
                    </p>


                </div>


                <form
                    method="POST"
                    action="settings.php"
                    autocomplete="off"
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
                        value="change_password"
                    >


                    <div class="form-grid">


                        <div
                            class="
                                form-group
                                full
                            "
                        >


                            <label
                                for="current_password"
                            >
                                Current Password
                            </label>


                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >


                        </div>


                        <div class="form-group">


                            <label
                                for="new_password"
                            >
                                New Password
                            </label>


                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >


                        </div>


                        <div class="form-group">


                            <label
                                for="confirm_password"
                            >
                                Confirm New Password
                            </label>


                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >


                        </div>


                    </div>


                    <p class="password-note">
                        Use at least 8 characters
                        with an uppercase letter,
                        lowercase letter, and number.
                    </p>


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Change Password
                    </button>


                </form>


            </section>


            <!-- ACCOUNT MANAGEMENT -->

            <section
                class="
                    settings-card
                    danger-card
                "
            >


                <div class="settings-heading">


                    <span class="section-label">
                        ACCOUNT MANAGEMENT
                    </span>


                    <h2>
                        Deactivate Account
                    </h2>


                    <p>
                        Temporarily disable access
                        to your CVSWHO account.
                    </p>


                </div>


                <div class="danger-warning">

                    Deactivating your account will
                    prevent you from logging in and
                    your active student profile will
                    no longer be available through
                    the normal CVSWHO student system.

                </div>


                <button
                    type="button"
                    class="danger-button"
                    onclick="openDeactivateModal()"
                >
                    Deactivate Account
                </button>


            </section>


        </div>


        <aside class="settings-sidebar">


            <!-- ACCOUNT -->

            <section class="settings-card">


                <div class="account-profile">


                    <div
                        class="account-avatar"

                        <?php if (
                            $profilePhoto !== ""
                        ): ?>

                            style="
                                background-image:
                                url('<?= htmlspecialchars(
                                    $profilePhoto,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>');

                                color:
                                    transparent;
                            "

                        <?php endif; ?>
                    >

                        <?= htmlspecialchars(
                            $avatarInitial
                        ) ?>

                    </div>


                    <div>


                        <h2>
                            <?= htmlspecialchars(
                                $fullName
                            ) ?>
                        </h2>


                        <p>
                            <?= htmlspecialchars(
                                $programDisplay
                            ) ?>
                        </p>


                    </div>


                </div>


                <div class="account-grid">


                    <div class="account-item">


                        <span>
                            Student ID
                        </span>


                        <strong>
                            <?= htmlspecialchars(
                                $studentIdDisplay
                            ) ?>
                        </strong>


                    </div>


                    <div class="account-item">


                        <span>
                            Status
                        </span>


                        <strong
                            class="<?= $user[
                                "account_status"
                            ] === "active"
                                ? "status-active"
                                : ""
                            ?>"
                        >

                            <?= htmlspecialchars(
                                $accountStatusDisplay
                            ) ?>

                        </strong>


                    </div>


                    <div class="account-item">


                        <span>
                            Email
                        </span>


                        <strong>
                            <?= htmlspecialchars(
                                $user["email"]
                            ) ?>
                        </strong>


                    </div>


                    <div class="account-item">


                        <span>
                            Member Since
                        </span>


                        <strong>
                            <?= htmlspecialchars(
                                $memberSince
                            ) ?>
                        </strong>


                    </div>


                    <div class="account-item">


                        <span>
                            Year Level
                        </span>


                        <strong>
                            <?= htmlspecialchars(
                                $yearDisplay
                            ) ?>
                        </strong>


                    </div>


                    <div class="account-item">


                        <span>
                            Section
                        </span>


                        <strong>
                            <?= htmlspecialchars(
                                $sectionDisplay
                            ) ?>
                        </strong>


                    </div>


                </div>


            </section>


            <!-- PRIVACY -->

            <section class="settings-card">


                <div class="settings-heading">


                    <span class="section-label">
                        PRIVACY
                    </span>


                    <h2>
                        Profile Visibility
                    </h2>


                </div>


                <div class="visibility-box">


                    <span>
                        Current Visibility
                    </span>


                    <strong>
                        <?= htmlspecialchars(
                            $visibilityDisplay
                        ) ?>
                    </strong>


                    <p>

                        Public profiles can be
                        viewed by everyone.

                        School Only profiles are
                        available to logged-in
                        CVSWHO users.

                        Private profiles stay
                        hidden from other users.

                    </p>


                </div>


                <a
                    href="privacy.php"
                    class="secondary-button"
                >
                    Manage Privacy Settings
                </a>


            </section>


            <!-- QUICK LINKS -->

            <section class="settings-card">


                <div class="settings-heading">


                    <span class="section-label">
                        QUICK LINKS
                    </span>


                    <h2>
                        Account Shortcuts
                    </h2>


                </div>


                <div class="settings-links">


                    <a
                        href="student_profile.php"
                        class="setting-link"
                    >


                        <div class="setting-link-icon">
                            P
                        </div>


                        <div class="setting-link-content">

                            <h3>
                                View Profile
                            </h3>

                        </div>


                        <span class="setting-arrow">
                            →
                        </span>


                    </a>


                    <a
                        href="digital_portfolio.php"
                        class="setting-link"
                    >


                        <div class="setting-link-icon">
                            D
                        </div>


                        <div class="setting-link-content">

                            <h3>
                                Digital Portfolio
                            </h3>

                        </div>


                        <span class="setting-arrow">
                            →
                        </span>


                    </a>


                    <a
                        href="settings.php?logout=1"
                        class="setting-link"
                    >


                        <div class="setting-link-icon">
                            L
                        </div>


                        <div class="setting-link-content">

                            <h3>
                                Log Out
                            </h3>

                        </div>


                        <span class="setting-arrow">
                            →
                        </span>


                    </a>


                </div>


            </section>


        </aside>


    </div>


</main>


<!-- DEACTIVATE ACCOUNT -->

<div
    class="modal"
    id="deactivateModal"
>


    <div class="modal-card">


        <h2>
            Deactivate Account
        </h2>


        <p>
            Confirm your password and type
            DEACTIVATE below. You will be
            logged out immediately after the
            account is deactivated.
        </p>


        <form
            method="POST"
            action="settings.php"
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
                value="deactivate_account"
            >


            <div class="form-group">


                <label
                    for="deactivate_password"
                >
                    Password
                </label>


                <input
                    type="password"
                    id="deactivate_password"
                    name="deactivate_password"
                    autocomplete="current-password"
                    required
                >


            </div>


            <div
                class="form-group"
                style="margin-top: 12px;"
            >


                <label
                    for="deactivate_confirmation"
                >
                    Type DEACTIVATE
                </label>


                <input
                    type="text"
                    id="deactivate_confirmation"
                    name="deactivate_confirmation"
                    autocomplete="off"
                    required
                >


            </div>


            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeDeactivateModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="danger-button"
                >
                    Deactivate
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

    const deactivateModal =
        document.getElementById(
            "deactivateModal"
        );


    function openDeactivateModal() {

        deactivateModal.classList.add(
            "show"
        );
    }


    function closeDeactivateModal() {

        deactivateModal.classList.remove(
            "show"
        );
    }


    deactivateModal.addEventListener(
        "click",
        function (event) {

            if (
                event.target ===
                deactivateModal
            ) {

                closeDeactivateModal();
            }
        }
    );

</script>


</body>

</html>