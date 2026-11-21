<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;

class ListUsers extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:list');
        $this->setDescription('List users');
    }

    protected function executeForUsers(): string
    {
        $lines = [];
        foreach (APIUsersManager::getInstance()->getUsers() as $user) {
            $super = $user['superuser_access'] == 1 ? ' [super user]' : '';
            $lines[] = "Username: <comment>{$user['login']} ({$user['email']})</comment>$super";
        }

        return implode("\n", $lines);
    }
}
