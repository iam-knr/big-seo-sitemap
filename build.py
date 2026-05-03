import os

files = {}

# ============================================================
# GENERATOR
# ============================================================
files['includes/class-big-sitemap-generator.php'] = """<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Big_Sitemap_Generator {

    public static function generate() {
        $settings      = get_option( 'big_sitemap_settings', [] );
        $overrides     = get_option( 'big_sitemap_url_overrides', [] );
        $content_types = isset( $settings['content_types'] ) ? $settings['content_types'] : ['post','page','category','cpt','tag','author','product'];
        $urls = [];

        // Homepage
        $urls[] = [
            'loc'        => home_url('/'),
            'lastmod'    => date('c'),
            'changefreq' => 'daily',
            'priority'   => '1.0',
            'group'      => 'Homepage',
        ];

        // Pages
        if ( in_array('page', $content_types) ) {
            foreach ( get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>-1]) as $p ) {
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

        // Apply per-URL overrides (latest action wins)
        foreach ( $urls as &$entry ) {
            if ( isset($overrides[$entry['loc']]) ) {
                $ov = $overrides[$entry['loc']];
                if ( ! empty($ov['priority']) )            $entry['priority']   = $ov['priority'];
                if ( ! empty($ov['changefreq']) )          $entry['changefreq'] = $ov['changefreq'];
                if ( ! empty($ov['lastmod']) )             $entry['lastmod']    = $ov['lastmod'];
                if ( isset($ov['exclude']) && $ov['exclude'] ) $entry['exclude'] = true;
            }
        }
        unset($entry);

        // Handle manually added URLs not in auto list
        foreach ( $overrides as $loc => $ov ) {
            if ( ! empty($ov['manual']) ) {
                $urls[] = [
                    'loc'        => $loc,
                    'lastmod'    => ! empty($ov['lastmod']) ? $ov['lastmod'] : date('c'),
                    'changefreq' => ! empty($ov['changefreq']) ? $ov['changefreq'] : 'monthly',
                    'priority'   => ! empty($ov['priority'])   ? $ov['priority']   : '0.5',
                    'group'      => 'Manual',
                ];
            }
        }

        $urls = array_values( array_filter($urls, function($u){ return empty($u['exclude']); }) );

        // Deduplicate by loc
        $seen = [];
        $deduped = [];
        foreach ($urls as $u) {
            if (!isset($seen[$u['loc']])) {
                $seen[$u['loc']] = true;
                $deduped[] = $u;
            }
        }

        update_option( 'big_sitemap_urls', $deduped, false );
        update_option( 'big_sitemap_last_updated', current_time('mysql') );

        self::write_xml( $deduped );
        self::ping_search_engines();

        return count($deduped);
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
        $lm  = ! empty($lastmod) ? date('c', strtotime($lastmod)) : date('c');
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
"""

# ============================================================
# SCHEDULER
# ============================================================
files['includes/class-big-sitemap-scheduler.php'] = """<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Big_Sitemap_Scheduler {

    const HOOK     = 'big_sitemap_cron_event';
    const INTERVAL = 'big_sitemap_24h';

    public static function init() {
        add_filter( 'cron_schedules',           [__CLASS__, 'add_interval'] );
        add_action( self::HOOK,                 [__CLASS__, 'run'] );
        add_action( 'big_sitemap_reschedule',   [__CLASS__, 'reschedule'] );
        // Auto-trigger on publish/update
        add_action( 'save_post',     [__CLASS__, 'on_post_change'], 10, 2 );
        add_action( 'create_term',   [__CLASS__, 'on_term_change'] );
        add_action( 'edit_term',     [__CLASS__, 'on_term_change'] );
        add_action( 'delete_term',   [__CLASS__, 'on_term_change'] );
    }

    public static function add_interval( $schedules ) {
        $schedules[self::INTERVAL] = [
            'interval' => 24 * HOUR_IN_SECONDS,
            'display'  => __('Every 24 Hours', 'big-sitemap'),
        ];
        return $schedules;
    }

    public static function schedule() {
        $settings    = get_option('big_sitemap_settings',[]);
        $mode        = isset($settings['schedule_mode']) ? $settings['schedule_mode'] : 'rolling';
        $fixed_time  = isset($settings['schedule_time']) ? $settings['schedule_time'] : '00:00';

        wp_clear_scheduled_hook( self::HOOK );

        if ( $mode === 'fixed' ) {
            list($h, $m) = explode(':', $fixed_time);
            $now         = current_time('timestamp');
            $next        = mktime( (int)$h, (int)$m, 0 );
            if ( $next <= $now ) $next = strtotime('+1 day', $next);
            wp_schedule_event( $next, self::INTERVAL, self::HOOK );
        } else {
            // Rolling: 24h from now
            wp_schedule_event( time() + HOUR_IN_SECONDS, self::INTERVAL, self::HOOK );
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook( self::HOOK );
    }

    public static function reschedule() {
        self::schedule();
    }

    public static function run() {
        Big_Sitemap_Generator::generate();
    }

    public static function on_post_change( $post_id, $post ) {
        if ( wp_is_post_revision($post_id) )     return;
        if ( wp_is_post_autosave($post_id) )     return;
        if ( $post->post_status !== 'publish' )  return;
        Big_Sitemap_Generator::generate();
    }

    public static function on_term_change() {
        Big_Sitemap_Generator::generate();
    }
}
"""

# ============================================================
# SETTINGS
# ============================================================
files['includes/class-big-sitemap-settings.php'] = """<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Big_Sitemap_Settings {

    const OPTION = 'big_sitemap_settings';

    public static function init() {
        add_action( 'admin_init', [__CLASS__, 'register'] );
    }

    public static function register() {
        register_setting( 'big_sitemap_settings_group', self::OPTION, [__CLASS__, 'sanitize'] );
    }

    public static function defaults() {
        return [
            'content_types' => ['post','page','category','cpt','tag','author','product'],
            'schedule_mode' => 'rolling',
            'schedule_time' => '00:00',
            'type_defaults'  => [
                'post'    => ['priority'=>'0.8','changefreq'=>'weekly'],
                'page'    => ['priority'=>'0.6','changefreq'=>'monthly'],
                'category'=> ['priority'=>'0.5','changefreq'=>'weekly'],
                'tag'     => ['priority'=>'0.4','changefreq'=>'monthly'],
                'author'  => ['priority'=>'0.3','changefreq'=>'monthly'],
                'cpt'     => ['priority'=>'0.7','changefreq'=>'weekly'],
                'product' => ['priority'=>'0.8','changefreq'=>'weekly'],
            ],
        ];
    }

    public static function get() {
        return wp_parse_args( get_option(self::OPTION,[]), self::defaults() );
    }

    public static function sanitize( $input ) {
        $clean = [];
        $allowed_types = ['post','page','category','cpt','tag','author','product'];
        $clean['content_types'] = isset($input['content_types']) ? array_intersect((array)$input['content_types'], $allowed_types) : [];
        $clean['schedule_mode'] = in_array($input['schedule_mode']??'rolling', ['rolling','fixed','both']) ? $input['schedule_mode'] : 'rolling';
        $clean['schedule_time'] = sanitize_text_field($input['schedule_time'] ?? '00:00');
        $freqs = ['always','hourly','daily','weekly','monthly','yearly','never'];
        foreach ($allowed_types as $t) {
            $p = $input['type_defaults'][$t]['priority']  ?? '0.5';
            $f = $input['type_defaults'][$t]['changefreq'] ?? 'monthly';
            $clean['type_defaults'][$t] = [
                'priority'   => in_array($p,['0.0','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0']) ? $p : '0.5',
                'changefreq' => in_array($f,$freqs) ? $f : 'monthly',
            ];
        }
        // Reschedule cron after settings save
        do_action('big_sitemap_reschedule');
        return $clean;
    }
}
"""


# Continuing the script - writing remaining files now

for path, content in files.items():
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content)
    print(f'✅ {path}')

print('\n🎯 ALL PLUGIN FILES WRITTEN! Run python3 build_admin.py next for Admin UI.')
