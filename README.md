# WP Pop!

Create and manage popups for your WordPress site — sitewide or page-specific, with flexible frequency controls.

## Features

- Create popups as a custom post type via **WP Pop!** in the admin menu.
- Display popups **sitewide** or restrict them to **specific pages/posts**.
- Control how often a popup appears per visitor: always, per session, daily, weekly, or once.
- Set a **delay** (in seconds) before a popup appears on page load.
- Global defaults for frequency and delay, overridable per popup.

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Upload the `wp-pop` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **WP Pop! → Add New** to create your first popup.

## Template Overrides

Themes can fully customise the HTML markup used to render popups without editing plugin files.

### How it works

When displaying a popup, the plugin looks for a template file in the active theme (child theme is checked first, then parent theme) before falling back to its own default template.

**Priority order (highest to lowest):**

| Template file | Scope |
|---|---|
| `{theme}/wp-pop/popup-{id}.php` | Overrides one specific popup (by post ID) |
| `{theme}/wp-pop/popup.php` | Overrides all popups |
| Plugin default (`public/partials/wp-pop-popup.php`) | Built-in fallback |

### Creating a theme override

1. Inside your theme folder, create a `wp-pop/` subdirectory.
2. Copy `wp-content/plugins/wp-pop/public/partials/wp-pop-popup.php` into that folder as `popup.php` (or `popup-{id}.php` to target a single popup).
3. Edit the copy however you like.

### Available template variables

The following variables are in scope inside any popup template:

| Variable | Type | Description |
|---|---|---|
| `$popup` | `WP_Post` | The popup post object |
| `$popup_id` | `string` | HTML-safe identifier, e.g. `wp-pop-42` |
| `$frequency` | `string` | Frequency setting (`always`, `session`, `daily`, `weekly`, `once`) |
| `$delay` | `int` | Delay in seconds before the popup appears |
| `$content` | `string` | Filtered post content, safe to `echo` directly |

### Example: minimal custom template

```php
<?php // mytheme/wp-pop/popup.php ?>
<div id="<?php echo esc_attr( $popup_id ); ?>" class="my-popup" hidden
     data-wp-pop-id="<?php echo esc_attr( $popup_id ); ?>"
     data-wp-pop-frequency="<?php echo esc_attr( $frequency ); ?>"
     data-wp-pop-delay="<?php echo esc_attr( absint( $delay ) ); ?>">
    <h2><?php echo esc_html( get_the_title( $popup ) ); ?></h2>
    <div><?php echo wp_kses_post( $content ); ?></div>
    <button class="wp-pop__close" type="button"><?php esc_html_e( 'Close', 'wp-pop' ); ?></button>
</div>
```

> **Note:** The `data-wp-pop-id`, `data-wp-pop-frequency`, and `data-wp-pop-delay` attributes are read by the plugin's JavaScript and must be present for the popup to function correctly.

## Changelog

### 0.1.0
- Initial release.
