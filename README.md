# Appoint — Premium Booking & Appointment System

A full-stack PHP/MySQL booking system with a modern, elegant UI built for portfolios and production use.

---

## ✨ Features

### Customer Side
- User registration & login
- Browse and select services
- Interactive FullCalendar.js booking interface
- Real-time available time slots
- Booking confirmation page with reference number
- Email notification UI preview
- Customer dashboard with stats & mini calendar
- Booking history with filtering (All / Confirmed / Pending / Completed / Cancelled)
- Cancel appointments with reason
- Reschedule appointments
- Profile management (name, email, phone, password)
- Notification preferences

### Admin Panel
- Admin dashboard with business KPIs
- Manage all appointments (search, filter, update status, cancel)
- Full calendar view (Month / Week / List views)
- Customer directory with booking counts
- Services CRUD (add, edit, enable/disable, delete)
- Reports & Analytics:
  - Monthly revenue bar chart
  - Booking status donut chart
  - Revenue by service table
  - Top bookings table

### Technical
- PHP 8+ backend with PDO
- MySQL database
- AJAX (Fetch API) — no page reloads
- FullCalendar.js v6
- Responsive design (mobile-first)
- Session-based authentication
- Role-based access (customer / admin)
- Setup wizard at `/install.php`

---

## 🚀 Quick Start

### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- A web server (Apache / Nginx) or `php -S localhost:8000`

### Option A — Setup Wizard (Recommended)
1. Upload all files to your web server
2. Navigate to `http://yoursite.com/install.php`
3. Enter your database credentials
4. Create your admin account
5. Done! Login at `index.php`

### Option B — Manual Setup
1. Create a MySQL database:
   ```sql
   CREATE DATABASE appoint_db;
   ```
2. Import the schema:
   ```bash
   mysql -u root -p appoint_db < setup.sql
   ```
3. Edit `includes/db.php` with your credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'appoint_db');
   ```
4. Visit `http://localhost/booking-system/`

### Local Development (PHP built-in server)
```bash
cd booking-system
php -S localhost:8000
```
Then open: http://localhost:8000

---

## 🔐 Demo Credentials

| Role     | Email               | Password  |
|----------|---------------------|-----------|
| Customer | customer@demo.com   | demo123   |
| Admin    | admin@demo.com      | admin123  |

---

## 📁 Project Structure

```
booking-system/
├── index.php              # Login / Registration
├── dashboard.php          # Customer dashboard
├── booking.php            # Book appointment (3-step)
├── bookings.php           # Booking history
├── profile.php            # Customer profile
├── install.php            # Setup wizard
├── setup.sql              # Database schema + seed data
│
├── admin/
│   ├── index.php          # Admin overview
│   ├── appointments.php   # Manage all appointments
│   ├── calendar.php       # Full calendar view
│   ├── customers.php      # Customer directory
│   ├── services.php       # Services CRUD
│   └── reports.php        # Analytics & reports
│
├── php/
│   ├── auth.php           # Login / register API
│   ├── bookings.php       # Bookings CRUD API
│   ├── notifications.php  # Notifications API
│   ├── profile.php        # Profile update API
│   ├── admin.php          # Admin API
│   ├── reports.php        # Reports API
│   ├── install.php        # Installation API
│   └── logout.php         # Session destroy
│
├── includes/
│   ├── db.php             # PDO connection + helpers
│   └── sidebar.php        # Navigation sidebar partial
│
├── js/
│   ├── app.js             # Shared utilities (toast, modal, helpers)
│   ├── auth.js            # Login/register logic
│   ├── dashboard.js       # Customer dashboard logic
│   ├── booking.js         # Booking flow logic
│   ├── bookings.js        # Booking history logic
│   └── admin.js           # Admin dashboard logic
│
└── css/
    └── style.css          # Full stylesheet
```

---

## 🎨 Design System

- **Font Display:** Cormorant Garamond (serif, elegant)
- **Font Body:** DM Sans (clean, modern)
- **Primary Color:** `#0f0e0c` (rich black)
- **Accent Gold:** `#c9a84c`
- **Success Green:** `#27ae60`
- **Danger Red:** `#c0392b`

---

## 📧 Email Notifications

The system includes a full email notification UI preview. To enable actual email sending, integrate your preferred mail solution in `php/bookings.php` after the `createBooking()` function completes:

```php
// Example with PHPMailer or mail()
mail($userEmail, 'Booking Confirmed — ' . $service['name'], $emailBody);
```

---

## 🔧 Customization

- **Business Hours:** Edit slot generation in `php/bookings.php` → `getAvailableSlots()`
- **Slot Interval:** Change `30 * 60` to `60 * 60` for hourly slots
- **Services:** Add/edit via Admin Panel → Services
- **Colors:** CSS variables in `css/style.css` `:root`

---

## 📄 License

MIT — Free for personal and commercial use.
