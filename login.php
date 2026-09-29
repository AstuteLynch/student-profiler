<?php
// Backend functionality will be added here later.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CVSWHO</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<main class="auth-page">
<section class="auth-card">
    <div class="brand">
        <div class="brand-mark">C</div>
        <div>
            <h1>CVSWHO</h1>
            <p>Student profile management system</p>
        </div>
    </div>

    <div class="form-heading">
        <h2>Welcome back</h2>
        <p>Sign in to continue to your student profile.</p>
    </div>

    <form action="" method="POST" class="auth-form">
        <div class="form-group">
<label for="email">Email address</label>
<input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
</div>
        <button type="submit" class="auth-button">Log in</button>
    </form>

    <p class="switch-page">Don't have an account? <a href="register.php">Create an account</a></p>
    <a href="#" class="forgotpass-link">forgot password?</a>
    <a href="index.php" class="back-link">← Back to home</a>
</section>
</main>
</body>
</html>
