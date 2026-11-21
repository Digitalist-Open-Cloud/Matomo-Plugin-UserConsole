# Matomo UserConsole Plugin

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

If a user with the login or the email already exists, nothing is changed and the command
still ends successfully, so that a setup can be run again.

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

Deletes a user, without asking.

Options:

* `login` User name for the user (required)

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

No options.

### <a name="resetpassword">user:reset-password</a>

Resets password for a user. The user is logged out everywhere.

Options:

* `login` User name for the user (required)
* `new-password` New password for the user (required). It has to follow Matomo's password rules.
