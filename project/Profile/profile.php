<?php
session_start();

/* Example user data */
$user = [
    "id" => 1,
    "name" => "Minal",
    "email" => "gminal2045@gmail.com",
    "profile_image" => ""
];

$uploads = [
    [
        "id" => 1,
        "title" => "Data Science",
        "subject" => "Computer Science",
        "category" => "Data Science",
        "date" => "Feb 15, 2026",
        "downloads" => 0,
        "file" => "uploads/datascience.pdf"
    ]
];

$saved = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>NoteStation Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        /* profile card */
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ddd;
        }

        /* profile image */
        .profile-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            cursor: pointer;
            transition: 0.3s;
        }

        .profile-icon:hover {
            background: #d0d7ff;
        }

        /* upload cards */
        .upload-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #ddd;
            margin-top: 15px;
            transition: 0.3s;
            cursor: pointer;
        }

        .upload-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* clickable email */
        .email-link {
            text-decoration: none;
        }

        .email-link:hover {
            text-decoration: underline;
        }

        /* tabs */
        .tab {
            background: #e9ecef;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
        }

        .active-tab {
            background: white;
            border: 1px solid #ccc;
            font-weight: bold;
        }
    </style>

</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar bg-white border-bottom">

        <div class="container-fluid">

            <a class="navbar-brand text-primary">
                <i class="bi bi-journal-text"></i> NoteStation
            </a>

            <div class="d-flex gap-4">

                <a class="nav-link">Browse</a>

                <a class="nav-link">Upload</a>

                <a class="nav-link active">
                    <i class="bi bi-person"></i> Profile
                </a>

                <button class="btn btn-outline-secondary">
                    Sign Out
                </button>

            </div>

        </div>

    </nav>


    <div class="container-fluid">

        <div class="col-lg-10">

            <!-- PROFILE -->
            <div class="profile-card d-flex align-items-center">

                <!-- Clickable Profile Image -->
                <label for="profileUpload">

                    <?php if ($user['profile_image'] != "") { ?>

                        <img src="<?php echo $user['profile_image']; ?>" class="profile-icon">

                    <?php } else { ?>

                        <div class="profile-icon">
                            <?php echo strtoupper($user['name'][0]); ?>
                        </div>

                    <?php } ?>

                </label>

                <input type="file" id="profileUpload" hidden onchange="uploadProfile()">

                <div class="ms-3">

                    <h4>
                        <?php echo $user['name']; ?>
                    </h4>

                    <a href="mailto:<?php echo $user['email']; ?>" class="text-muted email-link">

                        <?php echo $user['email']; ?>

                    </a>

                    <div class="mt-2">

                        <span class="me-3 clickable" onclick="showUploads()">

                            <i class="bi bi-upload"></i>
                            <?php echo count($uploads); ?> uploads

                        </span>

                        <span class="clickable" onclick="showSaved()">

                            <i class="bi bi-bookmark"></i>
                            <?php echo count($saved); ?> saved

                        </span>

                    </div>

                </div>

            </div>


            <!-- TABS -->
            <div class="row mt-3">

                <div class="col-md-6">
                    <div class="tab active-tab" id="uploadTab" onclick="showUploads()">

                        My Uploads

                    </div>
                </div>

                <div class="col-md-6">
                    <div class="tab" id="savedTab" onclick="showSaved()">

                        Saved Items

                    </div>
                </div>

            </div>


            <!-- UPLOAD SECTION -->
            <div id="uploadSection">

                <?php foreach ($uploads as $file) { ?>

                    <div class="upload-card">

                        <div class="d-flex justify-content-between">

                            <div onclick="openFile('<?php echo $file['file']; ?>')">

                                <h5>
                                    <?php echo $file['title']; ?>
                                </h5>

                                <span class="badge bg-secondary">
                                    <?php echo $file['subject']; ?>
                                </span>

                                <span class="badge bg-light text-dark">
                                    <?php echo $file['category']; ?>
                                </span>

                                <br>

                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i>
                                    <?php echo $file['date']; ?>
                                </small>

                            </div>


                            <div>

                                <button class="btn btn-sm btn-primary" onclick="openFile('<?php echo $file['file']; ?>')">

                                    View

                                </button>

                                <button class="btn btn-sm btn-danger" onclick="deleteFile(<?php echo $file['id']; ?>)">

                                    Delete

                                </button>

                            </div>

                        </div>

                    </div>

                <?php } ?>

            </div>


            <!-- SAVED -->
            <div id="savedSection" style="display:none;">

                <div class="upload-card text-muted text-center">
                    No saved items
                </div>

            </div>

        </div>

    </div>


    <script>

        /* switch tabs */
        function showUploads() {

            document.getElementById("uploadSection").style.display = "block";
            document.getElementById("savedSection").style.display = "none";

            document.getElementById("uploadTab").classList.add("active-tab");
            document.getElementById("savedTab").classList.remove("active-tab");

        }

        function showSaved() {

            document.getElementById("uploadSection").style.display = "none";
            document.getElementById("savedSection").style.display = "block";

            document.getElementById("savedTab").classList.add("active-tab");
            document.getElementById("uploadTab").classList.remove("active-tab");

        }

        /* open file */
        function openFile(file) {

            window.open(file, "_blank");

        }

        /* delete */
        function deleteFile(id) {

            if (confirm("Delete this file?")) {

                alert("Delete logic here for ID: " + id);

            }

        }

        /* upload profile */
        function uploadProfile() {

            alert("Profile upload logic here");

        }

    </script>

</body>

</html>