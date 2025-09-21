<?php
require_once 'lib/password.php';

session_start();

// Define a directory to store the password file
define('DATA_DIR', __DIR__ . '/data');
define('PASSWORD_FILE', DATA_DIR . '/password.php');

// Function to check if a password is set
function isPasswordSet() {
    return file_exists(PASSWORD_FILE);
}

// Function to set the password
function setPassword($password) {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    file_put_contents(PASSWORD_FILE, "<?php\n// Silence is golden.\n");
    file_put_contents(PASSWORD_FILE, "<?php return '" . $hash . "';\n", LOCK_EX);
}

// Function to recursively delete a directory
function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

// Function to check the password
function checkPassword($password) {
    if (!isPasswordSet()) {
        return false;
    }
    $hash = include(PASSWORD_FILE);
    return password_verify($password, $hash);
}

// Handle password setup
if (!isPasswordSet()) {
    if (isset($_POST['set_password'])) {
        setPassword($_POST['password']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Password</title>
</head>
<body>
    <h1>Setup Your Password</h1>
    <form method="post">
        <label for="password">Enter a new password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" name="set_password">Set Password</button>
    </form>
</body>
</html>
<?php
    exit;
}

// Handle login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_POST['login'])) {
        if (checkPassword($_POST['password'])) {
            $_SESSION['loggedin'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = 'Invalid password!';
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
    <form method="post">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" name="login">Login</button>
    </form>
</body>
</html>
<?php
    exit;
}

// Main file manager interface
define('UPLOADS_DIR', __DIR__ . '/uploads');

$current_dir = isset($_GET['dir']) ? realpath(UPLOADS_DIR . '/' . $_GET['dir']) : UPLOADS_DIR;

// Security check to ensure the user stays within the uploads directory
if (strpos($current_dir, UPLOADS_DIR) !== 0) {
    $current_dir = UPLOADS_DIR;
}

// Handle file and directory creation
if (isset($_POST['delete'])) {
    $path_to_delete = realpath(UPLOADS_DIR . '/' . $_POST['path']);
    if (strpos($path_to_delete, UPLOADS_DIR) === 0) {
        if (is_dir($path_to_delete)) {
            deleteDir($path_to_delete);
        } else {
            unlink($path_to_delete);
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode(ltrim(str_replace(UPLOADS_DIR, '', $current_dir), '/')));
    exit;
}

if (isset($_POST['create_dir'])) {
    $new_dir = $current_dir . '/' . $_POST['dir_name'];
    if (!file_exists($new_dir)) {
        mkdir($new_dir, 0755, true);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode(ltrim(str_replace(UPLOADS_DIR, '', $current_dir), '/')));
    exit;
}

if (isset($_POST['create_file'])) {
    $new_file = $current_dir . '/' . $_POST['file_name'];
    file_put_contents($new_file, $_POST['file_content']);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode(ltrim(str_replace(UPLOADS_DIR, '', $current_dir), '/')));
    exit;
}

$path_parts = explode('/', str_replace(UPLOADS_DIR, '', $current_dir));
$path_parts = array_filter($path_parts);

?>
<!DOCTYPE html>
<html>
<head>
    <title>File Manager</title>
    <style>
        body { font-family: sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; }
        a:hover { text-decoration: underline; }
        .logout { float: right; }
    </style>
</head>
<body>
    <h1>File Manager</h1>
    <a href="?logout=true" class="logout">Logout</a>

    <p>Current Directory: /<?php echo implode('/', $path_parts); ?></p>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Parent directory link
            if ($current_dir !== UPLOADS_DIR) {
                $parent_dir = dirname($current_dir);
                $relative_parent = str_replace(UPLOADS_DIR, '', $parent_dir);
                echo "<tr><td><a href='?dir={$relative_parent}'>..</a></td><td>Parent Directory</td><td></td><td></td></tr>";
            }

            $files = scandir($current_dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = $current_dir . '/' . $file;
                $is_dir = is_dir($path);
                $relative_path = ltrim(str_replace(UPLOADS_DIR, '', $path), '/');
            ?>
            <tr>
                <td>
                    <?php if ($is_dir): ?>
                        <a href="?dir=<?php echo urlencode($relative_path); ?>"><?php echo $file; ?></a>
                    <?php else: ?>
                        <?php echo $file; ?>
                    <?php endif; ?>
                </td>
                <td><?php echo $is_dir ? 'Directory' : 'File'; ?></td>
                <td><?php echo $is_dir ? '' : filesize($path) . ' B'; ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="path" value="<?php echo urlencode($relative_path); ?>">
                        <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this?');">Delete</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <hr>

    <h3>Create New Directory</h3>
    <form method="post">
        <input type="text" name="dir_name" required>
        <button type="submit" name="create_dir">Create Directory</button>
    </form>

    <h3>Create New File</h3>
    <form method="post">
        <label for="file_name">Filename:</label><br>
        <input type="text" id="file_name" name="file_name" required><br>
        <label for="file_content">Content:</label><br>
        <textarea id="file_content" name="file_content" rows="5" cols="50"></textarea><br>
        <button type="submit" name="create_file">Create File</button>
    </form>

    <hr>

    <h3>Upload Files</h3>
    <div id="drop-zone" style="border: 2px dashed #ccc; padding: 20px; text-align: center;">
        Drop files here to upload
    </div>
    <div id="upload-progress"></div>


    <?php
        if (isset($_GET['logout'])) {
            session_destroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    ?>

    <script>
    const dropZone = document.getElementById('drop-zone');
    const uploadProgress = document.getElementById('upload-progress');
    const currentDir = '<?php echo urlencode(ltrim(str_replace(UPLOADS_DIR, '', $current_dir), '/')); ?>';

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#333';
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#ccc';
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#ccc';

        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    function handleFiles(files) {
        for (const file of files) {
            uploadFile(file);
        }
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `upload.php?dir=${currentDir}`, true);

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                uploadProgress.innerHTML = `Uploading ${file.name}: ${percentComplete.toFixed(2)}%`;
            }
        };

        xhr.onload = () => {
            if (xhr.status === 200) {
                uploadProgress.innerHTML = `Successfully uploaded ${file.name}`;
                setTimeout(() => location.reload(), 1000);
            } else {
                uploadProgress.innerHTML = `Error uploading ${file.name}: ${xhr.responseText}`;
            }
        };

        xhr.send(formData);
    }
    </script>
</body>
</html>
