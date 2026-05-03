<?php
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
            'display'  => __('Every 24 Hours', 'big-seo-sitemap'),
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
