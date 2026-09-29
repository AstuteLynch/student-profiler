<?php

$accomplishment = [
    "title" => "Academic Achievement",
    "category" => "Academic",
    "description" => "Recognized for academic performance and participation in academic activities.",
    "date" => "2025-06-15",
    "organization" => "Cavite State University",
    "document" => "Certificate of Recognition.pdf"
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accomplishment Details | StudentProfiler</title>

    <link rel="stylesheet" href="accomplishment_details.css">
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


    <main class="details-page">

        <section class="page-header">

            <div>

                <span class="eyebrow">
                    ACCOMPLISHMENTS
                </span>

                <h1>
                    Accomplishment Details
                </h1>

                <p>
                    View the complete information and supporting document for this accomplishment.
                </p>

            </div>

            <a href="accomplishments.php" class="back-button">
                ← Back to Accomplishments
            </a>

        </section>


        <div class="details-layout">


            <div class="details-main">


                <section class="details-card">

                    <div class="achievement-heading">

                        <div class="achievement-icon">
                            ✓
                        </div>

                        <div class="achievement-title">

                            <span class="category-label">
                                <?php echo htmlspecialchars($accomplishment["category"]); ?>
                            </span>

                            <h2>
                                <?php echo htmlspecialchars($accomplishment["title"]); ?>
                            </h2>

                            <p>
                                <?php echo date("F d, Y", strtotime($accomplishment["date"])); ?>
                            </p>

                        </div>

                    </div>


                    <div class="information-section">

                        <span class="section-label">
                            ACHIEVEMENT INFORMATION
                        </span>

                        <h3>
                            Description
                        </h3>

                        <p class="description">
                            <?php echo htmlspecialchars($accomplishment["description"]); ?>
                        </p>

                    </div>


                    <div class="information-grid">

                        <div class="information-item">

                            <span>
                                CATEGORY
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($accomplishment["category"]); ?>
                            </strong>

                        </div>


                        <div class="information-item">

                            <span>
                                DATE ACHIEVED
                            </span>

                            <strong>
                                <?php echo date("F d, Y", strtotime($accomplishment["date"])); ?>
                            </strong>

                        </div>


                        <div class="information-item full-width">

                            <span>
                                ORGANIZATION
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($accomplishment["organization"]); ?>
                            </strong>

                        </div>

                    </div>

                </section>


                <section class="certificate-card">

                    <div class="section-heading">

                        <span class="section-label">
                            SUPPORTING DOCUMENT
                        </span>

                        <h2>
                            Uploaded Certificate
                        </h2>

                        <p>
                            View the certificate or supporting document associated with this accomplishment.
                        </p>

                    </div>


                    <div class="document-preview">

                        <div class="document-icon">
                            PDF
                        </div>

                        <div class="document-information">

                            <h3>
                                <?php echo htmlspecialchars($accomplishment["document"]); ?>
                            </h3>

                            <p>
                                Supporting document
                            </p>

                        </div>

                        <a
                            href="#"
                            class="view-document-button"
                        >
                            View Certificate
                        </a>

                    </div>

                </section>


            </div>


            <aside class="details-sidebar">


                <section class="action-card">

                    <span class="section-label">
                        MANAGE
                    </span>

                    <h2>
                        Accomplishment Actions
                    </h2>

                    <div class="action-list">

                        <a
                            href="edit_accomplishment.php"
                            class="edit-button"
                        >
                            Edit Accomplishment
                        </a>

                        <button
                            type="button"
                            class="delete-button"
                            onclick="showDeleteConfirmation()"
                        >
                            Delete Accomplishment
                        </button>

                    </div>

                </section>


                <section class="summary-card">

                    <span class="section-label">
                        SUMMARY
                    </span>

                    <h2>
                        Record Information
                    </h2>

                    <div class="summary-list">

                        <div class="summary-item">

                            <span>
                                Category
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($accomplishment["category"]); ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                Date
                            </span>

                            <strong>
                                <?php echo date("M d, Y", strtotime($accomplishment["date"])); ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                Organization
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($accomplishment["organization"]); ?>
                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                Document
                            </span>

                            <strong>
                                Available
                            </strong>

                        </div>

                    </div>

                </section>


                <section class="privacy-card">

                    <div class="privacy-icon">
                        ✓
                    </div>

                    <div>

                        <h3>
                            Portfolio Visibility
                        </h3>

                        <p>
                            The visibility of this accomplishment can be controlled through your privacy settings.
                        </p>

                    </div>

                </section>


            </aside>

        </div>

    </main>


    <div
        class="delete-overlay"
        id="deleteOverlay"
    >

        <div class="delete-modal">

            <div class="delete-icon">
                !
            </div>

            <h2>
                Delete Accomplishment?
            </h2>

            <p>
                Are you sure you want to delete this accomplishment? This action cannot be undone.
            </p>

            <div class="delete-actions">

                <button
                    type="button"
                    class="cancel-delete"
                    onclick="hideDeleteConfirmation()"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="confirm-delete"
                >
                    Delete
                </button>

            </div>

        </div>

    </div>


    <footer class="footer">

        <p>
            StudentProfiler
        </p>

        <span>
            Manage your student profile with ease.
        </span>

    </footer>


    <script>

        const deleteOverlay = document.getElementById("deleteOverlay");

        function showDeleteConfirmation() {
            deleteOverlay.classList.add("show");
        }

        function hideDeleteConfirmation() {
            deleteOverlay.classList.remove("show");
        }

        deleteOverlay.addEventListener("click", function(event) {

            if (event.target === deleteOverlay) {
                hideDeleteConfirmation();
            }

        });

    </script>

</body>
</html>