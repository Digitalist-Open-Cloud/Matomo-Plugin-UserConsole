# Matomo UserConsole Plugin

[![PHPCS check](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/phpcs.yaml/badge.svg)](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/phpcs.yaml)
[![Semgrep OSS scan](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/semgrep.yaml/badge.svg)](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/semgrep.yaml)
[![Test plugin with Matomo](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/matomo.yaml/badge.svg)](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/matomo.yaml)
[![PHPStan check](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/phpstan.yaml/badge.svg)](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-UserConsole/actions/workflows/phpstan.yaml)

Manage Matomo users from the command line: create, invite, delete and list users, set their
access and reset passwords.

## Install

Install it from the Matomo Marketplace, or put this repository in `plugins/UserConsole` and
activate it:

```sh
./console plugin:activate UserConsole
```

## Console commands

* [user:access](#useraccess)
* [user:create](#usercreate)
* [user:invite](#userinvite)
* [user:delete](#userdelete)
* [user:list](#userlist)
* [user:make-super](#makesuper)
* [user:remove-super](#removesuper)
* [user:reset-password](#resetpassword)
* [user:update](#userupdate)
* [user:logout](#userlogout)
* [user:show](#usershow)
* [user:invite-resend](#inviteresend)
* [user:capability](#usercapability)
* [user:token-create](#tokencreate)
* [user:token-list](#tokenlist)
* [user:token-delete](#tokendelete)

A command that cannot do what was asked prints the reason and ends with a failure code. That
is the case when a required option is missing, when the user does not exist, and when Matomo
refuses the change, for example a password that is too short.

A password given as an option can be seen by other users of the server in the process list,
and it is kept in the history of the shell.

### <a name="usercreate">user:create</a>

Creates a user.

Options:

* `login` User name for the user (required)
* `email` Email for the user (required)
* `password` Password for the user (required). It has to follow Matomo's password rules.
* `super` Add super user privileges to the user (optional)
* `update` If the user exists, give it the email, the password and the super user access that were asked for (optional)

If a user with the login or the email already exists, nothing is changed and the command
still ends successfully, so that a setup can be run again.

With `update` an existing user is changed to what was asked for instead. Without `super` that
takes super user access away. The password is only set when it differs, as a new password logs
the user out everywhere.

### <a name="userinvite">user:invite</a>

Invites a user.

Options:

* `login` User name for the user (required)
* `email` Email for the user (required)
* `site` ID of the initial site (required)
* `expiry` Expiry in days (optional)

If a user with the login or the email already exists, nothing is changed and the command
still ends successfully.

### <a name="userdelete">user:delete</a>

Deletes a user.

Options:

* `login` User name for the user (required)
* `force` Do not ask before deleting

Asks before deleting when run by a person at a terminal. A script is not asked.

### <a name="useraccess">user:access</a>

Manage the access of a user: for which sites and what access.

Options:

* `login` User name for the user (required)
* `sites` One or more site IDs, comma separated, that you would like to set the permission for, or `all` (required)
* `access` Which access the user should have to the site - `noaccess`, `view`, `write`, `admin` (required)

### <a name="makesuper">user:make-super</a>

Adds super user privileges to a user

Options:

* `login` User name for the user (required)

### <a name="removesuper">user:remove-super</a>

Remove super user privileges from a user

Options:

* `login` User name for the user (required)

If the user is the only one that has super user access, this will fail. At least one super user is needed.

### <a name="userlist">user:list</a>

List all users.

Options:

* `format` `text` (default) or `json`. JSON has for every user the login, email, whether it is a super user, the invitation (`active`, `pending` or `expired`), when it was registered and last seen, and the access per website.

### <a name="resetpassword">user:reset-password</a>

Resets password for a user. The user is logged out everywhere.

Options:

* `login` User name for the user (required)
* `new-password` New password for the user (required). It has to follow Matomo's password rules.

### <a name="userupdate">user:update</a>

Changes the email of a user.

Options:

* `login` User name for the user (required)
* `email` New email for the user (required)

### <a name="userlogout">user:logout</a>

Logs a user out everywhere. The password and the API tokens stay as they are, so delete the
tokens too with `user:token-delete --all` if they may have leaked.

Options:

* `login` User name for the user (required)

### <a name="usershow">user:show</a>

Shows one user: email, super user access, invitation, when it was registered and last seen,
and the access and capabilities per website.

Options:

* `login` User name for the user (required)
* `format` `text` (default) or `json`. JSON has the same fields as `user:list`.

### <a name="inviteresend">user:invite-resend</a>

Sends the invitation again to a user that has not accepted it. The link sent before stops
working. `user:list` marks the users that have not accepted.

Options:

* `login` User name for the user (required)
* `expiry` Expiry in days (optional, 7 by default)

### <a name="usercapability">user:capability</a>

Gives a user capabilities on websites, or takes them away. A capability is a right on top of
the access level, for example `tagmanager_write`. The user needs access to the website first.

    ./console user:capability --login=anna --capability=tagmanager_write --sites=1,2

Options:

* `login` User name for the user (required)
* `capability` ID(s) of capabilities, comma separated (required)
* `sites` ID(s) of sites, comma separated, or `all` (required)
* `remove` Take the capabilities away instead of giving them

### <a name="tokencreate">user:token-create</a>

Creates an API token for a user and prints it. Matomo does not store the token itself, so it cannot be shown again.

    TOKEN=$(./console user:token-create --login=importer --description="Log import")

Options:

* `login` User name for the user (required)
* `description` What the token is for (required)
* `expire-date` Last day the token works, as `YYYY-MM-DD`. Without it the token does not expire.
* `secure-only` The token is only accepted in POST requests

### <a name="tokenlist">user:token-list</a>

Lists the API tokens of a user: ID, description, when it was created, last used and when it expires.

Options:

* `login` User name for the user (required)
* `format` `text` (default) or `json`

### <a name="tokendelete">user:token-delete</a>

Deletes one API token of a user, or all of them.

Options:

* `login` User name for the user (required)
* `id` ID of the token, as shown by `user:token-list`
* `all` Delete all API tokens of the user

The user gets Matomo's security notification by email when a token is created or deleted.
