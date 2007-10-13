<?php
/*
+----------------------------------------------------------------+
|																							|
|	WordPress 2.1 Plugin: WP-Ban 1.21											|
|	Copyright (c) 2007 Lester "GaMerZ" Chan									|
|																							|
|	File Written By:																	|
|	- Lester "GaMerZ" Chan															|
|	- http://lesterchan.net															|
|																							|
|	File Information:																	|
|	- Banned Message Preview														|
|	- wp-content/plugins/ban/ban-preview.php								|
|																							|
+----------------------------------------------------------------+
*/


### Require wp-config.php
require('../../../wp-config.php');


### Display Banned Message
$banned_stats = get_option('banned_stats');
$banned_stats['count'] = number_format_i18n(intval($banned_stats['count']));
$banned_stats['users'][get_IP()] = number_format_i18n(intval($banned_stats['users'][get_IP()]));
$banned_message = stripslashes(get_option('banned_message'));
$banned_message = str_replace("%SITE_NAME%", get_option('blogname'), $banned_message);
$banned_message = str_replace("%SITE_URL%",  get_option('siteurl'), $banned_message);
$banned_message = str_replace("%USER_ATTEMPTS_COUNT%",  $banned_stats['users'][get_IP()], $banned_message);
$banned_message = str_replace("%USER_IP%", get_IP(), $banned_message);
$banned_message = str_replace("%USER_HOSTNAME%",  @gethostbyaddr(get_IP()), $banned_message);
$banned_message = str_replace("%TOTAL_ATTEMPTS_COUNT%",  $banned_stats['count'], $banned_message);				
echo $banned_message;
exit();
?>