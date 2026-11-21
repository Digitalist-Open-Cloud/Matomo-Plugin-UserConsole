<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Date;
use Piwik\Plugins\UsersManager\Model;

class LogoutUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:logout');
        $this->setDescription('Log a user out everywhere. The password and the API tokens stay as they are.');
        $this->addRequiredValueOption('login', null, 'User login name');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();

        // Matomo ends every session that started before the password was last changed.
        (new Model())->updateUserFields($login, ['ts_password_modified' => Date::now()->getDatetime()]);

        return "User $login is logged out everywhere";
    }
}
