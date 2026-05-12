# Azure Sands Resort – Full-Stack Web Application
### Built with HTML5 · CSS3 · JavaScript · PHP · MySQL · XAMPP

---

## 📁 Project Structure

```
azure-sands-resort/
├── index.php                  ← Main website (homepage)
├── .htaccess                  ← Apache security & routing
├── README.md
│
├── config/
│   └── database.php           ← DB connection + helper functions
│
├── includes/
│   └── common.php             ← Session, auth helpers
│
├── auth/
│   └── auth.php               ← Register / Login / Logout / Forgot Password API
│
├── bookings/
│   ├── booking.php            ← Booking create / my-bookings API
│   └── export.php             ← Admin CSV export
│
├── orders/
│   └── order.php              ← Food order API
│
├── careers/
│   └── apply.php              ← Job application API (with CV upload)
│
├── memberships/
│   └── dashboard.php          ← Member dashboard (logged-in users)
│
├── api/
│   └── newsletter.php         ← Newsletter subscribe / contact API
│
├── admin/
│   ├── login.php              ← Admin login
│   ├── logout.php             ← Admin logout
│   ├── header.php             ← Shared admin header/sidebar
│   ├── footer.php             ← Shared admin footer
│   ├── index.php              ← Dashboard with analytics
│   ├── bookings.php           ← Manage bookings (status updates, search, filter)
│   ├── orders.php             ← Manage food orders
│   ├── rooms.php              ← Add / Edit / Delete rooms
│   ├── food.php               ← Add / Edit / Delete food items
│   ├── users.php              ← View / delete registered users
│   ├── careers.php            ← Manage job applications (status + CV download)
│   └── messages.php           ← Contact messages & newsletter subscribers
│
├── assets/
│   ├── css/
│   │   ├── style.css          ← Main frontend stylesheet
│   │   └── admin.css          ← Admin panel stylesheet
│   ├── js/
│   │   └── app.js             ← All frontend JavaScript (modals, forms, AJAX)
│   └── images/                ← All resort images
│
├── uploads/
│   ├── .htaccess              ← Blocks PHP execution in uploads
│   └── cvs/                   ← Uploaded CVs (auto-created)
│
└── database/
    └── resort.sql             ← Complete MySQL schema + seed data
```

---

## ⚡ XAMPP Setup (Step-by-Step)

### Step 1 – Install XAMPP
Download from [https://www.apachefriends.org](https://www.apachefriends.org) and install.

### Step 2 – Copy Project
Move the `azure-sands-resort` folder into:
```
C:\xampp\htdocs\azure-sands-resort\
```

### Step 3 – Start Services
Open **XAMPP Control Panel** and start:
- ✅ Apache
- ✅ MySQL

### Step 4 – Open phpMyAdmin
Visit: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

### Step 5 – Create Database
Click **New** → Database name: `azure_sands_resort` → **Create**

### Step 6 – Import Schema
- Click the `azure_sands_resort` database
- Click the **Import** tab
- Choose file: `database/resort.sql`
- Click **Go**

### Step 7 – Verify Config
Open `config/database.php` – default XAMPP settings should work:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // blank for default XAMPP
define('DB_NAME', 'azure_sands_resort');
define('SITE_URL', 'http://localhost/azure-sands-resort');
```

### Step 8 – Launch Website
Visit: [http://localhost/azure-sands-resort/](http://localhost/azure-sands-resort/)

### Step 9 – Admin Panel
Visit: [http://localhost/azure-sands-resort/admin/](http://localhost/azure-sands-resort/admin/)

**Default admin credentials:**
- Username: `admin`
- Password: `admin123`

> ⚠️ **Change the admin password immediately** after first login. To update via phpMyAdmin:
> ```sql
> UPDATE admins SET password = '$2y$10$YOUR_NEW_HASH' WHERE username = 'admin';
> ```
> Generate a hash with: `<?php echo password_hash('your_new_password', PASSWORD_BCRYPT); ?>`

---

## 🔘 Button → Modal Logic (Preserved from original)

| Button / ID              | Opens                   |
|--------------------------|-------------------------|
| `#openBooking`           | Booking Modal           |
| `.open-booking`          | Booking Modal           |
| `#openBookingDropdown`   | My Bookings (if logged in) / Booking Modal |
| `#openMembership`        | Sign In / Register Modal |
| `#openMembershipDropdown`| Sign In / Register Modal |
| `.open-order`            | Food Order Modal        |
| `#exploreFood`           | Smooth scroll → Dining  |
| `#openOrderDropdown`     | My Orders (if logged in) / Order Modal |
| `#openApplicationDropdown`| Careers Modal          |
| `#viewAllRooms`          | All Rooms Modal         |
| `#exroom`                | Smooth scroll → Rooms   |
| `#viewFullMenu`          | Full Menu Modal         |

---

## 🔐 Security Features

- **Password hashing**: `password_hash()` with BCRYPT cost 12
- **Prepared statements**: All DB queries use PDO prepared statements
- **CSRF protection**: Tokens on all forms
- **XSS prevention**: All output via `htmlspecialchars()`
- **Input sanitization**: All inputs sanitized before processing
- **File upload validation**: Extension + size checks for CVs
- **PHP execution blocked**: `.htaccess` in `/uploads/` directory
- **Directory listing disabled**: `Options -Indexes` everywhere
- **Session security**: Secure cookie flags, session regeneration

---

## 📊 Database Tables

| Table                   | Purpose                          |
|-------------------------|----------------------------------|
| `users`                 | Registered guests                |
| `admins`                | Admin panel users                |
| `rooms`                 | Room catalogue                   |
| `bookings`              | Room reservations                |
| `food_items`            | Menu items                       |
| `food_orders`           | Food orders                      |
| `food_order_items`      | Order line items                 |
| `memberships`           | Loyalty programme                |
| `careers`               | Job positions                    |
| `applications`          | Job applications                 |
| `reviews`               | Guest reviews                    |
| `contact_messages`      | Contact form submissions         |
| `newsletter_subscribers`| Email subscribers                |

---

## 🚀 Features

### Frontend
- Premium luxury design with glassmorphism effects
- Fully responsive (mobile-first)
- Smooth scroll animations
- Modal system — all 7 modals with correct triggers
- Live booking price calculator
- Wishlist / favourite toggles
- Back to top button
- Preloader animation

### Authentication
- Register / Login / Logout
- Remember me (persistent cookie)
- Forgot password token system
- Auto-login after registration
- Session-aware dropdown (changes links when logged in)

### Booking System
- Availability conflict detection
- Live price summary
- Loyalty points awarded on booking
- Booking reference number
- Admin status management

### Food Ordering
- 8 menu items, 2 selections per order
- Quantity controls
- Order reference number
- Delivery datetime scheduling
- Admin status management

### Career Portal
- CV upload (PDF/DOC/DOCX, max 5MB)
- 4 positions
- Application tracking
- Admin review with status updates

### Membership / Loyalty
- 4 tiers: Bronze → Silver → Gold → Platinum
- Points earned per booking
- Member dashboard at `/memberships/dashboard.php`
- Tier benefits displayed

### Admin Panel
- Dashboard with live stats + monthly revenue
- Bookings management (filter, search, status update, CSV export)
- Orders management (filter, search, status update)
- Rooms CRUD (add/edit/delete/toggle availability)
- Food items CRUD
- Users listing (delete)
- Applications management (status update, CV download)
- Contact messages + newsletter subscribers

---

## 📝 Notes

- Images in `assets/images/` — replace with high-quality resort photos for production
- Newsletter subscription saves to `newsletter_subscribers` table
- The forgot-password flow saves a token to DB; wire up an email sender (e.g. PHPMailer) for production
- For HTTPS, update `SITE_URL` in `config/database.php`
