<?php
session_start();
require_once 'config.php';

// Function to format file permissions
function format_permissions($perms) {
    if (($perms & 0xC000) == 0xC000) { $info = 's'; }
    elseif (($perms & 0xA000) == 0xA000) { $info = 'l'; }
    elseif (($perms & 0x8000) == 0x8000) { $info = '-'; }
    elseif (($perms & 0x6000) == 0x6000) { $info = 'b'; }
    elseif (($perms & 0x4000) == 0x4000) { $info = 'd'; }
    elseif (($perms & 0x2000) == 0x2000) { $info = 'c'; }
    elseif (($perms & 0x1000) == 0x1000) { $info = 'p'; }
    else { $info = 'u'; }

    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

    return $info;
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
    global $password_hash;
    // Note: sha1 is not a secure hashing algorithm. This is for compatibility with very old PHP versions.
    return sha1($password) === $password_hash;
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

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

$current_dir = isset($_GET['dir']) ? realpath(UPLOADS_DIR . '/' . $_GET['dir']) : UPLOADS_DIR;

// Security check to ensure the user stays within the uploads directory
if (!$current_dir || strpos($current_dir, UPLOADS_DIR) !== 0) {
    $current_dir = UPLOADS_DIR;
}

// Handle file and directory creation
if (isset($_POST['delete'])) {
    $path_to_delete = realpath(UPLOADS_DIR . '/' . $_POST['path']);
    if ($path_to_delete && strpos($path_to_delete, UPLOADS_DIR) === 0) {
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
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        .logout { float: right; }
        .location-bar { display: flex; align-items: center; background-color: #f8f9fa; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .location-path { flex-grow: 1; font-family: monospace; }
        .action-button { background-color: #007bff; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; margin-right: 5px; }
        .action-button:hover { background-color: #0056b3; }
        .action-button-delete { background-color: #dc3545; }
        .action-button-delete:hover { background-color: #c82333; }
    </style>
</head>
<body>
    <h1>File Manager</h1>
    <a href="?logout=true" class="logout">Logout</a>

    <div class="location-bar">
        <div class="location-path">Location: <?php echo htmlspecialchars($current_dir); ?></div>
        <?php
        if ($current_dir !== UPLOADS_DIR) {
            $parent_dir = dirname($current_dir);
            $relative_parent = str_replace(UPLOADS_DIR, '', $parent_dir);
            echo "<a href='?dir=" . urlencode($relative_parent) . "' class='action-button'>Parent Directory</a>";
        }
        ?>
    </div>

    <table>
        <thead>
            <tr>
                <th></th>
                <th>Name</th>
                <th>Last Modified</th>
                <th>Type</th>
                <th>Size</th>
                <th>Perms</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $files = scandir($current_dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = $current_dir . '/' . $file;
                $is_dir = is_dir($path);
                $relative_path = ltrim(str_replace(UPLOADS_DIR, '', $path), '/');
            ?>
            <tr>
                <td><?php echo $is_dir ? '&#128193;' : '&#128196;'; ?></td>
                <td>
                    <?php if ($is_dir): ?>
                        <a href="?dir=<?php echo urlencode($relative_path); ?>"><?php echo htmlspecialchars($file); ?></a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($file); ?>
                    <?php endif; ?>
                </td>
                <td><?php echo date("Y-m-d H:i:s", filemtime($path)); ?></td>
                <td><?php echo $is_dir ? 'Directory' : pathinfo($path, PATHINFO_EXTENSION); ?></td>
                <td><?php echo $is_dir ? '' : filesize($path) . ' B'; ?></td>
                <td><?php echo format_permissions(fileperms($path)); ?></td>
                <td>
                    <button class="action-button">Chmod</button>
                    <button class="action-button">Move</button>
                    <button class="action-button">Rename</button>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="path" value="<?php echo urlencode($relative_path); ?>">
                        <button type="submit" name="delete" class="action-button action-button-delete" onclick="return confirm('Are you sure?');">Delete</button>
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
