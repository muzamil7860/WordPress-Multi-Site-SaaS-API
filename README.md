# WordPress-Multi-Site-SaaS-API
WordPress SaaS API for automated site creation. Duplicates the main database, configures WordPress settings, assigns admin users, and sets up a custom uploads directory. Implements a secure REST API endpoint for seamless multi-site management. 🚀

#Created by Muzammil Attiq | Senior Software Engineer

# WordPress Multi-Site SaaS API Plugin Documentation

## Overview
The **WordPress Multi-Site SaaS API Plugin** enables seamless creation of multiple WordPress sites from a single installation. Each site gets its own **separate database** and **unique upload folder** to maintain isolation.

## Features
- API-driven multi-site creation
- Separate database per site
- Unique upload folder for media storage
- Automatic WordPress table duplication from a base database
- Admin user creation or update
- Automatic URL and upload path configuration

---
## Configuration

### 1. **Database Configuration**
Modify your `wp-config.php` file to dynamically set the database name based on the subdomain:

```php
$domain = $_SERVER['HTTP_HOST'];
$subdomain = explode('.', $domain)[0];

if ($subdomain == 'wpsaas') {
    define('DB_NAME', 'saaswp');
} else {
    define('DB_NAME', 'wp_' . $subdomain);
}
```

---
## API Usage

### Endpoint
**URL:** `http://yourdomain.com/wp-json/custom/v1/create-site`

**Method:** `POST`

**Headers:**
```json
Content-Type: application/json
```

**Request Body:**
```json
{
  "subdomain": "mysite",
  "email": "admin@mysite.com",
  "admin_user": "admin",
  "admin_pass": "securepassword"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Site created successfully",
  "url": "http://mysite.yourdomain.com",
  "uploads_path": "/wp-content/uploads/mysite",
  "database": "wp_mysite"
}
```

---
## Plugin Installation
1. Place the plugin files in `wp-content/plugins/` directory.
2. Activate the plugin from the WordPress dashboard.
3. Ensure the SQL dump file (`saaswp.sql`) is present in the root WordPress directory.

---
## How It Works
1. The API receives a request to create a new site.
2. It validates inputs and generates a **new database**.
3. The default WordPress tables are copied from the base `saaswp` database.
4. The **admin user** is created or updated.
5. The **upload directory** is set up per site.
6. The site URL and upload paths are updated.

---
## Troubleshooting
### 1. **Database already exists**
- The subdomain might be reused.
- Delete the existing database manually or use a different subdomain.

### 2. **SQL file not found**
- Ensure `saaswp.sql` is placed correctly in the WordPress root directory.

### 3. **Uploads directory creation failed**
- Check file permissions (`755` or `775` recommended).

For more details, visit [GitHub Repository](https://github.com/muzamil7860/WordPress-Multi-Site-SaaS-API)

