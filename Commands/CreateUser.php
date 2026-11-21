<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\Plugins\UsersManager\UserUpdater;

class CreateUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:create');
        $this->setDescription('Create user');
        $this->addNoValueOption('super', null, 'Create the user as a super user');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('email', null, 'User email');
        $this->addRequiredValueOption('password', null, 'User password');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getRequiredOption('login');
        $email = $this->getRequiredOption('email');
        $password = $this->getRequiredOption('password');

        $api = APIUsersManager::getInstance();
        if ($api->userExists($login) || $api->userEmailExists($email)) {
            // Not a failure, so that a setup can be run again.
            return "User with login name $login or/and email $email already exists. Nothing was changed.";
        }

        $api->addUser($login, $password, $email);
        if ($this->getInput()->getOption('super')) {
            (new UserUpdater())->setSuperUserAccessWithoutCurrentPassword($login, 1);
        }

        return "User $login created";
    }
}
