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

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if (!isCvsuEmail($email)) {
        $error = "Please use a valid CvSU student email in the format firstname.lastname@cvsu.edu.ph.";
    } elseif ($password === "") {
        $error = "Please enter your password.";
    } else {

        $stmt = $conn->prepare("
            SELECT id, email, password_hash, role, account_status, email_verified
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {

            $error = "Invalid email or password.";

        } elseif ($user["account_status"] !== "active") {

            $error = "This account is not active.";

        } elseif (!$user["email_verified"]) {

            $error = "This email has not been verified yet.";

        } elseif (!password_verify($password, $user["password_hash"])) {

            $error = "Invalid email or password.";

        } else {

            $code = createAuthCode(
                $conn,
                (int) $user["id"],
                "login"
            );

            if (!sendAuthCode($email, $code, "login")) {

                $error = "We could not send the authentication code. Please try again.";

            } else {

                $_SESSION["pending_auth_user_id"] = $user["id"];
                $_SESSION["pending_auth_purpose"] = "login";
                $_SESSION["pending_auth_email"] = $email;
                $_SESSION["pending_auth_role"] = $user["role"];

                header("Location: auth.php");
                exit;
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

    <title>Login | StudentProfiler</title>

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
        href="login.css"
    >

</head>

<body>

<div class="page-wrapper">

    <div class="auth-container">

        <div class="auth-card">

            <!-- BRAND -->

            <div class="auth-brand">

                <a href="index.php" class="brand">

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
                    Welcome back
                </h1>

                <p>
                    Sign in to continue building your student profile.
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

                </div>


                <div class="form-group">

                    <div class="label-row">

                        <label for="password">
                            Password
                        </label>

                        <a
                            href="#"
                            class="forgot-link"
                        >
                            Forgot password?
                        </a>

                    </div>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="auth-button"
                >
                    Login
                </button>

            </form>


            <!-- BOTTOM -->

            <div class="auth-bottom">

                <p>

                    Don't have an account?

                    <a href="register.php">
                        Create an account
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