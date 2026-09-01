<?php
// Handle file download
if (isset($_GET['download']) && isset($_GET['dir'])) {
    $dir = $_GET['dir'];
    if (is_dir($dir)) {
        $dir = realpath($dir);
    }
    $file = basename($_GET['download']);
    $file_path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
    
    if (is_file($file_path) && is_readable($file_path)) {
        $file_size = filesize($file_path);
        $file_name = basename($file_path);
        
        // Set appropriate headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Read and output the file
        readfile($file_path);
        exit;
    }
    header("HTTP/1.0 404 Not Found");
    echo json_encode(['error' => 'File not found or not readable']);
    exit;
}

if (isset($_GET['view']) && isset($_GET['dir'])) {
    $dir = $_GET['dir'];
    if (is_dir($dir)) {
        $dir = realpath($dir);
    }
    $file = basename($_GET['view']);
    $file_path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
    
    if (is_file($file_path) && is_readable($file_path)) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file_path);
            finfo_close($finfo);
        } else {
            $mime = mime_content_type($file_path);
        }
        
        $response = ['mime' => $mime];
        $filesize = filesize($file_path);
        
        if (strpos($mime, 'image/') === 0) {
            if ($filesize > 10 * 1024 * 1024) {
                $response['is_image'] = false;
                $response['content'] = 'Image is too large to display inline (max 10MB).';
            } else {
                $base64 = base64_encode(file_get_contents($file_path));
                $response['is_image'] = true;
                $response['content'] = 'data:' . $mime . ';base64,' . $base64;
            }
        } else {
            $response['is_image'] = false;
            if ($filesize > 5 * 1024 * 1024) {
                $response['content'] = 'File is too large to display inline (max 5MB).';
            } else {
                $content = file_get_contents($file_path);
                if (mb_check_encoding($content, 'UTF-8') || preg_match('//u', $content)) {
                    $response['content'] = htmlspecialchars($content);
                } else {
                    $response['content'] = 'Cannot display binary file content.';
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    header("HTTP/1.0 404 Not Found");
    echo json_encode(['error' => 'File not found or not readable']);
    exit;
}

// Handle terminal commands
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminal_command'])) {
    header('Content-Type: application/json');
    
    $command = trim($_POST['terminal_command']);
    $current_dir = isset($_POST['current_dir']) ? $_POST['current_dir'] : getcwd();
    
    if (is_dir($current_dir)) {
        $current_dir = realpath($current_dir);
    } else {
        $current_dir = getcwd();
    }
    
    $output = '';
    $error = '';
    
    // Parse command
    $parts = preg_split('/\s+/', $command, 2);
    $cmd = strtolower($parts[0]);
    $args = isset($parts[1]) ? $parts[1] : '';
    
    try {
        switch($cmd) {
            case 'pwd':
                $output = $current_dir;
                break;
            
            case 'ls':
            case 'dir':
                $list_dir = trim($args) ? $current_dir . DIRECTORY_SEPARATOR . trim($args) : $current_dir;
                if (is_dir($list_dir)) {
                    $list_dir = realpath($list_dir);
                    if ($handle = opendir($list_dir)) {
                        $files = [];
                        while (false !== ($file = readdir($handle))) {
                            if ($file != "." && $file != "..") {
                                $files[] = $file;
                            }
                        }
                        closedir($handle);
                        sort($files);
                        foreach ($files as $file) {
                            $full_path = $list_dir . DIRECTORY_SEPARATOR . $file;
                            $type = is_dir($full_path) ? 'd' : '-';
                            $perms = substr(sprintf('%o', fileperms($full_path)), -4);
                            $size = is_file($full_path) ? filesize($full_path) : '-';
                            $output .= sprintf("%-10s %10s %s\n", $type . $perms, $size, $file);
                        }
                    }
                } else {
                    $error = "Directory not found: " . htmlspecialchars($list_dir);
                }
                break;
            
            case 'chmod':
                $chmod_parts = preg_split('/\s+/', $args);
                $is_recursive = false;
                $perms = null;
                $file = null;
                
                // Check for -R flag
                if (count($chmod_parts) >= 2 && $chmod_parts[0] === '-R') {
                    $is_recursive = true;
                    $perms = $chmod_parts[1];
                    $file = $chmod_parts[2] ?? null;
                } elseif (count($chmod_parts) >= 2) {
                    $perms = $chmod_parts[0];
                    $file = $chmod_parts[1];
                }
                
                if ($perms && $file) {
                    $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($file_path)) {
                        $changed_count = 0;
                        
                        // Function to recursively change permissions
                        function changePermissionsRecursive($path, $perms, &$count) {
                            if (@chmod($path, octdec($perms))) {
                                $count++;
                            }
                            if (is_dir($path)) {
                                $items = @scandir($path);
                                if ($items) {
                                    foreach ($items as $item) {
                                        if ($item !== '.' && $item !== '..') {
                                            $subpath = $path . DIRECTORY_SEPARATOR . $item;
                                            changePermissionsRecursive($subpath, $perms, $count);
                                        }
                                    }
                                }
                            }
                        }
                        
                        if ($is_recursive) {
                            if (is_dir($file_path)) {
                                changePermissionsRecursive($file_path, $perms, $changed_count);
                                $output = "Permissions changed to " . $perms . " for " . htmlspecialchars($file) . " and all contents (" . $changed_count . " items)";
                            } else {
                                $error = "Cannot use -R on a file. Use 'chmod " . $perms . " " . htmlspecialchars($file) . "'";
                            }
                        } else {
                            if (chmod($file_path, octdec($perms))) {
                                $output = "Permissions changed to " . $perms . " for " . htmlspecialchars($file);
                            } else {
                                $error = "Failed to change permissions";
                            }
                        }
                    } else {
                        $error = "File not found: " . htmlspecialchars($file);
                    }
                } else {
                    $error = "Usage: chmod [-R] <permissions> <file>";
                }
                break;
            
            case 'chown':
                $chown_parts = preg_split('/\s+/', $args);
                if (count($chown_parts) >= 2) {
                    $owner = $chown_parts[0];
                    $file = $chown_parts[1];
                    $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($file_path)) {
                        if (function_exists('chown')) {
                            if (@chown($file_path, $owner)) {
                                $output = "Owner changed to " . htmlspecialchars($owner) . " for " . htmlspecialchars($file);
                            } else {
                                $error = "Failed to change owner (may require root privileges)";
                            }
                        } else {
                            $error = "chown function not available on this system";
                        }
                    } else {
                        $error = "File not found: " . htmlspecialchars($file);
                    }
                } else {
                    $error = "Usage: chown <owner> <file>";
                }
                break;
            
            case 'cat':
                $file = trim($args);
                $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
                if (is_file($file_path) && is_readable($file_path)) {
                    $size = filesize($file_path);
                    if ($size > 1024 * 1024) {
                        $error = "File too large to display (max 1MB)";
                    } else {
                        $output = file_get_contents($file_path);
                    }
                } else {
                    $error = "File not found or not readable: " . htmlspecialchars($file);
                }
                break;
            
            case 'touch':
                $file = trim($args);
                $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
                if (!file_exists($file_path)) {
                    if (touch($file_path)) {
                        $output = "File created: " . htmlspecialchars($file);
                    } else {
                        $error = "Failed to create file";
                    }
                } else {
                    if (touch($file_path)) {
                        $output = "File updated: " . htmlspecialchars($file);
                    } else {
                        $error = "Failed to update file";
                    }
                }
                break;
            
            case 'mkdir':
                $dir = trim($args);
                $dir_path = $current_dir . DIRECTORY_SEPARATOR . $dir;
                if (!is_dir($dir_path)) {
                    if (mkdir($dir_path)) {
                        $output = "Directory created: " . htmlspecialchars($dir);
                    } else {
                        $error = "Failed to create directory";
                    }
                } else {
                    $error = "Directory already exists";
                }
                break;
            
            case 'rmdir':
                $dir = trim($args);
                $dir_path = $current_dir . DIRECTORY_SEPARATOR . $dir;
                if (is_dir($dir_path)) {
                    if (@rmdir($dir_path)) {
                        $output = "Directory removed: " . htmlspecialchars($dir);
                    } else {
                        $error = "Failed to remove directory (must be empty)";
                    }
                } else {
                    $error = "Directory not found: " . htmlspecialchars($dir);
                }
                break;
            
            case 'rm':
                $file = trim($args);
                $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
                if (is_file($file_path)) {
                    if (unlink($file_path)) {
                        $output = "File deleted: " . htmlspecialchars($file);
                    } else {
                        $error = "Failed to delete file";
                    }
                } else {
                    $error = "File not found: " . htmlspecialchars($file);
                }
                break;
            
            case 'cp':
                $cp_parts = preg_split('/\s+/', $args);
                if (count($cp_parts) >= 2) {
                    $source = $current_dir . DIRECTORY_SEPARATOR . $cp_parts[0];
                    $dest = $current_dir . DIRECTORY_SEPARATOR . $cp_parts[1];
                    if (is_file($source)) {
                        if (copy($source, $dest)) {
                            $output = "File copied: " . htmlspecialchars($cp_parts[0]) . " -> " . htmlspecialchars($cp_parts[1]);
                        } else {
                            $error = "Failed to copy file";
                        }
                    } else {
                        $error = "Source file not found";
                    }
                } else {
                    $error = "Usage: cp <source> <destination>";
                }
                break;
            
            case 'mv':
                $mv_parts = preg_split('/\s+/', $args);
                if (count($mv_parts) >= 2) {
                    $source = $current_dir . DIRECTORY_SEPARATOR . $mv_parts[0];
                    $dest = $current_dir . DIRECTORY_SEPARATOR . $mv_parts[1];
                    if (file_exists($source)) {
                        if (rename($source, $dest)) {
                            $output = "File/directory moved: " . htmlspecialchars($mv_parts[0]) . " -> " . htmlspecialchars($mv_parts[1]);
                        } else {
                            $error = "Failed to move file/directory";
                        }
                    } else {
                        $error = "Source not found";
                    }
                } else {
                    $error = "Usage: mv <source> <destination>";
                }
                break;
            
            case 'file':
                $file = trim($args);
                $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
                if (file_exists($file_path)) {
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $file_path);
                        finfo_close($finfo);
                        $output = htmlspecialchars($file) . ": " . $mime;
                    } else {
                        $output = htmlspecialchars($file) . ": " . (is_dir($file_path) ? "Directory" : "File");
                    }
                } else {
                    $error = "File not found: " . htmlspecialchars($file);
                }
                break;
            
            case 'cd':
                $target_dir = trim($args);
                if (empty($target_dir) || $target_dir === '~') {
                    // Go to home directory
                    $target_dir = $_SERVER['HOME'] ?? '/root';
                    $new_dir = $target_dir;
                } elseif ($target_dir === '..') {
                    // Go to parent directory
                    $new_dir = dirname($current_dir);
                } elseif ($target_dir === '/') {
                    // Go to root
                    $new_dir = '/';
                } elseif (substr($target_dir, 0, 1) === '/') {
                    // Absolute path
                    $new_dir = $target_dir;
                } else {
                    // Relative path
                    $new_dir = $current_dir . DIRECTORY_SEPARATOR . $target_dir;
                }
                
                // Normalize and validate
                $new_dir = realpath($new_dir);
                
                if ($new_dir && is_dir($new_dir)) {
                    $current_dir = $new_dir;
                    $output = "Changed directory to: " . $current_dir;
                } else {
                    $error = "Directory not found: " . htmlspecialchars($target_dir);
                }
                break;
            
            case 'help':
                $output = "Available Commands:\n";
                $output .= "  pwd                  - Print working directory\n";
                $output .= "  cd <dir>             - Change directory (use .. for parent, ~ for home)\n";
                $output .= "  ls [dir]             - List directory contents\n";
                $output .= "  dir [dir]            - Alias for ls\n";
                $output .= "  chmod [-R] <p> <f>   - Change file permissions (-R for recursive)\n";
                $output .= "  chown <owner> <f>    - Change file owner\n";
                $output .= "  cat <file>           - Display file contents\n";
                $output .= "  touch <file>         - Create/update file\n";
                $output .= "  mkdir <dir>          - Create directory\n";
                $output .= "  rmdir <dir>          - Remove empty directory\n";
                $output .= "  rm <file>            - Delete file\n";
                $output .= "  cp <src> <dst>       - Copy file\n";
                $output .= "  mv <src> <dst>       - Move/rename file\n";
                $output .= "  file <file>          - Show file type\n";
                $output .= "  help                 - Show this help message\n";
                break;
            
            default:
                $error = "Command not found: " . htmlspecialchars($cmd) . ". Type 'help' for available commands.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
    
    echo json_encode([
        'success' => empty($error),
        'output' => $output,
        'error' => $error,
        'current_dir' => $current_dir
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>PHPFileManager</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    h1 {
        text-align: center;
        color: #fff;
        margin-bottom: 30px;
        font-size: 2.5em;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .file-browser {
        background-color: #fff;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .toolbar {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        padding: 25px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 15px;
        flex-wrap: wrap;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        align-items: center;
    }

    .toolbar form {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 350px;
        background: rgba(255, 255, 255, 0.9);
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .toolbar form:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        background: rgba(255, 255, 255, 1);
    }

    .toolbar form label {
        font-weight: 600;
        color: #333;
        white-space: nowrap;
        font-size: 0.95em;
    }

    .toolbar form input[type="text"] {
        flex: 1;
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 0.9em;
        transition: all 0.3s ease;
        background-color: #fff;
        color: #333;
    }

    .toolbar form input[type="text"]:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        background-color: #fff;
    }

    .toolbar form input[type="text"]::placeholder {
        color: #bbb;
    }

    .current-path {
        padding: 15px 20px;
        background: #f8f9fa;
        border-left: 4px solid #667eea;
        border-radius: 5px;
        margin-bottom: 20px;
        font-family: 'Courier New', monospace;
        color: #495057;
        font-weight: 500;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        margin-bottom: 20px;
    }

    .back-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 30px;
        overflow: hidden;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    }

    th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 0.5px;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 30px;
        overflow: hidden;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
        table-layout: auto;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        background-color: #fff;
        vertical-align: middle;
    }

    td:last-child {
        width: 35%;
    }

    tr.file-row {
        display: table-row;
    }

    tr.file-row td {
        display: table-cell;
    }

    tr.file-row:hover td {
        background-color: #f8f9fa;
    }

    .folder-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .folder-link:before {
        content: "📁";
        font-size: 1.2em;
    }

    .folder-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    .file-name {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #495057;
    }

    .file-name:before {
        content: "📄";
        font-size: 1.2em;
    }

    .type-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 600;
    }

    .type-folder {
        background-color: #e3f2fd;
        color: #1976d2;
    }

    .type-file {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }

    .perm-display {
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
        color: #6c757d;
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 5px;
    }

    .owner-display {
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
        color: #495057;
    }

    .action-form {
        display: flex;
        gap: 5px;
        align-items: center;
        flex-wrap: nowrap;
        margin: 0;
    }

    .actions-container {
        display: flex;
        gap: 5px;
        align-items: center;
        flex-wrap: nowrap;
        width: 100%;
    }

    .action-form input[type="text"] {
        padding: 8px 12px;
        border: 2px solid #e9ecef;
        border-radius: 6px;
        font-size: 0.9em;
        transition: border-color 0.3s ease;
        flex: 1;
        min-width: 80px;
        max-width: 120px;
        line-height: 1.5;
        box-sizing: border-box;
    }

    .action-form input[type="text"]:focus {
        outline: none;
        border-color: #667eea;
    }

    .btn {
        padding: 12px 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9em;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        text-decoration: none;
        display: inline-block;
    }

    .toolbar .btn {
        padding: 12px 20px;
        font-size: 0.85em;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
    }

    .btn-danger {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(245, 87, 108, 0.3);
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(245, 87, 108, 0.5);
    }

    .btn-warning {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3);
    }

    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 172, 254, 0.5);
    }

    .upload-section {
        padding: 25px;
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        border-radius: 10px;
        margin-top: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .upload-section form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .upload-section label {
        font-weight: 600;
        color: #6c3483;
        display: block;
        font-size: 1.1em;
        margin: 0;
    }

    .upload-file-wrapper {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .upload-section input[type="file"] {
        padding: 12px 15px;
        background-color: #fff;
        border: 2px dashed #c39bd3;
        border-radius: 8px;
        flex: 1;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9em;
        color: #666;
    }

    .upload-section input[type="file"]:hover {
        border-color: #8e44ad;
        background-color: #fef5e7;
    }

    .upload-section input[type="file"]:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .upload-section .btn {
        padding: 12px 24px;
        white-space: nowrap;
    }

    .message {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .message-success {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .message-error {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .footer {
        text-align: center;
        margin-top: 30px;
        padding: 20px;
        color: #fff;
        font-size: 0.9em;
    }

    .checkbox-col {
        width: 50px;
        text-align: center;
    }

    input[type="checkbox"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
        accent-color: #667eea;
    }

    .bulk-actions {
        display: none;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        border-radius: 5px;
    }

    .bulk-actions.show {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .bulk-actions form {
        display: flex;
        align-items: center;
        gap: 15px;
        width: 100%;
    }

    .bulk-actions-info {
        flex: 1;
        font-weight: 600;
        color: #856404;
    }

    .bulk-delete-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    /* Viewer Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(5px);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 900px;
        border-radius: 12px;
        box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }
    
    .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
        text-align: right;
    }
    
    .close-modal:hover,
    .close-modal:focus {
        color: #333;
        text-decoration: none;
    }

    #viewer-title {
        margin-top: 0;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
        color: #333;
    }

    #viewer-body {
        overflow-y: auto;
        flex: 1;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    #viewer-body pre {
        margin: 0;
        white-space: pre-wrap;
        word-wrap: break-word;
        font-family: 'Courier New', monospace;
        font-size: 14px;
    }

    #viewer-body img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .view-file-link {
        color: #495057;
        text-decoration: none;
        cursor: pointer;
        transition: color 0.2s;
    }
    
    .view-file-link:hover {
        color: #667eea;
        text-decoration: underline;
    }

    /* Breadcrumbs */
    .breadcrumb-link {
        color: #667eea;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    .breadcrumb-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    .breadcrumb-separator {
        color: #adb5bd;
        margin: 0 8px;
    }
    .breadcrumb-home {
        color: #667eea;
        text-decoration: none;
    }
    .breadcrumb-home:hover {
        text-decoration: underline;
    }

    /* Terminal Modal Styles */
    .terminal-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
    }

    .terminal-content {
        background-color: #1e1e1e;
        margin: 5% auto;
        padding: 0;
        border: 1px solid #333;
        width: 90%;
        max-width: 1200px;
        height: 70vh;
        border-radius: 12px;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .terminal-header {
        background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
        padding: 12px 20px;
        border-bottom: 1px solid #444;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .terminal-header h3 {
        margin: 0;
        color: #00ff00;
        font-family: 'Courier New', monospace;
        font-size: 1.1em;
        font-weight: 600;
    }

    .close-terminal {
        color: #888;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
        transition: color 0.3s ease;
    }

    .close-terminal:hover {
        color: #ff4444;
    }

    #terminal {
        flex: 1;
        background-color: #1e1e1e;
        color: #00ff00;
        padding: 15px;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        overflow-y: auto;
        overflow-x: hidden;
        line-height: 1.5;
    }

    .terminal-line {
        word-wrap: break-word;
        white-space: pre-wrap;
        margin: 0;
    }

    .terminal-error {
        color: #ff6b6b;
    }

    .terminal-warning {
        color: #ffd93d;
    }

    .terminal-input-line {
        display: flex;
        align-items: center;
        margin-top: 10px;
    }

    .terminal-prompt {
        color: #00ff00;
        font-weight: bold;
        margin-right: 5px;
        user-select: none;
    }

    #terminal-input {
        flex: 1;
        background-color: transparent;
        color: #00ff00;
        border: none;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        outline: none;
        line-height: 1.5;
    }

    .terminal-button {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: #fff;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85em;
        margin-left: 10px;
    }

    .terminal-button:hover {
        box-shadow: 0 4px 12px rgba(79, 172, 254, 0.5);
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        h1 {
            font-size: 1.8em;
        }

        .toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .toolbar form {
            flex-direction: column;
            min-width: auto;
            width: 100%;
        }

        .toolbar form input[type="text"] {
            width: 100%;
        }

        .toolbar .btn {
            width: 100%;
            text-align: center;
        }

        table {
            font-size: 0.85em;
        }

        .action-form {
            flex-direction: column;
        }

        .upload-file-wrapper {
            flex-direction: column;
        }

        .upload-section input[type="file"] {
            width: 100%;
        }

        .upload-section .btn {
            width: 100%;
            text-align: center;
        }

        .terminal-content {
            width: 95%;
            height: 60vh;
            margin: 20% auto;
        }
    }
    </style>

</head>

<body>
    <div class="container">
        <h1>📂 PHPFileManager</h1>
        <div class="file-browser">
            <div class="toolbar">
                <form method="POST">
                    <label for="folder_name">📁 Create New Folder:</label>
                    <input type="text" name="folder_name" placeholder="Folder name" required>
                    <button type="submit" name="action" value="create_folder" class="btn btn-primary">Create
                        Folder</button>
                </form>
                <button type="button" class="btn btn-primary" id="open-terminal-btn">🖥️ Open Terminal</button>
            </div>
            <?php
                    // Function to format file size in human-readable format
                    function formatFileSize($bytes) {
                        if ($bytes <= 0) return '0 bytes';
                        
                        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
                        $bytes = max($bytes, 0);
                        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                        $pow = min($pow, count($units) - 1);
                        $bytes /= (1 << (10 * $pow));
                        
                        return round($bytes, 2) . ' ' . $units[$pow];
                    }

                    // Function to recursively delete a directory
                    function deleteDirectory($dir)
                    {
                        if (! file_exists($dir)) {
                            return true;
                        }
                        if (! is_dir($dir)) {
                            return unlink($dir);
                        }
                        foreach (scandir($dir) as $item) {
                            if ($item == '.' || $item == '..') {
                                continue;
                            }
                            if (! deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                                return false;
                            }
                        }
                        return rmdir($dir);
                    }

                    // Get the current directory path
                    $current_dir = getcwd();

                    // Check if a directory was clicked
                    if (isset($_GET['dir'])) {
                        $requested_dir = $_GET['dir'];
                        // Validate that it's a directory and exists
                        if (is_dir($requested_dir)) {
                            $current_dir = realpath($requested_dir);
                        }
                    }

                    // Ensure current_dir is valid
                    if (! is_dir($current_dir)) {
                        $current_dir = getcwd();
                    }

                    // Check if a file was uploaded
                    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                        $file_name   = $_FILES['file']['name'];
                        $file_tmp    = $_FILES['file']['tmp_name'];
                        $target_path = $current_dir . '/' . $file_name;
                        
                        // Validate file name
                        if (empty($file_name)) {
                            echo "<div class='message message-error'>✗ Error: Invalid file name.</div>";
                        } elseif (file_exists($target_path)) {
                            echo "<div class='message message-error'>✗ Error: File already exists.</div>";
                        } else {
                            if (move_uploaded_file($file_tmp, $target_path)) {
                                echo "<div class='message message-success'>✓ File uploaded successfully!</div>";
                            } else {
                                // Provide detailed error information
                                $dir_owner    = posix_getpwuid(fileowner($current_dir));
                                $current_user = posix_getpwuid(posix_geteuid());
                                $dir_perms    = substr(sprintf('%o', fileperms($current_dir)), -4);
                                $error        = error_get_last();
                                echo "<div class='message message-error'>";
                                echo "<strong>✗ Error: Could not upload file.</strong><br><br>";
                                echo "<strong>Directory:</strong> " . htmlspecialchars($current_dir) . "<br>";
                                echo "<strong>Directory owner:</strong> " . $dir_owner['name'] . "<br>";
                                echo "<strong>Script running as:</strong> " . $current_user['name'] . "<br>";
                                echo "<strong>Directory permissions:</strong> " . $dir_perms . "<br>";
                                echo "<strong>File name:</strong> " . htmlspecialchars($file_name) . "<br>";
                                echo "<strong>File size:</strong> " . formatFileSize($_FILES['file']['size']) . "<br>";
                                echo($error ? "<strong>PHP Error:</strong> " . $error['message'] . "<br><br>" : "<br>");
                                echo "<strong>Possible solutions:</strong><br>";
                                echo "1. Check directory write permissions<br>";
                                echo "2. Try using the terminal: <code>chmod 777 \"" . htmlspecialchars($current_dir) . "\"</code><br>";
                                echo "3. Check if filename contains invalid characters<br>";
                                echo "4. Check available disk space<br>";
                                echo "</div>";
                            }
                        }
                    } elseif (isset($_FILES['file']) && $_FILES['file']['error'] != 0) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini',
                            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE from form',
                            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
                            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension'
                        ];
                        $error_msg = $upload_errors[$_FILES['file']['error']] ?? 'Unknown upload error';
                        echo "<div class='message message-error'>✗ Upload error: " . $error_msg . "</div>";
                    }

                    // Check if bulk delete is requested
                    if (isset($_POST['bulk_action']) && $_POST['bulk_action'] == 'bulk_delete' && isset($_POST['selected_files'])) {
                        $selected_files = $_POST['selected_files'];
                        $deleted_count  = 0;
                        $failed_count   = 0;

                        foreach ($selected_files as $file) {
                            $delete_path = $current_dir . '/' . $file;
                            if (is_file($delete_path)) {
                                if (unlink($delete_path)) {
                                    $deleted_count++;
                                } else {
                                    $failed_count++;
                                }
                            } elseif (is_dir($delete_path)) {
                                if (deleteDirectory($delete_path)) {
                                    $deleted_count++;
                                } else {
                                    $failed_count++;
                                }
                            }
                        }

                        if ($deleted_count > 0) {
                            echo "<div class='message message-success'>✓ " . $deleted_count . " item" . ($deleted_count !== 1 ? 's' : '') . " deleted successfully!";
                            if ($failed_count > 0) {
                                echo " (" . $failed_count . " failed)";
                            }
                            echo "</div>";
                        }
                        if ($failed_count > 0 && $deleted_count == 0) {
                            echo "<div class='message message-error'>✗ Error: Could not delete " . $failed_count . " item" . ($failed_count !== 1 ? 's' : '') . ". Check permissions.</div>";
                        }
                    }

                    // Check if action was requested
                    if (isset($_POST['action'])) {
                        $action   = $_POST['action'];
                        $old_name = isset($_POST['old_name']) ? $_POST['old_name'] : '';
                        $new_name = isset($_POST['new_name']) ? $_POST['new_name'] : '';

                        if ($action == 'rename' && ! empty($new_name)) {
                            $old_path = $current_dir . '/' . $old_name;
                            $new_path = $current_dir . '/' . $new_name;
                            if (file_exists($old_path)) {
                                if (rename($old_path, $new_path)) {
                                    echo "<div class='message message-success'>✓ Renamed successfully!</div>";
                                } else {
                                    echo "<div class='message message-error'>✗ Error: Could not rename.</div>";
                                }
                            }
                        } elseif ($action == 'delete') {
                            $delete_path = $current_dir . '/' . $old_name;
                            if (is_file($delete_path)) {
                                if (unlink($delete_path)) {
                                    echo "<div class='message message-success'>✓ File deleted successfully!</div>";
                                } else {
                                    $file_owner   = posix_getpwuid(fileowner($delete_path));
                                    $current_user = posix_getpwuid(posix_geteuid());
                                    $dir_perms    = substr(sprintf('%o', fileperms($current_dir)), -4);
                                    $dir_owner    = posix_getpwuid(fileowner($current_dir));
                                    $error        = error_get_last();
                                    echo "<div class='message message-error'>";
                                    echo "<strong>✗ Error: Could not delete file.</strong><br><br>";
                                    echo "<strong>File owner:</strong> " . $file_owner['name'] . "<br>";
                                    echo "<strong>Script running as:</strong> " . $current_user['name'] . "<br>";
                                    echo "<strong>File permissions:</strong> " . substr(sprintf('%o', fileperms($delete_path)), -4) . "<br>";
                                    echo "<strong>Directory owner:</strong> " . $dir_owner['name'] . "<br>";
                                    echo "<strong>Directory permissions:</strong> " . $dir_perms;
                                    echo($error ? "<br><strong>PHP Error:</strong> " . $error['message'] : '');
                                    echo "<br><br><strong>To fix:</strong> Run this command:<br>";
                                    echo "<code>sudo chown -R www-data:www-data \"" . $current_dir . "\"</code><br>or<br>";
                                    echo "<code>sudo chmod -R 777 \"" . $current_dir . "\"</code></div>";
                                }
                            } elseif (is_dir($delete_path)) {
                                if (deleteDirectory($delete_path)) {
                                    echo "<div class='message message-success'>✓ Folder deleted successfully!</div>";
                                } else {
                                    $dir_owner    = posix_getpwuid(fileowner($delete_path));
                                    $current_user = posix_getpwuid(posix_geteuid());
                                    $parent_perms = substr(sprintf('%o', fileperms($current_dir)), -4);
                                    $parent_owner = posix_getpwuid(fileowner($current_dir));
                                    $error        = error_get_last();
                                    echo "<div class='message message-error'>";
                                    echo "<strong>✗ Error: Could not delete folder.</strong><br><br>";
                                    echo "<strong>Folder owner:</strong> " . $dir_owner['name'] . "<br>";
                                    echo "<strong>Script running as:</strong> " . $current_user['name'] . "<br>";
                                    echo "<strong>Folder permissions:</strong> " . substr(sprintf('%o', fileperms($delete_path)), -4) . "<br>";
                                    echo "<strong>Parent directory owner:</strong> " . $parent_owner['name'] . "<br>";
                                    echo "<strong>Parent directory permissions:</strong> " . $parent_perms;
                                    echo($error ? "<br><strong>PHP Error:</strong> " . $error['message'] : '');
                                    echo "<br><br><strong>To fix:</strong> Run this command:<br>";
                                    echo "<code>sudo chown -R www-data:www-data \"" . $current_dir . "\"</code><br>or<br>";
                                    echo "<code>sudo chmod -R 777 \"" . $current_dir . "\"</code></div>";
                                }
                            }
                        }
                    }

                    // Check if a folder was created
                    if (isset($_POST['action']) && $_POST['action'] == 'create_folder' && isset($_POST['folder_name'])) {
                        $folder_name = $_POST['folder_name'];
                        // Check if folder with the same name already exists
                        if (! is_dir($current_dir . '/' . $folder_name)) {
                            if (mkdir($current_dir . '/' . $folder_name)) {
                                echo "<div class='message message-success'>✓ Folder created successfully!</div>";
                            } else {
                                echo "<div class='message message-error'>✗ Error: Could not create folder. Check permissions.</div>";
                            }
                        } else {
                            echo "<div class='message message-error'>✗ Folder with the same name already exists!</div>";
                        }
                    }

                    // Display the current directory path with breadcrumbs
                    $path_parts = explode(DIRECTORY_SEPARATOR, trim($current_dir, DIRECTORY_SEPARATOR));
                    $breadcrumb_html = "<strong>📌 Current Directory:</strong> <a href='?dir=/" . "' class='breadcrumb-home'>/</a>";
                    $accumulated_path = "";
                    
                    if ($current_dir === DIRECTORY_SEPARATOR || empty($current_dir)) {
                        $breadcrumb_html = "<strong>📌 Current Directory:</strong> /";
                    } else {
                        $is_windows = DIRECTORY_SEPARATOR === '\\';
                        if ($is_windows) {
                            $breadcrumb_html = "<strong>📌 Current Directory:</strong> ";
                        }
                        
                        foreach ($path_parts as $index => $part) {
                            if ($part === '') continue;
                            
                            if ($is_windows && $index === 0 && preg_match('/^[a-zA-Z]:$/', $part)) {
                                $accumulated_path = $part . DIRECTORY_SEPARATOR;
                                $breadcrumb_html .= "<a href='?dir=" . urlencode($accumulated_path) . "' class='breadcrumb-link'>" . htmlspecialchars($part) . "</a>";
                            } else {
                                $accumulated_path .= ($accumulated_path === '' && !$is_windows ? DIRECTORY_SEPARATOR : ($accumulated_path === '' || substr($accumulated_path, -1) === DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR)) . $part;
                                if (!$is_windows || $index > 0) {
                                    $breadcrumb_html .= "<span class='breadcrumb-separator'>/</span>";
                                }
                                if ($index === count($path_parts) - 1) {
                                    $breadcrumb_html .= "<span style='color: #495057; font-weight: 600;'>" . htmlspecialchars($part) . "</span>";
                                } else {
                                    $breadcrumb_html .= "<a href='?dir=" . urlencode($accumulated_path) . "' class='breadcrumb-link'>" . htmlspecialchars($part) . "</a>";
                                }
                            }
                        }
                    }
                    echo "<div class='current-path'>" . $breadcrumb_html . "</div>";

                    // Add back button to go to parent directory
                    $parent_dir = dirname($current_dir);
                    if ($current_dir != '/' && $parent_dir != $current_dir) {
                        echo "<a href=\"?dir=" . urlencode($parent_dir) . "\" class='back-button'>← Back to Parent Folder</a>";
                    }

                    // Display the file browser
                    echo "<div class='bulk-actions'><form method='POST' id='bulk-form'><span class='bulk-actions-info'>✓ <span id='selected-count'></span></span><button type='submit' name='bulk_action' value='bulk_delete' class='btn btn-danger' onclick='return confirm(\"Delete selected items? This action cannot be undone.\")'>  Delete Selected</button></form></div>";
                    echo "<table>";
                    echo "<thead>";
                    echo "<tr><th class='checkbox-col'><input type='checkbox' id='select-all'></th><th>📄 Name</th><th>📊 Type</th><th>💾 Size</th><th>🔒 Permissions</th><th>👤 Owner</th><th>⚙️ Actions</th></tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    echo "<tr><td colspan='7'><strong>Note:</strong> Click on folder names to navigate into them.</td></tr>";

                    // Open the current directory
                    if (! is_dir($current_dir)) {
                        echo "<tr><td colspan='6'><div class='message message-error'>✗ Error: Path is not a valid directory: " . htmlspecialchars($current_dir) . "</div></td></tr>";
                    } elseif (! is_readable($current_dir)) {
                        $dir_owner    = posix_getpwuid(fileowner($current_dir));
                        $current_user = posix_getpwuid(posix_geteuid());
                        $dir_perms    = substr(sprintf('%o', fileperms($current_dir)), -4);
                        echo "<tr><td colspan='6'><div class='message message-error'>";
                        echo "<strong>✗ Error: No permission to read directory</strong><br><br>";
                        echo "<strong>Directory:</strong> " . htmlspecialchars($current_dir) . "<br>";
                        echo "<strong>Directory owner:</strong> " . $dir_owner['name'] . "<br>";
                        echo "<strong>Script running as:</strong> " . $current_user['name'] . "<br>";
                        echo "<strong>Directory permissions:</strong> " . $dir_perms . "<br><br>";
                        echo "<strong>To fix:</strong> Run: <code>sudo chmod -R 755 \"" . htmlspecialchars($current_dir) . "\"</code></div></td></tr>";
                    } elseif ($handle = opendir($current_dir)) {
                        $files = [];
                        // Loop through all the files and directories
                        while (false !== ($file = readdir($handle))) {
                            if ($file != "." && $file != "..") {
                                $files[] = $file;
                            }
                        }
                        closedir($handle);

                        // Sort files
                        sort($files);

                        // Display files
                        foreach ($files as $file) {
                            $full_path = $current_dir . '/' . $file;

                            // Get the file/folder type and size
                            $type = is_file($full_path) ? 'File' : 'Folder';
                            $size = is_file($full_path) ? formatFileSize(filesize($full_path)) : '-';

                            // Get permissions
                            $perms        = fileperms($full_path);
                            $perms_string = '';

                            // File type
                            if (($perms & 0xC000) == 0xC000) {
                                $perms_string = 's'; // Socket
                            } elseif (($perms & 0xA000) == 0xA000) {
                                $perms_string = 'l'; // Symbolic Link
                            } elseif (($perms & 0x8000) == 0x8000) {
                                $perms_string = '-'; // Regular
                            } elseif (($perms & 0x6000) == 0x6000) {
                                $perms_string = 'b'; // Block special
                            } elseif (($perms & 0x4000) == 0x4000) {
                                $perms_string = 'd'; // Directory
                            } elseif (($perms & 0x2000) == 0x2000) {
                                $perms_string = 'c'; // Character special
                            } elseif (($perms & 0x1000) == 0x1000) {
                                $perms_string = 'p'; // FIFO pipe
                            } else {
                                $perms_string = 'u'; // Unknown
                            }

                            // Owner permissions
                            $perms_string .= (($perms & 0x0100) ? 'r' : '-');
                            $perms_string .= (($perms & 0x0080) ? 'w' : '-');
                            $perms_string .= (($perms & 0x0040) ?
                                (($perms & 0x0800) ? 's' : 'x') :
                                (($perms & 0x0800) ? 'S' : '-'));

                            // Group permissions
                            $perms_string .= (($perms & 0x0020) ? 'r' : '-');
                            $perms_string .= (($perms & 0x0010) ? 'w' : '-');
                            $perms_string .= (($perms & 0x0008) ?
                                (($perms & 0x0400) ? 's' : 'x') :
                                (($perms & 0x0400) ? 'S' : '-'));

                            // Other permissions
                            $perms_string .= (($perms & 0x0004) ? 'r' : '-');
                            $perms_string .= (($perms & 0x0002) ? 'w' : '-');
                            $perms_string .= (($perms & 0x0001) ?
                                (($perms & 0x0200) ? 't' : 'x') :
                                (($perms & 0x0200) ? 'T' : '-'));

                            // Add numeric permissions in parentheses
                            $perms_string .= ' (' . substr(sprintf('%o', $perms), -4) . ')';

                            // Get owner information
                            $owner_info = posix_getpwuid(fileowner($full_path));
                            $group_info = posix_getgrgid(filegroup($full_path));
                            $owner      = $owner_info['name'] . ':' . $group_info['name'];

                            // Display the file/folder row
                            echo "<tr class='file-row'><td class='checkbox-col'><input type='checkbox' name='selected_files[]' value='" . htmlspecialchars($file) . "' form='bulk-form'></td><td>";
                            if (is_dir($full_path)) {
                                // For folders, navigate into them
                                echo "<a href=\"?dir=" . urlencode($full_path) . "\" class='folder-link'>" . htmlspecialchars($file) . "</a>";
                            } else {
                                // For files, open viewer
                                echo "<a href='#' class='file-name view-file-link' data-file='" . htmlspecialchars($file) . "' data-dir='" . htmlspecialchars($current_dir) . "'>" . htmlspecialchars($file) . "</a>";
                            }
                            echo "</td><td><span class='type-badge type-" . strtolower($type) . "'>" . $type . "</span></td>";
                            echo "<td>" . $size . "</td>";
                            echo "<td><span class='perm-display'>" . $perms_string . "</span></td>";
                            echo "<td><span class='owner-display'>" . $owner . "</span></td>";
                            echo "<td>";
                            echo "<div class='actions-container'>";

                            echo "<form method=\"POST\" class='action-form'>";
                            echo "<input type=\"hidden\" name=\"old_name\" value=\"" . htmlspecialchars($file) . "\">";
                            echo "<input type=\"text\" name=\"new_name\" placeholder=\"New name\" value=\"\">";
                            echo "<button type=\"submit\" name=\"action\" value=\"rename\" class='btn btn-warning'>Rename</button>";
                            echo "<button type=\"submit\" name=\"action\" value=\"delete\" class='btn btn-danger'>Delete</button>";
                            echo "</form>";
                            if (is_file($full_path)) {
                                echo "<a href='?download=" . urlencode($file) . "&dir=" . urlencode($current_dir) . "' class='btn btn-primary'>Download</a>";
                            }
                            echo "</div></td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>Error: Unable to open directory</td></tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";

                    // Display the file upload form
                    echo "<div class='upload-section'>";
                    echo "<form method=\"POST\" enctype=\"multipart/form-data\">";
                    echo "<label for=\"file\">📤 Upload a File:</label>";
                    echo "<div class='upload-file-wrapper'>";
                    echo "<input type=\"file\" name=\"file\" id=\"file\" required>";
                    echo "<button type=\"submit\" class='btn btn-primary'>Upload File</button>";
                    echo "</div>";
                    echo "</form>";
                    echo "</div>";
                ?>

        </div>
        <div class="footer">
            <p>💙 PHPFileManager • Made with love by KB</p>
        </div>
    </div>

    <!-- Viewer Modal -->
    <div id="viewer-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="viewer-title">File</h2>
            <div id="viewer-body"></div>
        </div>
    </div>

    <!-- Terminal Modal -->
    <div id="terminal-modal" class="terminal-modal">
        <div class="terminal-content">
            <div class="terminal-header">
                <h3>🖥️ Terminal</h3>
                <span class="close-terminal">&times;</span>
            </div>
            <div id="terminal"></div>
            <div class="terminal-input-line">
                <span class="terminal-prompt">$</span>
                <input type="text" id="terminal-input" placeholder="Enter command..." autocomplete="off">
                <button class="terminal-button" id="clear-terminal-btn">Clear</button>
            </div>
        </div>
    </div>

    <script>
        // File Viewer JS
        const modal = document.getElementById('viewer-modal');
        const closeBtn = document.querySelector('.close-modal');
        const viewerTitle = document.getElementById('viewer-title');
        const viewerBody = document.getElementById('viewer-body');

        if (modal && closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = 'none';
                viewerBody.innerHTML = '';
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                    viewerBody.innerHTML = '';
                }
            }
        }

        document.querySelectorAll('.view-file-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const fileName = this.getAttribute('data-file');
                const dir = this.getAttribute('data-dir');
                
                viewerTitle.textContent = fileName;
                viewerBody.innerHTML = 'Loading...';
                modal.style.display = 'block';

                fetch('?view=' + encodeURIComponent(fileName) + '&dir=' + encodeURIComponent(dir))
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            viewerBody.innerHTML = '<div style="color: red;">' + data.error + '</div>';
                        } else if (data.is_image) {
                            viewerBody.innerHTML = '<img src="' + data.content + '" alt="' + fileName + '">';
                        } else {
                            viewerBody.innerHTML = '<pre><code>' + data.content + '</code></pre>';
                        }
                    })
                    .catch(error => {
                        viewerBody.innerHTML = '<div style="color: red;">Error loading file.</div>';
                    });
            });
        });

		const selectAllCheckbox = document.getElementById('select-all');
		const fileCheckboxes = document.querySelectorAll('input[name="selected_files[]"]');
		const bulkActionsDiv = document.querySelector('.bulk-actions');
		const selectedCountSpan = document.getElementById('selected-count');

		function updateBulkActions() {
			const checkedCount = document.querySelectorAll('input[name="selected_files[]"]:checked').length;
			if (checkedCount > 0) {
				bulkActionsDiv.classList.add('show');
				selectedCountSpan.textContent = checkedCount + ' item' + (checkedCount !== 1 ? 's' : '') + ' selected';
			} else {
				bulkActionsDiv.classList.remove('show');
			}
		}

		if (selectAllCheckbox) {
			selectAllCheckbox.addEventListener('change', function() {
				fileCheckboxes.forEach(checkbox => {
					checkbox.checked = this.checked;
				});
				updateBulkActions();
			});
		}

		fileCheckboxes.forEach(checkbox => {
			checkbox.addEventListener('change', function() {
				const allChecked = Array.from(fileCheckboxes).every(cb => cb.checked);
				if (selectAllCheckbox) {
					selectAllCheckbox.checked = allChecked;
				}
				updateBulkActions();
			});
		});

        // Terminal Modal JS
        const terminalModal = document.getElementById('terminal-modal');
        const terminalDisplay = document.getElementById('terminal');
        const terminalInput = document.getElementById('terminal-input');
        const openTerminalBtn = document.getElementById('open-terminal-btn');
        const closeTerminalBtn = document.querySelector('.close-terminal');
        const clearTerminalBtn = document.getElementById('clear-terminal-btn');
        let terminalCurrentDir = window.location.pathname.split('index.php')[0] || '/';

        // Open terminal
        openTerminalBtn.addEventListener('click', function() {
            terminalModal.style.display = 'block';
            terminalInput.focus();
            if (terminalDisplay.innerHTML === '') {
                addTerminalLine('Welcome to PHPFileManager Terminal');
                addTerminalLine('Type "help" for available commands');
                addTerminalLine('');
            }
        });

        // Close terminal
        closeTerminalBtn.addEventListener('click', function() {
            terminalModal.style.display = 'none';
        });

        // Close on background click
        window.addEventListener('click', function(event) {
            if (event.target === terminalModal) {
                terminalModal.style.display = 'none';
            }
        });

        // Clear terminal
        clearTerminalBtn.addEventListener('click', function() {
            terminalDisplay.innerHTML = '';
            terminalInput.value = '';
            terminalInput.focus();
        });

        // Add line to terminal
        function addTerminalLine(text, type = 'normal') {
            const line = document.createElement('div');
            line.className = 'terminal-line';
            if (type === 'error') {
                line.className += ' terminal-error';
            } else if (type === 'warning') {
                line.className += ' terminal-warning';
            }
            line.textContent = text;
            terminalDisplay.appendChild(line);
            terminalDisplay.scrollTop = terminalDisplay.scrollHeight;
        }

        // Execute terminal command
        function executeTerminalCommand(command) {
            const formData = new FormData();
            formData.append('terminal_command', command);
            formData.append('current_dir', terminalCurrentDir);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.output) {
                    const lines = data.output.split('\n');
                    lines.forEach(line => {
                        if (line.trim()) {
                            addTerminalLine(line);
                        }
                    });
                }
                if (data.error) {
                    addTerminalLine(data.error, 'error');
                }
                if (data.current_dir) {
                    terminalCurrentDir = data.current_dir;
                }
                addTerminalLine('');
            })
            .catch(error => {
                addTerminalLine('Error: ' + error.message, 'error');
            });
        }

        // Handle terminal input
        terminalInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const command = this.value.trim();
                if (command) {
                    addTerminalLine('$ ' + command);
                    this.value = '';
                    executeTerminalCommand(command);
                }
                e.preventDefault();
            }
        });
    </script>
</body>
</html>