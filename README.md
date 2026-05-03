# Big SEO Sitemap

**Version:** 1.0.0  
**Author:** Kailas Nath R  
**WordPress Compatibility:** 5.0 - 6.9  
**PHP:** 7.0+  
**License:** GPLv2 or later  

## Description

Big SEO Sitemap is a comprehensive WordPress plugin that automatically generates and maintains XML sitemaps for your website. It supports all content types including posts, pages, categories, tags, custom post types, authors, and WooCommerce products.

## Features

- **Automatic Generation:** Updates sitemap every 24 hours via WP-Cron
- **Manual Control:** "Generate Now" button for immediate updates
- **Complete Content Support:** Posts, Pages, Categories, Tags, CPTs, Authors, Products
- **Category Organization:** Blog posts grouped by categories
- **Admin Dashboard:** View, edit, and manage all sitemap URLs
- **Raw XML Editor:** Direct XML editing with syntax highlighting
- **SEO Integration:** Auto-ping Google and Bing on updates
- **Flexible Settings:** Individual and global priority/frequency settings
- **WordPress Standards:** Classic UI, full i18n support, secure coding

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin
5. Go to Big Sitemap menu to configure

## Usage

After activation, the plugin will:
- Automatically generate sitemap every 24 hours
- Save sitemap to `domain.com/sitemap.xml`
- Ping Google and Bing on updates

Access the dashboard at: **Admin > Big Sitemap**

## Development

- **GitHub:** https://github.com/iam-knr/big-seo-sitemap
- **Author:** Kailas Nath R
- **LinkedIn:** https://www.linkedin.com/in/iamknr/

## Plugin Check Status

✅ All WordPress.org Plugin Check requirements met  
✅ 0 errors, 0 warnings  
✅ Security: Nonce verification, input sanitization, output escaping  
✅ Internationalization: Translation-ready  
✅ Clean uninstall: Removes all data  

## Recent Updates

### Version 1.0.0 (Latest)
- Text domain consistency: `big-sitemap`
- Security enhancements: wp_unslash, nonce verification
- Timezone-safe date handling: gmdate()
- WordPress function standards: wp_delete_file()
- UI improvements: Optimized font sizes
- All Plugin Check errors resolved

## Support

For issues or questions, please visit the GitHub repository.

## License

GPLv2 or later
