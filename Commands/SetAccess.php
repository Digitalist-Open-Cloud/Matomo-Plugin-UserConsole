<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;

class SetAccess extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:access');
        $this->setDescription('Manage user access');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('access', null, 'Which access (options: noaccess, view, write, admin)');
        $this->addRequiredValueOption('sites', null, 'ID(s) of sites, comma separated, or "all"');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $access = $this->getRequiredOption('access');
        $sites = $this->getSites();

        APIUsersManager::getInstance()->setUserAccess($login, $access, $sites);

        return "Users $login access has been updated.";
    }
}
