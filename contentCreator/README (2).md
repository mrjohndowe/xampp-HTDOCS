# Private & Personal Storefront

A standalone PHP storefront for the premium menu. It uses PHP 8.5, PDO SQLite, and the XAMPP Apache server. The database is created automatically at `storefront/storage/storefront.sqlite` on the first page load.

## Run with XAMPP

1. Copy this project folder into `C:\xampp\htdocs\folder_structure` (or copy only `storefront` into `C:\xampp\htdocs\storefront`).
2. In the XAMPP Control Panel, start Apache. In `php.ini`, enable `extension=pdo_sqlite` and `extension=sqlite3` if they are commented out, then restart Apache.
3. Open `http://localhost/folder_structure/storefront/` (or `http://localhost/storefront/` if copied on its own).
4. Confirm the adult-only gate and add a package. Submitting checkout stores a pending request; it does not charge a customer or send email.
5. Set up the owner inbox below before accepting customer orders.

The Apache `.htaccess` files deny web access to the SQLite storage folder and prevent directory listings. Keep Apache `AllowOverride All` enabled for this directory. The storefront requires PHP 8.5 or newer.

## Configure the order inbox

1. Make a copy of `config.example.php` named `config.local.php` in this folder.
2. Generate a strong password hash from a terminal with XAMPP PHP:

   ```powershell
   C:\xampp\php\php.exe -r "echo password_hash('replace-this-with-a-unique-password', PASSWORD_DEFAULT), PHP_EOL;"
   ```

3. Paste the generated hash into `config.local.php` as the `admin_password_hash` value. Use a long, unique password and never upload this file to a public repository.
4. Visit `/storefront/admin.php` and sign in. The inbox shows the latest 100 requests and customer contact details. Protect the host and database backups; order details are private customer data.

## Store behavior and important notes

- The catalog and prices are defined server-side in `includes/bootstrap.php`. Prices are integer cents and are recalculated on the server when an order is submitted.
- Girlfriend-experience tiers display the listed starting price. Custom durations, upgrades, availability, access and final terms must be confirmed with the customer before payment.
- Custom requests and upgrades can be described in the notes field; they are not priced automatically.
- No card details are collected, no payment gateway is configured, and no automatic fulfillment or email is enabled. Contact the customer from the inbox and agree on payment and delivery directly.
- Update the footer/brand copy and configure your own secure hosting, privacy notice, and customer contact process before publishing. Use HTTPS in production and restrict access to the admin page at the server level when possible.
- SQLite needs the web server account to have write access to `storefront/storage`. Back up the database securely and do not expose it publicly.
