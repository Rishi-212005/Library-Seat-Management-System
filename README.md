Library Seat Management System
==============================

Modern web-based system for managing reading room seats in a public library (built for **District Library, Anantapur**).  
It provides separate portals for public users and librarians, with real-time seat availability, booking, attendance tracking, and user verification.

---

## Features

- **Public / Student Portal**
  - Self-registration with photo and ID upload
  - Login using roll number / Aadhar and password
  - View registration status (pending / approved / rejected + reason)
  - Real-time **seat availability** view for:
    - 80 study seats across two floors
    - 20 computer stations (`C1–C20`)
  - Book seats for a selected date, start time, and duration
  - See current and past bookings with:
    - Booking code
    - Attendance code
    - Status: `booked`, `attended`, `cancelled`
  - Email confirmation for successful bookings (via PHPMailer)

- **Librarian Portal**
  - Secure login (separate from public users)
  - Dashboard with:
    - Total registered users
    - Pending / approved / rejected user counts
    - Today’s bookings, active bookings, and attendance rate
  - Manage users:
    - View registration details and uploaded photos
    - Approve / reject users with issue/reason
  - Check-in interface:
    - Mark bookings as `attended` using attendance codes
  - Live seat view for today’s bookings
  - Booking history and reports (export to CSV)

- **Seat Booking & Attendance Logic**
  - Time-slot based booking (e.g. 9:00–11:00, 13:00–15:00)
  - Real-time conflict detection:
    - Seats marked as `booked` or `attended` when overlapping reservations exist
  - Auto-cancellation script for late check-ins (cron-friendly)

- **Others**
  - Responsive modern UI using plain HTML/CSS/JS (no framework build step)
  - Clear separation between public and librarian views
  - Email sending via **PHPMailer** (Composer-managed)

---

## Tech Stack

- **Backend**: PHP (procedural + helper functions)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML, CSS, vanilla JavaScript
- **Mail**: `phpmailer/phpmailer` (via Composer)
- **Session & Security**
  - PHP sessions for auth
  - CSRF tokens on sensitive forms
  - Server-side validation and sanitization

---

## Project Structure (Simplified)

```text
.
├── about-us.html / gallery.html / contact-us.html   # Public information pages
├── index.html                                       # Landing page with public & librarian entry points
├── student-login.php                                # Public login
├── student-register.php                             # Public registration with photo uploads
├── seat-booking.php                                 # Authenticated seat booking UI (public)
├── librarian-login-final.html                       # Librarian login page
├── librarian-login-handler.php                      # Librarian auth (JSON API)
├── librarian-dashboard.html / librarian-dashboard.js# Librarian dashboard & stats
├── librarian-checkin.php / librarian-checkin-api.php# Attendance / check-in tools
├── manage-students.html / manage-students.js        # Librarian user management UI
├── manage-students-api.php                          # JSON API for student management & stats
├── get-seat-status.php / get-booked-seats.php       # Seat availability APIs
├── auto-cancel-late-bookings.php                    # Cron script for auto-cancellations
├── includes/
│   ├── functions.php                                # Core business logic & helpers
│   └── email-functions.php                          # PHPMailer integration
├── config/
│   └── database.php                                 # `getDBConnection()` (PDO)
├── styles.css                                       # Global styling & layout
├── index.js / navigation.js / script.js             # Frontend helpers and navigation
├── vendor/                                          # Composer (PHPMailer)
├── database/
│   └── update_schema.sql                            # Database schema & updates
└── composer.json / composer.lock                    # PHP dependencies
```

> Note: There are additional helper/utility files for diagnostics, exports, and visual seat maps (e.g. `see/`, `seatb/`), but the above list covers the main flows.

---

## Database Schema (Overview)

The exact schema is defined in `database/update_schema.sql`, but the key tables are:

- **`students`**
  - Core public user records with:
    - `student_name`, `roll_number`, `email`, `phone`
    - `password` (hashed)
    - `passport_photo`, `id_card_photo` (stored in compressed/base64 form)
    - `status` (`pending`, `approved`, `rejected`)
    - `issue` (optional rejection reason)
    - `registration_date`, `updated_at`

- **`seat_bookings`**
  - Seat reservation records:
    - `student_id`, `seat_id` (1–80, C1–C20)
    - `booking_date`
    - `start_time` (hour, e.g. `9` for 9:00)
    - `duration` (hours)
    - `status` (`booked`, `attended`, `cancelled`)
    - `booking_code`, `attendance_code`
    - `created_at`, `updated_at`, `check_in_time`

Additional internal tables may be used for diagnostics or logging.

---

## Getting Started (Local Development)

### Prerequisites

- PHP 8.x (with PDO MySQL extension)
- MySQL / MariaDB database
- Composer (for PHP dependencies)
- A web server that can run PHP (e.g. Apache, Nginx, or PHP’s built-in server)

### 1. Clone the Repository

```bash
git clone https://github.com/<your-username>/<your-repo-name>.git
cd <your-repo-name>
```

### 2. Install PHP Dependencies

```bash
composer install
```

This installs **PHPMailer** under `vendor/`.

### 3. Create the Database

1. Create a new MySQL database (e.g. `library_management`).
2. Import the schema:

```bash
mysql -u <user> -p <database_name> < database/update_schema.sql
```

Alternatively, you can use the provided helper scripts (if present in your version) such as `database-setup.php` or `update-database-schema.php` after configuring database access.

### 4. Configure Database Connection

Update your database credentials to match your local environment.  
For local development, you typically configure:

- `config/database.php` (for PDO-based APIs)
- or `config.php` / `db_connection.php` where used.

Example (`config/database.php`):

```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_db_user');
define('DB_PASSWORD', 'your_db_password');
define('DB_NAME', 'library_management');
```

> **Important**: Do **not** commit real production credentials to GitHub. Replace any existing production values with placeholders before pushing.

### 5. Configure PHPMailer (Email)

The project uses PHPMailer via Composer.  
Update the mail configuration (typically in `includes/email-functions.php`) with your SMTP settings:

- SMTP host
- SMTP username/password
- From address and name

You can test email sending using `test-email.php` if included.

### 6. Run the Application

Using PHP’s built-in server:

```bash
php -S localhost:8000
```

Then open `http://localhost:8000/index.html` in your browser.

Or configure a virtual host in Apache/Nginx pointing to the project root.

---

## Usage

### Public / Student Flow

1. **Register**
   - Go to `student-register.php`.
   - Fill in your details, upload a passport photo and ID/Aadhar photo.
   - Submit the form; your status becomes **pending**.
2. **Wait for Approval**
   - Librarian reviews your application.
   - You can check status via `check-status.php` or from the login page.
3. **Login**
   - Once approved, log in via `student-login.php`.
4. **Book a Seat**
   - Navigate to the booking page (`seat-booking.php` / `library-system.html → booking`).
   - Select date, start time, and duration.
   - Choose a seat from the interactive map (two floors + computer stations).
   - Confirm the booking; note down the **booking code** and **attendance code** (also sent by email if configured).
5. **Attend**
   - Arrive at the library.
   - Provide your attendance code at the librarian’s desk for check-in.

### Librarian Flow

1. **Login**
   - Go to the librarian login page (`librarian-login-final.html`).
   - Submit credentials (configured in `librarian-login-handler.php`).
2. **Review Registrations**
   - Open **Manage Students**.
   - View each applicant’s details and photos.
   - Approve or reject, optionally adding an issue/reason.
3. **Monitor Dashboard**
   - View total users, pending/approved/rejected counts.
   - Track today’s bookings and attendance.
4. **Check-In Users**
   - Use **Check In Public** page.
   - Enter booking/attendance codes to mark users as **attended**.
5. **Seat and Booking History**
   - View current seat occupancy maps.
   - Export logs/bookings for reporting where supported.

---

## Cron / Scheduled Tasks

The file `auto-cancel-late-bookings.php` is designed to be run periodically (e.g. every 5–10 minutes) to automatically cancel bookings where users did not check in on time.

Example cron entry:

```bash
*/10 * * * * /usr/bin/php /path/to/project/auto-cancel-late-bookings.php
```

This script logs its actions under `logs/auto-cancel.log`.

---

## Security Notes

- **CSRF Protection**: Sensitive forms (login, registration, seat booking) use CSRF tokens generated and verified server-side.
- **Password Storage**: Passwords are stored in hashed form (see `hashPassword()` / `verifyPassword()` helpers).
- **Input Validation**:
  - All user input is sanitized using helper functions before DB interaction.
  - Additional client-side validation in JavaScript is provided for better UX.
- **Sessions**:
  - Public users and librarians use PHP sessions to persist their login state.
  - Some views also use `localStorage` / `sessionStorage` for UX improvements (e.g. showing “already logged in” prompts).

You should still perform your own security review before deploying this to a public/production environment.

---

## Customization

- **Branding**
  - Update library name, logo, and text in:
    - `index.html`
    - `student-login.php`, `student-register.php`
    - `library-system.html`, `librarian-dashboard.html`
  - Replace `assets/college-logo.avif` with your library’s logo.

- **Seat Layout**
  - The seat map (tables, seat IDs, floors, computer stations) is defined in:
    - `seat-booking.php` (and related HTML/JS)
  - You can change the number of seats, tables, or floor names by updating both:
    - The HTML layout
    - The default seat generation logic in APIs (`get-seat-status.php`, etc.)

- **Librarian Credentials**
  - Initial credentials are hard-coded in `librarian-login-handler.php`.
  - For production, you should move these into a secure data store (DB or config file) and implement password hashing.

---

## Running in Production

- Serve via Apache or Nginx with PHP-FPM.
- Make sure:
  - `logs/` is writable by the web server user.
  - `vendor/` and `includes/` are not web-browsable (configure `deny`/`location` rules).
  - Real database and SMTP credentials are stored securely.
  - Display of detailed error messages is turned **off** in production (`display_errors=0`).

---

## License

This project is licensed under the **MIT License**.

---

## Author

Developed by **RISHI VEDI AND HIS TEAM** for **District Library, Anantapur**.  
Feel free to fork and adapt it for your own institution or library.
