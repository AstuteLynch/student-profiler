<?php
// Backend functionality will be added here later.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | StudentProfiler</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
<main class="auth-page">
<section class="auth-card">
    <div class="brand">
        <div class="brand-mark">SP</div>
        <div>
            <h1>StudentProfiler</h1>
            <p>Student profile management system</p>
        </div>
    </div>

    <div class="form-heading">
        <h2>Create your account</h2>
        <p>Set up your account to start building your student profile.</p>
    </div>

    <form action="" method="POST" class="auth-form">
        <div class="form-group">
<label for="full_name">Full name</label>
<input type="text" id="full_name" name="full_name" placeholder="Enter your full name" autocomplete="name" required>
</div>
<div class="form-group">
<label for="student_id">Student ID</label>
<input type="text" id="student_id" name="student_id" placeholder="Enter your student ID" autocomplete="off" required>
</div>
<div class="form-group">
<label for="email">Email address</label>
<input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
</div>
<div class="form-group">
<label for="password">Password</label>
<input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" required>
</div>
<div class="form-group">
<label for="confirm_password">Confirm password</label>
<input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" autocomplete="new-password" required>
</div>
        <button type="submit" class="auth-button">Create account</button>
    </form>

    <p class="switch-page">Already have an account? <a href="login.php">Log in</a></p>
    <a href="index.php" class="back-link">← Back to home</a>
</section>
</main>
</body>
</html>
