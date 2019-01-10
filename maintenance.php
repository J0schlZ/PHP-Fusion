<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: maintenance.php
| Author: PHP-Fusion Development Team
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
require_once dirname(__FILE__).'/maincore.php';

if (!fusion_get_settings("maintenance")) {
    redirect(BASEDIR.'index.php');
}

if (fusion_get_settings("site_seo") == 1 && !defined("IN_PERMALINK")) {
    \PHPFusion\Rewrite\Permalinks::getPermalinkInstance()->handle_url_routing("");
}

$locale = fusion_get_locale();

$info = [];

ob_start();

if (!iMEMBER) {
    switch (fusion_get_settings('login_method')) {
        case "2" :
            $placeholder = $locale['global_101c'];
            break;
        case "1" :
            $placeholder = $locale['global_101b'];
            break;
        default:
            $placeholder = $locale['global_101a'];
    }
    $user_name = isset($_POST['user_name']) ? form_sanitizer($_POST['user_name'], "", "user_name") : "";
    $user_password = isset($_POST['user_pass']) ? form_sanitizer($_POST['user_pass'], "", "user_pass") : "";
    $path = fusion_get_settings('opening_page');
    if (!defined('IN_PERMALINK')) {
        $path = BASEDIR.(!stristr(fusion_get_settings('opening_page'), '.php') ? fusion_get_settings('opening_page').'/index.php' : fusion_get_settings('opening_page'));
    }
    $info = [
        "open_form"            => openform('loginpageform', 'POST', $path),
        "user_name"            => form_text('user_name', "", $user_name, ['placeholder' => $placeholder, 'inline' => TRUE]),
        "user_pass"            => form_text('user_pass', "", $user_password, ['placeholder' => $locale['global_102'], 'type' => 'password', 'inline' => TRUE]),
        "remember_me"          => form_checkbox('remember_me', $locale['global_103'], ""),
        "login_button"         => form_button('login', $locale['global_104'], $locale['global_104'], ['class' => 'btn-primary btn-block m-b-20']),
        "registration_link"    => (fusion_get_settings('enable_registration')) ? "<p>".$locale['global_105']."</p>\n" : "",
        "forgot_password_link" => $locale['global_106'],
        "close_form"           => closeform()
    ];
}

require_once THEME."theme.php";
require_once INCLUDES."header_includes.php";
require_once INCLUDES."theme_functions_include.php";
require_once THEMES."templates/render_functions.php";
include THEMES."templates/global/maintenance.php";

header("Content-Type: text/html; charset=".$locale['charset']."");
echo "<!DOCTYPE html>\n";
echo "<html lang='".$locale['xml_lang']."' dir='".$locale['text-direction']."'>\n";
echo "<head>\n";
echo "<title>".fusion_get_settings('sitename')."</title>\n";
echo "<meta charset='".$locale['charset']."' />\n";
echo "<meta name='description' content='".fusion_get_settings('description')."' />\n";
echo "<meta name='url' content='".fusion_get_settings('siteurl')."' />\n";
echo "<meta name='keywords' content='".fusion_get_settings('keywords')."' />\n";
echo "<meta name='image' content='".fusion_get_settings('siteurl').fusion_get_settings('sitebanner')."' />\n";
// Load bootstrap stylesheets
if (fusion_get_settings('bootstrap') == TRUE || defined('BOOTSTRAP')) {
    echo "<meta http-equiv='X-UA-Compatible' content='IE=edge' />\n";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0' />\n";
    echo "<link rel='stylesheet' href='".INCLUDES."bootstrap/bootstrap.min.css' type='text/css' />\n";
    echo "<link rel='stylesheet' href='".INCLUDES."bootstrap/bootstrap-submenu.min.css' type='text/css' />\n";
    if (fusion_get_locale('text-direction') == 'rtl') {
        echo "<link href='".INCLUDES."bootstrap/bootstrap-rtl.min.css' rel='stylesheet' media='screen' />";
    }
    $user_theme = fusion_get_userdata('user_theme');
    $theme_name = $user_theme !== 'Default' ? $user_theme : fusion_get_settings('theme');
    $theme_data = dbarray(dbquery("SELECT theme_file FROM ".DB_THEME." WHERE theme_name='".$theme_name."' AND theme_active='1'"));
    if (!empty($theme_data)) {
        echo "<link href='".THEMES.$theme_data['theme_file']."' rel='stylesheet' type='text/css' />\n";
    }
}

if (fusion_get_settings('entypo') || defined('ENTYPO')) {
    echo "<link rel='stylesheet' href='".INCLUDES."fonts/entypo/entypo.min.css' type='text/css' />\n";
}

// Font Awesome 4
if (defined('FONTAWESOME-V4')) {
    if (fusion_get_settings('fontawesome') || defined('FONTAWESOME')) {
        echo "<link rel='stylesheet' href='".INCLUDES."fonts/font-awesome/css/font-awesome.min.css' type='text/css' />\n";
    }
}

// Font Awesome 5
if (!defined('FONTAWESOME-V4')) {
    if (fusion_get_settings('fontawesome') || defined('FONTAWESOME')) {
        echo "<link rel='stylesheet' href='".INCLUDES."fonts/font-awesome-5/css/all.min.css' type='text/css' />\n";
        echo "<link rel='stylesheet' href='".INCLUDES."fonts/font-awesome-5/css/v4-shims.min.css' type='text/css' />\n";
    }
}

if (!defined('NO_DEFAULT_CSS')) {
    echo "<link href='".THEMES."templates/default.min.css' rel='stylesheet' type='text/css' media='screen' />\n";
}
echo "<link href='".THEME."styles.css' rel='stylesheet' type='text/css' media='screen' />\n";

echo render_favicons(defined('THEME_ICON') ? THEME_ICON : IMAGES.'favicons/');

echo "<script type='text/javascript' src='".INCLUDES."jquery/jquery.js'></script>\n";
echo "</head>\n";

display_maintenance($info);

echo \PHPFusion\OutputHandler::$pageFooterTags;
$fusion_jquery_tags = PHPFusion\OutputHandler::$jqueryTags;
if (!empty($fusion_jquery_tags)) {
    $minifier = new PHPFusion\Minify\JS($fusion_jquery_tags);
    echo "<script type='text/javascript'>$(function(){".$minifier->minify()."});</script>\n";
}

if (fusion_get_settings('bootstrap') || defined('BOOTSTRAP')) {
    echo "<script type='text/javascript' src='".INCLUDES."bootstrap/bootstrap.min.js'></script>\n";
}
echo "</body>\n";
echo "</html>";

$output = ob_get_contents();
if (ob_get_length() !== FALSE) {
    ob_end_clean();
}
$output = handle_output($output);
echo $output;
if ((ob_get_length() > 0)) {
    ob_end_flush();
}
