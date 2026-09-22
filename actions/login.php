<?php
// Action {{login}} de l'extension loginldap. Elle remplace le login natif de
// YesWiki quand l'extension est installée, et se replie dessus dès que
// l'annuaire n'est pas configuré ou ne répond pas.

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

require_once 'tools/loginldap/libs/loginldap.lib.php';

$missingConfig = loginLdapMissingConfig($this);
if (!empty($missingConfig)) {
    echo '<div class="alert alert-warning">'
        . _t('LDAP_CONFIG_MISSING') . ' <code>' . implode('</code>, <code>', $missingConfig) . '</code>. '
        . _t('LDAP_FALLBACK_NOTICE')
        . ' <a href="' . loginLdapDocUrl($this) . '">' . _t('LDAP_READ_DOC') . '</a>'
        . '</div>';
    echo loginLdapRenderCoreLogin($this);

    return;
}

if (!extension_loaded('ldap')) {
    echo '<div class="alert alert-warning">'
        . _t('LDAP_PHP_EXTENSION_MISSING') . ' ' . _t('LDAP_FALLBACK_NOTICE')
        . '</div>';
    echo loginLdapRenderCoreLogin($this);

    return;
}

if (!isset($this->config['ldap_base'])) {
    $this->config['ldap_base'] = '';
} else {
    if (!isset($this->config['ldap_organisation'])) {
        $this->config['ldap_group'] = '';
    }
    if (!isset($this->config['ldap_group'])) {
        $this->config['ldap_group'] = '';
    }
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
$currenturl = 'http' . ($isHttps ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

$signupurl = $currenturl;

$profileurl = $this->GetParameter('profileurl');

$incomingurl = $this->GetParameter('incomingurl');
if (empty($incomingurl)) {
    $incomingurl = $currenturl;
}

$userpage = $this->GetParameter('userpage');
if (empty($userpage)) {
    $userpage = $incomingurl;
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout') {
        $userpage = str_replace('&action=logout', '', $userpage);
    }
} else {
    if ($this->IsWikiName($userpage)) {
        $userpage = $this->href('', $userpage);
    }
}

$class = $this->GetParameter('class');

$btnclass = $this->GetParameter('btnclass');
if (empty($btnclass)) {
    $btnclass = 'btn-default';
}
$nobtn = $this->GetParameter('nobtn');

$template = $this->GetParameter('template');
if (empty($template) || !file_exists('tools/loginldap/presentation/templates/' . $template)) {
    $template = 'default.tpl.html';
}

$error = '';
$serverFailure = '';
$PageMenuUser = '';

if (!isset($_REQUEST['action'])) {
    $_REQUEST['action'] = '';
}

if ($_REQUEST['action'] == 'logout') {
    $this->LogoutUser();
    $this->SetMessage(_t('LOGIN_YOU_ARE_NOW_DISCONNECTED'));
    $this->Redirect(str_replace('&action=logout', '', $incomingurl));
    exit;
}

if ($_REQUEST['action'] == 'ldaplogin' && isset($_POST['name']) && isset($_POST['password'])) {
    $username = $_POST['name'];
    $password = $_POST['password'];

    if ($password === '') {
        $error = '<div class="alert alert-danger">' . _t('LDAP_INVALID_CREDENTIALS') . '</div>';
    } else {
        $ldap = loginLdapConnect($this);
        if ($ldap === false) {
            $serverFailure = _t('LDAP_SERVER_UNREACHABLE');
        } else {
            $ldaprdn = 'uid=' . $username;
            if (!empty($this->config['ldap_base'])) {
                $ldaprdn .= ',' . $this->config['ldap_base'];
            } else {
                if (!empty($this->config['ldap_group'])) {
                    $ldaprdn .= ',ou=' . $this->config['ldap_group'];
                }
                if (!empty($this->config['ldap_organisation'])) {
                    $ldaprdn .= ',o=' . $this->config['ldap_organisation'];
                }
            }

            $bind = @ldap_bind($ldap, $ldaprdn, $password);

            if ($bind) {
                $filter = "(uid=$username)";
                $justthese = ['uid', 'sn', 'GivenName', 'mail', 'cn'];

                $result = @ldap_search($ldap, $ldaprdn, $filter, $justthese);
                $info = $result ? ldap_get_entries($ldap, $result) : ['count' => 0];
                for ($i = 0; $i < $info['count']; $i++) {
                    if ($info['count'] > 1) {
                        break;
                    }
                    $email = isset($info[$i]['mail'][0]) ? $info[$i]['mail'][0] : '';
                    $nomwiki = (!empty($info[$i]['uid'][0]) && !is_int($info[$i]['uid'][0]))
                        ? $info[$i]['uid'][0]
                        : genere_nom_wiki($info[$i]['cn'][0]);
                    $user = $this->LoadUser($nomwiki);
                    if ($user) {
                        $this->SetUser($user, 1);
                    } else {
                        $this->Query('insert into ' . $this->config['table_prefix'] . 'users set ' .
                        'signuptime = now(), ' .
                        "motto = '', " .
                        "name = '" . mysqli_real_escape_string($this->dblink, $nomwiki) . "', " .
                        "email = '" . mysqli_real_escape_string($this->dblink, $email) . "', " .
                        "password = md5('" . mysqli_real_escape_string($this->dblink, $password) . "')");

                        $this->SetUser($this->LoadUser($nomwiki));
                    }
                }
                @ldap_close($ldap);
            } elseif (loginLdapIsServerFailure($ldap)) {
                $serverFailure = _t('LDAP_SERVER_UNREACHABLE') . ' (' . ldap_error($ldap) . ')';
                @ldap_close($ldap);
            } else {
                $error = '<div class="alert alert-danger">' . _t('LDAP_INVALID_CREDENTIALS') . '</div>';
                @ldap_close($ldap);
            }
        }
    }
}

if (!empty($serverFailure)) {
    echo '<div class="alert alert-warning">' . $serverFailure . ' ' . _t('LDAP_FALLBACK_NOTICE') . '</div>';
    echo loginLdapRenderCoreLogin($this);

    return;
}

if ($user = $this->GetUser()) {
    $connected = true;
    if ($this->LoadPage('PageMenuUser')) {
        $PageMenuUser .= $this->Format('{{include page="PageMenuUser"}}');
    }

    if (empty($profileurl)) {
        $profileurl = $this->href('', 'ParametresUtilisateur', '');
    } elseif ($profileurl == 'WikiName') {
        $profileurl = $this->href('edit', $user['name'], '');
    } else {
        if ($this->IsWikiName($profileurl)) {
            $profileurl = $this->href('', $profileurl);
        }
    }
} else {
    $connected = false;

    if ($_REQUEST['action'] == 'checklogged') {
        $error = '<div class="alert alert-danger">' . _t('LDAP_COOKIES_REQUIRED') . '</div>';
    }
}

$html = $this->render('@loginldap/' . $template, [
    'connected' => $connected,
    'user' => ((isset($user['name'])) ? $user['name'] : ((isset($_POST['name'])) ? $_POST['name'] : '')),
    'email' => ((isset($user['email'])) ? $user['email'] : ((isset($_POST['email'])) ? $_POST['email'] : '')),
    'incomingurl' => $incomingurl,
    'signupurl' => $signupurl,
    'profileurl' => $profileurl,
    'userpage' => $userpage,
    'PageMenuUser' => $PageMenuUser,
    'btnclass' => $btnclass,
    'nobtn' => $nobtn,
    'error' => $error,
]);

$output = (!empty($class)) ? '<div class="' . $class . '">' . "\n" . $html . "\n" . '</div>' . "\n" : $html;

echo $output;
