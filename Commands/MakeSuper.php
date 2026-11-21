<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\UserUpdater;

class MakeSuper extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:make-super');
        $this->setDescription('Make user a super user');
        $this->addRequiredValueOption('login', null, 'User login name');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();

        // On the command line there is no logged in user whose password could confirm this.
        (new UserUpdater())->setSuperUserAccessWithoutCurrentPassword($login, 1);

        return "User $login is now a super user.";
    }
}
