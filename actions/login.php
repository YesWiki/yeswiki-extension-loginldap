<?php
if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

// Verification si le fichier de conf est bien renseigné
if (!isset($this->config['ldap_host']) or empty($this->config['ldap_host'])) {
    echo '<div class="alert alert-danger">'._t('action {{ldaplogin}} : valeur de <code>ldap_host</code> manquante dans wakka.config.php.').'</div>';
    return;
}
if (!isset($this->config['ldap_port']) or empty($this->config['ldap_port'])) {
    echo '<div class="alert alert-danger">'._t('action {{ldaplogin}} : valeur de <code>ldap_port</code> manquante dans wakka.config.php.').'</div>';
    return;
}
// parametres non obligatoires, on mets une valeur vide par defaut si non existant
if (!isset($this->config['ldap_organisation'])) {
    $this->config['ldap_group'] = '';
}
if (!isset($this->config['ldap_group'])) {
    $this->config['ldap_group'] = '';
}

// Lecture des parametres de l'action

// url d'inscription
$signupurl = 'http'.((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

// url du profil
$profileurl = $this->GetParameter('profileurl');

// sauvegarde de l'url d'ou on vient
$incomingurl = $this->GetParameter('incomingurl');
if (empty($incomingurl)) {
    $incomingurl = 'http'.((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
}

$userpage = $this->GetParameter("userpage");
// si pas d'url de page de sortie renseignée, on retourne sur la page courante
if (empty($userpage)) {
    $userpage = $incomingurl;
    // si l'url de sortie contient le passage de parametres de déconnexion, on l'efface
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "logout") {
        $userpage = str_replace('&action=logout', '', $userpage);
    }
} else {
    if ($this->IsWikiName($userpage)) {
        $userpage = $this->href('', $userpage);
    }
}

// classe css pour l'action
$class = $this->GetParameter("class");

// classe css pour les boutons
$btnclass = $this->GetParameter("btnclass");
if (empty($btnclass)) {
    $btnclass = 'btn-default';
}
$nobtn = $this->GetParameter("nobtn");

// template par défaut
$template = $this->GetParameter("template");
if (empty($template) || !file_exists('tools/loginldap/presentation/templates/' . $template)) {
    $template = "default.tpl.html";
}

$error = '';
$PageMenuUser = '';

// on initialise la valeur vide si elle n'existe pas
if (!isset($_REQUEST["action"])) {
    $_REQUEST["action"] = '';
}

// cas de la déconnexion
if ($_REQUEST["action"] == "logout") {
    $this->LogoutUser();
    $this->SetMessage(_t('LOGIN_YOU_ARE_NOW_DISCONNECTED'));
    $this->Redirect(str_replace('&action=logout', '', $incomingurl));
    exit;
}

// cas de l'identification
if ($_REQUEST["action"] == "ldaplogin") {
    if (isset($_POST['name']) && isset($_POST['password'])) {
        $ldap = ldap_connect($this->config['ldap_host'], $this->config['ldap_port']);
        $username = $_POST['name'];
        $password = $_POST['password'];

        $ldaprdn = 'uid='.$username;
        if (!empty($this->config['ldap_group'])) {
            $ldaprdn .= ',ou='.$this->config['ldap_group'];
        }
        if (!empty($this->config['ldap_organisation'])) {
            $ldaprdn .= ',o='.$this->config['ldap_organisation'];
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        $bind = @ldap_bind($ldap, $ldaprdn, $password);

        if ($bind) {
            $filter="(uid=$username)";
            $justthese = array('uid', 'sn', 'GivenName', 'mail', 'cn');

            $result = ldap_search($ldap, "o=enercoop", $filter, $justthese);
            $info = ldap_get_entries($ldap, $result);
            // echo '<ol>';
            for ($i=0; $i<$info["count"]; $i++) {
                if ($info['count'] > 1) {
                    break;
                }
                // echo "<li><strong> ". $info[$i]["uid"][0] .", " . $info[$i]["cn"][0] ."</strong><br /> (" . $info[$i]["mail"][0] .")</li>\n";
                $email = isset($info[$i]["mail"][0]) ? $info[$i]["mail"][0] : '';
                $nomwiki = genere_nom_wiki($info[$i]['cn'][0]);
                $user = $this->LoadUser($nomwiki);
                if ($user) {
                    $this->SetUser($user, 1);
                } else {
                    $this->Query("insert into ".$this->config["table_prefix"]."users set ".
                    "signuptime = now(), ".
                    "name = '".mysqli_real_escape_string($this->dblink, $nomwiki)."', ".
                    "email = '".mysqli_real_escape_string($this->dblink, $email)."', ".
                    "password = md5('".mysqli_real_escape_string($this->dblink, $password)."')");

                    // log in
                    $this->SetUser($this->LoadUser($nomwiki));
                }
            }
            // echo '</ol>';
            @ldap_close($ldap);
        } else {
            $error = '<div class="alert alert-danger">Invalid username / password</div>';
        }
    }
}

// cas d'une personne connectée déjà
if ($user = $this->GetUser()) {
    $connected = true;
    if ($this->LoadPage("PageMenuUser")) {
        $PageMenuUser.= $this->Format("{{include page=\"PageMenuUser\"}}");
    }

    // si pas de pas d'url de profil renseignée, on utilise ParametresUtilisateur
    if (empty($profileurl)) {
        $profileurl = $this->href("", "ParametresUtilisateur", "");
    } elseif ($profileurl == 'WikiName') {
        $profileurl = $this->href("edit", $user['name'], "");
    } else {
        if ($this->IsWikiName($profileurl)) {
            $profileurl = $this->href('', $profileurl);
        }
    }
} else {
    // cas d'une personne non connectée
    $connected = false;

    // si l'authentification passe mais la session n'est pas créée, on a un problème de cookie
    if ($_REQUEST['action'] == 'checklogged') {
        $error = 'Vous devez accepter les cookies pour pouvoir vous connecter.';
    }
}

//
// on affiche le template
//

include_once('includes/squelettephp.class.php');

// on cherche un template personnalise dans le repertoire themes/tools/bazar/templates
$templatetoload = 'themes/tools/loginldap/templates/' . $template;

if (!is_file($templatetoload)) {
    $templatetoload = 'tools/loginldap/presentation/templates/' . $template;
    if (!is_file($templatetoload)) {
        exit('<div class="alert alert-danger">template non trouvé : '.$template.'.</div>');
    }
}

$squel = new SquelettePhp($templatetoload, 'loginldap');

$html = $squel->render(array(
    "connected" => $connected,
    "user" => ((isset($user["name"])) ? $user["name"] : ((isset($_POST["name"])) ? $_POST["name"] : '')),
    "email" => ((isset($user["email"])) ? $user["email"] : ((isset($_POST["email"])) ? $_POST["email"] : '')),
    "incomingurl" => $incomingurl,
    "signupurl" => $signupurl,
    "profileurl" => $profileurl,
    "userpage" => $userpage,
    "PageMenuUser" => $PageMenuUser,
    "btnclass" => $btnclass,
    "nobtn" => $nobtn,
    "error" => $error
));

$output = (!empty($class)) ? '<div class="'.$class.'">'."\n".$html."\n".'</div>'."\n" : $html;

echo $output;
