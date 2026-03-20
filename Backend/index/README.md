# Backend/index — Setup Guide (MongoDB + PHP)

## Prerequisites

| Requirement | Details |
|---|---|
| XAMPP | Apache + PHP running |
| MongoDB | Running on `localhost:27017` |
| PHP ext-mongodb | MongoDB PHP driver installed |
| Composer | For MongoDB PHP Library |

---

## Step 1 — Install MongoDB PHP Library (Composer)

Open terminal inside `Loan-Management-System/Backend/index/` and run:

```bash
composer require mongodb/mongodb
```

This creates a `vendor/` folder used by `config/db.php`.

---

## Step 2 — Install PHP MongoDB Extension (ext-mongodb)

Download the correct `.dll` for your PHP version from:
https://pecl.php.net/package/mongodb

Then add to `php.ini` (found in `C:/xampp/php/php.ini`):
```ini
extension=mongodb
```

Restart Apache in XAMPP Control Panel.

---

## Step 3 — Seed the Database

Open **MongoDB Shell (mongosh)** and run:

```bash
mongosh fundbee_db "C:/xampp/htdocs/Loan-Management-System/Database/seed.js"
```

This inserts all `site_stats` and `loan_products` documents.

---

## Step 4 — Verify Collections

In mongosh:
```js
use fundbee_db
db.site_stats.find()
db.loan_products.find()
```

---

## API Endpoints

| File | Method | URL | Purpose |
|---|---|---|---|
| `get_stats.php` | GET | `/Backend/index/get_stats.php` | Homepage stats |
| `get_products.php` | GET | `/Backend/index/get_products.php` | Loan products |
| `contact_inquiry.php` | POST | `/Backend/index/contact_inquiry.php` | CTA form submit |
| `newsletter_subscribe.php` | POST | `/Backend/index/newsletter_subscribe.php` | Newsletter signup |

---

## MongoDB Collections

| Collection | Created By | Purpose |
|---|---|---|
| `site_stats` | seed.js | Stats band numbers |
| `loan_products` | seed.js | Services/products cards |
| `contact_inquiries` | contact_inquiry.php | CTA form submissions |
| `newsletter_subscribers` | newsletter_subscribe.php | Email subscriptions |
