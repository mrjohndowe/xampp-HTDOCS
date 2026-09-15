# Private Novel Library - XAMPP + SQLite Edition

This build is based on the recovered original `novel-flipbook` project.

## Storage

The application now uses a real **SQLite database** at:

`data/novel_library.sqlite`

Books, reviews, administrator accounts, and login-attempt throttling are all stored in SQLite. JSON files are no longer used as the application database.

The `data` directory is blocked from direct web access by `.htaccess`.

## Preserved from the recovered project

- All 7 existing novels
- Existing cover artwork
- Existing descriptions and authors
- Bundled PDF.js reader files
- 3D two-page book reader and page-flip animation
- Animated shelf-loading splash screen
- Splash displays once per browser session
- Random 15-30 second striped loading progress

## Added / retained

- Separate Upload page
- 1-5 star ratings
- Written reviews
- Average rating and review counts
- Secure first-run administrator setup
- Password hashing with `password_hash()`
- Session authentication
- CSRF protection
- Prepared PDO queries
- Login-attempt throttling
- Admin moderation for books and reviews
- Automatic review deletion when a book is deleted

## Install

1. Extract the folder to your XAMPP `htdocs` directory, for example:
   `B:\xampp\htdocs\novel-flipbook`
2. Start Apache in XAMPP.
3. Visit:
   `http://localhost/novel-flipbook/`
4. Open the administrator page:
   `http://localhost/novel-flipbook/admin/`
5. Create the first admin account.

The SQLite database is already included with the seven recovered books. If the database file is removed, the application will automatically create a new empty database and schema.

## PHP extensions

PHP 8.1+ is recommended. The application requires:

- `pdo_sqlite`
- `sqlite3`
- `fileinfo`

XAMPP for Windows includes these extensions. If SQLite has been disabled, open XAMPP's `php.ini`, ensure these lines are enabled, then restart Apache:

```ini
extension=pdo_sqlite
extension=sqlite3
```

## PDF upload size

For PDFs up to 100 MB, set these values in `php.ini` if necessary:

```ini
upload_max_filesize=100M
post_max_size=110M
```

Restart Apache after changing `php.ini`.


## Splash Animation

The splash screen now uses an animated library backdrop showing people placing books onto shelves behind the loading panel. It appears only once per browser session and still uses the random 15–30 second striped loading bar.


## Bookshelf Interaction

On the library page, books now appear as visible binders/spines on the shelf. Clicking a spine slides the book outward, reveals the front cover, and lets the reader either open the book or flip it over to read the back-cover description.

The splash screen background now shows stylized illustrated people actively placing books onto the shelves.

When a spine is clicked, the book now pops out into the center of the screen in a dimmed-background preview overlay.

Book uploads are restricted to the authenticated Admin section at `/admin/upload.php`; the public navigation no longer exposes an upload form.

The splash background now uses real-looking library workers shelving books through animated crossfading photo scenes.

The splash background now animates multiple differently colored books being placed onto shelves over photoreal library-worker scenes.

The splash background now uses a three-frame photoreal sequence of real library workers actually shelving different colored books, with no overlaid animated fake books.


The splash background now uses a real animated GIF built frame by frame from photoreal shelving scenes. The fake overlaid books have been removed.
