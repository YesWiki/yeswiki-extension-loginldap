<?php
// Fonctions de l'extension loginldap : contrôle de la configuration, connexion
// à l'annuaire, et repli sur le formulaire de connexion natif de YesWiki.

use YesWiki\Core\Service\Performer;

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

/** Les clés de configuration obligatoires qui manquent dans wakka.config.php. */
function loginLdapMissingConfig($wiki)
{
    $missing = [];
    foreach (['ldap_host', 'ldap_port'] as $key) {
        if (empty($wiki->config[$key])) {
            $missing[] = $key;
        }
    }

    return $missing;
}

/** L'URI de l'annuaire, que la configuration donne un hôte nu ou une URI complète. */
function loginLdapUri($wiki)
{
    $host = trim($wiki->config['ldap_host']);
    if (preg_match('#^ldaps?://#i', $host)) {
        return rtrim($host, '/');
    }

    return 'ldap://' . $host . ':' . (int) $wiki->config['ldap_port'];
}

/** Le lien vers l'annuaire, ou false s'il est injoignable. */
function loginLdapConnect($wiki)
{
    $ldap = @ldap_connect(loginLdapUri($wiki));
    if (!$ldap) {
        return false;
    }
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 5);

    return $ldap;
}

/** Le dernier échec vient-il de l'annuaire lui-même plutôt que des identifiants saisis ? */
function loginLdapIsServerFailure($ldap)
{
    $credentialErrors = [
        49,
        50,
    ];

    return !in_array(ldap_errno($ldap), $credentialErrors, true);
}

/** Le formulaire de connexion natif de YesWiki, rendu tel quel. */
function loginLdapRenderCoreLogin($wiki)
{
    $coreAction = 'tools/login/actions/LoginAction.php';
    if (!file_exists($coreAction)) {
        return '';
    }

    $savedParameters = $wiki->parameter ?? [];
    $vars = is_array($wiki->parameter ?? null) ? $wiki->parameter : [];
    $output = '';
    try {
        $performable = $wiki->services->get(Performer::class)->createPerformable([
            'filePath' => $coreAction,
            'baseName' => 'LoginAction',
            'isDefinedAsClass' => true,
        ], $vars, $output);
        $html = $performable->run();
    } catch (Throwable $exception) {
        $html = '';
    }
    $wiki->parameter = $savedParameters;

    return $html;
}

/** L'URL de la documentation embarquée de l'extension, dans la langue de l'usager. */
function loginLdapDocUrl($wiki)
{
    $lang = ($GLOBALS['prefered_language'] ?? 'fr') === 'en' ? 'en' : 'fr';

    return $wiki->config['base_url'] . 'doc/#tools/loginldap/' . $lang . '/README.md';
}
