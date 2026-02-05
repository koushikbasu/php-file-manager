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
	text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
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
	padding: 20px;
	background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
	border-radius: 10px;
	flex-wrap: wrap;
}

.toolbar form {
	display: flex;
	align-items: center;
	gap: 10px;
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
	box-shadow: 0 0 20px rgba(0,0,0,0.05);
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

td {
	padding: 15px;
	border-bottom: 1px solid #e9ecef;
	background-color: #fff;
}

tr:hover td {
	background-color: #f8f9fa;
}

tr:last-child td {
	border-bottom: none;
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
}

.action-form input[type="text"] {
	padding: 8px 12px;
	border: 2px solid #e9ecef;
	border-radius: 6px;
	font-size: 0.9em;
	transition: border-color 0.3s ease;
}

.action-form input[type="text"]:focus {
	outline: none;
	border-color: #667eea;
}

.btn {
	padding: 8px 16px;
	border: none;
	border-radius: 6px;
	cursor: pointer;
	font-weight: 600;
	font-size: 0.85em;
	transition: all 0.3s ease;
	text-transform: uppercase;
	letter-spacing: 0.5px;
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
}

.upload-section label {
	font-weight: 600;
	color: #6c3483;
	margin-bottom: 10px;
	display: block;
	font-size: 1.1em;
}

.upload-section input[type="file"] {
	padding: 12px;
	background-color: #fff;
	border: 2px dashed #c39bd3;
	border-radius: 8px;
	width: 100%;
	margin-bottom: 15px;
	cursor: pointer;
	transition: all 0.3s ease;
}

.upload-section input[type="file"]:hover {
	border-color: #8e44ad;
	background-color: #fef5e7;
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

@media (max-width: 768px) {
	h1 {
		font-size: 1.8em;
	}
	
	.toolbar {
		flex-direction: column;
	}
	
	table {
		font-size: 0.85em;
	}
	
	.action-form {
		flex-direction: column;
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
						<button type="submit" name="action" value="create_folder" class="btn btn-primary">Create Folder</button>
					</form>
				</div>
				<?php
	// Function to recursively delete a directory
	function deleteDirectory($dir) {
		if (!file_exists($dir)) {
			return true;
		}
		if (!is_dir($dir)) {
			return unlink($dir);
		}
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}
			if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
				return false;
			}
		}
		return rmdir($dir);
	}

	// Get the current directory path
	$current_dir = getcwd();
	
	// Check if a directory was clicked
	if(isset($_GET['dir'])) {
		$requested_dir = $_GET['dir'];
		// Validate that it's a directory and exists
		if(is_dir($requested_dir)) {
			$current_dir = realpath($requested_dir);
		}
	}
	
	// Ensure current_dir is valid
	if(!is_dir($current_dir)) {
		$current_dir = getcwd();
	}

	// Check if a file was uploaded
	if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
		$file_name = $_FILES['file']['name'];
		$file_tmp = $_FILES['file']['tmp_name'];
		$target_path = $current_dir . '/' . $file_name;
		if(move_uploaded_file($file_tmp, $target_path)) {
			echo "<div class='message message-success'>✓ File uploaded successfully!</div>";
		} else {
			echo "<div class='message message-error'>✗ Error: Could not upload file. Check permissions.</div>";
		}
	} elseif(isset($_FILES['file']) && $_FILES['file']['error'] != 0) {
		echo "<div class='message message-error'>✗ Upload error: " . $_FILES['file']['error'] . "</div>";
	}

	// Check if a file/folder was renamed or deleted
	if(isset($_POST['action'])) {
		$action = $_POST['action'];
		$old_name = $_POST['old_name'];
		$new_name = isset($_POST['new_name']) ? $_POST['new_name'] : '';
		
		if($action == 'rename' && !empty($new_name)) {
			$old_path = $current_dir . '/' . $old_name;
			$new_path = $current_dir . '/' . $new_name;
			if(file_exists($old_path)) {
				if(rename($old_path, $new_path)) {
					echo "<div class='message message-success'>✓ Renamed successfully!</div>";
				} else {
					echo "<div class='message message-error'>✗ Error: Could not rename.</div>";
				}
			}
		} elseif ($action == 'delete') {
			$delete_path = $current_dir . '/' . $old_name;
			if(is_file($delete_path)) {
				if(unlink($delete_path)) {
					echo "<div class='message message-success'>✓ File deleted successfully!</div>";
				} else {
					$file_owner = posix_getpwuid(fileowner($delete_path));
					$current_user = posix_getpwuid(posix_geteuid());
					$dir_perms = substr(sprintf('%o', fileperms($current_dir)), -4);
					$dir_owner = posix_getpwuid(fileowner($current_dir));
					$error = error_get_last();
					echo "<div class='message message-error'>";
					echo "<strong>✗ Error: Could not delete file.</strong><br><br>";
					echo "<strong>File owner:</strong> " . $file_owner['name'] . "<br>";
					echo "<strong>Script running as:</strong> " . $current_user['name'] . "<br>";
					echo "<strong>File permissions:</strong> " . substr(sprintf('%o', fileperms($delete_path)), -4) . "<br>";
					echo "<strong>Directory owner:</strong> " . $dir_owner['name'] . "<br>";
					echo "<strong>Directory permissions:</strong> " . $dir_perms;
					echo ($error ? "<br><strong>PHP Error:</strong> " . $error['message'] : '');
					echo "<br><br><strong>To fix:</strong> Run this command:<br>";
					echo "<code>sudo chown -R www-data:www-data \"" . $current_dir . "\"</code><br>or<br>";
					echo "<code>sudo chmod -R 777 \"" . $current_dir . "\"</code></div>";
				}
			} elseif(is_dir($delete_path)) {
				if(deleteDirectory($delete_path)) {
					echo "<div class='message message-success'>✓ Folder deleted successfully!</div>";
				} else {
					$dir_owner = posix_getpwuid(fileowner($delete_path));
					$current_user = posix_getpwuid(posix_geteuid());
					$parent_perms = substr(sprintf('%o', fileperms($current_dir)), -4);
					$parent_owner = posix_getpwuid(fileowner($current_dir));
					$error = error_get_last();
					echo "<div class='message message-error'>";
					echo "<strong>✗ Error: Could not delete folder.</strong><br><br>";
					echo "<strong>Folder owner:</strong> " . $dir_owner['name'] . "<br>";
					echo "<strong>Script running as:</strong> " . $current_user['name'] . "<br>";
					echo "<strong>Folder permissions:</strong> " . substr(sprintf('%o', fileperms($delete_path)), -4) . "<br>";
					echo "<strong>Parent directory owner:</strong> " . $parent_owner['name'] . "<br>";
					echo "<strong>Parent directory permissions:</strong> " . $parent_perms;
					echo ($error ? "<br><strong>PHP Error:</strong> " . $error['message'] : '');
					echo "<br><br><strong>To fix:</strong> Run this command:<br>";
					echo "<code>sudo chown -R www-data:www-data \"" . $current_dir . "\"</code><br>or<br>";
					echo "<code>sudo chmod -R 777 \"" . $current_dir . "\"</code></div>";
				}
			}
		}
	}

// Check if a folder was created
if(isset($_POST['action']) && $_POST['action'] == 'create_folder' && isset($_POST['folder_name'])) {
    $folder_name = $_POST['folder_name'];
    // Check if folder with the same name already exists
    if(!is_dir($current_dir . '/' . $folder_name)) {
	        if(mkdir($current_dir . '/' . $folder_name)) {
	            echo "<div class='message message-success'>✓ Folder created successfully!</div>";
	        } else {
	            echo "<div class='message message-error'>✗ Error: Could not create folder. Check permissions.</div>";
	        }
	    } else {
	        echo "<div class='message message-error'>✗ Folder with the same name already exists!</div>";
	    }
	}




	// Display the current directory path with back button
	echo "<div class='current-path'><strong>📌 Current Directory:</strong> " . htmlspecialchars($current_dir) . "</div>";
	
	// Add back button to go to parent directory
	$parent_dir = dirname($current_dir);
	if ($current_dir != '/' && $parent_dir != $current_dir) {
		echo "<a href=\"?dir=" . urlencode($parent_dir) . "\" class='back-button'>← Back to Parent Folder</a>";
	}
	
	// Display the file browser
	echo "<table>";
	echo "<tr><th>📄 Name</th><th>📊 Type</th><th>💾 Size</th><th>🔒 Permissions</th><th>👤 Owner</th><th>⚙️ Actions</th></tr>";

	// Open the current directory
	if (!is_dir($current_dir)) {
		echo "<tr><td colspan='6'><div class='message message-error'>✗ Error: Path is not a valid directory: " . htmlspecialchars($current_dir) . "</div></td></tr>";
	} elseif (!is_readable($current_dir)) {
		$dir_owner = posix_getpwuid(fileowner($current_dir));
		$current_user = posix_getpwuid(posix_geteuid());
		$dir_perms = substr(sprintf('%o', fileperms($current_dir)), -4);
		echo "<tr><td colspan='6'><div class='message message-error'>";
		echo "<strong>✗ Error: No permission to read directory</strong><br><br>";
		echo "<strong>Directory:</strong> " . htmlspecialchars($current_dir) . "<br>";
		echo "<strong>Directory owner:</strong> " . $dir_owner['name'] . "<br>";
		echo "<strong>Script running as:</strong> " . $current_user['name'] . "<br>";
		echo "<strong>Directory permissions:</strong> " . $dir_perms . "<br><br>";
		echo "<strong>To fix:</strong> Run: <code>sudo chmod -R 755 \"" . htmlspecialchars($current_dir) . "\"</code></div></td></tr>";
	} elseif ($handle = opendir($current_dir)) {
		$files = array();
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
		foreach($files as $file) {
			$full_path = $current_dir . '/' . $file;
				
				// Get the file/folder type and size
				$type = is_file($full_path) ? 'File' : 'Folder';
				$size = is_file($full_path) ? filesize($full_path) . ' bytes' : '-';
				
				// Get permissions
				$perms = fileperms($full_path);
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
								(($perms & 0x0800) ? 's' : 'x' ) :
								(($perms & 0x0800) ? 'S' : '-'));
				
				// Group permissions
				$perms_string .= (($perms & 0x0020) ? 'r' : '-');
				$perms_string .= (($perms & 0x0010) ? 'w' : '-');
				$perms_string .= (($perms & 0x0008) ?
								(($perms & 0x0400) ? 's' : 'x' ) :
								(($perms & 0x0400) ? 'S' : '-'));
				
				// Other permissions
				$perms_string .= (($perms & 0x0004) ? 'r' : '-');
				$perms_string .= (($perms & 0x0002) ? 'w' : '-');
				$perms_string .= (($perms & 0x0001) ?
								(($perms & 0x0200) ? 't' : 'x' ) :
								(($perms & 0x0200) ? 'T' : '-'));
				
				// Add numeric permissions in parentheses
				$perms_string .= ' (' . substr(sprintf('%o', $perms), -4) . ')';
				
				// Get owner information
				$owner_info = posix_getpwuid(fileowner($full_path));
				$group_info = posix_getgrgid(filegroup($full_path));
				$owner = $owner_info['name'] . ':' . $group_info['name'];

				// Display the file/folder row
				echo "<tr><td>";
				if (is_dir($full_path)) {
					// For folders, navigate into them
				echo "<a href=\"?dir=" . urlencode($full_path) . "\" class='folder-link'>" . htmlspecialchars($file) . "</a>";
			} else {
				// For files, just display the name
				echo "<span class='file-name'>" . htmlspecialchars($file) . "</span>";
			}
			echo "</td><td><span class='type-badge type-" . strtolower($type) . "'>" . $type . "</span></td><td>" . $size . "</td><td><span class='perm-display'>" . $perms_string . "</span></td><td><span class='owner-display'>" . $owner . "</span></td><td>";
			echo "<form method=\"POST\" class='action-form'>";
			echo "<input type=\"hidden\" name=\"old_name\" value=\"" . htmlspecialchars($file) . "\">";
			echo "<input type=\"text\" name=\"new_name\" placeholder=\"New name\">";
			echo "<button type=\"submit\" name=\"action\" value=\"rename\" class='btn btn-warning'>Rename</button>";
			echo "<button type=\"submit\" name=\"action\" value=\"delete\" class='btn btn-danger'>Delete</button>";
				echo "</form></td></tr>";
		}
	} else {
		echo "<tr><td colspan='6'>Error: Unable to open directory</td></tr>";
	}
	echo "</table>";

	// Display the file upload form
	echo "<div class='upload-section'>";
	echo "<form method=\"POST\" enctype=\"multipart/form-data\">";
	echo "<label for=\"file\">📤 Upload a File:</label>";
	echo "<input type=\"file\" name=\"file\" id=\"file\" required>";
	echo "<button type=\"submit\" class='btn btn-primary'>Upload File</button>";
	echo "</form>";
	echo "</div>";
?>

			</div>
			<div class="footer">
				<p>💙 PHPFileManager • Made with love by KB</p>
			</div>
		</div>
	</body>
</html>
