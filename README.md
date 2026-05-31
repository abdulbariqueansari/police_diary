# Police Diary Management System

A comprehensive and dynamic Police Diary Management System designed to securely and digitally log daily activities, generate PDF reports, manage officer credentials, and more. 

## Features

- **Multi-user Support**: distinct interfaces and workflows for Administrators and Users (Officers).
- **Voice Recognition Integration**: Multilingual voice-typing capabilities for seamless diary entry creation. Uses the Google Web Speech API and translation services.
- **Secure Authentication**: Includes hashed passwords with Bcrypt (`password_hash`), "Remember Me" secure cookies functionality, and login attempts tracking.
- **Dynamic PDF Export**: Create daily/weekly logs easily. The application structures data into a clean PDF format.
- **Responsive Aesthetics**: A beautiful, modern UI/UX design with gradients, micro-animations, and responsive tables to ensure excellent usability across varying device screens.
- **Admin Controls**: Manage incoming registration requests with approvals and rejections.

## Installation Instructions

1. **Clone/Move Repository**: Place the application folder in your local web server's public directory (e.g., `htdocs/police_diary` for XAMPP).
2. **Web Server Requirement**: You will need Apache & MySQL running (via XAMPP, WAMP, or similar stack).
3. **Database Configuration**: The database handles its own creation automatically through `config.php`.
4. **Initial Setup**: Run the installation script via your browser by visiting `http://localhost/police_diary/install.php` to set up all tables and the default admin user.
5. **Login Credentials**:
   - **Username**: admin
   - **Password**: admin123
   *(Please log in and update your password immediately for security)*

## Technologies Used

- **Frontend**: HTML5, Vanilla CSS3, JavaScript (ES6)
- **Backend**: PHP 8.x
- **Database**: MySQL (using `mysqli` driver)
- **APIs**: Web Speech API, Google Translate API

## Troubleshooting

- **Database Errors on First Load:** The system is built to create its database (`police_diary`) automatically. However, ensure that the MySQL user specified in `config.php` has the `CREATE DATABASE` privilege.
- **Redirect Issues:** Make sure `BASE_URL` in `config.php` aligns properly with your local alias or directory structure. 
- **Voice Typing Not Working:** This feature uses browser-specific APIs and requires Google Chrome to function smoothly with microphones. Ensure microphone permissions are granted.

## Maintenance

Created and maintained for advanced digital police logging.
