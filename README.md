# NADUfind – Lost and Found Management System

NADUfind is a web-based Lost and Found Management System designed to help users report, search, and manage lost and found items efficiently. The platform provides user authentication, item reporting, claim submission, and administrative management features.

## Features

### User Authentication
- Secure user registration and login.
- Password hashing and session management.
- User profile management.

### Item Reporting
- Report lost and found items.
- Provide item descriptions, categories, locations, and dates.
- Upload item photos.

### Search and Filtering
- Search items using keywords.
- Filter items by type, category, and status.
- AJAX-based search functionality.

### Claims Management
- Submit claims for found items.
- Provide proof-of-ownership details.
- Track claim status.

### Admin Dashboard
- Manage registered users.
- Verify and manage item reports.
- Approve or reject submitted claims.
- Monitor item and claim records.

### Item Status Tracking
- Track item statuses: Lost, Found, Claimed, and Returned.
- Monitor changes in item records.

## Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap
- **Backend:** PHP
- **Database:** MySQL
- **Local Development:** XAMPP
- **Hosting:** InfinityFree

## Project Structure

```text
lost-found-system/
├── admin/                  # Administrative modules
├── api/                    # AJAX search endpoints
├── asset/                  # Bootstrap resources and libraries
├── assets/                 # Custom CSS and JavaScript
├── config/                 # Database configuration
├── includes/               # Shared PHP components
├── uploads/                # Uploaded item photos
├── database.sql            # Database structure
├── migration.sql           # Database migrations
├── index.php               # Main page
├── login.php               # User login
├── register.php            # User registration
├── report-item.php         # Item reporting
├── item-details.php        # Item details
├── claim-item.php          # Claim submission
├── my-items.php            # User reported items
├── my-claims.php           # User claims
└── profile.php             # User profile
```

## Installation and Setup

1. Install XAMPP with Apache and MySQL.
2. Copy the project folder into `htdocs`.
3. Start Apache and MySQL through the XAMPP Control Panel.
4. Create a MySQL database.
5. Import `database.sql` using phpMyAdmin.
6. Configure the database connection using your local database settings.
7. Open the application in your browser:

```text
http://localhost/lost-found-system/
```

## Hosting

NADUfind is hosted using InfinityFree with PHP and MySQL support.

The hosted application requires the appropriate database configuration and an active internet connection.

## Future Improvements

- Email notifications for claim updates.
- Additional security enhancements.
- Improved search and filtering.
- Additional reporting and analytics features.

## Project Information

**Project Name:** NADUfind  
**Project Type:** Web-Based Lost and Found Management System  
**Backend:** PHP  
**Database:** MySQL