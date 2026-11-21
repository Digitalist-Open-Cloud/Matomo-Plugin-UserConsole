<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;

class InviteUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:invite');
        $this->setDescription('Invite user');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('email', null, 'User email');
        $this->addRequiredValueOption('site', null, 'ID of the initial site');
        $this->addOptionalValueOption('expiry', null, 'Expiry in days');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getRequiredOption('login');
        $email = $this->getRequiredOption('email');
        $site = (int) $this->getRequiredOption('site');
        $expiry = $this->getInput()->getOption('expiry');

        $api = APIUsersManager::getInstance();
        if ($api->userExists($login) || $api->userEmailExists($email)) {
            // Not a failure, so that a setup can be run again.
            return "User with login name $login or/and email $email already exists. Nothing was changed.";
        }

        $api->inviteUser($login, $email, $site, $expiry);

        return "User $login invited";
    }
}
