# DUET PDF Library

A platform where users can read PDF books for free, but downloading requires authentication via a Microsoft Org account (e.g., `@student.duet.ac.bd`). Admins manage book uploads and user requests.

## Core Features

### User Roles
- **Guest:** Read any book online (no download).
- **Authenticated User:** Download books after login with DUET email.
- **Admin:** Upload books + approve/reject user-submitted books.

### Authentication
- Microsoft Org Account login (Azure AD) for `@student.duet.ac.bd` emails only.
- Block personal emails (Gmail/Yahoo).

### Book Management
- **Admin:**
  - Upload/delete books directly.
  - Approve/reject books submitted by users.
- **Users:** Request to upload a book (pending admin approval).

### PDF Viewer
- Embedded viewer (PDF.js) for reading books without downloading.

### Download Restrictions
- Download button hidden unless logged in.

## Tech Stack
- **Frontend:** HTML, CSS, Bootstrap, JS
- **Backend:** PHP
- **Auth:** Microsoft Identity Platform (Azure AD)
- **Database:** MySQL for user/book data
- **Storage:** Firebase for PDF files

## Project Structure
```
/
├── assets/            # Static assets (CSS, JS, images)
├── config/            # Configuration files
├── includes/          # PHP includes and functions
├── auth/              # Authentication related files
├── admin/             # Admin panel
├── uploads/           # Temporary upload directory
├── vendor/            # Composer dependencies
├── index.php          # Main entry point
└── README.md          # Project documentation
```

## Installation

1. Clone the repository
2. Import the database schema from `Schema.sql`
3. Configure your web server to point to the project directory
4. Copy `config/config.example.php` to `config/config.php` and update with your settings
5. Run `composer install` to install dependencies

## Configuration

You'll need to set up the following:

1. Azure AD application for authentication
2. Firebase project for PDF storage
3. MySQL database

## License

This project is proprietary and intended for use by DUET only.