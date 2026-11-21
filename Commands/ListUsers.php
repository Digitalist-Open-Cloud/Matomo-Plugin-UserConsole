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
        $this->addFormatOption();
    }

    protected function executeForUsers(): string
    {
        $users = APIUsersManager::getInstance()->getUsers();

        if ($this->isJsonWanted()) {
            return $this->toJson(array_map([$this, 'describeUser'], $users));
        }

        $lines = [];
        foreach ($users as $user) {
            $super = $user['superuser_access'] == 1 ? ' [super user]' : '';
            $invite = $this->describeUser($user)['invite'];
            $invite = $invite === 'active' ? '' : " [invitation $invite]";
            $lines[] = "Username: <comment>{$user['login']} ({$user['email']})</comment>$super$invite";
        }

        return implode("\n", $lines);
    }
}
