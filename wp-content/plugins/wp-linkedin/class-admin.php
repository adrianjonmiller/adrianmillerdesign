<?php

class WPLinkedInAdmin {

	function WPLinkedInAdmin($plugin) {
		$this->plugin = $plugin;
		$this->linkedin = wp_linkedin_connection();
		$this->add_settings();
		add_action('admin_notices', array(&$this, 'admin_notices'));
	}

	function add_settings() {
		add_filter('plugin_action_links_wp-linkedin/wp-linkedin.php', array(&$this, 'add_settings_link'));
		add_submenu_page('options-general.php', __('LinkedIn Options', 'wp-linkedin'), __('LinkedIn', 'wp-linkedin'), 'manage_options', 'wp-linkedin', array(&$this, 'options_page'));
		add_settings_section('default', '', false, 'wp-linkedin');
		$this->add_settings_field('wp-linkedin_fields', __('Profile fields', 'wp-linkedin'), 'add_settings_field_fields');
		$this->add_settings_field('wp-linkedin_profilelanguage', __('Profile language', 'wp-linkedin'), 'add_settings_field_profilelanguage');
		$this->add_settings_field('wp-linkedin_sendmail_on_token_expiry', __('Send mail on token expiry', 'wp-linkedin'), 'add_settings_field_sendmail_on_token_expiry');
		$this->add_settings_field('wp-linkedin_ssl_verifypeer', __('Verify SSL peer', 'wp-linkedin'), 'add_settings_field_ssl_verifypeer');
		$this->add_settings_field('wp-linkedin_add_card_to_content', __('LinkedIn cards', 'wp-linkedin'), 'add_settings_field_add_card_to_content');
	}

	function add_settings_field($id, $title, $callback) {
		register_setting('wp-linkedin', $id);
		add_settings_field($id, $title, array(&$this, $callback), 'wp-linkedin');
	}

	function add_settings_link($links) {
		$url = site_url('/wp-admin/options-general.php?page=wp-linkedin');
		$links[] = '<a href="' . $url . '">' . __('Settings') . '</a>';
		return $links;
	}

	function add_settings_field_fields() { ?>
		<textarea id="wp-linkedin_fields" name="wp-linkedin_fields" rows="5"
		cols="50"><?php echo get_option('wp-linkedin_fields', LINKEDIN_FIELDS_DEFAULT); ?></textarea>
		<p><em><?php _e('Comma separated list of fields to show on the profile.', 'wp-linkedin'); ?><br/>
		<?php _e('You can overide this setting in the shortcode with the `fields` attribute.', 'wp-linkedin'); ?><br/>
		<?php _e('See the <a href="https://developers.linkedin.com/documents/profile-fields" target="_blank">LinkedIn API documentation</a> for the complete list of fields.', 'wp-linkedin'); ?></em></p>
	<?php }

	function add_settings_field_profilelanguage() { ?>
		<select id="wp-linkedin_profilelanguage" name="wp-linkedin_profilelanguage">
		<?php
			$lang = get_option('wp-linkedin_profilelanguage');
			$languages = $this->getLanguages();

			echo '<option value="" ' . selected($lang, '', false) . '>' . __('Default', 'wp-linkedin') . '</option>';

			foreach ($languages as $k => $v) {
				echo '<option value="' . $k . '" ' . selected($lang, $k, false) . '>' . $v . '</option>';
			}
		?>
		</select>
		<p><em><?php _e('The language of the profile to display if you have several profiles in different languages.', 'wp-linkedin'); ?><br/>
		<?php _e('You can overide this setting in the shortcode with the `lang` attribute.', 'wp-linkedin'); ?><br/>
		<?php _e('See "Selecting the profile language" <a href="https://developer.linkedin.com/documents/profile-api" target="_blank">LinkedIn API documentation</a> for details.', 'wp-linkedin'); ?></em></p>
	<?php }

	function add_settings_field_sendmail_on_token_expiry() { ?>
		<label><input type="checkbox" name="wp-linkedin_sendmail_on_token_expiry"
			value="1" <?php checked(LINKEDIN_SENDMAIL_ON_TOKEN_EXPIRY); ?> />&nbsp;
			<?php _e('Check this option if you want the plugin to send you an email when the token has expired or is invalid.', 'wp-linkedin') ?></label>
	<?php }

	function add_settings_field_ssl_verifypeer() { ?>
		<label><input type="checkbox" name="wp-linkedin_ssl_verifypeer"
			value="1" <?php checked(LINKEDIN_SSL_VERIFYPEER); ?> />&nbsp;
			<?php _e('Uncheck this option only if you have SSL certificate issues on your server.', 'wp-linkedin') ?></label>
	<?php }

	function add_settings_field_add_card_to_content() {
		$post_types = $this->plugin->get_post_types();
		$wp_post_types = get_post_types(array('public' => true), 'objects'); ?>
		<p><?php foreach ($wp_post_types as $name => $post_type): ?>
		<label style="white-space:nowrap;"><input type="checkbox" name="wp-linkedin_add_card_to_content[]"
			value="<?php echo $name; ?>" <?php checked(in_array($name, $post_types)); ?> /><?php echo $post_type->labels->name; ?></label>&nbsp;
		<?php endforeach; ?></p>
		<p><em><?php _e('Check the content types where you want your LinkedIn card inserted.', 'wp-linkedin') ?></em></p><?php
	}


	function admin_notices() {
		if ($this->linkedin->get_last_error() || !$this->linkedin->is_access_token_valid()) { ?>
			<div class="error" style="font-weight:bold;"><ul>
				<?php if ($this->linkedin->get_last_error()): ?>
		        <li><?php echo __('An error has occured while retrieving the profile:', 'wp-linkedin'); ?> <?php echo $this->linkedin->get_last_error(); ?></li>
				<?php endif; ?>

				<?php if (!$this->linkedin->is_access_token_valid()):
				$format = __('Your LinkedIn access token is invalid or has expired, please <a href="%s">click here</a> to get a new one.', 'wp-linkedin');
				$notice = sprintf($format, $this->linkedin->get_authorization_url()); ?>
		        <li><?php echo $notice; ?></li>
				<?php endif ?>

			</ul></div><?php
		}

		if (!isset($_GET['settings-updated'])) {
			if (isset($_GET['oauth_success'])) { ?>
				<div class="updated"><p><strong><?php _e('The access token has been successfully updated.', 'wp-linkedin'); ?></strong></p></div>
			<?php }

			if (isset($_GET['oauth_error'])) {
				$message = isset($_GET['message']) ? $_GET['message'] : false; ?>
				<div class="error">
					<p><strong><?php _e('An error has occured while updating the access token, please try again.', 'wp-linkedin'); ?></strong>
					<?php echo ($message) ? '<br/>' . __('Error message: ', 'wp-linkedin') . $message : ''; ?></p></div>
			<?php }

			if (isset($_GET['cache_cleared'])) { ?>
				<div class="updated"><p><strong><?php _e('The cache has been cleared.', 'wp-linkedin'); ?></strong></p></div>
			<?php }
		}
	}

	function redirect($code, $message=false) {
		$path = '/wp-admin/options-general.php?page=wp-linkedin&' . urlencode($code);
		if ($message) $path .= '&message=' . urlencode($message);
		$location = site_url($path);

		if (headers_sent()) {
			// If the headers have already been sent then use Javascript
			echo "<script>window.location='$location';</script>";
		} else {
			// Other wise, just a normal redirect
			wp_redirect($location);
		}

		exit;
	}

	function options_page() {
		if (isset($_GET['code']) && isset($_GET['state'])) {
			if ($this->linkedin->check_state_token($_GET['state'])) {
				$retcode = $this->linkedin->set_access_token($_GET['code']);

				if (!is_wp_error($retcode)) {
					$this->linkedin->clear_cache();
					$this->redirect('oauth_success');
				} else {
					$this->redirect('oauth_error', $retcode->get_error_message());
				}
			} else {
				$this->redirect('oauth_error', __('Invalid state', 'wp-linkedin'));
			}
		} elseif (isset($_GET['clear_cache'])) {
			$this->linkedin->clear_cache();
			$this->redirect('cache_cleared');
		} ?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('LinkedIn Options', 'wp-linkedin'); ?></h2>
	<div id="main-container" class="postbox-container metabox-holder" style="width:75%;"><div style="margin:0 8px;">
		<div class="postbox">
			<h3 style="cursor:default;"><span><?php _e('Options', 'wp-linkedin'); ?></span></h3>
			<div class="inside">
				<form method="POST" action="options.php"><?php
				settings_fields('wp-linkedin');
				do_settings_sections('wp-linkedin');
				submit_button();
				?></form>
			</div> <!-- .inside -->
		</div> <!-- .postbox -->
		<div class="postbox">
			<h3 style="cursor:default;"><span><?php _e('Administration', 'wp-linkedin'); ?></span></h3>
			<div class="inside">
				<p>
					<span class="submit"><a href="<?php echo $this->linkedin->get_authorization_url(); ?>" class="button button-primary"><?php _e('Regenerate LinkedIn Access Token', 'wp-linkedin'); ?></a></span>
					<span class="submit"><a href="<?php echo site_url('/wp-admin/options-general.php?page=wp-linkedin&clear_cache'); ?>" class="button button-primary"><?php _e('Clear the Cache', 'wp-linkedin'); ?></a></span>
				</p>
			</div> <!-- .inside -->
		</div> <!-- .postbox -->
		</div></div> <!-- #main-container -->

	<div id="side-container" class="postbox-container metabox-holder" style="width:24%;"><div style="margin:0 8px;">
		<div class="postbox">
			<h3 style="cursor:default;"><span><?php _e('Do you like this Plugin?', 'wp-linkedin'); ?></span></h3>
			<div class="inside">
				<p><?php _e('Please consider a donation.', 'wp-linkedin'); ?></p>
				<div style="text-align:center">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
					">
					<input type="image" src="https://www.paypalobjects.com/en_US/CH/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
				</div>
				<p><?php _e('We also need volunteers to translate that plugin into more languages.', 'wp-linkedin'); ?>
					<?php _e('If you wish to help then contact <a href="https://twitter.com/cvedovini">@cvedovini</a> on Twitter or use that <a href="http://vedovini.net/contact/">contact form</a>.', 'wp-linkedin'); ?></p>
			</div> <!-- .inside -->
		</div> <!-- .postbox -->
		<div>
			<a class="twitter-timeline" href="https://twitter.com/cvedovini" data-widget-id="377037845489139712">Tweets by @cvedovini</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</div>
	</div></div> <!-- #side-container -->

</div><?php
	}

	function getLanguages() {
		static $languages;

		if (!isset($languages)) {
			$languages = array(
				'in-ID' => __('Bahasa Indonesia', 'wp-linkedin'),
				'cs-CZ' => __('Czech', 'wp-linkedin'),
				'da-DK' => __('Danish', 'wp-linkedin'),
				'nl-NL' => __('Dutch', 'wp-linkedin'),
				'fr-FR' => __('French', 'wp-linkedin'),
				'de-DE' => __('German', 'wp-linkedin'),
				'it-IT' => __('Italian', 'wp-linkedin'),
				'ja-JP' => __('Japanese', 'wp-linkedin'),
				'ko-KR' => __('Korean', 'wp-linkedin'),
				'ms-MY' => __('Malay', 'wp-linkedin'),
				'no-NO' => __('Norwegian', 'wp-linkedin'),
				'pl-PL' => __('Polish', 'wp-linkedin'),
				'pt-BR' => __('Portuguese', 'wp-linkedin'),
				'ro-RO' => __('Romanian', 'wp-linkedin'),
				'ru-RU' => __('Russian', 'wp-linkedin'),
				'es-ES' => __('Spanish', 'wp-linkedin'),
				'sv-SE' => __('Swedish', 'wp-linkedin'),
				'tr-TR' => __('Turkish', 'wp-linkedin'),
				'sq-AL' => __('Albanian', 'wp-linkedin'),
				'hy-AM' => __('Armenian', 'wp-linkedin'),
				'bs-BA' => __('Bosnian', 'wp-linkedin'),
				'my-MM' => __('Burmese (Myanmar)', 'wp-linkedin'),
				'zh-CN' => __('Chinese (Simplified)', 'wp-linkedin'),
				'zh-TW' => __('Chinese (Traditional)', 'wp-linkedin'),
				'hr-HR' => __('Croatian', 'wp-linkedin'),
				'fi-FI' => __('Finnish', 'wp-linkedin'),
				'el-GR' => __('Greek', 'wp-linkedin'),
				'hi-IN' => __('Hindi', 'wp-linkedin'),
				'hu-HU' => __('Hungarian', 'wp-linkedin'),
				'is-IS' => __('Icelandic', 'wp-linkedin'),
				'jv-JV' => __('Javanese', 'wp-linkedin'),
				'kn-IN' => __('Kannada', 'wp-linkedin'),
				'lv-LV' => __('Latvian', 'wp-linkedin'),
				'lt-LT' => __('Lithuanian', 'wp-linkedin'),
				'ml-IN' => __('Malayalam', 'wp-linkedin'),
				'sr-BA' => __('Serbian', 'wp-linkedin'),
				'sk-SK' => __('Slovak', 'wp-linkedin'),
				'tl-PH' => __('Tagalog', 'wp-linkedin'),
				'ta-IN' => __('Tamil', 'wp-linkedin'),
				'te-IN' => __('Telugu', 'wp-linkedin'),
				'th-TH' => __('Thai', 'wp-linkedin'),
				'uk-UA' => __('Ukrainian', 'wp-linkedin'),
				'vi-VN' => __('Vietnamese', 'wp-linkedin'),
				'xx-XX' => __('Other', 'wp-linkedin')
			);
			asort($languages);
		}

		return $languages;
	}
}