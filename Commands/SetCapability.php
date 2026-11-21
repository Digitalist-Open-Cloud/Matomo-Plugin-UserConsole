<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;

class SetCapability extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:capability');
        $this->setDescription('Give a user capabilities on websites, or take them away');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('capability', null, 'ID(s) of capabilities, comma separated, for example tagmanager_write');
        $this->addRequiredValueOption('sites', null, 'ID(s) of sites, comma separated, or "all"');
        $this->addNoValueOption('remove', null, 'Take the capabilities away');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $capabilities = array_values(array_filter(array_map('trim', explode(',', $this->getRequiredOption('capability')))));
        $sites = $this->getSites();

        $api = APIUsersManager::getInstance();
        $known = array_column($api->getAvailableCapabilities(), 'id');
        $unknown = array_diff($capabilities, $known);
        if ($unknown) {
            throw new \InvalidArgumentException('Unknown capability: ' . implode(', ', $unknown) . '. Known are: ' . implode(', ', $known) . '.');
        }

        if ($this->getInput()->getOption('remove')) {
            $api->removeCapabilities($login, $capabilities, $sites);
        } else {
            $api->addCapabilities($login, $capabilities, $sites);
        }

        return "Users $login capabilities have been updated.";
    }
}
