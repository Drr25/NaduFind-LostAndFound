# NADUfind Lost and Found System Workflow

## Overview

NADUfind is a PHP application backed by MySQL. It lets users browse verified lost-and-found reports, submit reports, and make claims for found items. Administrators verify reports, review claims, manage accounts, and update item statuses.

The main request flow is:

1. A PHP page starts or reads the user's session and loads shared database, helper, and access-control code as needed.
2. The page reads or updates records in MySQL through the shared PDO connection in `config/db.php`.
3. HTML pages render the result. `api/search.php` also provides filtered verified items as JSON.

## Roles and Access

- **Visitor:** Can browse, search, filter, and view verified reports. Must sign in to submit a report or claim.
- **User:** Can report a lost or found item, claim an eligible found item, view their own reports and claims, and update their profile or password.
- **Admin:** Uses the admin dashboard to verify or hide reports, change item statuses, review claims, and manage user accounts. Admin accounts may browse the public pages but cannot submit reports or claims.

Registration and login are handled in `login.php` (the signup tab); `register.php` redirects there for older links. Registration checks the supplied details, requires the privacy agreement, and stores a password hash. Login verifies the password and account-active flag, then records the user ID, name, and role in the session. `logout.php` ends the session.

## User Workflow

### Find an item

1. Open `index.php` to browse verified reports.
2. Search by keyword (item name, description, or location), and optionally filter by report type, category, or status.
3. Open an item to see its details. Unverified reports are not listed publicly; the reporter and admins can view them while logged in.

### Report an item

1. Sign in as a regular user and open `report-item.php`.
2. Choose **Lost Item** or **Found Item**, then provide its name, category, description, location, and date. A photo is optional.
3. The application validates required details and, if supplied, accepts JPG, PNG, GIF, or WEBP photos up to 5 MB.
4. The report is saved with `is_verified = 0`. Its initial status is `Lost` or `Found`, matching the report type, and that initial status is written to `item_status_log`.
5. The user can track verification and status on `my-items.php`. Admin verification is require before the repor appears in public browse result.

### Claim a found item

1. Sign in as a regular user and open a verified item whose status is `Found`.
2. The reporter cannot claim their own item. A user with an existing pending or approved claim cannot submit another claim for that same item.
3. Submit an ownership explanation (at least 10 characters) and contact information.
4. The claim is saved with status `Pending` and appears on `my-claims.php` while awaiting admin review.

## Administrator Workflow

1. Sign in with an admin account to open `admin/admin_dashboard.php`.
2. In **Manage Items**, review reports pending verification. Verifying a report makes it visible publicly; unverifying it hides it again. Admins can also update its status or delete the report.
3. In **Manage Claims**, review the claimant's proof and contact information.
4. Approving a claim changes its status to `Approved`, changes the associated item's status to `Claimed`, logs that status change, and rejects other pending claims for the same item.
5. Rejecting a claim changes its status to `Rejected` and saves optional admin notes. The item status is not changed by rejection.
6. After handover, an admin can mark an approved claim's item as `Returned`; this status change is also logged.
7. Admins can manage user accounts from `admin/user_manage.php`.

## Statuses

### Item status

- **Lost:** A lost-item report's initial status.
- **Found:** A found-item report's initial status and the status required for a claim.
- **Claimed:** Set when an admin approves a claim.
- **Returned:** Set by an admin after the item is handed back.

Admins can also change an item's status directly in **Manage Items**. Status transitions are recorded in `item_status_log`.

### Claim status

- **Pending:** Newly submitted and awaiting review.
- **Approved:** Accepted by an admin; the item becomes `Claimed`.
- **Rejected:** Declined by an admin, or automatically rejected when another claim on the same item is approved.

## Main Data Records

- `users`: account details, password hash, role, and active state.
- `items`: report owner, lost/found type, item details, photo filename, status, and verification state.
- `claims`: claimant, proof and contact information, review status, and admin notes.
- `item_status_log`: history of item status changes and the account that made each change, when available.

Deleting a user cascades to that user's reports and claims. Deleting a report cascades to its claims and status history.

## Local Setup

1. Install XAMPP and start Apache and MySQL.
2. Place this project folder in XAMPP's `htdocs` directory.
3. Import `database.sql` into MySQL. It creates the `lost_found_db` database and its tables, along with a default admin account.
4. Check the connection settings in `config/db.php` and adjust them for the local MySQL instance.
5. Open `http://localhost/NaduFind-LostAndFound/` (or use the actual folder name if it differs).

The database seed defines `admin@lostfound.com` with password `admin123`; change the password before using the system beyond local testing.
