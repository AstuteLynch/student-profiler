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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CVSWHO</title>
    <link rel="stylesheet" href="login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<main class="auth-page">

    <div class="auth-card">

        <div class="brand-area">
            <div class="brand-logo">C</div>
            <div>
                <div class="brand-name">CVSWHO</div>
                <div class="brand-subtitle">Student Profile Platform</div>
            </div>
        </div>

        <div class="auth-heading">
            <span>WELCOME BACK</span>
            <h1>Login to your account</h1>
            <p>Enter your CvSU student email and password to continue.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="message error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="input-group">
                <label for="email">CvSU Student Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="firstname.lastname@cvsu.edu.ph"
                    pattern="[A-Za-z]+\.[A-Za-z]+@cvsu\.edu\.ph"
                    required
                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                >
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="submit-button">
                Login
            </button>

        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Create an account</a></p>
            <a href="index.php">← Back to Home</a>
        </div>

    </div>

</main>

</body>
</html>