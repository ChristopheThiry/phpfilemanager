# PHP File Manager

This is a simple, single-file PHP file manager with password protection.

## Features

-   **Password Protection:** Set a password on first use.
-   **File & Directory Operations:** List, create, and delete files and directories.
-   **Directory Navigation:** Navigate through directories.
-   **Drag-and-Drop Uploads:** Easily upload files by dragging them into the browser window.

## Setup

1.  **Download:** Place the `index.php`, `upload.php`, and the `data` and `uploads` directories on your web server.
2.  **Permissions:** Ensure that the `data` and `uploads` directories are writable by the web server.
    ```bash
    chmod -R 755 data
    chmod -R 755 uploads
    ```
3.  **First Run:** Open the `index.php` file in your web browser. You will be prompted to set a password. This password will be stored securely in the `data` directory.
4.  **Login:** After setting the password, you will be redirected to the login page. Enter the password you just created to access the file manager.

## Usage

-   **Navigation:** Click on directory names to navigate into them. Click on the ".." link to go to the parent directory.
-   **Create Directory:** Enter a directory name in the "Create New Directory" form and click "Create Directory".
-   **Create File:** Enter a filename and content in the "Create New File" form and click "Create File".
-   **Delete:** Click the "Delete" button next to a file or directory to remove it. You will be asked for confirmation.
-   **Upload:** Drag and drop files from your computer onto the "Drop files here to upload" area. The files will be uploaded to the current directory.
