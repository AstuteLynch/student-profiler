<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";

$error = "";

/* ALREADY LOGGED IN */

if (isset($_SESSION["user_id"])) {
    $role = $_SESSION["user_role"] ?? $_SESSION["role"] ?? "student";

    if ($role === "administrator") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }

    exit;
}

/* REGISTER */

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
        $error =
            "Please use a CvSU student email in the format firstname.lastname@cvsu.edu.ph.";

    } elseif (strlen($password) < 8) {
        $error =
            "Password must be at least 8 characters.";

    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error =
            "Password must contain at least one uppercase letter.";

    } elseif (!preg_match('/[a-z]/', $password)) {
        $error =
            "Password must contain at least one lowercase letter.";

    } elseif (!preg_match('/[0-9]/', $password)) {
        $error =
            "Password must contain at least one number.";

    } elseif ($password !== $confirmPassword) {
        $error =
            "Passwords do not match.";

    } else {

        $check = $conn->prepare("
            SELECT
                id,
                email_verified,
                role,
                account_status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if (!$check) {
            $error =
                "Unable to check the email address.";

        } else {
            $check->bind_param(
                "s",
                $email
            );

            $check->execute();

            $existing =
                $check
                    ->get_result()
                    ->fetch_assoc();

            $check->close();

            /* EXISTING VERIFIED ACCOUNT */

            if (
                $existing &&
                (int) $existing["email_verified"] === 1
            ) {
                $error =
                    "This email is already registered. Please login instead.";

            /* EXISTING UNVERIFIED ACCOUNT */

            } elseif ($existing) {

                if (
                    ($existing["role"] ?? "") !== "student"
                ) {
                    $error =
                        "This email cannot be registered as a student.";

                } else {
                    $userId =
                        (int) $existing["id"];

                    $passwordHash =
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                    $update = $conn->prepare("
                        UPDATE users
                        SET
                            password_hash = ?,
                            account_status = 'pending',
                            email_verified = 0
                        WHERE
                            id = ?
                            AND role = 'student'
                    ");

                    if (!$update) {
                        $error =
                            "Unable to update the account.";

                    } else {
                        $update->bind_param(
                            "si",
                            $passwordHash,
                            $userId
                        );

                        if (!$update->execute()) {
                            $error =
                                "Unable to update the account.";
                        }

                        $update->close();
                    }

                    if ($error === "") {

                        $profile = $conn->prepare("
                            INSERT INTO student_profiles (
                                user_id,
                                first_name,
                                middle_name,
                                last_name
                            )
                            VALUES (?, ?, ?, ?)

                            ON DUPLICATE KEY UPDATE
                                first_name = VALUES(first_name),
                                middle_name = VALUES(middle_name),
                                last_name = VALUES(last_name)
                        ");

                        if (!$profile) {
                            $error =
                                "Unable to update the student profile.";

                        } else {
                            $profile->bind_param(
                                "isss",
                                $userId,
                                $firstName,
                                $middleName,
                                $lastName
                            );

                            if (!$profile->execute()) {
                                $error =
                                    "Unable to update the student profile.";
                            }

                            $profile->close();
                        }
                    }

                    if ($error === "") {

                        $code = createAuthCode(
                            $conn,
                            $userId,
                            "registration"
                        );

                        /* DEVELOPMENT ONLY */

                        $_SESSION["dev_auth_code"] =
                            $code;

                        $_SESSION["pending_auth_user_id"] =
                            $userId;

                        $_SESSION["pending_auth_purpose"] =
                            "registration";

                        $_SESSION["pending_auth_email"] =
                            $email;

                        $_SESSION["pending_auth_role"] =
                            "student";

                        header(
                            "Location: auth.php"
                        );

                        exit;
                    }
                }

            /* NEW ACCOUNT */

            } else {

                $passwordHash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                $stmt = $conn->prepare("
                    INSERT INTO users (
                        email,
                        password_hash,
                        role,
                        account_status,
                        email_verified
                    )
                    VALUES (
                        ?,
                        ?,
                        'student',
                        'pending',
                        0
                    )
                ");

                if (!$stmt) {
                    $error =
                        "We could not create your account.";

                } else {
                    $stmt->bind_param(
                        "ss",
                        $email,
                        $passwordHash
                    );

                    if (!$stmt->execute()) {
                        $error =
                            "We could not create your account. Please try again.";

                    } else {
                        $userId =
                            (int) $stmt->insert_id;
                    }

                    $stmt->close();
                }

                if ($error === "") {

                    $profile = $conn->prepare("
                        INSERT INTO student_profiles (
                            user_id,
                            first_name,
                            middle_name,
                            last_name
                        )
                        VALUES (?, ?, ?, ?)
                    ");

                    if (!$profile) {
                        $error =
                            "Unable to create the student profile.";

                    } else {
                        $profile->bind_param(
                            "isss",
                            $userId,
                            $firstName,
                            $middleName,
                            $lastName
                        );

                        if (!$profile->execute()) {
                            $error =
                                "Unable to create the student profile.";
                        }

                        $profile->close();
                    }
                }

                if ($error === "") {

                    $profileSettings =
                        $conn->prepare("
                            INSERT INTO profile_settings (
                                user_id
                            )
                            VALUES (?)
                        ");

                    if ($profileSettings) {
                        $profileSettings->bind_param(
                            "i",
                            $userId
                        );

                        $profileSettings->execute();
                        $profileSettings->close();
                    }

                    $privacySettings =
                        $conn->prepare("
                            INSERT INTO privacy_settings (
                                user_id
                            )
                            VALUES (?)
                        ");

                    if ($privacySettings) {
                        $privacySettings->bind_param(
                            "i",
                            $userId
                        );

                        $privacySettings->execute();
                        $privacySettings->close();
                    }

                    $code = createAuthCode(
                        $conn,
                        $userId,
                        "registration"
                    );

                    /* DEVELOPMENT ONLY */

                    $_SESSION["dev_auth_code"] =
                        $code;

                    $_SESSION["pending_auth_user_id"] =
                        $userId;

                    $_SESSION["pending_auth_purpose"] =
                        "registration";

                    $_SESSION["pending_auth_email"] =
                        $email;

                    $_SESSION["pending_auth_role"] =
                        "student";

                    header(
                        "Location: auth.php"
                    );

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

    <title>
        Register | CVSWHO
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

    <link
        rel="stylesheet"
        href="register.css"
    >

</head>

<body>

<div class="page-wrapper">

    <div class="auth-container">

        <div class="auth-brand">

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

        </div>


        <div class="auth-card">

            <div class="auth-header">

                <span class="section-label">
                    CREATE YOUR ACCOUNT
                </span>

                <h1>
                    Register as a CvSU student
                </h1>

                <p>
                    Create your CVSWHO account using your official CvSU student email.
                </p>

            </div>


            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


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
                            value="<?= htmlspecialchars(
                                $_POST["first_name"] ?? ""
                            ) ?>"
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
                            value="<?= htmlspecialchars(
                                $_POST["last_name"] ?? ""
                            ) ?>"
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
                        value="<?= htmlspecialchars(
                            $_POST["middle_name"] ?? ""
                        ) ?>"
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
                        value="<?= htmlspecialchars(
                            $_POST["email"] ?? ""
                        ) ?>"
                        required
                    >

                    <span class="input-help">
                        Example: firstname.lastname@cvsu.edu.ph
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


            <div class="auth-divider">
                <span>
                    OR
                </span>
            </div>


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


        <div class="auth-security">

            <span class="security-icon">
                ✓
            </span>

            <div>

                <strong>
                    CvSU Student Verification
                </strong>

                <p>
                    Your official CvSU email will be verified before your account is activated.
                </p>

            </div>

        </div>

    </div>

</div>

</body>

</html>