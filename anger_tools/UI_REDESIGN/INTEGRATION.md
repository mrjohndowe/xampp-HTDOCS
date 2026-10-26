# Anger Tools — Modern Nav & UI Integration Guide

This package modernizes the navigation and login page of
`https://mrjohndowe.com/anger_tools/` without touching your PHP logic.

## What was wrong

- The nav was an old sliced-image menu (`.onlycssmenu`) that relies on
  `menu_stip.png` / `menu_div.png` background images and floated list items.
- `modern.css` tried to restyle it with conflicting green gradients and a
  crimson border on top of the legacy red theme.
- The nav HTML had stray `<a>` tags wrapped around list items, a
  hard-coded active state on Home, and the date wrapped in a dead link.
- No mobile navigation at all — the menu simply wrapped or overflowed.

## What this package does

- A new sticky top nav with brand, pill links, active states, a date pill,
  and a hamburger menu below 880px (with scrim, Escape-to-close, and
  outside-click close).
- A modern dark theme for the page shell and login card, keeping your
  site's red identity.
- Active nav link is detected automatically from the `?p=` query
  parameter — no PHP changes required.
- All routes, form field names, and endpoints are unchanged:

| Item | Kept as-is |
|---|---|
| Nav destinations | `.`, `?p=treatment`, `?p=anger`, `?p=publicanger`, `?p=allLogs` |
| Login form | `action="?p=login" method="post"` |
| Input names | `username`, `pass` |
| Register link | `?p=register` |

## Files

| File | Goes to | Purpose |
|---|---|---|
| `extra/css/am-modern.css` | `anger_tools/extra/css/` | Modern UI layer (loads last) |
| `extra/js/am-nav.js` | `anger_tools/extra/js/` | Mobile menu + active-link logic |
| `snippets/nav.html` | replaces your nav block | New navigation markup |
| `snippets/login-main.html` | replaces login page content | Modern login card |

## Installation steps

### 1. Upload the two new files

- `am-modern.css` → `anger_tools/extra/css/`
- `am-nav.js` → `anger_tools/extra/js/`

### 2. Add the viewport meta tag (required for the mobile menu)

Your current pages have no viewport meta tag, so phones render at a
~980px layout width and the 880px mobile menu never triggers. Add
this to the `<head>` on every page:

```html
<meta name="viewport" content="width=device-width, initial-scale=1">
```

### 3. Add the stylesheet and script

In the `<head>`, after your existing stylesheet links (order matters —
`am-modern.css` must be last):

```html
<link href="extra/css/modern.css" rel="stylesheet">
<link href="extra/css/am-modern.css" rel="stylesheet">   <!-- add this -->
```

Before the closing `</body>` (or in the head with `defer`):

```html
<script src="extra/js/am-nav.js" defer></script>
```

Optional — the Nunito font your `admin.css` expects (add to `<head>`):

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
```

### 4. Replace the nav block

Find this block in your template (it appears on every page):

```html
<div class="container onlycssmenu clearfix">
  ... </div>
```

Replace the entire block — from its opening `<div>` through its closing
`</div>` — with the contents of `snippets/nav.html`.

If your PHP echoes the date or timezone inside the old nav, move that
echo inside the `<li class="am-nav-time">` element — otherwise the JS
fills in today's date automatically (America/Denver time).

Note: `am-modern.css` does NOT hide the old nav, so update the nav
block on every page that includes it. Until a page is updated, it
simply keeps its current menu.

### 5. Replace the login page content (login page only)

On the login page, the body after the nav block looks like this:

```html
America/Denver
<div class="container">
  <center><h1 ...>...</h1></center>
  <hr>
  <div class="row">
    <form ...>...</form>
  </div>
</div>
<hr>
<center>
  <footer class="container shadow footer">...</footer>
</center>
<hr>
```

First, delete the standalone `America/Denver` line between the nav and
the content (comment out that PHP echo — it is debug output; the
timezone now lives in the nav date pill's tooltip).

Then replace the complete, balanced block — from the opening
`<div class="container">` (the one holding the login form) through the
final `<hr>` after the footer's `</center>` — with the contents of
`snippets/login-main.html` (which includes its own footer).

The form still posts to `?p=login` with the `username` and `pass` fields,
so your authentication code needs no changes.

### 6. Verify

- Desktop: nav is sticky, active link follows the `?p=` parameter.
- Mobile (under 880px): hamburger opens the menu, scrim dims the page,
  Escape or tapping outside closes it.
- Login still works end to end.

## Notes

- The old stylesheets (`admin.css`, `custom.css`, `modern.css`) stay in
  place — `am-modern.css` overrides only what it styles. Text colors,
  headings, and rules on your other pages (Treatment Tool, Anger Log,
  etc.) are untouched, so their existing content keeps its readable
  styling on top of the new dark background.
- Other pages only need steps 1–4; their content keeps existing styles.
- Rollback: remove the `am-modern.css` and `am-nav.js` includes, remove
  the viewport meta if you added it only for this, and restore the
  original nav block and the original login block on the login page.
