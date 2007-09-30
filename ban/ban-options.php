<?php
/*
+----------------------------------------------------------------+
|																							|
|	WordPress 2.1 Plugin: WP-Ban 1.20											|
|	Copyright (c) 2007 Lester "GaMerZ" Chan									|
|																							|
|	File Written By:																	|
|	- Lester "GaMerZ" Chan															|
|	- http://lesterchan.net															|
|																							|
|	File Information:																	|
|	- WP-Ban Options																	|
|	- wp-content/plugins/ban/ban-options.php								|
|																							|
+----------------------------------------------------------------+
*/


### Check Whether User Can Manage Ban Options
if(!current_user_can('manage_options')) {
	die('Access Denied');
}


### Variables
$base_name = plugin_basename('ban/ban-options.php');
$base_page = 'admin.php?page='.$base_name;
$admin_login = trim($current_user->user_login);
$mode = trim($_GET['mode']);
$ban_settings = array('banned_ips', 'banned_hosts', 'banned_stats', 'banned_message', 'banned_referers', 'banned_exclude_ips', 'banned_ips_range');


### Form Processing 
if(!empty($_POST['do'])) {
	// Decide What To Do
	switch($_POST['do']) {
		// Credits To Joe (Ttech) - http://blog.fileville.net/
		case __('Reset Ban Stats', 'wp-ban'):
			if($_POST['reset_ban_stats'] == 'yes') {
				$banned_stats = array('users' => array(), 'count' => 0);
				update_option('banned_stats', $banned_stats);
				$text = '<font color="green">'.__('All IP Ban Stats And Total Ban Stat Reseted', 'wp-ban').'</font>';
			} else {
				$banned_stats = get_option('banned_stats');
				$delete_ips = $_POST['delete_ips'];
				foreach($delete_ips as $delete_ip) {
					unset($banned_stats['users'][$delete_ip]);
				}
				update_option('banned_stats', $banned_stats);
				$text = '<font color="green">'.__('Selected IP Ban Stats Reseted', 'wp-ban').'</font>';
			}
			break;
		case __('Update Options', 'wp-ban'):
			$text = '';
			$update_ban_queries = array();
			$update_ban_text = array();	
			$banned_ips_post = explode("\n", trim($_POST['banned_ips']));
			$banned_ips_range_post = explode("\n", trim($_POST['banned_ips_range']));
			$banned_hosts_post = explode("\n", trim($_POST['banned_hosts']));	
			$banned_referers_post = explode("\n", trim($_POST['banned_referers']));
			$banned_exclude_ips_post = explode("\n", trim($_POST['banned_exclude_ips']));
			$banned_message = trim($_POST['banned_template_message']);
			if(!empty($banned_ips_post)) {
				$banned_ips = array();
				foreach($banned_ips_post as $banned_ip) {
					if($admin_login == 'admin' && ($banned_ip == get_IP() || is_admin_ip($banned_ip))) {
						$text .= '<font color="blue">'.sprintf(__('This IP \'%s\' Belongs To The Admin And Will Not Be Added To Ban List', 'wp-ban'),$banned_ip).'</font><br />';
					} else {
						$banned_ips[] = trim($banned_ip);
					}
				}
			}
			if(!empty($banned_ips_range_post)) {
				$banned_ips_range = array();
				foreach($banned_ips_range_post as $banned_ip_range) {
					$range = explode('-', $banned_ip_range);
					$range_start = trim($range[0]);
					$range_end = trim($range[1]);
					if($admin_login == 'admin' && (check_ip_within_range(get_IP(), $range_start, $range_end))) {
						$text .= '<font color="blue">'.sprintf(__('The Admin\'s IP \'%s\' Fall Within This Range (%s - %s) And Will Not Be Added To Ban List', 'wp-ban'), get_IP(), $range_start, $range_end).'</font><br />';
					} else {
						$banned_ips_range[] = trim($banned_ip_range);
					}
				}
			}
			if(!empty($banned_hosts_post)) {
				$banned_hosts = array();
				foreach($banned_hosts_post as $banned_host) {
					if($admin_login == 'admin' && ($banned_host == @gethostbyaddr(get_IP()) || is_admin_hostname($banned_host))) {
						$text .= '<font color="blue">'.sprintf(__('This Hostname \'%s\' Belongs To The Admin And Will Not Be Added To Ban List', 'wp-ban'), $banned_host).'</font><br />';
					} else {
						$banned_hosts[] = trim($banned_host);
					}
				}
			}
			if(!empty($banned_referers_post)) {
				$banned_referers = array();
				foreach($banned_referers_post as $banned_referer) {
					if(is_admin_referer($banned_referer)) {
						$text .= '<font color="blue">'.sprintf(__('This Referer \'%s\' Belongs To This Site And Will Not Be Added To Ban List', 'wp-ban'), $banned_referer).'</font><br />';
					} else {
						$banned_referers[] = trim($banned_referer);
					}
				}
			}
			if(!empty($banned_exclude_ips_post)) {
				$banned_exclude_ips = array();
				foreach($banned_exclude_ips_post as $banned_exclude_ip) {
					$banned_exclude_ips[] = trim($banned_exclude_ip);
				}
			}
			$update_ban_queries[] = update_option('banned_ips', $banned_ips);
			$update_ban_queries[] = update_option('banned_ips_range', $banned_ips_range);
			$update_ban_queries[] = update_option('banned_hosts', $banned_hosts);
			$update_ban_queries[] = update_option('banned_referers', $banned_referers);
			$update_ban_queries[] = update_option('banned_exclude_ips', $banned_exclude_ips);
			$update_ban_queries[] = update_option('banned_message', $banned_message);
			$update_ban_text[] = __('Banned IPs', 'wp-ban');
			$update_ban_text[] = __('Banned IP Range', 'wp-ban');
			$update_ban_text[] = __('Banned Host Names', 'wp-ban');
			$update_ban_text[] = __('Banned Referers', 'wp-ban');
			$update_ban_text[] = __('Banned Excluded IPs', 'wp-ban');
			$update_ban_text[] = __('Banned Message', 'wp-ban');
			$i=0;
			foreach($update_ban_queries as $update_ban_query) {
				if($update_ban_query) {
					$text .= '<font color="green">'.$update_ban_text[$i].' '.__('Updated', 'wp-ban').'</font><br />';
				}
				$i++;
			}
			if(empty($text)) {
				$text = '<font color="red">'.__('No Ban Option Updated', 'wp-ban').'</font>';
			}
			break;
		// Uninstall WP-Ban
		case __('UNINSTALL WP-Ban', 'wp-ban') :
			if(trim($_POST['uninstall_ban_yes']) == 'yes') {
				echo '<div id="message" class="updated fade">';
				echo '<p>';
				foreach($ban_settings as $setting) {
					$delete_setting = delete_option($setting);
					if($delete_setting) {
						echo '<font color="green">';
						printf(__('Setting Key \'%s\' has been deleted.', 'wp-ban'), "<strong><em>{$setting}</em></strong>");
						echo '</font><br />';
					} else {
						echo '<font color="red">';
						printf(__('Error deleting Setting Key \'%s\'.', 'wp-ban'), "<strong><em>{$setting}</em></strong>");
						echo '</font><br />';
					}
				}
				echo '</p>';
				echo '</div>'; 
				$mode = 'end-UNINSTALL';
			}
			break;
	}
}


### Determines Which Mode It Is
switch($mode) {
		//  Deactivating WP-Ban
		case 'end-UNINSTALL':
			$deactivate_url = 'plugins.php?action=deactivate&amp;plugin=ban/ban.php';
			if(function_exists('wp_nonce_url')) { 
				$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-plugin_ban/ban.php');
			}
			echo '<div class="wrap">';
			echo '<h2>'.__('Uninstall WP-Ban', 'wp-ban').'</h2>';
			echo '<p><strong>'.sprintf(__('<a href="%s">Click Here</a> To Finish The Uninstallation And WP-Ban Will Be Deactivated Automatically.', 'wp-ban'), $deactivate_url).'</strong></p>';
			echo '</div>';
			break;
	// Main Page
	default:
		$banned_ips = get_option('banned_ips');
		$banned_ips_range = get_option('banned_ips_range');
		$banned_hosts = get_option('banned_hosts');
		$banned_referers = get_option('banned_referers');
		$banned_exclude_ips = get_option('banned_exclude_ips');
		$banned_ips_display = '';
		$banned_ips_range_display = '';
		$banned_hosts_display = '';
		$banned_referers_display = '';
		$banned_exclude_ips_display = '';
		if(!empty($banned_ips)) {
			foreach($banned_ips as $banned_ip) {
				$banned_ips_display .= $banned_ip."\n";
			}
		}
		if(!empty($banned_ips_range)) {
			foreach($banned_ips_range as $banned_ip_range) {
				$banned_ips_range_display .= $banned_ip_range."\n";
			}
		}
		if(!empty($banned_hosts)) {
			foreach($banned_hosts as $banned_host) {
				$banned_hosts_display .= $banned_host."\n";
			}
		}
		if(!empty($banned_referers)) {
			foreach($banned_referers as $banned_referer) {
				$banned_referers_display .= $banned_referer."\n";
			}
		}
		if(!empty($banned_exclude_ips)) {
			foreach($banned_exclude_ips as $banned_exclude_ip) {
				$banned_exclude_ips_display .= $banned_exclude_ip."\n";
			}
		}
		$banned_ips_display = trim($banned_ips_display);
		$banned_ips_range_display = trim($banned_ips_range_display);
		$banned_hosts_display = trim($banned_hosts_display);
		$banned_referers_display = trim($banned_referers_display);
		$banned_exclude_ips_display = trim($banned_exclude_ips_display);
		$banned_stats = get_option('banned_stats');
?>
<script type="text/javascript">
/* <![CDATA[*/
	var checked = 0;
	function banned_default_templates(template) {
		var default_template;
		switch(template) {
			case "message":
				default_template = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=<?php echo get_option('blog_charset'); ?>\" />\n<title>%SITE_NAME% - %SITE_URL%</title>\n</head>\n<body>\n<p style=\"text-align: center; font-weight: bold;\"><?php _e('You Are Banned.', 'wp-ban'); ?></p>\n</body>\n</html>";
				break;
		}
		document.getElementById("banned_template_" + template).value = default_template;
	}
	function toggle_checkbox() {
		checkboxes =  document.getElementsByName('delete_ips[]');
		total = checkboxes.length;
		if(checked == 0) {
			for (var i = 0; i < total; i++) {
				checkboxes[i].checked = true;
			}
			checked++;
		} else if(checked == 1) {
			for (var i = 0; i < total; i++) {
				checkboxes[i].checked = false;
			}
			checked--;
		}
	}
	function preview_bannedmessage() {
		window.open('<?php echo get_option('siteurl').'/wp-content/plugins/ban/ban-preview.php'; ?>');
	}
/* ]]> */
</script>
<?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
<!-- Ban Options -->
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<div class="wrap">
	<h2><?php _e('Ban Options', 'wp-ban'); ?></h2>
	<table width="100%" cellspacing="3" cellpadding="3" border="0">
		<tr>
			<td valign="top" colspan="2" align="center">
				<?php printf(__('Your IP is: <strong>%s</strong><br />Your Host Name is: <strong>%s</strong><br />Your Site URL is: <strong>%s</strong>', 'wp-ban'), get_IP(), @gethostbyaddr(get_IP()), get_option('siteurl')); ?><br />
				<?php _e('Please <strong>DO NOT</strong> ban yourself.', 'wp-ban'); ?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<strong><?php _e('Banned IPs', 'wp-ban'); ?>:</strong><br />
				<?php _e('Use <strong>*</strong> for wildcards.', 'wp-ban'); ?><br />
				<?php _e('Start each entry on a new line.', 'wp-ban'); ?><br /><br />
				<?php _e('Examples:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> 192.168.1.100<br />
				<strong>&raquo;</strong> 192.168.1.*<br />
				<strong>&raquo;</strong> 192.168.*.*<br />
			</td>
			<td>
				<textarea cols="40" rows="10" name="banned_ips"><?php echo $banned_ips_display; ?></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<strong><?php _e('Banned IP Range', 'wp-ban'); ?>:</strong><br />
				<?php _e('Start each entry on a new line.', 'wp-ban'); ?><br /><br />
				<?php _e('Examples:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> 192.168.1.1-192.168.1.255<br /><br />
				<?php _e('Notes:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> <?php _e('No Wildcards Allowed.', 'wp-ban'); ?><br />
			</td>
			<td>
				<textarea cols="40" rows="10" name="banned_ips_range"><?php echo $banned_ips_range_display; ?></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<strong><?php _e('Banned Host Names', 'wp-ban'); ?>:</strong><br />
				<?php _e('Use <strong>*</strong> for wildcards', 'wp-ban'); ?>.<br />
				<?php _e('Start each entry on a new line.', 'wp-ban'); ?><br /><br />
				<?php _e('Examples:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> *.sg<br />
				<strong>&raquo;</strong> *.cn<br />
				<strong>&raquo;</strong> *.th<br />
			</td>
			<td>
				<textarea cols="40" rows="10" name="banned_hosts"><?php echo $banned_hosts_display; ?></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<strong><?php _e('Banned Referers', 'wp-ban'); ?>:</strong><br />
				<?php _e('Use <strong>*</strong> for wildcards', 'wp-ban'); ?>.<br />
				<?php _e('Start each entry on a new line.', 'wp-ban'); ?><br /><br />
				<?php _e('Examples:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> http://*.blogspot.com<br /><br />
				<?php _e('Notes:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> <?php _e('There are ways to bypass this method of banning.', 'wp-ban'); ?>
			</td>
			<td>
				<textarea cols="40" rows="10" name="banned_referers"><?php echo $banned_referers_display; ?></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<strong><?php _e('Banned Exclude IPs', 'wp-ban'); ?>:</strong><br />
				<?php _e('Start each entry on a new line.', 'wp-ban'); ?><br /><br />
				<?php _e('Examples:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> 192.168.1.100<br /><br />
				<?php _e('Notes:', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> <?php _e('No Wildcards Allowed.', 'wp-ban'); ?><br />
				<strong>&raquo;</strong> <?php _e('These Users Will Not Get Banned.', 'wp-ban'); ?>
			</td>
			<td>
				<textarea cols="40" rows="10" name="banned_exclude_ips"><?php echo $banned_exclude_ips_display; ?></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<strong><?php _e('Banned Message', 'wp-ban'); ?>:</strong><br /><br /><br />
					<?php _e('Allowed Variables:', 'wp-ban'); ?><br />
					- %SITE_NAME%<br />
					- %SITE_URL%<br />
					- %USER_ATTEMPTS_COUNT%<br />
					- %USER_IP%<br />
					- %USER_HOSTNAME%<br />
					- %TOTAL_ATTEMPTS_COUNT%<br /><br />
					<input type="button" name="RestoreDefault" value="<?php _e('Restore Default Template', 'wp-ban'); ?>" onclick="banned_default_templates('message');" class="button" /><br /><br />
					<input type="button" name="RestoreDefault" value="<?php _e('Preview Banned Message', 'wp-ban'); ?>" onclick="preview_bannedmessage();" class="button" /><br />
			</td>
			<td>
				<textarea cols="60" rows="20" id="banned_template_message" name="banned_template_message"><?php echo stripslashes(get_option('banned_message')); ?></textarea>
			</td>
		</tr>
		<tr>
			<td width="100%" colspan="2" align="center"><input type="submit" name="do" class="button" value="<?php _e('Update Options', 'wp-ban'); ?>" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'wp-ban'); ?>" class="button" onclick="javascript:history.go(-1)" /></td>
		</tr>
	</table>
</div>
</form>

<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<div class="wrap">
	<h2><?php _e('Ban Stats', 'wp-ban'); ?></h2>
	<table width="100%" cellspacing="3" cellpadding="3" border="0">
		<tr class="thead">
			<th width="40%"><?php _e('IPs', 'wp-ban'); ?></th>
			<th width="30%"><?php _e('Attempts', 'wp-ban'); ?></th>
			<th width="30%"><input type="checkbox" name="toogle_checkbox" value="1" onclick="toggle_checkbox();" />&nbsp; <?php _e('Action', 'wp-ban'); ?></th>
		</tr>
			<?php
				// Credits To Joe (Ttech) - http://blog.fileville.net/
				if(!empty($banned_stats['users'])) {
					$i = 0;
					foreach($banned_stats['users'] as $key => $value) {
						if($i%2 == 0) {
							$style = 'style=\'background-color: #eee\'';
						}  else {
							$style = 'style=\'background-color: none\'';
						}
						echo "<tr $style>\n";
						echo "<td style=\"text-align: center;\">$key</td>\n";
						echo "<td style=\"text-align: center;\">$value</td>\n";
						echo "<td><input type=\"checkbox\" name=\"delete_ips[]\" value=\"$key\" />&nbsp;Reset this IP ban stat?</td>\n";
						echo '</tr>'."\n";
						$i++;
					}
				} else {
					echo "<tr>\n";
					echo '<td colspan="3" align="center">'.__('No Attempts', 'wp-ban').'</td>'."\n";
					echo '</tr>'."\n";
				}
			?>
		<tr class="thead">
			<td style="text-align: center;"><strong><?php _e('Total  Attempts:', 'wp-ban'); ?></strong></td>
			<td style="text-align: center;"><strong><?php echo intval($banned_stats['count']); ?></strong></td>
			<td><input type="checkbox" name="reset_ban_stats" value="yes" />	&nbsp;<?php _e('Reset all IP ban stats and total ban stat?', 'wp-ban'); ?>&nbsp;</td>
		</tr>
	</table>
	<p style="text-align: center;"><input type="submit" name="do" value="<?php _e('Reset Ban Stats', 'wp-ban'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Reset Ban Stats.', 'wp-ban'); ?>\n\n<?php _e('This Action Is Not Reversible. Are you sure?', 'wp-ban'); ?>')" /></p>
</div>
</form>

<!-- Uninstall WP-Ban -->
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
<div class="wrap"> 
	<h2><?php _e('Uninstall WP-Ban', 'wp-ban'); ?></h2>
	<p style="text-align: left;">
		<?php _e('Deactivating WP-Ban plugin does not remove any data that may have been created, such as the ban options. To completely remove this plugin, you can uninstall it here.', 'wp-ban'); ?>
	</p>
	<p style="text-align: left; color: red">
		<strong><?php _e('WARNING:', 'wp-ban'); ?></strong><br />
		<?php _e('Once uninstalled, this cannot be undone. You should use a Database Backup plugin of WordPress to back up all the data first.', 'wp-ban'); ?>
	</p>
	<p style="text-align: left; color: red">
		<strong><?php _e('The following WordPress Options will be DELETED:', 'wp-ban'); ?></strong><br />
	</p>
	<table width="70%"  border="0" cellspacing="3" cellpadding="3">
		<tr class="thead">
			<td align="center"><strong><?php _e('WordPress Options', 'wp-ban'); ?></strong></td>
		</tr>
		<tr>
			<td valign="top" style="background-color: #eee;">
				<ol>
				<?php
					foreach($ban_settings as $settings) {
						echo '<li>'.$settings.'</li>'."\n";
					}
				?>
				</ol>
			</td>
		</tr>
	</table>
	<p>&nbsp;</p>
	<p style="text-align: center;">
		<input type="checkbox" name="uninstall_ban_yes" value="yes" />&nbsp;<?php _e('Yes', 'wp-ban'); ?><br /><br />
		<input type="submit" name="do" value="<?php _e('UNINSTALL WP-Ban', 'wp-ban'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Uninstall WP-Ban From WordPress.\nThis Action Is Not Reversible.\n\n Choose [Cancel] To Stop, [OK] To Uninstall.', 'wp-ban'); ?>')" />
	</p>
</div> 
</form>
<?php
} // End switch($mode)
?>