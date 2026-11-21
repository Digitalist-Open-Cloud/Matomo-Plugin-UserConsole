<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\UserUpdater;

class RemoveSuper extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:remove-super');
        $this->setDescription('Removes super user privileges');
        $this->addRequiredValueOption('login', null, 'User login name');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();

        // On the command line there is no logged in user whose password could confirm this.
        // Matomo reads false as a value that was not given, so it is 0.
        (new UserUpdater())->setSuperUserAccessWithoutCurrentPassword($login, 0);

        return "User $login is no longer a super user.";
    }
}
