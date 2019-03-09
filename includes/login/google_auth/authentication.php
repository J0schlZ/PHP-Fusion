<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: login/google_auth/authentication.php
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
require_once __DIR__.'/../../../maincore.php';
require_once THEMES.'templates/header.php';
require_once __DIR__.'/google_auth.login.inc';

$google = new GoogleAuthenticator();
$settings = fusion_get_settings();
$secret_key = defined('SECRET_KEY') ? SECRET_KEY : "secret_key";
$secret_key_salt = defined('SECRET_KEY_SALT') ? SECRET_KEY_SALT : "secret_salt";
$algo = $settings['password_algorithm'] ? $settings['password_algorithm'] : "sha256";
$user = [];
$secret = '';
$remember = '';

// restore code
if (isset($_GET['restore_code']) && isset($_GET['uid']) && isnum($_GET['uid'])) {
    $restore_code = stripinput($_GET['restore_code']);
    $result = dbquery("SELECT * FROM ".DB_USERS." WHERE user_id=:uid", [':uid' => intval($_GET['uid'])]);
    if (dbrows($result)) {
        $salt = md5(isset($user['user_salt']) ? $user['user_salt'].SECRET_KEY_SALT : SECRET_KEY_SALT);
        if ($restore_code == $user['user_id'].hash_hmac($algo, $user['user_id'].$secret.$secret_key, $salt)) {
            // restore the account
            dbquery("UPDATE ".DB_USERS." SET user_status=0 WHERE user_status=5 AND user_id=:uid", [':uid' => intval($_GET['uid'])]);
            addNotice("success", $locale['uf_gauth_130']);
        } else {
            addNotice("danger", $locale['uf_gauth_131']);
            redirect(BASEDIR.'index.php');
        }
    } else {
        addNotice("danger", $locale['uf_gauth_132']);
        redirect(BASEDIR.'index.php');
    }
}

if (isset($_SESSION['secret_code'])) {
    $secret = stripinput($_SESSION['secret_code']);
    $user_id = intval($_SESSION['uid']);
    $verify_secret = dbcount("(user_id)", DB_USERS, "user_id=:uid AND user_gauth=:secret", [':uid' => $user_id, ':secret' => $secret]);
    if (!empty($verify_secret)) {
        $user = fusion_get_user($user_id);
        if (!empty($user)) {
            if (isset($_POST['authenticate'])) {
                if (!isset($_SESSION['auth_attempt'][USER_IP])) {
                    $_SESSION['auth_attempt'][USER_IP] = 3;
                }
                $gCode = form_sanitizer($_POST['g_code'], '', 'g_code');
                if (\Defender::safe()) {
                    $checkResult = $google->verifyCode($secret, $gCode, 2);    // 2 = 2*30sec clock tolerance
                    if ($checkResult) {
                        // Authenticate the User
                        \PHPFusion\Authenticate::setUserCookie($user['user_id'], $user['user_salt'], $user['user_algo'], $remember, TRUE);
                        \PHPFusion\Authenticate::_setUserTheme($user);
                        unset($_SESSION['uid']);
                        unset($_SESSION['secret_code']);
                        unset($_SESSION['auth_attempt'][USER_IP]);
                        redirect(BASEDIR.'index.php');

                    } else {

                        if (!empty($_SESSION['auth_attempt'][USER_IP])) {
                            $_SESSION['auth_attempt'][USER_IP] = $_SESSION['auth_attempt'][USER_IP] - 1;
                            addNotice('danger', str_replace('{D}', $_SESSION['auth_attempt'][USER_IP], $locale['uf_gauth_123']));
                        } else {

                            $key = $user_id.$secret.$secret_key;
                            $hash = hash_hmac($algo, $key, $salt);
                            $restore_hash = $user_id.$hash;
                            $restore_link = $settings['siteurl']."/includes/login/google_auth/authentication.php?uid=$user_id&amp;restore_hash=$restore_hash";
                            // ban the user
                            addNotice("danger", str_replace('{SITE_NAME}', $settings['sitename'], $locale['uf_gauth_120']));
                            $subject = $locale['uf_gauth_121'];
                            $message = parse_textarea(strtr($locale['uf_gauth_122'], [
                                '{USERNAME}'     => $user['user_name'],
                                '{SITENAME}'     => $settings['sitename'],
                                '{RESTORE_LINK}' => "<a href='$restore_link'>$restore_link</a>",
                                '{SITE_ADMIN}'   => $settings['siteusername'],
                            ]));
                            $mail = sendemail($user['user_name'], $user['user_email'], $settings['siteusername'], $settings['site_email'], $subject, $message);
                            if ($mail) {
                                dbquery("UPDATE ".DB_USERS." SET user_status=5 WHERE user_id=:uid", [':uid' => $user['user_id']]);
                                unset($_SESSION['secret_code']);
                                unset($_SESSION['auth_attempt'][USER_IP]);
                                redirect(BASEDIR.'index.php');
                            }
                        }
                    }
                }
            }

            $tpl = \PHPFusion\Template::getInstance('g_authenticate');
            $tpl->set_template(__DIR__."/templates/authorize.html");
            $tpl->set_file([IMAGES]);
            $tpl->set_tag('image_src', 'images/google2fa.png');
            $tpl->set_tag('title', $locale['uf_gauth_100']);
            $tpl->set_tag('description', $locale['uf_gauth_101']);
            $tpl->set_tag('detail', $locale['uf_gauth_102']);
            $tpl->set_tag('input', form_text('g_code', $locale['uf_gauth_103'], '', [
                'required'    => TRUE,
                'error_text'  => $locale['uf_gauth_104'],
                'placeholder' => $locale['uf_gauth_105']
            ]));
            $tpl->set_tag('button', form_button('authenticate', $locale['uf_gauth_106'], $locale['uf_gauth_106'], ['class' => 'btn-block btn-primary btn-md']));
            echo openform('gauth_frm', 'post', FUSION_REQUEST);
            echo $tpl->get_output();
            echo closeform();

        } else {
            // invalid user
            redirect(BASEDIR.'index.php');
        }
    } else {
        // deliberately visit this page, so must redirect.
        redirect(BASEDIR.'index.php');
    }
} else {
    redirect(BASEDIR.'index.php');
}

require_once THEMES.'templates/footer.php';
