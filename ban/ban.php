<?php
/*
Plugin Name: WP-Ban
Plugin URI: http://www.lesterchan.net/portfolio/programming.php
Description: Ban users by IP or host name from visiting your WordPress's blog. It will display a custom ban message when the banned IP/host name trys to visit you blog. There will be statistics recordered on how many times they attemp to visit your blog. It allows wildcard matching too.
Version: 1.11
Author: GaMerZ
Author URI: http://www.lesterchan.net
*/


/*  
	Copyright 2007  Lester Chan  (email : gamerz84@hotmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


### Create Text Domain For Translation
load_plugin_textdomain('wp-ban', 'wp-content/plugins/ban');


### Function: Ban Menu
add_action('admin_menu', 'ban_menu');
function ban_menu() {
	if (function_exists('add_management_page')) {
		add_management_page(__('Ban', 'wp-ban'), __('Ban', 'wp-ban'), 'manage_options', 'ban.php',  'ban_options');
	}
}


### Function: Get IP Address
if(!function_exists('get_IP')) {
	function get_IP() {
		if(empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip_address = $_SERVER["REMOTE_ADDR"];
		} else {
			$ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
		if(strpos($ip_address, ',') !== false) {
			$ip_address = explode(',', $ip_address);
			$ip_address = $ip_address[0];
		}
		return $ip_address;
	}
}


### Function: Process Banning
function process_ban($banarray, $against)  {
	if(!empty($banarray) && !empty($against)) {
		foreach($banarray as $cban) {
			$regexp = str_replace ('.', '\\.', $cban);
			$regexp = str_replace ('*', '.+', $regexp);
			if(ereg("^$regexp$", $against)) {
				// Credits To Joe (Ttech) - http://blog.fileville.net/
				$banned_stats = get_option('banned_stats');
				$banned_stats['count'] = (intval($banned_stats['count'])+1);
				$banned_stats['users'][get_IP()] = intval($banned_stats['users'][get_IP()]+1);
				update_option('banned_stats', $banned_stats);
				$banned_message = stripslashes(get_option('banned_message'));
				$banned_message = str_replace("%SITE_NAME%", get_option('blogname'), $banned_message);
				$banned_message = str_replace("%SITE_URL%",  get_option('siteurl'), $banned_message);
				$banned_message = str_replace("%USER_ATTEMPTS_COUNT%",  $banned_stats['users'][get_IP()], $banned_message);
				$banned_message = str_replace("%USER_IP%", get_IP(), $banned_message);
				$banned_message = str_replace("%USER_HOSTNAME%",  @gethostbyaddr(get_IP()), $banned_message);
				$banned_message = str_replace("%TOTAL_ATTEMPTS_COUNT%",  $banned_stats['count'], $banned_message);				
				echo $banned_message;
				exit(); 
			}
		}
	}
	return;
}


### Function: Banned
add_action('init', 'banned');
function banned() {
	$banned_ips = get_option('banned_ips');
	$banned_hosts = get_option('banned_hosts');
	$banned_referers = get_option('banned_referers');
	$banned_exclude_ips = get_option('banned_exclude_ips');
	$is_excluded = false;
	if(!empty($banned_exclude_ips)) {
		foreach($banned_exclude_ips as $banned_exclude_ip) {
			if(get_IP() == $banned_exclude_ip) {
				$is_excluded = true;
				break;
			}
		}
	}
	if(!$is_excluded) {
		process_ban($banned_ips, get_IP());
		process_ban($banned_hosts, @gethostbyaddr(get_IP()));
		process_ban($banned_referers, $_SERVER['HTTP_REFERER']);
	}
}


### Function: Ban Options
function ban_options() {
	global $wpdb, $current_user;
	$admin_login = trim($current_user->user_login);
	// Form Processing 
	if(!empty($_POST['do'])) {
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
		}
	}
	if($_POST['Submit']) {
		$text = '';
		$update_ban_queries = array();
		$update_ban_text = array();	
		$banned_ips_post = explode("\n", trim($_POST['banned_ips']));
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
		$update_ban_queries[] = update_option('banned_hosts', $banned_hosts);
		$update_ban_queries[] = update_option('banned_referers', $banned_referers);
		$update_ban_queries[] = update_option('banned_exclude_ips', $banned_exclude_ips);
		$update_ban_queries[] = update_option('banned_message', $banned_message);
		$update_ban_text[] = __('Banned IPs', 'wp-ban');
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
	}
	// Get Banned IPs/Hosts
	$banned_ips = get_option('banned_ips');
	$banned_hosts = get_option('banned_hosts');
	$banned_referers = get_option('banned_referers');
	$banned_exclude_ips = get_option('banned_exclude_ips');
	$banned_ips_display = '';
	$banned_hosts_display = '';
	$banned_referers_display = '';
	$banned_exclude_ips_display = '';
	if(!empty($banned_ips)) {
		foreach($banned_ips as $banned_ip) {
			$banned_ips_display .= $banned_ip."\n";
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
	$banned_hosts_display = trim($banned_hosts_display);
	$banned_referers_display = trim($banned_referers_display);
	$banned_exclude_ips_display = trim($banned_exclude_ips_display);
	// Get Banned Stats
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
/* ]]> */
</script>
<?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
<!-- Ban Options -->
<div class="wrap">
	<h2><?php _e('Ban Options', 'wp-ban'); ?></h2>
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
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
						<input type="button" name="RestoreDefault" value="<?php _e('Restore Default Template', 'wp-ban'); ?>" onclick="javascript: banned_default_templates('message');" class="button" />
				</td>
				<td>
					<textarea cols="60" rows="20" id="banned_template_message" name="banned_template_message"><?php echo stripslashes(get_option('banned_message')); ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="100%" colspan="2" align="center"><input type="submit" name="Submit" class="button" value="<?php _e('Update Options', 'wp-ban'); ?>" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'wp-ban'); ?>" class="button" onclick="javascript:history.go(-1)" /></td>
			</tr>
		</table>
	</form>
</div>
<div class="wrap">
	<h2><?php _e('Ban Stats', 'wp-ban'); ?></h2>
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
	<table width="100%" cellspacing="3" cellpadding="3" border="0">
		<tr class="thead">
			<th width="40%">IPs</th>
			<th width="30%">Attempts</th>
			<th width="30%"><input type="checkbox" name="toogle_checkbox" value="1" onclick="toggle_checkbox();" />&nbsp; Action</th>
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
	</form>
</div>
<?php
}


### Function: Check Whether Or Not The IP Address Belongs To Admin
function is_admin_ip($check) {
	$admin_ip = get_IP();
	$regexp = str_replace ('.', '\\.', $check);
	$regexp = str_replace ('*', '.+', $regexp);
	if(ereg("^$regexp$", $admin_ip)) {
		return true;
	}
	return false;
}


### Function: Check Whether Or Not The Hostname Belongs To Admin
function is_admin_hostname($check) {
	$admin_hostname = @gethostbyaddr(get_IP());
	$regexp = str_replace ('.', '\\.', $check);
	$regexp = str_replace ('*', '.+', $regexp);
	if(ereg("^$regexp$", $admin_hostname)) {
		return true;
	}
	return false;
}

### Function: Check Whether Or Not The Referer Belongs To This Site
function is_admin_referer($check) {
	$regexp = str_replace ('.', '\\.', $check);
	$regexp = str_replace ('*', '.+', $regexp);
	$url_patterns = array(get_option('siteurl'), get_option('home'), get_option('siteurl').'/', get_option('home').'/', get_option('siteurl').'/ ', get_option('home').'/ ', $_SERVER['HTTP_REFERER']);
	foreach($url_patterns as $url) {
		if(ereg("^$regexp$", $url)) {
			return true;
		}
	}
	return false;
}


### Function: Create Ban Options
add_action('activate_ban/ban.php', 'ban_init');
function ban_init() {
	global $wpdb;
	$banned_ips = array();
	$banned_hosts = array();
	$banned_referers = array();
	$banned_exclude_ips = array();
	$banned_stats = array('users' => array(), 'count' => 0);
	add_option('banned_ips', $banned_ips, 'Banned IPs');
	add_option('banned_hosts', $banned_hosts, 'Banned Hosts');
	add_option('banned_stats', $banned_stats, 'WP-Ban Stats');
	add_option('banned_message', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n".
	'<html xmlns="http://www.w3.org/1999/xhtml">'."\n".
	'<head>'."\n".
	'<meta http-equiv="Content-Type" content="text/html; charset='.get_option('blog_charset').'" />'."\n".
	'<title>%SITE_NAME% - %SITE_URL%</title>'."\n".
	'</head>'."\n".
	'<body>'."\n".
	'<p style="text-align: center; font-weight: bold;">'.__('You Are Banned.', 'wp-ban').'</p>'."\n".
	'</body>'."\n".
	'</html>', 'Banned Message');
	// Database Upgrade For WP-Ban 1.11
	add_option('banned_referers', $banned_referers, 'Banned Referers');
	add_option('banned_exclude_ips', $banned_exclude_ips, 'Banned Exclude IP');
}
?>