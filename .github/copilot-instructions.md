# Copilot Instructions for Apprenticeship Connect

## Overview
This WordPress plugin integrates with the UK Government's Find an Apprenticeship API to display real-time apprenticeship vacancies. It is designed for educational institutions, training providers, and career services to keep their audiences informed of current opportunities.

## Architecture & Key Components
- **Main Entrypoint:** `apprenticeship-connect.php` initializes the plugin, registers hooks, and loads core classes.
- **Core Classes:**
  - `includes/class-apprenticeship-connect.php`: Main plugin logic and API integration.
  - `includes/class-apprenticeship-connect-admin.php`: Admin panel, settings, and manual sync logic.
  - `includes/class-apprenticeship-connect-setup-wizard.php`: Guided setup for API credentials and initial configuration.
- **Assets:**
  - CSS: `assets/css/apprenticeship-connect.css` (customisable frontend styles)
  - JS: `assets/js/admin.js` (admin panel AJAX/manual sync)
- **Localization:**
  - Translations in `languages/apprenticeship-connect.pot`

## Data Flow
- **API Sync:**
  - Daily WP-Cron job fetches vacancies from the UK Government API and stores them as custom post type `vacancy`.
  - Manual sync can be triggered from the admin panel (AJAX via `admin.js`).
- **Display:**
  - Vacancies are rendered via the `[apprenticeship_vacancies]` shortcode, using display settings from plugin options.

## Developer Workflows
- **Activation:**
  - On activation, creates DB table `wp_apprenticeship_data` for vacancy references.
- **Manual Sync:**
  - Triggered via admin panel button (`#aprcn-manual-sync`), uses AJAX (`aprcn_manual_sync` action).
- **Testing API:**
  - Admin panel includes API connection test.
- **Debugging:**
  - Errors are logged to WordPress error logs.

## Project-Specific Conventions
- **Settings:**
  - All display and API settings stored in `aprcn_plugin_options`.
- **Shortcode Parameters:**
  - Only settings from the admin panel are used; shortcode parameters are ignored in rendering.
- **Custom Post Type:**
  - `vacancy` CPT is not publicly queryable; only used for internal display.
- **Setup Wizard:**
  - Initial configuration is guided via a setup wizard in the admin panel.

## Integration Points
- **External API:**
  - UK Government Display Advert API (`api.apprenticeships.education.gov.uk/vacancies`)
- **WordPress Hooks:**
  - Uses `init`, `plugins_loaded`, `register_activation_hook`, `register_deactivation_hook`, and custom cron events.

## Patterns & Examples
- **Admin AJAX:** See `assets/js/admin.js` for manual sync pattern.
- **Frontend Display:** See `apprenticeship-connect.php` for shortcode rendering and vacancy item markup.
- **Custom Styles:** Customise via `assets/css/apprenticeship-connect.css`.

## Key Files
- `apprenticeship-connect.php` (entrypoint)
- `includes/class-apprenticeship-connect.php` (core logic)
- `includes/class-apprenticeship-connect-admin.php` (admin logic)
- `includes/class-apprenticeship-connect-setup-wizard.php` (setup wizard)
- `assets/css/apprenticeship-connect.css` (styles)
- `assets/js/admin.js` (admin JS)
- `languages/apprenticeship-connect.pot` (translations)

---
For questions or unclear patterns, review the admin panel UI or consult the README for usage details.
