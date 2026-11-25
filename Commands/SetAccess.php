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
        $this->addRequiredValueOption('access', null, 'Which access: noaccess, view, write or admin. Capabilities can follow, comma separated, for example write,tagmanager_publish_live_container');
        $this->addRequiredValueOption('sites', null, 'ID(s) of sites, comma separated, or "all"');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $access = array_values(array_filter(array_map('trim', explode(',', $this->getRequiredOption('access')))));
        $sites = $this->getSites();

        $api = APIUsersManager::getInstance();
        $capabilities = array_intersect($access, array_column($api->getAvailableCapabilities(), 'id'));
        if ($capabilities && count($capabilities) === count($access)) {
            throw new \InvalidArgumentException(
                implode(', ', $capabilities) . ' is a capability. Give it after an access level, for example --access=write,'
                . reset($capabilities) . ', or add it to the access the user has with user:capability.'
            );
        }

        // Matomo takes one access level as text, and a level with capabilities as a list.
        $api->setUserAccess($login, count($access) === 1 ? $access[0] : $access, $sites);

        return "Users $login access has been updated.";
    }
}
