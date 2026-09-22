# loginldap extension

This extension replaces the YesWiki login form by an authentication against an LDAP
directory.

## Enabling the extension

The extension installs safely. As long as `ldap_host` and `ldap_port` are not set, it
stays inactive: a message reports the missing configuration and the usual YesWiki login
form keeps working. The same happens when the PHP `ldap` extension is missing from the
server, or when the directory does not answer.

A login or password rejected by the directory does not trigger that fallback: the LDAP
form simply shows an error, as expected.

## Configuration

In `wakka.config.php`.

| Key | Required | Purpose |
|---|---|---|
| `ldap_host` | yes | directory host, or a full `ldap://…` / `ldaps://…` URI |
| `ldap_port` | yes | directory port, ignored when `ldap_host` is a URI |
| `ldap_base` | no | base DN, for instance `ou=users,dc=example,dc=org` |
| `ldap_organisation` | no | value of `o=` in the DN, when `ldap_base` is not used |
| `ldap_group` | no | value of `ou=` in the DN, when `ldap_base` is not used |

With a base DN:

```php
'ldap_host' => '127.0.0.1',
'ldap_port' => '389',
'ldap_base' => 'ou=users,dc=yunohost,dc=org',
```

Or by building the DN from the organisation and the group:

```php
'ldap_host' => 'my-ldap-domain.com',
'ldap_port' => '389',
'ldap_organisation' => 'my-org',
'ldap_group' => 'my-group',
```

The DN used for the bind is `uid=<login>` followed by `ldap_base`, or otherwise by
`ou=<ldap_group>,o=<ldap_organisation>`.

To encrypt the connection, give `ldap_host` a full URI:

```php
'ldap_host' => 'ldaps://my-ldap-domain.com:636',
'ldap_port' => '636',
```

## How the login works

On the first successful login, the extension creates the account in the wiki `users`
table from the `uid`, `cn` and `mail` attributes of the directory. The account name is
the `uid` when it is not numeric, otherwise a wiki name derived from the `cn`.

The directory stays the source of truth: on every login it is the directory, not the
wiki, that validates the password.

## Usage

The extension replaces the `{{login}}` action. Nothing to change in your pages.

If `logincas`, `loginsso` or another authentication extension is installed at the same
time, only one wins: YesWiki loads extensions in alphabetical order and the last one
loaded takes over. Install only one of them.

## Troubleshooting

| Message | Cause | What to do |
|---|---|---|
| The loginldap extension is not configured | `ldap_host` or `ldap_port` missing | set the keys named in the message |
| The PHP ldap extension is not installed | the `ldap` module is missing from PHP | install `php-ldap` and restart PHP |
| The LDAP directory does not answer or is misconfigured | wrong host, port or base DN, unreachable server | the message gives the exact LDAP error in brackets |
| Wrong login or password | the directory rejected the credentials | check the account in the directory |

In the first three cases the usual login form is still displayed right below the
message, so local wiki accounts remain usable. The fourth keeps the user on the LDAP
form.

An empty password is rejected before the directory is even queried: many LDAP servers
accept an anonymous bind in that case, which would open a session without a password.
