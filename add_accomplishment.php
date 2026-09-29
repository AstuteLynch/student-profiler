<?php

$categories = [
    "Academic",
    "Competition",
    "Project",
    "Organization",
    "Certification",
    "Other"
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Accomplishment | StudentProfiler</title>

    <link rel="stylesheet" href="add_accomplishment.css">
</head>

<body>

    <header class="navbar">

        <div class="nav-container">

            <a href="student_dashboard.php" class="brand">

                <div class="brand-mark">
                    SP
                </div>

                <div class="brand-text">

                    <span class="brand-name">
                        StudentProfiler
                    </span>

                    <span class="brand-subtitle">
                        Student Profile Management
                    </span>

                </div>

            </a>

            <nav class="desktop-nav">

                <a href="student_dashboard.php" class="nav-link">
                    Dashboard
                </a>

                <a href="student_profile.php" class="nav-link">
                    Profile
                </a>

                <a href="accomplishments.php" class="nav-link active">
                    Accomplishments
                </a>

                <a href="settings.php" class="nav-link">
                    Settings
                </a>

            </nav>

            <a href="login.php" class="logout-button">
                Log out
            </a>

        </div>

    </header>


    <main class="add-page">

        <section class="page-header">

            <div>

                <span class="eyebrow">
                    ACCOMPLISHMENTS
                </span>

                <h1>
                    Add Accomplishment
                </h1>

                <p>
                    Add an achievement, certificate, project, competition, or other accomplishment to your student profile.
                </p>

            </div>

            <a href="accomplishments.php" class="back-button">
                ← Back to Accomplishments
            </a>

        </section>


        <form
            action=""
            method="POST"
            enctype="multipart/form-data"
            class="accomplishment-form"
        >


            <section class="form-card">

                <div class="section-heading">

                    <span class="section-label">
                        ACHIEVEMENT DETAILS
                    </span>

                    <h2>
                        Accomplishment Information
                    </h2>

                    <p>
                        Provide the basic information about your achievement.
                    </p>

                </div>


                <div class="form-grid">


                    <div class="form-group full-width">

                        <label for="achievement_title">
                            Achievement Title
                        </label>

                        <input
                            type="text"
                            id="achievement_title"
                            name="achievement_title"
                            placeholder="Enter the title of your achievement"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="category">
                            Category
                        </label>

                        <select
                            id="category"
                            name="category"
                            required
                        >

                            <option value="">
                                Select category
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="date_achieved">
                            Date Achieved
                        </label>

                        <input
                            type="date"
                            id="date_achieved"
                            name="date_achieved"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label for="organization">
                            Organization
                        </label>

                        <input
                            type="text"
                            id="organization"
                            name="organization"
                            placeholder="Enter the organization, school, company, or institution"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            placeholder="Describe your achievement, accomplishment, or experience"
                            required
                        ></textarea>

                        <small>
                            Provide a short description that explains what you achieved.
                        </small>

                    </div>


                </div>

            </section>


            <section class="form-card">

                <div class="section-heading">

                    <span class="section-label">
                        SUPPORTING DOCUMENT
                    </span>

                    <h2>
                        Certificate or Document
                    </h2>

                    <p>
                        Upload a supporting document such as a certificate or proof of achievement.
                    </p>

                </div>


                <div class="upload-area">

                    <div class="upload-icon">
                        ↑
                    </div>

                    <div class="upload-content">

                        <h3>
                            Upload Supporting Document
                        </h3>

                        <p>
                            Select a certificate or supporting file from your device.
                        </p>

                        <label for="supporting_document" class="upload-button">
                            Choose File
                        </label>

                        <input
                            type="file"
                            id="supporting_document"
                            name="supporting_document"
                            accept=".pdf,.jpg,.jpeg,.png"
                        >

                        <span
                            class="file-name"
                            id="fileName"
                        >
                            No file selected
                        </span>

                        <small>
                            Accepted formats: PDF, JPG, JPEG, PNG
                        </small>

                    </div>

                </div>

            </section>


            <section class="privacy-notice">

                <div class="privacy-icon">
                    ✓
                </div>

                <div>

                    <h3>
                        Privacy Reminder
                    </h3>

                    <p>
                        Your accomplishment may appear on your Digital Portfolio depending on your profile privacy settings.
                    </p>

                </div>

            </section>


            <div class="form-actions">

                <a
                    href="accomplishments.php"
                    class="cancel-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="save-button"
                >
                    Add Accomplishment
                </button>

            </div>


        </form>

    </main>


    <footer class="footer">

        <p>
            StudentProfiler
        </p>

        <span>
            Manage your student profile with ease.
        </span>

    </footer>


    <script>

        const fileInput = document.getElementById("supporting_document");
        const fileName = document.getElementById("fileName");

        fileInput.addEventListener("change", function () {

            if (this.files.length > 0) {

                fileName.textContent = this.files[0].name;

            } else {

                fileName.textContent = "No file selected";

            }

        });

    </script>

</body>
</html>