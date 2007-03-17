<?php
/*
Plugin Name: WP-Ban
Plugin URI: http://www.lesterchan.net/portfolio/programming.php
Description: Ban Users By IP Or Host Name From Visiting Your WordPress Site
Version: 1.00
Author: GaMerZ
Author URI: http://www.lesterchan.net
*/


/*  Copyright 2006  Lester Chan  (email : gamerz84@hotmail.com)

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


### Function: Ban Menu
add_action('admin_menu', 'ban_menu');
function ban_menu() {
	if (function_exists('add_options_page')) {
		add_options_page(__('Ban'), __('Ban'), 'manage_options', 'ban.php',  'ban_options');
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
	if(!empty($banarray)) {
		foreach($banarray as $cban) {
			$regexp = str_replace ('.', '\\.', $cban);
			$regexp = str_replace ('*', '.+', $regexp);
			if(ereg("^$regexp$", $against)) {
				$banned_message = stripslashes(get_settings('banned_message'));
				$banned_message = str_replace("%SITE_NAME%", get_settings('blogname'), $banned_message);
				$banned_message = str_replace("%SITE_URL%",  get_settings('siteurl'), $banned_message);
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
	$banned_ips = get_settings('banned_ips');
	$banned_hosts = get_settings('banned_hosts');
	process_ban($banned_ips, get_IP());
	process_ban($banned_hosts, gethostbyaddr(get_IP()));
}


### Function: Ban Options
function ban_options() {
	global $wpdb;
	if($_POST['Submit']) {
		$update_ban_queries = array();
		$update_ban_text = array();	
		$banned_ips_post = explode("\n", trim($_POST['banned_ips']));
		$banned_hosts_post = explode("\n", trim($_POST['banned_hosts']));	
		$banned_message = trim($_POST['banned_template_message']);
		if(!empty($banned_ips_post)) {
			$banned_ips = array();
			foreach($banned_ips_post as $banned_ip) {
				if($banned_ip != get_IP()) {
					$banned_ips[] = trim($banned_ip);
				}
			}
		}
		if(!empty($banned_hosts_post)) {
			$banned_hosts = array();
			foreach($banned_hosts_post as $banned_host) {
				if($banned_host != gethostbyaddr(get_IP())) {
					$banned_hosts[] = trim($banned_host);
				}
			}
		}
		$update_ban_queries[] = update_option('banned_ips', $banned_ips);
		$update_ban_queries[] = update_option('banned_hosts', $banned_hosts);
		$update_ban_queries[] = update_option('banned_message', $banned_message);
		$update_ban_text[] = __('Banned IPs');
		$update_ban_text[] = __('Banned Host Names');
		$update_ban_text[] = __('Banned Message');
		$i=0;
		$text = '';
		foreach($update_ban_queries as $update_ban_query) {
			if($update_ban_query) {
				$text .= '<font color="green">'.$update_ban_text[$i].' '.__('Updated').'</font><br />';
			}
			$i++;
		}
		if(empty($text)) {
			$text = '<font color="red">'.__('No Ban Option Updated').'</font>';
		}
	}
	### Get Useronline Bots
	$banned_ips = get_settings('banned_ips');
	$banned_hosts = get_settings('banned_hosts');
	$banned_ips_display = '';
	$banned_hosts_display = '';
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
	$banned_ips_display = trim($banned_ips_display);
	$banned_hosts_display = trim($banned_hosts_display);
?>
<script type="text/javascript">
/* <![CDATA[*/
	function banned_default_templates(template) {
		var default_template;
		switch(template) {
			case "message":
				default_template = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=<?php echo get_settings('blog_charset'); ?>\" />\n<title>%SITE_NAME% - %SITE_URL%</title>\n</head>\n<body>\n<p style=\"text-align: center; font-weight: bold;\">You Are Banned.</p>\n</body>\n</html>";
				break;
		}
		document.getElementById("banned_template_" + template).value = default_template;
	}
/* ]]> */
</script>
<?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
<!-- Ban Options -->
<div class="wrap">
	<h2>Ban Options</h2>
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
		<table width="100%" cellspacing="3" cellpadding="3" border="0">
			<tr>
				<td valign="top" colspan="2" align="center">
					Your IP is: <strong><?php echo get_IP(); ?></strong><br />Your Host Name is: <strong><?php echo gethostbyaddr(get_IP()); ?></strong><br />
					Please <strong>DO NOT</strong> ban yourself.
				</td>
			</tr>
			<tr>
				<td valign="top">
					<strong><?php _e('Banned IPs'); ?>:</strong><br />
					Use <strong>*</strong> for wildcards.<br />
					Start each entry on a new line.<br /><br />
					Examples:<br />
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
					<strong><?php _e('Banned Host Names'); ?>:</strong><br />
					Use <strong>*</strong> for wildcards.<br />
					Start each entry on a new line.<br /><br />
					Examples:<br />
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
					<strong><?php _e('Banned Message'); ?>:</strong><br /><br /><br />
						<?php _e('Allowed Variables:'); ?><br />
						- %SITE_NAME%<br />
						- %SITE_URL%<br /><br />
						<input type="button" name="RestoreDefault" value="<?php _e('Restore Default Template'); ?>" onclick="javascript: banned_default_templates('message');" class="button" />
				</td>
				<td>
					<textarea cols="60" rows="20" id="banned_template_message" name="banned_template_message"><?php echo stripslashes(get_settings('banned_message')); ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="100%" colspan="2" align="center"><input type="submit" name="Submit" class="button" value="<?php _e('Update Options'); ?>" />&nbsp;&nbsp;<input type="button" name="cancel" value="Cancel" class="button" onclick="javascript:history.go(-1)" /></td>
			</tr>
		</table>
	</form>
</div>
<?php
}


### Function: Create Ban Options
add_action('activate_ban.php', 'ban_init');
function ban_init() {
	global $wpdb;
	$banned_ips = array();
	$banned_hosts = array();
	add_option('banned_ips', $banned_ips, 'Banned IPs');
	add_option('banned_hosts', $banned_hosts, 'Banned Hosts');
	add_option('banned_message', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n".
	'<html xmlns="http://www.w3.org/1999/xhtml">'."\n".
	'<head>'."\n".
	'<meta http-equiv="Content-Type" content="text/html; charset='.get_settings('blog_charset').'" />'."\n".
	'<title>%SITE_NAME% - %SITE_URL%</title>'."\n".
	'</head>'."\n".
	'<body>'."\n".
	'<p style="text-align: center; font-weight: bold;">You Are Banned.</p>'."\n".
	'</body>'."\n".
	'</html>', 'Banned Hosts');	
}
?>