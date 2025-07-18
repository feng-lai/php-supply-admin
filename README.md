# PHP Supply & Demand Admin Platform

This is a lightweight supply and demand management platform built with PHP. It provides a backend administration system for managing product supply and demand information, user submissions, and basic business operations. Designed for small and medium enterprises, this system helps streamline the matching of supply and demand resources.

## 🌟 Features

- 🔐 **Admin Dashboard**  
  Simple and clean backend interface for managing all platform operations.

- 📦 **Supply & Demand Listings**  
  Add, edit, and delete supply/demand entries with optional categorization and tags.

- 📝 **User Submissions**  
  Supports user-submitted content (supply or demand forms), pending approval by admins.

- 🔍 **Search & Filtering**  
  Basic keyword search and filter by categories to improve data accessibility.

- 📊 **Statistics Overview**  
  Overview of total supply, demand, user data, and publishing trends.

- 🧩 **Modular Architecture**  
  Easily extendable with additional modules or integrations.

## 🛠️ Tech Stack

- **Backend:** PHP (ThinkPHP Framework)
- **Frontend:** HTML + CSS + JavaScript (Admin UI Templates)
- **Database:** MySQL
- **Other:** jQuery, Bootstrap (legacy UI usage)

## 🚀 Getting Started

### Prerequisites

- PHP >= 7.1
- MySQL >= 5.6
- Apache / Nginx
- Composer (optional, if you want to manage packages)

### Installation

1. Clone the project:

```bash
git clone https://github.com/feng-lai/php-supply-admin.git
cd php-supply-admin


2. Import the SQL schema into your MySQL database (e.g., `supply_admin.sql`).

3. Configure your database in `/application/database.php` or `/config/database.php`.

```php
'hostname' => '127.0.0.1',
'database' => 'your_db_name',
'username' => 'your_db_user',
'password' => 'your_db_pass',
```

4. Deploy the project on your web server (Apache/Nginx) pointing to the `/public` directory as the root.

5. Access the admin panel via:

```
http://yourdomain.com/admin
```

Default credentials (if available in DB seed):
**Username:** admin
**Password:** admin123 *(Please change after first login)*

## 📁 Project Structure

```
php-supply-admin/
├── application/     # Main application logic (controllers, models, views)
├── public/          # Web root directory
├── config/          # Configuration files
├── runtime/         # Temporary storage (logs, cache)
├── static/          # Static resources (CSS, JS, images)
└── database/        # (optional) Database schema or seed files
```

## 📌 Notes

* This project is suitable for internal or SME-level deployments.
* For better security, enable SSL and add input validation if used in production.
* Legacy code may require updating for newer PHP versions or frameworks.

## 📄 License

This project is open-sourced for learning and customization. Refer to the repository or contact the author for licensing terms.

## 🙋 Author

Maintained by [feng-lai](https://github.com/feng-lai)

