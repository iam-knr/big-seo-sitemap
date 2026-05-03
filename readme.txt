=== Big SEO Sitemap ===
Contributors: kailasnathr
Donate link: https://www.linkedin.com/in/iamknr/
Tags: sitemap, xml sitemap, seo, google sitemap, auto sitemap
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatic XML sitemap generator with 24-hour auto-updates, category grouping, priority control, search engine pinging, and complete admin management.

== Description ==

Big SEO Sitemap is a comprehensive WordPress plugin that automatically generates and maintains XML sitemaps for your entire website. Perfect for improving your SEO and ensuring search engines can easily discover and index your content.

= Key Features =

* **Automatic Generation** - Creates sitemap on activation and auto-updates every 24 hours via WP-Cron
* **Complete Content Coverage** - Includes posts, pages, categories, tags, custom post types, authors, and WooCommerce products
* **Smart Category Grouping** - Organizes blog posts by categories for better structure
* **Manual Control** - "Generate Now" button for immediate sitemap updates
* **Search Engine Integration** - Automatically pings Google and Bing after every update
* **Flexible Priority Settings** - Set global defaults or customize per content type
* **Change Frequency Control** - Configure update frequency for each content type
* **Advanced URL Management** - View, edit, and exclude specific URLs from your sitemap
* **Per-URL Customization** - Override priority and change frequency for individual URLs
* **Excluded URLs Manager** - Easily exclude and re-include URLs as needed
* **Raw XML Editor** - Direct XML editing capability for advanced users
* **Clean Admin Interface** - Beautiful, intuitive dashboard with tabs for different features
* **Security First** - Built with WordPress coding standards, nonce verification, and proper sanitization
* **Translation Ready** - Fully internationalized and ready for translation

= Perfect For =

* Bloggers who want automated sitemap management
* WooCommerce store owners
* Agencies managing multiple client sites
* SEO professionals needing granular control
* Content-heavy websites with frequent updates

= How It Works =

1. **Install & Activate** - Sitemap is automatically generated at `yourdomain.com/sitemap.xml`
2. **Auto-Updates** - Refreshes every 24 hours automatically
3. **Search Engine Pinging** - Google and Bing are notified after each update
4. **Customize** - Use the admin dashboard to configure content types, priorities, and frequencies
5. **Manage** - View all URLs, exclude specific ones, or manually regenerate anytime

= Admin Dashboard Tabs =

* **Dashboard** - Overview stats, content type selection, and priority/frequency defaults
* **View & Edit** - Complete URL list with inline editing, exclusion controls, and grouped display
* **Raw XML** - Direct XML editor for advanced customization
* **Settings** - Configure auto-update schedule (rolling or fixed time)

= Technical Details =

* Creates standard XML sitemap format (sitemap.org protocol)
* Supports unlimited URLs
* Deduplicates URLs automatically
* Uses WordPress Filesystem API for secure file operations
* Compatible with page caching plugins
* No external dependencies

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "Big SEO Sitemap"
4. Click "Install Now" and then "Activate"
5. Access the plugin via "Big SEO Sitemap" in your admin menu

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"
6. Access the plugin via "Big SEO Sitemap" in your admin menu

= After Activation =

1. Your sitemap is automatically created at `yourdomain.com/sitemap.xml`
2. Go to **Big SEO Sitemap** in your admin menu
3. Review and customize content types to include
4. Adjust priority and change frequency settings if needed
5. Click "Save & Generate Sitemap" to apply your settings

== Frequently Asked Questions ==

= Where is my sitemap located? =

Your sitemap is automatically generated at `yourdomain.com/sitemap.xml` in your site's root directory.

= How often does the sitemap update? =

By default, the sitemap automatically regenerates every 24 hours. You can also manually regenerate anytime from the Dashboard tab.

= Does this work with WooCommerce? =

Yes! The plugin includes full support for WooCommerce products with customizable priority and change frequency settings.

= Can I exclude specific URLs? =

Absolutely. Go to the View & Edit tab, check the "Exclude" box next to any URL, and click "Save Changes & Regenerate".

= Does it ping search engines? =

Yes, the plugin automatically pings Google and Bing every time the sitemap is regenerated.

= Can I customize priority and change frequency? =

Yes, you can set global defaults for each content type on the Dashboard tab, or customize individual URLs on the View & Edit tab.

= Will this work with custom post types? =

Yes! All public custom post types are automatically detected and can be included in your sitemap.

= Is the plugin translation ready? =

Yes, the plugin is fully internationalized with text domain `big-seo-sitemap` and ready for translation.

= Does it affect site performance? =

No. Sitemap generation runs via WP-Cron in the background and doesn't impact front-end performance.

= Can I edit the XML directly? =

Yes, the Raw XML tab provides a text editor for direct XML modifications.

== Screenshots ==

1. Dashboard - Content type selection and priority/frequency settings
2. View & Edit - Complete URL management with inline editing
3. Excluded URLs - Manage excluded URLs and re-include them easily
4. Raw XML Editor - Direct XML editing for advanced users
5. URL Breakdown - View URLs grouped by content type

== Changelog ==

= 1.0.0 - 2026-05-03 =
* Initial release
* Automatic sitemap generation on activation
* 24-hour auto-update via WP-Cron
* Support for posts, pages, categories, tags, authors, CPTs, and products
* Category-based post grouping
* Search engine pinging (Google & Bing)
* Admin dashboard with 4 tabs
* Per-content-type priority and change frequency settings
* Per-URL override capability
* URL exclusion feature
* Raw XML editor
* Clean uninstall (removes all data)
* WordPress coding standards compliance
* Security: nonce verification, input sanitization, output escaping
* Internationalization ready
* Auto-reload UI for immediate feedback
* Dynamic content type filtering in settings

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install to start automatically generating and managing your XML sitemap.
