<?php

$hobbies = [
    [
        "name" => "Gaming",
        "type" => "Hobby",
        "description" => "Playing video games during free time."
    ],
    [
        "name" => "Programming",
        "type" => "Interest",
        "description" => "Learning programming and exploring software development."
    ],
    [
        "name" => "Music",
        "type" => "Interest",
        "description" => "Listening to different genres of music."
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hobbies & Interests | StudentProfiler</title>
    <link rel="stylesheet" href="hobbies.css">
</head>

<body>

<header class="navbar">
    <div class="nav-container">

        <a href="student_dashboard.php" class="brand">
            <div class="brand-mark">SP</div>
            <div class="brand-text">
                <span class="brand-name">StudentProfiler</span>
                <span class="brand-subtitle">Student Profile Management</span>
            </div>
        </a>

        <nav class="desktop-nav">
            <a href="student_dashboard.php" class="nav-link">Dashboard</a>
            <a href="student_profile.php" class="nav-link active">Profile</a>
            <a href="accomplishments.php" class="nav-link">Accomplishments</a>
            <a href="settings.php" class="nav-link">Settings</a>
        </nav>

        <a href="login.php" class="logout-button">Log out</a>

    </div>
</header>

<main class="page">

    <section class="page-header">

        <div>
            <span class="eyebrow">PERSONAL PROFILE</span>
            <h1>Hobbies & Interests</h1>
            <p>Manage the hobbies and personal interests that you want to include in your student profile.</p>
        </div>

        <button class="primary-button" onclick="openForm()">+ Add Entry</button>

    </section>

    <section class="entries-card">

        <div class="card-heading">
            <div>
                <span class="section-label">MY ENTRIES</span>
                <h2>Hobbies and Interests</h2>
            </div>

            <span class="entry-count">
                <?php echo count($hobbies); ?> Entries
            </span>
        </div>

        <div class="entries-list">

            <?php foreach ($hobbies as $hobby): ?>

            <div class="entry">

                <div class="entry-icon">
                    H
                </div>

                <div class="entry-content">

                    <div class="entry-title-row">
                        <h3><?php echo htmlspecialchars($hobby["name"]); ?></h3>
                        <span class="entry-type"><?php echo htmlspecialchars($hobby["type"]); ?></span>
                    </div>

                    <p><?php echo htmlspecialchars($hobby["description"]); ?></p>

                </div>

                <div class="entry-actions">
                    <button class="edit-action">Edit</button>
                    <button class="remove-action">Remove</button>
                </div>

            </div>

            <?php endforeach; ?>

        </div>

    </section>

</main>

<div class="modal" id="entryModal">

    <div class="modal-box">

        <div class="modal-header">
            <div>
                <span class="section-label">NEW ENTRY</span>
                <h2>Add Hobby or Interest</h2>
            </div>

            <button class="close-button" onclick="closeForm()">×</button>
        </div>

        <form method="POST" action="">

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="Example: Photography" required>
            </div>

            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="Hobby">Hobby</option>
                    <option value="Interest">Interest</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe your hobby or interest"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-button" onclick="closeForm()">Cancel</button>
                <button type="submit" class="save-button">Add Entry</button>
            </div>

        </form>

    </div>

</div>

<footer class="footer">
    <p>StudentProfiler</p>
    <span>Manage your student profile with ease.</span>
</footer>

<script>
function openForm() {
    document.getElementById("entryModal").classList.add("show");
}

function closeForm() {
    document.getElementById("entryModal").classList.remove("show");
}
</script>

</body>
</html>