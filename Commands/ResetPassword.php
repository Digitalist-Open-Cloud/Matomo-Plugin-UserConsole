<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\UserUpdater;

class ResetPassword extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:reset-password');
        $this->setDescription('Reset password for an user');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('new-password', null, 'New password for the user');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $password = $this->getRequiredOption('new-password');

        // Matomo's own way to change a password: it checks the password rules and logs the user
        // out everywhere. On the command line there is no logged in user to confirm it.
        // @phpstan-ignore-next-line Matomo documents the password as a boolean, but it is the new password
        (new UserUpdater())->updateUserWithoutCurrentPassword($login, $password);

        return "User $login new password is set";
    }
}
