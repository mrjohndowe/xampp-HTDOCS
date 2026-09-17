# NovelShelf - XAMPP Edition

A drop-in PHP + SQLite novel library built for XAMPP.

## Requirements

- XAMPP with Apache and PHP 8.1+
- PHP extensions: PDO_SQLite, SQLite3, fileinfo
- No Composer
- No Node.js
- No MySQL setup

## Install

1. Extract the `novelshelf` folder into your XAMPP `htdocs` folder, for example:
   `B:\xampp\htdocs\novelshelf`
2. Start Apache in XAMPP.
3. Visit:
   `http://localhost/novelshelf/`
4. Open the admin area:
   `http://localhost/novelshelf/admin/`
5. On the first visit, create the administrator account.

The SQLite database is created automatically at `data/novelshelf.sqlite`.

## Features

- Novel library
- Separate upload page
- PDF uploads
- Optional JPG/PNG/WEBP cover images
- Built-in browser PDF reader
- 1-5 star ratings
- Written reviews
- Average rating and review count
- Secure first-run administrator creation
- Password hashing with PHP `password_hash()`
- Session-based admin login
- Login attempt throttling
- CSRF tokens for state-changing forms
- Prepared SQL statements
- Admin novel deletion
- Admin review moderation
- Randomized uploaded file names
- File MIME validation
- Script execution blocked inside uploads when Apache permits `.htaccess`
- Custom generated NovelShelf logo and splash artwork

## Upload Limits

NovelShelf allows PDFs up to 100 MB in application code. XAMPP/PHP may have lower limits.

If large PDFs fail, edit `php.ini` and raise:

```ini
upload_max_filesize=100M
post_max_size=110M
```

Then restart Apache.

## Security

The first administrator is created through `/admin/setup.php`. Once an administrator exists, the setup page automatically redirects to login.

For public internet hosting, also use HTTPS and keep PHP/XAMPP updated.
