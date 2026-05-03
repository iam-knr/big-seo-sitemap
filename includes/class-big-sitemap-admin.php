<?php
if (!defined('ABSPATH')) exit;

class Big_Sitemap_Admin {
	public static function init() {
		add_action('admin_menu', [__CLASS__, 'menu']);
		add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
		add_action('admin_enqueue_scripts', [__CLASS__, 'menu_icon_css']);
		add_action('wp_ajax_big_sitemap_generate_now', [__CLASS__, 'ajax_generate']);
		add_action('wp_ajax_big_sitemap_save_overrides', [__CLASS__, 'ajax_save_overrides']);
		add_action('wp_ajax_big_sitemap_save_xml', [__CLASS__, 'ajax_save_xml']);
		add_action('wp_ajax_big_sitemap_save_content_types', [__CLASS__, 'ajax_save_content_types']);
		add_action('wp_ajax_big_sitemap_save_type_defaults', [__CLASS__, 'ajax_save_type_defaults']);
	}

	public static function menu() {
		add_menu_page(
			'Big SEO Sitemap',
			'Big SEO Sitemap',
			'manage_options',
			'big-sitemap',
			[__CLASS__, 'page'],
			'none',
			30
		);
	}

	public static function menu_icon_css() {
		$icon_url = esc_url( BIG_SITEMAP_URL . 'assets/images/icon-256x256.png' );
		$css = '
			#adminmenu #toplevel_page_big-sitemap .wp-menu-image {
				background-image: url(' . $icon_url . ') !important;
				background-size: 20px 20px !important;
				background-position: center center !important;
				background-repeat: no-repeat !important;
			}
			#adminmenu #toplevel_page_big-sitemap .wp-menu-image:before {
				display: none !important;
			}
		';
		wp_register_style( 'big-sitemap-menu-icon', false, [], BIG_SITEMAP_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'big-sitemap-menu-icon' );
		wp_add_inline_style( 'big-sitemap-menu-icon', $css );
	}

	public static function assets($hook) {
		if ($hook !== 'toplevel_page_big-sitemap') return;
		wp_enqueue_style('big-sitemap-admin', BIG_SITEMAP_URL.'assets/css/admin.css', [], BIG_SITEMAP_VERSION);
		wp_enqueue_script('big-sitemap-admin', BIG_SITEMAP_URL.'assets/js/admin.js', ['jquery'], BIG_SITEMAP_VERSION, true);
		wp_localize_script('big-sitemap-admin', 'bigSitemapAjax', [
			'url'   => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('big_sitemap_nonce'),
		]);
	}

	public static function ajax_generate() {
		check_ajax_referer('big_sitemap_nonce', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

		$allowed = ['post', 'page', 'category', 'tag', 'author', 'cpt', 'product'];
		if ( isset( $_POST['content_types'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below via in_array + sanitize_key
			$raw   = (array) wp_unslash( $_POST['content_types'] );
			$types = array_values( array_filter( $raw, function( $t ) use ( $allowed ) {
				return in_array( sanitize_key( $t ), $allowed, true );
			} ) );
			$settings = Big_Sitemap_Settings::get();
			$settings['content_types'] = $types;
			update_option( 'big_sitemap_settings', $settings, false );
		}

		$count = Big_Sitemap_Generator::generate();
		wp_send_json_success(['message' => "Sitemap generated: $count URLs", 'count' => $count]);
	}

	public static function ajax_save_content_types() {
		check_ajax_referer('big_sitemap_nonce', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

		$allowed = ['post', 'page', 'category', 'tag', 'author', 'cpt', 'product'];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below via in_array
		$raw = isset($_POST['content_types']) ? (array) wp_unslash($_POST['content_types']) : [];
		$types = array_values(array_filter($raw, function($t) use ($allowed) {
			return in_array(sanitize_key($t), $allowed, true);
		}));

		$settings = Big_Sitemap_Settings::get();
		$settings['content_types'] = $types;
		update_option('big_sitemap_settings', $settings, false);
		wp_send_json_success(['message' => 'Content types saved']);
	}

	public static function ajax_save_type_defaults() {
		check_ajax_referer('big_sitemap_nonce', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

		$allowed_types = ['post','page','category','tag','author','cpt','product'];
		$freqs         = ['always','hourly','daily','weekly','monthly','yearly','never'];
		$priorities    = ['0.0','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0'];

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field-by-field below via in_array
		$raw_defaults = isset($_POST['type_defaults']) ? (array) wp_unslash($_POST['type_defaults']) : [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below via in_array + sanitize_key
		$raw_types = isset($_POST['content_types']) ? (array) wp_unslash($_POST['content_types']) : null;

		$settings = Big_Sitemap_Settings::get();

		// Save type defaults
		foreach ($allowed_types as $t) {
			$p = $raw_defaults[$t]['priority']   ?? '';
			$f = $raw_defaults[$t]['changefreq'] ?? '';
			$settings['type_defaults'][$t] = [
				'priority'   => in_array($p, $priorities, true) ? $p : ($settings['type_defaults'][$t]['priority']   ?? '0.5'),
				'changefreq' => in_array($f, $freqs, true)      ? $f : ($settings['type_defaults'][$t]['changefreq'] ?? 'monthly'),
			];
		}

		// Save content types
		if ( $raw_types !== null ) {
			$settings['content_types'] = array_values( array_filter( $raw_types, function($t) use ($allowed_types) {
				return in_array( sanitize_key($t), $allowed_types, true );
			}));
		}

		update_option('big_sitemap_settings', $settings, false);

		$count = Big_Sitemap_Generator::generate();
		wp_send_json_success(['message' => "Sitemap generated: $count URLs", 'count' => $count]);
	}

	public static function ajax_save_overrides() {
		check_ajax_referer('big_sitemap_nonce', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON-decoded and each field sanitized individually below.
		$raw     = wp_unslash( $_POST['overrides'] ?? '' );
		$decoded = json_decode( $raw, true );
		$overrides = [];

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $loc => $ov ) {
				$clean_loc = esc_url_raw( $loc );
				if ( empty( $clean_loc ) ) continue;
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				$overrides[ $clean_loc ] = [
					'priority'   => isset( $ov['priority'] )   ? sanitize_text_field( $ov['priority'] )   : '',
					'changefreq' => isset( $ov['changefreq'] ) ? sanitize_text_field( $ov['changefreq'] ) : '',
					'exclude'    => ! empty( $ov['exclude'] ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
					'manual'     => ! empty( $ov['manual'] ),
					'lastmod'    => isset( $ov['lastmod'] )    ? sanitize_text_field( $ov['lastmod'] )    : '',
				];
			}
		}

		update_option('big_sitemap_url_overrides', $overrides, false);
		Big_Sitemap_Generator::generate();
		wp_send_json_success(['message' => 'Saved and regenerated']);
	}

	public static function ajax_save_xml() {
		check_ajax_referer('big_sitemap_nonce', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

		$xml = wp_unslash( $_POST['xml'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( ABSPATH . 'sitemap.xml', $xml, FS_CHMOD_FILE );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( ABSPATH . 'sitemap.xml', $xml );
		}
		wp_send_json_success(['message' => 'XML saved to sitemap.xml']);
	}

	public static function page() {
		$settings     = Big_Sitemap_Settings::get();
		$all_urls     = get_option('big_sitemap_all_urls', []);
		$active_urls  = get_option('big_sitemap_urls', []);
		$last_updated = get_option('big_sitemap_last_updated', 'Never');
		$last_pinged  = get_option('big_sitemap_last_pinged', 'Never');
		$overrides    = get_option('big_sitemap_url_overrides', []);
		$next_cron    = wp_next_scheduled('big_sitemap_cron_event');
		$sitemap_url  = home_url('/sitemap.xml');
		$all_types    = ['post', 'page', 'category', 'tag', 'author', 'cpt', 'product'];
		$active_types = $settings['content_types'] ?? [];
		$logo_url     = BIG_SITEMAP_URL . 'assets/images/icon-256x256.png';

		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		$excluded_urls = array_filter($all_urls, function($u) {
			return ! empty($u['exclude']); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		});

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
		$allowed_tabs = [ 'dashboard', 'view', 'xml', 'settings' ];
		if ( ! in_array( $tab, $allowed_tabs, true ) ) {
			$tab = 'dashboard';
		}

		$groups = [];
		foreach ($active_urls as $u) {
			$g = $u['group'] ?? 'Other';
			$groups[$g] = ($groups[$g] ?? 0) + 1;
		}
		?>
		<div class="wrap big-sitemap-wrap">
			<h1 style="display:flex;align-items:center;gap:8px;">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="Big SEO Sitemap" width="32" height="32" style="width:32px;height:32px;object-fit:contain;flex-shrink:0;display:inline-block;">
				Big SEO Sitemap
			</h1>

			<div id="big-sitemap-message" class="notice" style="display:none;"></div>

			<nav class="nav-tab-wrapper">
				<a href="?page=big-sitemap&tab=dashboard" class="nav-tab <?php echo ($tab === 'dashboard' ? 'nav-tab-active' : ''); ?>">Dashboard</a>
				<a href="?page=big-sitemap&tab=view" class="nav-tab <?php echo ($tab === 'view' ? 'nav-tab-active' : ''); ?>">View & Edit</a>
				<a href="?page=big-sitemap&tab=xml" class="nav-tab <?php echo ($tab === 'xml' ? 'nav-tab-active' : ''); ?>">Raw XML</a>
				<a href="?page=big-sitemap&tab=settings" class="nav-tab <?php echo ($tab === 'settings' ? 'nav-tab-active' : ''); ?>">Settings</a>
			</nav>

			<?php if ($tab === 'dashboard'): ?>
			<div class="big-sitemap-section">

				<!-- Stats -->
				<div class="big-sitemap-stats">
					<div class="stat-box">
						<div class="stat-value"><?php echo esc_html(count($active_urls)); ?></div>
						<div class="stat-label">Active URLs</div>
					</div>
					<div class="stat-box">
						<div class="stat-value stat-value-sm"><?php echo esc_html($last_updated); ?></div>
						<div class="stat-label">Last Updated</div>
					</div>
					<div class="stat-box">
						<div class="stat-value stat-value-sm"><?php echo esc_html($last_pinged); ?></div>
						<div class="stat-label">Last Pinged</div>
					</div>
					<div class="stat-box">
						<div class="stat-value stat-value-sm"><?php echo esc_html($next_cron ? gmdate('Y-m-d H:i', $next_cron) : 'Not scheduled'); ?></div>
						<div class="stat-label">Next Auto Update</div>
					</div>
				</div>

				<!-- Content Types -->
				<div class="big-sitemap-content-types">
					<h3>Content Types to Include</h3>
					<div class="big-sitemap-type-checkboxes">
						<?php foreach ($all_types as $t): ?>
						<label class="big-sitemap-type-label">
							<input type="checkbox"
								class="big-sitemap-type-check"
								name="content_types[]"
								value="<?php echo esc_attr($t); ?>"
								<?php checked(in_array($t, $active_types, true)); ?>
							/>
							<?php echo esc_html(ucfirst($t)); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Priority & Change Frequency per Type -->
				<div class="big-sitemap-content-types" style="margin-top:16px;">
					<h3>Default Priority &amp; Change Frequency per Type</h3>
					<table class="widefat" style="max-width:680px;margin-top:10px;">
						<thead><tr><th>Type</th><th>Priority</th><th>Change Freq</th></tr></thead>
						<tbody>
						<?php
						$freqs = ['always','hourly','daily','weekly','monthly','yearly','never'];
						foreach ($all_types as $t):
							$td = $settings['type_defaults'][$t] ?? ['priority'=>'0.5','changefreq'=>'monthly'];
						?>
						<tr>
							<td><strong><?php echo esc_html(ucfirst($t)); ?></strong></td>
							<td>
								<select name="type_defaults[<?php echo esc_attr($t); ?>][priority]" class="big-sitemap-type-default">
									<?php foreach (['0.0','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0'] as $v): ?>
									<option value="<?php echo esc_attr($v); ?>" <?php selected($td['priority'], $v); ?>><?php echo esc_html($v); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select name="type_defaults[<?php echo esc_attr($t); ?>][changefreq]" class="big-sitemap-type-default">
									<?php foreach ($freqs as $f): ?>
									<option value="<?php echo esc_attr($f); ?>" <?php selected($td['changefreq'], $f); ?>><?php echo esc_html($f); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Single action row -->
				<div class="action-buttons" style="margin-top:20px;">
					<button id="big-sitemap-generate" class="button button-primary button-hero">🔄 Save &amp; Generate Sitemap</button>
					<a href="<?php echo esc_url($sitemap_url); ?>" target="_blank" class="button button-hero">📋 View sitemap.xml</a>
				</div>

			</div>
			<?php endif; ?>

			<?php if ($tab === 'view'): ?>
			<div class="big-sitemap-section">
				<h2>URL Breakdown by Group</h2>
				<table class="widefat striped" style="max-width:600px;margin-top:8px;margin-bottom:25px;">
					<thead><tr><th>Group</th><th>URLs</th></tr></thead>
					<tbody>
						<?php if ( ! empty($groups) ): ?>
							<?php foreach ($groups as $g => $cnt): ?>
							<tr><td><?php echo esc_html($g); ?></td><td><?php echo esc_html($cnt); ?></td></tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr><td colspan="2">No URLs yet — click Save &amp; Generate Sitemap on the Dashboard.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>

				<h2>Active URLs</h2>
				<table id="big-sitemap-url-table" class="widefat striped">
					<thead><tr><th>URL</th><th>Group</th><th>Priority</th><th>Change Freq</th><th>Last Modified</th><th>Exclude</th></tr></thead>
					<tbody>
						<?php foreach ($active_urls as $u): ?>
						<tr data-loc="<?php echo esc_url($u['loc']); ?>">
							<td><a href="<?php echo esc_url($u['loc']); ?>" target="_blank"><?php echo esc_html($u['loc']); ?></a></td>
							<td><?php echo esc_html($u['group'] ?? 'Other'); ?></td>
							<td>
								<select class="priority-select">
									<?php foreach (['0.0','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0'] as $v): ?>
									<option value="<?php echo esc_attr($v); ?>" <?php selected($overrides[$u['loc']]['priority'] ?? $u['priority'], $v); ?>><?php echo esc_html($v); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td>
								<select class="changefreq-select">
									<?php foreach (['always','hourly','daily','weekly','monthly','yearly','never'] as $f): ?>
									<option value="<?php echo esc_attr($f); ?>" <?php selected($overrides[$u['loc']]['changefreq'] ?? $u['changefreq'], $f); ?>><?php echo esc_html($f); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
							<td><?php echo esc_html($u['lastmod'] ?? 'N/A'); ?></td>
							<td><input type="checkbox" class="exclude-check" data-url="<?php echo esc_attr($u['loc']); ?>"></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<button id="big-sitemap-save-urls" class="button button-primary" style="margin-top:15px;">Save Changes & Regenerate</button>

				<?php if ( ! empty($excluded_urls) ): ?>
				<h2 style="margin-top:30px;">Excluded URLs <span class="big-sitemap-excluded-count">(<?php echo esc_html(count($excluded_urls)); ?>)</span></h2>
				<p class="description">These URLs are excluded from the sitemap. Check &quot;Re-include&quot; and save to add them back.</p>
				<table id="big-sitemap-excluded-table" class="widefat striped">
					<thead><tr><th>URL</th><th>Group</th><th>Re-include</th></tr></thead>
					<tbody>
						<?php foreach ($excluded_urls as $u): ?>
						<tr data-loc="<?php echo esc_url($u['loc']); ?>">
							<td><a href="<?php echo esc_url($u['loc']); ?>" target="_blank"><?php echo esc_html($u['loc']); ?></a></td>
							<td><?php echo esc_html($u['group'] ?? 'Other'); ?></td>
							<td><input type="checkbox" class="reinclude-check" data-url="<?php echo esc_attr($u['loc']); ?>"></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<button id="big-sitemap-reinclude-urls" class="button button-secondary" style="margin-top:10px;">Re-include Selected & Regenerate</button>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ($tab === 'xml'): ?>
			<div class="big-sitemap-section">
				<h2>Edit Raw XML</h2>
				<?php
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$xml_content = file_exists(ABSPATH.'sitemap.xml') ? file_get_contents(ABSPATH.'sitemap.xml') : '';
				?>
				<textarea id="big-sitemap-xml-editor" rows="30" style="width:100%;font-family:monospace;"><?php echo esc_textarea($xml_content); ?></textarea>
				<button id="big-sitemap-save-xml" class="button button-primary">Save XML to sitemap.xml</button>
			</div>
			<?php endif; ?>

			<?php if ($tab === 'settings'): ?>
			<div class="big-sitemap-section">
				<h2>Auto-Update Schedule</h2>
				<form method="post" action="options.php">
					<?php settings_fields('big_sitemap_settings_group'); ?>
					<table class="form-table">
						<tr>
							<th>Schedule Mode</th>
							<td>
								<label>
									<input type="radio" name="big_sitemap_settings[schedule_mode]" value="rolling" <?php checked($settings['schedule_mode'], 'rolling'); ?> />
									Rolling (24h from last run)
								</label><br />
								<label>
									<input type="radio" name="big_sitemap_settings[schedule_mode]" value="fixed" <?php checked($settings['schedule_mode'], 'fixed'); ?> />
									Fixed Time Daily
								</label>
							</td>
						</tr>
						<tr>
							<th>Fixed Time (if selected)</th>
							<td>
								<input type="time" name="big_sitemap_settings[schedule_time]" value="<?php echo esc_attr($settings['schedule_time'] ?? '00:00'); ?>" />
							</td>
						</tr>
					</table>
					<?php submit_button('Save Schedule'); ?>
				</form>
			</div>
			<?php endif; ?>

		</div>
		<?php
	}
}

add_action('update_option_big_sitemap_settings', function($old, $new) {
	$old_types = $old['content_types'] ?? [];
	$new_types = $new['content_types'] ?? [];
	if ($old_types !== $new_types) {
		Big_Sitemap_Generator::generate();
	}
}, 10, 2);
