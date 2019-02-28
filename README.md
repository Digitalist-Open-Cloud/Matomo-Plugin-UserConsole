# Matomo UserConsole Plugin

## Usage

This plugin adds the possibility to work with users via console commands.

## Console commands

* [user:access](#usercreate) 
* [user:create](#usercreate)
* [user:delete](#userdelete) 
* [user:list](#userlist)
* [user:make-super](#makesuper)
* [user:remove-super](#removesuper)

### <a name=usercreate>user:create</a>

Creates a user.

Options:

* `login` User name for the user (required)
* `email` Email for the user (required)
* `password` Password for the user (required)
* `super` Add super user privileges to the user (optional)

### <a name=userdelete>user:delete</a>

Deletes a user. 

Options: 

* `login` User name for the user (required)

 ### <a name=useraccess>user:access</a>

Menage access to a user, for which sites and what access.

Options:

* `login` User name for the user (required)
* `sites` One or more sites that you would like to set the permission for (required)
* `access` Which access the user should have to the site - `noaccess`, `view`, `write`, `admin` (required)

### <a name=makesuper>user:make-super</a>

Adds super user privileges to a user

Options:

* `login` User name for the user (required)

### <a name=removesuper>user:remove-super</a>

Remove super user privileges from a user

Options:

- `login` User name for the user (required)

If the user is the only one that has super user access, this will fail. At least one super user is needed.

### <a name=userlist>user:list</a>

List all users.

No options.

