<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;

class ShowUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:show');
        $this->setDescription('Show a user and the access it has');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addFormatOption();
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $user = $this->describeUser(APIUsersManager::getInstance()->getUsers($login)[0]);

        if ($this->isJsonWanted()) {
            return $this->toJson($user);
        }

        $accessPerSite = [];
        foreach ($user['access'] as $access) {
            $accessPerSite[$access['site']][] = $access['access'];
        }

        $lines = [
            "Login: <comment>{$user['login']}</comment>",
            "Email: {$user['email']}",
            'Super user: ' . ($user['superuser'] ? 'yes' : 'no'),
            "Invitation: {$user['invite']}",
            "Registered: {$user['date_registered']}",
            'Last seen: ' . ($user['last_seen'] ?? 'never'),
        ];
        foreach ($accessPerSite as $idSite => $access) {
            $lines[] = "Website $idSite: " . implode(', ', $access);
        }

        return implode("\n", $lines);
    }
}
