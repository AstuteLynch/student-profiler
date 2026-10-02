<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";

$error = "";

if (isset($_SESSION["user_id"])) {
    header("Location: student_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["first_name"] ?? "");
    $middleName = trim($_POST["middle_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $firstName === "" ||
        $lastName === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!isCvsuEmail($email)) {

        $error = "Please use a CvSU student email in the format firstname.lastname@cvsu.edu.ph.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } elseif (!preg_match('/[A-Z]/', $password)) {

        $error = "Password must contain at least one uppercase letter.";

    } elseif (!preg_match('/[a-z]/', $password)) {

        $error = "Password must contain at least one lowercase letter.";

    } elseif (!preg_match('/[0-9]/', $password)) {

        $error = "Password must contain at least one number.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } else {

        $check = $conn->prepare("
            SELECT id, email_verified
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $check->bind_param("s", $email);
        $check->execute();

        $existing = $check->get_result()->fetch_assoc();

        if ($existing && $existing["email_verified"]) {

            $error = "This email is already registered. Please login instead.";

        } elseif ($existing) {

            $userId = (int) $existing["id"];

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $update = $conn->prepare("
                UPDATE users
                SET password_hash = ?,
                    account_status = 'pending'
                WHERE id = ?
            ");

            $update->bind_param(
                "si",
                $passwordHash,
                $userId
            );

            $update->execute();

            $profile = $conn->prepare("
                INSERT INTO student_profiles
                (user_id, first_name, middle_name, last_name)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    first_name = VALUES(first_name),
                    middle_name = VALUES(middle_name),
                    last_name = VALUES(last_name)
            ");

            $profile->bind_param(
                "isss",
                $userId,
                $firstName,
                $middleName,
                $lastName
            );

            $profile->execute();

            $code = createAuthCode(
                $conn,
                $userId,
                "registration"
            );

            if (!sendAuthCode($email, $code, "registration")) {

                $error = "We could not send the verification code. Please try again.";

            } else {

                $_SESSION["pending_auth_user_id"] = $userId;
                $_SESSION["pending_auth_purpose"] = "registration";
                $_SESSION["pending_auth_email"] = $email;

                header("Location: auth.php");
                exit;
            }

        } else {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare("
                INSERT INTO users
                (email, password_hash, role, account_status, email_verified)
                VALUES (?, ?, 'student', 'pending', 0)
            ");

            $stmt->bind_param(
                "ss",
                $email,
                $passwordHash
            );

            if (!$stmt->execute()) {

                $error = "We could not create your account. Please try again.";

            } else {

                $userId = $stmt->insert_id;

                $profile = $conn->prepare("
                    INSERT INTO student_profiles
                    (user_id, first_name, middle_name, last_name)
                    VALUES (?, ?, ?, ?)
                ");

                $profile->bind_param(
                    "isss",
                    $userId,
                    $firstName,
                    $middleName,
                    $lastName
                );

                $profile->execute();

                $profileSettings = $conn->prepare("
                    INSERT INTO profile_settings
                    (user_id)
                    VALUES (?)
                ");

                $profileSettings->bind_param(
                    "i",
                    $userId
                );

                $profileSettings->execute();

                $privacySettings = $conn->prepare("
                    INSERT INTO privacy_settings
                    (user_id)
                    VALUES (?)
                ");

                $privacySettings->bind_param(
                    "i",
                    $userId
                );

                $privacySettings->execute();

                $code = createAuthCode(
                    $conn,
                    $userId,
                    "registration"
                );

                if (!sendAuthCode(
                    $email,
                    $code,
                    "registration"
                )) {

                    $error = "Your account was created, but we could not send the verification code.";

                } else {

                    $_SESSION["pending_auth_user_id"] = $userId;
                    $_SESSION["pending_auth_purpose"] = "registration";
                    $_SESSION["pending_auth_email"] = $email;

                    header("Location: auth.php");
                    exit;
                }
            }
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

    <title>Register | StudentProfiler</title>

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

    <link
        rel="stylesheet"
        href="register.css"
    >

</head>

<body>

<div class="page-wrapper">

    <div class="auth-container">

        <div class="auth-card">

            <!-- BRAND -->

            <div class="auth-brand">

                <a
                    href="index.php"
                    class="brand"
                >

                    <div class="brand-mark">
                        SP
                    </div>

                    <div class="brand-text">

                        <span class="brand-name">
                            StudentProfiler
                        </span>

                        <span class="brand-subtitle">
                            Student profile management system
                        </span>

                    </div>

                </a>

            </div>


            <!-- HEADER -->

            <div class="auth-header">

                <h1>
                    Create your account
                </h1>

                <p>
                    Set up your account to start building your student profile.
                </p>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                action=""
                class="auth-form"
            >

                <div class="form-row">

                    <div class="form-group">

                        <label for="first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            placeholder="First name"
                            value="<?= htmlspecialchars($_POST["first_name"] ?? "") ?>"
                            required
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
                            placeholder="Last name"
                            value="<?= htmlspecialchars($_POST["last_name"] ?? "") ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="middle_name">
                        Middle Name
                    </label>

                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        placeholder="Middle name"
                        value="<?= htmlspecialchars($_POST["middle_name"] ?? "") ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        CvSU Student Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="firstname.lastname@cvsu.edu.ph"
                        pattern="[A-Za-z]+\.[A-Za-z]+@cvsu\.edu\.ph"
                        value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                        required
                    >

                    <span class="input-help">
                        Example: lynchariel.esplana@cvsu.edu.ph
                    </span>

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >

                    <span class="input-help">
                        At least 8 characters with uppercase, lowercase, and a number.
                    </span>

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="auth-button"
                >
                    Create Account
                </button>

            </form>


            <!-- BOTTOM -->

            <div class="auth-bottom">

                <p>

                    Already have an account?

                    <a href="login.php">
                        Login
                    </a>

                </p>

                <a
                    href="index.php"
                    class="back-link"
                >
                    ← Back to home
                </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>