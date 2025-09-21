# PHP File Manager

This is a simple, single-file PHP file manager with password protection.

## Features

-   **Password Protection:** Manually configured password for access.
-   **File & Directory Operations:** List, create, and delete files and directories.
-   **Directory Navigation:** Navigate through directories.
-   **Drag-and-Drop Uploads:** Easily upload files by dragging them into the browser window.

## Requirements

-   PHP >= 5.1

## Setup

1.  **Download:** Place all the files and directories (`index.php`, `upload.php`, `config.php`, `uploads/`) on your web server.
2.  **Configure Password:**
    -   Open the `config.php` file in a text editor.
    -   You need to set a SHA1 hash for your desired password. You can use an online generator or a command-line tool.
    -   For example, to generate a hash for the password "password", you can run this in a Linux/macOS terminal: `echo -n "password" | sha1sum`
    -   Paste the resulting hash into the `$password_hash` variable in `config.php`.
    -   **Important:** The default password is "password". You should change this immediately.
3.  **Permissions:** Ensure that the `uploads` directory is writable by the web server.
    ```bash
    chmod -R 755 uploads
    ```
4.  **Login:** Open the `index.php` file in your web browser. You will be prompted to enter the password you configured in step 2.

## Usage

-   **Navigation:** Click on directory names to navigate into them. Click on the ".." link to go to the parent directory.
-   **Create Directory:** Enter a directory name in the "Create New Directory" form and click "Create Directory".
-   **Create File:** Enter a filename and content in the "Create New File" form and click "Create File".
-   **Delete:** Click the "Delete" button next to a file or directory to remove it. You will be asked for confirmation.
-   **Upload:** Drag and drop files from your computer onto the "Drop files here to upload" area. The files will be uploaded to the current directory.
