# Apprenticeship Connect

**Apprenticeship Connect** is a WordPress plugin that seamlessly integrates with the official UK Government's Find an Apprenticeship service. Easily display the latest apprenticeship vacancies on your website, keeping your audience informed and engaged with up-to-date opportunities.
## Description

Apprenticeship Connect connects to the UK Government's Display Advert API to pull and display current apprenticeship vacancies directly on your WordPress site. It's perfect for training providers, colleges, and schools looking to enhance their offerings and attract more visitors.

### Key Features

- **Easy Onboarding**: A step-by-step setup wizard guides you through the configuration process.
- **API Integration**: Connects to the UK Government apprenticeship API to fetch the latest vacancies.
- **Automated Syncing**: A daily WP-Cron job keeps your vacancy listings up-to-date automatically.
- **Manual Control**: A "Test & Sync" button in the settings allows for on-demand API testing and data synchronisation.
- **Simple Shortcode**: Use `[apprenticeship_vacancies]` to display listings on any page or post.
- **Customisable Display**: Control which vacancy details (employer, location, closing date) are shown directly from the settings page.

## Installation

1. Upload the `apprenticeship-connect` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You will be automatically redirected to the **Setup Wizard**. Follow the steps to configure your API credentials and display settings.
4. Use the shortcode `[apprenticeship_vacancies]` to display vacancies on any page

## Configuration

### API Setup

1.  **Get API Credentials**: Register for an API subscription key from the UK Government Apprenticeship service.
2.  **Run the Setup Wizard**: The wizard will prompt you to enter your API subscription key.
3.  **Optional UKPRN**: You can add your UKPRN in the settings to filter vacancies by your provider.

### Display Settings

Configure how vacancies are displayed from the **Apprenticeship Connect > Settings** page:

- **Default Display Count**: Set the number of vacancies to show by default.
- **Show Employer**: Toggle the visibility of employer names.
- **Show Location**: Toggle the visibility of vacancy locations.
- **Show Closing Date**: Toggle the visibility of application closing dates.
- **Show Apply Button**: Toggle the visibility of the "Apply" button.

## Usage

### Shortcode

Use the shortcode `[apprenticeship_vacancies]` to display vacancies on any page or post. The shortcode does not accept any parameters; all display settings are managed from the plugin's settings page to keep configuration simple and centralised.

### Automatic Sync

The plugin automatically syncs vacancy data once per day. You can also trigger a manual sync at any time by clicking the **Test & Sync** button on the settings page.

## Frequently Asked Questions

### How do I get an API subscription key?

You can request an API subscription key from the UK Government's Find an Apprenticeship service.

### How often does the plugin sync data?

The plugin automatically syncs vacancy data once per day. You can also trigger a manual sync from the settings page at any time.

### Can I customise the appearance of the vacancy listings?

Yes. The vacancy listings are output with specific CSS classes (e.g., `.aprcn-vacancies-list`, `.aprcn-vacancy-item`), allowing you to add custom styles in your theme's stylesheet.

## Screenshots

1.  The main settings page where you can configure API credentials and display options.
2.  The step-by-step setup wizard for easy onboarding.
3.  The "Test & Sync" feature providing instant feedback on your API connection and data sync.
4.  An example of how the vacancy listings appear on the front-end of your website.

## Changelog

### 1.1.0
- Initial public release.
- Features a step-by-step Setup Wizard for easy configuration.
- Includes a "Test & Sync" button for on-demand API testing and synchronisation.
- Implements a daily cron job for automatic vacancy updates.
- Provides a `[apprenticeship_vacancies]` shortcode for displaying listings.

## Upgrade Notice

This is the initial release of Apprenticeship Connect. No upgrade notice needed.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- API subscription key from UK Government Apprenticeship service

## Support

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by ePark Team.

---

**Get started with Apprenticeship Connect today and help connect aspiring apprentices with their next great opportunity!** 