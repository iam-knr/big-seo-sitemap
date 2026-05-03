<?php
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

        // Preserve existing content_types from DB if none submitted via this form.
        // The Settings tab only has priority/changefreq — content_types are managed
        // on the Dashboard tab. Never overwrite with all-types as a fallback.
        $existing = self::get();
        if ( isset( $input['content_types'] ) && ! empty( $input['content_types'] ) ) {
            $clean['content_types'] = array_values( array_intersect( (array) $input['content_types'], $allowed_types ) );
        } else {
            $clean['content_types'] = $existing['content_types'];
        }

        $clean['schedule_mode'] = in_array( $input['schedule_mode'] ?? 'rolling', ['rolling','fixed','both'] ) ? $input['schedule_mode'] : 'rolling';
        $clean['schedule_time'] = sanitize_text_field( $input['schedule_time'] ?? '00:00' );

        $freqs = ['always','hourly','daily','weekly','monthly','yearly','never'];
        foreach ( $allowed_types as $t ) {
            $p = $input['type_defaults'][$t]['priority']   ?? '0.5';
            $f = $input['type_defaults'][$t]['changefreq'] ?? 'monthly';
            $clean['type_defaults'][$t] = [
                'priority'   => in_array( $p, ['0.0','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0'] ) ? $p : '0.5',
                'changefreq' => in_array( $f, $freqs ) ? $f : 'monthly',
            ];
        }

        // Reschedule cron after settings save
        do_action( 'big_sitemap_reschedule' );
        return $clean;
    }
}
