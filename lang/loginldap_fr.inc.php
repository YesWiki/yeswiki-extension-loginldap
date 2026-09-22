<?php
/*vim: set expandtab tabstop=4 shiftwidth=4: */
// +------------------------------------------------------------------------------------------------------+
// | PHP version 5                                                                                        |
// +------------------------------------------------------------------------------------------------------+
// | Copyright (C) 2012 Outils-Réseaux (accueil@outils-reseaux.org)                                       |
// +------------------------------------------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or                                        |
// | modify it under the terms of the GNU Lesser General Public                                           |
// | License as published by the Free Software Foundation; either                                         |
// | version 2.1 of the License, or (at your option) any later version.                                   |
// |                                                                                                      |
// | This library is distributed in the hope that it will be useful,                                      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of                                       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU                                    |
// | Lesser General Public License for more details.                                                      |
// |                                                                                                      |
// | You should have received a copy of the GNU Lesser General Public                                     |
// | License along with this library; if not, write to the Free Software                                  |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA                            |
// +------------------------------------------------------------------------------------------------------+
//
/**
* Fichier de traduction en francais de l'extension Login
*
*@package       login
*@author        Florian Schmitt <florian@outils-reseaux.org>
*@copyright     2012 Outils-Réseaux
*/

$GLOBALS['translations'] = array_merge(
    $GLOBALS['translations'],
    array(
        'LDAP_USERNAME' => 'Utilisateur LDAP',
        'LDAP_MESSAGE_INFO' => 'L\'identification est réservée aux personnes inscrites dans l\'annuaire LDAP.',
        'LDAP_CONFIG_MISSING' => 'L\'extension loginldap n\'est pas configurée. Il manque dans wakka.config.php :',
        'LDAP_PHP_EXTENSION_MISSING' => 'L\'extension PHP ldap n\'est pas installée sur ce serveur.',
        'LDAP_FALLBACK_NOTICE' => 'La connexion par l\'annuaire est désactivée, le formulaire habituel reste utilisable.',
        'LDAP_SERVER_UNREACHABLE' => 'L\'annuaire LDAP ne répond pas ou est mal configuré.',
        'LDAP_INVALID_CREDENTIALS' => 'Identifiant ou mot de passe incorrect.',
        'LDAP_READ_DOC' => 'Lire la documentation de l\'extension',
        'LDAP_COOKIES_REQUIRED' => 'Vous devez accepter les cookies pour pouvoir vous connecter.',
    )
);
