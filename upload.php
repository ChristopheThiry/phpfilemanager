<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

define('UPLOADS_DIR', __DIR__ . '/uploads');

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $path = UPLOADS_DIR . '/' . $_GET['dir'] . '/' . $file['name'];

    // Security check to prevent path traversal
    if (strpos(realpath(dirname($path)), UPLOADS_DIR) !== 0) {
        header('HTTP/1.1 400 Bad Request');
        exit('Invalid directory');
    }

    if (move_uploaded_file($file['tmp_name'], $path)) {
        echo "File uploaded successfully.";
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        exit('Error uploading file.');
    }
}
