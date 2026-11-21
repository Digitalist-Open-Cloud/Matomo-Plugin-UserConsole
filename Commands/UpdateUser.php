<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\UserUpdater;

class UpdateUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:update');
        $this->setDescription('Change the email of a user');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('email', null, 'New email of the user');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $email = $this->getRequiredOption('email');

        // @phpstan-ignore-next-line Matomo documents the email as a boolean, but it is the new email
        (new UserUpdater())->updateUserWithoutCurrentPassword($login, false, $email);

        return "User $login has the email $email";
    }
}
