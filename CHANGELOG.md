# Change log

## [Unreleased]

### Security

- `user:reset-password` changes the password the way Matomo does, instead of writing to the database itself. Matomo's password rules now apply, an empty password is refused, and the user is logged out everywhere. The query it used was also built from the login and the email without binding them.

### Fixed

- `user:remove-super` and `user:create --super` work without a logged in user to confirm them.
- `user:make-super` returns a failure code for a user that does not exist. It ended the whole process with a success code before.
- Every command fails with a message when a required option is missing or the user does not exist. `user:delete` reported success without a login, and `user:create` passed missing values on to Matomo.
- A change Matomo refuses is reported as a message and a failure code, without a stack trace.

### Added

- Tests, and workflows that run them, PHPCS and Semgrep.

## [5.0.4] - 2024-11-18

### Added

- user:invite by [mbrodala](https://github.com/mbrodala) (#5)

## [5.0.2] - 2024-08-30

### Added

- Cover for Marketplace
