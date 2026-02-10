# 📂 PHPFileManager

A modern, web-based file manager built with PHP featuring a beautiful UI, file operations, and permission management.

## 🌟 Description

PHPFileManager is a lightweight, single-file web-based file manager that allows you to browse, upload, rename, and delete files and folders directly from your browser. Built with pure PHP and featuring a modern gradient UI, it provides a simple yet powerful interface for managing your server files with detailed permission and ownership information.

Perfect for developers, system administrators, or anyone who needs quick access to manage files on a PHP-enabled web server.

## ✨ Features

- 📁 **Browse Directories** - Navigate through folders with detailed file information
- 📤 **Upload Files** - Upload files directly through the browser
- ✏️ **Rename Files & Folders** - Easily rename any file or directory
- 🗑️ **Delete Operations** - Delete files and directories (recursive deletion supported)
- 📂 **Create Folders** - Create new directories on the fly
- 🔒 **Permission Details** - View file permissions in both symbolic and numeric formats
- 👤 **Ownership Info** - See file owner and group information
- 🎨 **Modern UI** - Beautiful purple gradient design with responsive layout
- ⚡ **Single File** - Just one PHP file - upload and use immediately
- 🛡️ **Error Diagnostics** - Detailed error messages with permission diagnostics
- 📱 **Mobile Responsive** - Works seamlessly on all devices

## 🖼️ Screenshots

<img width="1506" height="1035" alt="image" src="https://github.com/user-attachments/assets/deb79ae5-0982-4c21-a6ac-1ac6325c0fcb" />


*Main interface showing file listing with permissions and actions*

## 📋 Requirements

- PHP 7.0 or higher
- Web server (Apache, Nginx, or any PHP-compatible server)
- POSIX functions enabled (typically available on Linux/Unix servers)

## 🚀 Installation

1. **Download** the `index.php` file
2. **Upload** it to your web server directory
3. **Access** via your browser: `http://yourserver.com/index.php`

That's it! No configuration needed.

## 🔧 Usage

### Browsing Files
- Click on folder names to navigate into directories
- Use the "Back to Parent Folder" button to go up one level
- Current directory path is displayed at the top

### Creating Folders
- Enter a folder name in the "Create New Folder" field
- Click "Create Folder" button

### Uploading Files
- Scroll to the bottom upload section
- Click "Choose File" and select your file
- Click "Upload File" button

### Renaming Files/Folders
- Enter new name in the text field next to the file/folder
- Click the "Rename" button

### Deleting Files/Folders
- Click the "Delete" button next to any file or folder
- Folders will be deleted recursively with all contents

## ⚠️ Security Considerations

**IMPORTANT:** This file manager provides direct access to your server's file system. Please consider the following:

1. **Restrict Access** - Place the file in a password-protected directory or add authentication
2. **Use .htaccess** - Limit access by IP address if possible
3. **File Permissions** - Ensure proper file permissions on your server
4. **Delete After Use** - Remove the file manager when not actively needed
5. **HTTPS** - Always use over HTTPS to encrypt data transmission

### Recommended .htaccess Protection

```apache
AuthType Basic
AuthName "Protected Area"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

## 🛠️ Troubleshooting

### Permission Errors
If you see permission errors when deleting or uploading:

```bash
# Give www-data ownership (recommended)
sudo chown -R www-data:www-data /path/to/directory

# Or set broader permissions (less secure)
sudo chmod -R 755 /path/to/directory
```

### Cannot Read Directory
Ensure the PHP process has read permissions:
```bash
sudo chmod -R 755 /path/to/directory
```

### Upload Fails
Check PHP upload settings in `php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

[**Koushik Basu** - Made with 💙](https://github.com/koushikbasu/)

## 🙏 Acknowledgments

- Built with pure PHP - no external dependencies
- Modern CSS3 gradients and animations
- Responsive design for all devices

## 📞 Support

If you encounter any issues or have questions, please open an issue on GitHub.

## 📊 Technical Details

- **Language**: PHP 7.0+
- **Lines of Code**: ~600
- **Dependencies**: None
- **Size**: ~20KB
- **License**: MIT

---

⭐ **Star this repository if you find it helpful!**

💡 **Tip**: Bookmark this file manager URL for quick access to your server files!
