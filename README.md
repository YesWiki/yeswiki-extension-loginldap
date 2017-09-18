# yeswiki-extension-ldap
Permet d'utiliser LDAP pour se connecter

## Installation
 - Mettre le contenu de ce depot dans le repertoire tools/loginldap
 - Editer le fichier de configuration `wakka.config.php` et ajouter les valeurs suivantes:
  ```
  'ldap_host' => 'mon-domaine-ldap.com',
  'ldap_port' => '389',
  'ldap_organisation' => 'mon-org', // non obligatoire
  'ldap_group' => 'mon-groupe', // non obligatoire
  ```
