# Extension loginldap

Cette extension remplace le formulaire de connexion de YesWiki par une
authentification sur un annuaire LDAP.

## Activer l'extension

L'extension s'installe sans rien casser. Tant que `ldap_host` et `ldap_port` ne sont
pas renseignés, elle reste inactive : un message signale la configuration manquante et
le formulaire de connexion habituel de YesWiki continue de fonctionner. Même chose si
l'extension PHP `ldap` est absente du serveur, ou si l'annuaire ne répond pas.

Un identifiant ou un mot de passe refusé par l'annuaire n'active pas ce repli : dans
ce cas le formulaire LDAP réaffiche une erreur, comme attendu.

## Configuration

Dans `wakka.config.php`.

| Clé | Obligatoire | Rôle |
|---|---|---|
| `ldap_host` | oui | hôte de l'annuaire, ou URI complète `ldap://…` / `ldaps://…` |
| `ldap_port` | oui | port de l'annuaire, ignoré si `ldap_host` est une URI |
| `ldap_base` | non | DN de base, par exemple `ou=users,dc=example,dc=org` |
| `ldap_organisation` | non | valeur de `o=` dans le DN, si `ldap_base` n'est pas utilisé |
| `ldap_group` | non | valeur de `ou=` dans le DN, si `ldap_base` n'est pas utilisé |

Avec un DN de base :

```php
'ldap_host' => '127.0.0.1',
'ldap_port' => '389',
'ldap_base' => 'ou=users,dc=yunohost,dc=org',
```

Ou en composant le DN à partir de l'organisation et du groupe :

```php
'ldap_host' => 'mon-domaine-ldap.com',
'ldap_port' => '389',
'ldap_organisation' => 'mon-org',
'ldap_group' => 'mon-groupe',
```

Le DN utilisé pour le bind est `uid=<identifiant>` suivi de `ldap_base`, ou à défaut
de `ou=<ldap_group>,o=<ldap_organisation>`.

Pour chiffrer la liaison, donner une URI complète à `ldap_host` :

```php
'ldap_host' => 'ldaps://mon-domaine-ldap.com:636',
'ldap_port' => '636',
```

## Comment se fait la connexion

À la première connexion réussie, l'extension crée le compte dans la table `users` du
wiki à partir des attributs `uid`, `cn` et `mail` de l'annuaire. Le nom du compte est
l'`uid` s'il n'est pas numérique, sinon un nom wiki dérivé du `cn`.

L'annuaire reste la source de vérité : à chaque connexion, c'est lui qui valide le mot
de passe, pas le wiki.

## Utilisation

L'extension remplace l'action `{{login}}`. Rien à changer dans vos pages.

Si `logincas`, `loginsso` ou une autre extension d'authentification est installée en
même temps, une seule l'emporte : YesWiki charge les extensions par ordre alphabétique
et la dernière chargée gagne. N'en installez qu'une.

## En cas de problème

| Message | Cause | Que faire |
|---|---|---|
| L'extension loginldap n'est pas configurée | `ldap_host` ou `ldap_port` absent | renseigner les clés que le message nomme |
| L'extension PHP ldap n'est pas installée | le module `ldap` manque à PHP | installer `php-ldap` puis redémarrer PHP |
| L'annuaire LDAP ne répond pas ou est mal configuré | hôte, port ou DN de base erroné, serveur injoignable | le message donne l'erreur LDAP exacte entre parenthèses |
| Identifiant ou mot de passe incorrect | l'annuaire a refusé les identifiants saisis | vérifier le compte dans l'annuaire |

Dans les trois premiers cas, le formulaire de connexion habituel reste affiché juste
en dessous du message, donc les comptes locaux du wiki restent utilisables. Le
quatrième garde l'usager sur le formulaire LDAP.

Un mot de passe vide est refusé avant même d'interroger l'annuaire : beaucoup de
serveurs LDAP acceptent une liaison anonyme dans ce cas, ce qui ouvrirait une session
sans mot de passe.
