# NADUfind – Lost and Found Management System

NADUfind is a web-based Lost and Found Management System designed to simplify the process of reporting, locating, and recovering lost belongings. The platform provides a centralized system where users can report lost or found items, search for reported belongings, submit ownership claims, and monitor the status of their submissions.

It also includes an administrative dashboard that allows administrators to manage users, verify item reports, process claims, and oversee the overall system.

---

## 1. Features and Functionalities

### User Authentication and Account Management
- Secure user registration and login.
- Password hashing and session management.
- User profile management.
- Account-based access to reporting and claim functionalities.

### Lost and Found Item Reporting
- Submit reports for lost and found items.
- Provide item descriptions, categories, locations, and dates.
- Upload item photos to help identify reported belongings.
- Manage personal item reports.

### Search and Filtering
- Search reported items using keywords.
- Filter items by type, category, and status.
- AJAX-based search functionality for improved browsing.
- View detailed information about reported items.

### Claims Management
- Submit claims for found items.
- Provide proof-of-ownership details to support claims.
- Track the status of submitted claims.
- View personal claim records.

### Administrative Dashboard
- Manage registered user accounts.
- Review, verify, and manage item reports.
- Approve or reject submitted ownership claims.
- Monitor item reports and claim records.
- Oversee lost and found activities within the system.

### Item Status Tracking
- Track item statuses throughout the recovery process:
  - **Lost** – An item has been reported missing.
  - **Found** – An item has been reported as found.
  - **Claimed** – A claim has been submitted or associated with the item.
  - **Returned** – The item has been successfully returned to its owner.
- Monitor changes in item records and their current statuses.

---

## 2. Technology Stack

| Component | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap |
| Backend | PHP |
| Database | MySQL |
| Local Development Environment | XAMPP |
| Web Hosting | InfinityFree |
| Asynchronous Requests | AJAX |

---

## 3. Project Structure

```text
lost-found-system/
│
├── admin/                  # Administrative modules
├── api/                    # AJAX search endpoints
├── asset/                  # Bootstrap resources and libraries
├── assets/                 # Custom CSS and JavaScript
├── config/                 # Database configuration
├── includes/               # Shared PHP components
├── uploads/                # Uploaded item photos
│
├── database.sql            # Database structure
├── migration.sql           # Database migrations
│
├── index.php               # Main page
├── login.php               # User login
├── register.php            # User registration
├── report-item.php         # Lost and found item reporting
├── item-details.php        # Item details
├── claim-item.php          # Claim submission
├── my-items.php            # User-reported items
├── my-claims.php           # User-submitted claims
└── profile.php             # User profile management
```

---

## 4. Installation and Setup

### Prerequisites
- XAMPP
- Apache Web Server
- MySQL Database
- PHP
- A modern web browser

### Local Installation

1. Install XAMPP on your computer.

2. Copy the `lost-found-system` project folder into the XAMPP `htdocs` directory:

   ```text
   C:\xampp\htdocs\
   ```

3. Open the XAMPP Control Panel and start:
   - Apache
   - MySQL

4. Open phpMyAdmin in your browser:

   ```text
   http://localhost/phpmyadmin/
   ```

5. Create a MySQL database for the application.

6. Import the `database.sql` file through phpMyAdmin.

7. If applicable, execute `migration.sql` to apply the additional database changes.

8. Configure the database connection in the appropriate file inside the `config/` directory using your local MySQL credentials.

9. Open NADUfind in your browser:

   ```text
   http://localhost/lost-found-system/
   ```

The application should now be accessible through your local XAMPP environment.

---

## 5. Web Hosting and Deployment

NADUfind is deployed using **InfinityFree**, a web hosting platform that supports PHP and MySQL.

The hosted application requires:
- Properly configured hosting database credentials.
- The correct database connection settings.
- Uploaded project files in the appropriate hosting directory.
- An active internet connection to access the hosted application.

When deploying the application, make sure the database configuration matches the credentials and database information provided by InfinityFree.

---

## 6. Future Enhancements

The following improvements may be considered for future development:

- **Email Notifications** – Notify users about claim approvals, rejections, and item status updates.
- **Enhanced Security** – Introduce additional security measures for authentication, file uploads, and user data protection.
- **Improved Search and Filtering** – Provide more advanced search options for faster item discovery.
- **Reporting and Analytics** – Add statistical reports and visual dashboards to monitor lost and found activities.
- **User Experience Improvements** – Enhance the interface, navigation, and responsiveness across different devices.

---

## 7. Project Information

| Information | Details |
|---|---|
| Project Name | NADUfind |
| Project Type | Web-Based Lost and Found Management System |
| Backend | PHP |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript, Bootstrap |
| Development Environment | XAMPP |
| Hosting Platform | InfinityFree |

---

**NADUfind – Report It. Find It. Get  Back.**

A centralized platform that makes reporting lost belongings, finding reported items, and managing ownership claims easier and more organized.