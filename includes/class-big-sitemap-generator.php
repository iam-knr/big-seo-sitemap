<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Big_Sitemap_Generator {

    public static function generate() {
        $settings      = Big_Sitemap_Settings::get();
        $overrides     = get_option( 'big_sitemap_url_overrides', [] );
        $content_types = $settings['content_types'];
        $urls = [];

        // Homepage
        $urls[] = [
            'loc'        => home_url('/'),
            'lastmod'    => gmdate( 'c' ),
            'changefreq' => 'daily',
            'priority'   => '1.0',
            'group'      => 'Homepage',
        ];

        // Pages
        if ( in_array('page', $content_types) ) {
            foreach ( get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>-1]) as $p ) {
            	if ( intval(get_option('page_on_front')) === $p->ID ) continue;
            	$urls[] = self::build_entry( get_permalink($p->ID), $p->post_modified, $settings, 'page', 'Pages' );
            }
        }

        // Posts grouped by category
        if ( in_array('post', $content_types) ) {
            $cats = get_categories(['hide_empty'=>true]);
            if ( ! empty($cats) ) {
                foreach ( $cats as $cat ) {
                    foreach ( get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'cat'=>$cat->term_id]) as $p ) {
                        $urls[] = self::build_entry( get_permalink($p->ID), $p->post_modified, $settings, 'post', 'Posts: '.$cat->name );
                    }
                }
            } else {
                foreach ( get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1]) as $p ) {
                    $urls[] = self::build_entry( get_permalink($p->ID), $p->post_modified, $settings, 'post', 'Posts: Uncategorized' );
                }
            }
        }

        // Categories
        if ( in_array('category', $content_types) ) {
            foreach ( get_categories(['hide_empty'=>true]) as $cat ) {
                $urls[] = self::build_entry( get_category_link($cat->term_id), '', $settings, 'category', 'Categories' );
            }
        }

        // Tags
        if ( in_array('tag', $content_types) ) {
            foreach ( get_tags(['hide_empty'=>true]) as $tag ) {
                $urls[] = self::build_entry( get_tag_link($tag->term_id), '', $settings, 'tag', 'Tags' );
            }
        }

        // Authors
        if ( in_array('author', $content_types) ) {
            foreach ( get_users(['who'=>'authors']) as $author ) {
                $urls[] = self::build_entry( get_author_posts_url($author->ID), '', $settings, 'author', 'Authors' );
            }
        }

        // CPT
        if ( in_array('cpt', $content_types) ) {
            foreach ( get_post_types(['public'=>true,'_builtin'=>false],'names') as $cpt ) {
                if ( $cpt === 'product' ) continue;
                foreach ( get_posts(['post_type'=>$cpt,'post_status'=>'publish','numberposts'=>-1]) as $p ) {
                    $urls[] = self::build_entry( get_permalink($p->ID), $p->post_modified, $settings, 'cpt', 'CPT: '.$cpt );
                }
            }
        }

        // WooCommerce Products
        if ( in_array('product', $content_types) && post_type_exists('product') ) {
            foreach ( get_posts(['post_type'=>'product','post_status'=>'publish','numberposts'=>-1]) as $p ) {
                $urls[] = self::build_entry( get_permalink($p->ID), $p->post_modified, $settings, 'product', 'Products' );
            }
        }

        // Deduplicate by loc before applying overrides
        $seen = [];
        $deduped = [];
        foreach ($urls as $u) {
            if (!isset($seen[$u['loc']])) {
                $seen[$u['loc']] = true;
                $deduped[] = $u;
            }
        }
        $urls = $deduped;

        // Apply per-URL overrides
        foreach ( $urls as &$entry ) {
            if ( isset($overrides[$entry['loc']]) ) {
                $ov = $overrides[$entry['loc']];
                if ( ! empty($ov['priority']) )   $entry['priority']   = $ov['priority'];
                if ( ! empty($ov['changefreq']) ) $entry['changefreq'] = $ov['changefreq'];
                if ( ! empty($ov['lastmod']) )    $entry['lastmod']    = $ov['lastmod'];
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- plugin-internal flag, not a WP_Query param.
                if ( isset($ov['exclude']) && $ov['exclude'] ) $entry['exclude'] = true;
            }
        }
        unset($entry);

        // Handle manually added URLs
        foreach ( $overrides as $loc => $ov ) {
            if ( ! empty($ov['manual']) ) {
                $urls[] = [
                    'loc'        => $loc,
                    'lastmod'    => ! empty($ov['lastmod']) ? $ov['lastmod'] : gmdate('c'),
                    'changefreq' => ! empty($ov['changefreq']) ? $ov['changefreq'] : 'monthly',
                    'priority'   => ! empty($ov['priority'])   ? $ov['priority']   : '0.5',
                    'group'      => 'Manual',
                ];
            }
        }

        // Save ALL urls (including excluded) so View & Edit can list excluded URLs
        update_option( 'big_sitemap_all_urls', $urls, false );

        // Filter to active-only for sitemap XML
        $active_urls = array_values( array_filter($urls, function($u){ return empty($u['exclude']); }) );

        update_option( 'big_sitemap_urls', $active_urls, false );
        update_option( 'big_sitemap_last_updated', current_time('mysql') );

        self::write_xml( $active_urls );
        self::ping_search_engines();

        return count($active_urls);
    }

    private static function build_entry( $loc, $lastmod, $settings, $type, $group ) {
        $defaults = [
            'post'     => ['priority'=>'0.8','changefreq'=>'weekly'],
            'page'     => ['priority'=>'0.6','changefreq'=>'monthly'],
            'category' => ['priority'=>'0.5','changefreq'=>'weekly'],
            'tag'      => ['priority'=>'0.4','changefreq'=>'monthly'],
            'author'   => ['priority'=>'0.3','changefreq'=>'monthly'],
            'cpt'      => ['priority'=>'0.7','changefreq'=>'weekly'],
            'product'  => ['priority'=>'0.8','changefreq'=>'weekly'],
        ];
        $ts  = isset($settings['type_defaults'][$type]) ? $settings['type_defaults'][$type] : [];
        $pri = ! empty($ts['priority'])   ? $ts['priority']   : $defaults[$type]['priority'];
        $chf = ! empty($ts['changefreq']) ? $ts['changefreq'] : $defaults[$type]['changefreq'];
        $lm  = ! empty( $lastmod ) ? gmdate( 'c', strtotime( $lastmod ) ) : gmdate( 'c' );
        return [ 'loc'=>$loc, 'lastmod'=>$lm, 'changefreq'=>$chf, 'priority'=>$pri, 'group'=>$group ];
    }

    public static function write_xml( $urls ) {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( $urls as $u ) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>'        . esc_url($u['loc'])          . '</loc>'        . "\n";
            $xml .= '    <lastmod>'    . esc_html($u['lastmod'])     . '</lastmod>'    . "\n";
            $xml .= '    <changefreq>' . esc_html($u['changefreq'])  . '</changefreq>' . "\n";
            $xml .= '    <priority>'   . esc_html($u['priority'])    . '</priority>'   . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';
        $path = ABSPATH . 'sitemap.xml';
        global $wp_filesystem;
        if ( ! function_exists('WP_Filesystem') ) require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        if ( $wp_filesystem ) {
            $wp_filesystem->put_contents( $path, $xml, FS_CHMOD_FILE );
        } else {
            @file_put_contents( $path, $xml );
        }
    }

    public static function ping_search_engines() {
        $sitemap = urlencode( home_url('/sitemap.xml') );
        wp_remote_get( 'https://www.google.com/ping?sitemap=' . $sitemap, ['timeout'=>5,'blocking'=>false] );
        wp_remote_get( 'https://www.bing.com/ping?sitemap='   . $sitemap, ['timeout'=>5,'blocking'=>false] );
        update_option( 'big_sitemap_last_pinged', current_time('mysql') );
    }
}
