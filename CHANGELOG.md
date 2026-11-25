# Change log

## [5.0.9] - 2026-11-25

### Added

- `user:update` takes `--password` and `--super=true|false` next to `--email`, and changes only what is given.
- `--format=yaml` for `user:list`, `user:show` and `user:token-list`.

### Fixed

- `user:access` takes capabilities after the access level, as in `--access=write,tagmanager_publish_live_container`. A capability given alone is refused with a message that tells how to give it.

## [5.0.5] - 2026-11-21

### Security

- `user:reset-password` changes the password the way Matomo does, instead of writing to the database itself. Matomo's password rules now apply, an empty password is refused, and the user is logged out everywhere. The query it used was also built from the login and the email without binding them.

### Fixed

- `user:remove-super` and `user:create --super` work without a logged in user to confirm them.
- `user:make-super` returns a failure code for a user that does not exist. It ended the whole process with a success code before.
- Every command fails with a message when a required option is missing or the user does not exist. `user:delete` reported success without a login, and `user:create` passed missing values on to Matomo.
- A change Matomo refuses is reported as a message and a failure code, without a stack trace.

### Added

- `user:token-create`, `user:token-list` and `user:token-delete` to manage the API tokens of a user.
- `user:list --format=json`, with the access per website, super user access, invitation and last seen. `user:token-list` has the same option.
- `user:update` to change the email of a user, and `user:create --update` to bring an existing user in line with what was asked for.
- `user:logout` to log a user out everywhere.
- `user:show` to see one user with its access.
- `user:invite-resend` to send an invitation again. `user:list` marks users that have not accepted their invitation.
- `user:capability` to give or take away capabilities on websites.

### Changed

- `user:delete` asks before deleting when run at a terminal. `--force` skips the question, and scripts are not asked.
- Tests, and workflows that run them, PHPCS and Semgrep.

## [5.0.4] - 2024-11-18

### Added

- user:invite by [mbrodala](https://github.com/mbrodala) (#5)

## [5.0.2] - 2024-08-30

### Added

- Cover for Marketplace
