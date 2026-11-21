<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\Plugins\UsersManager\Model;

class ResendInvite extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:invite-resend');
        $this->setDescription('Send the invitation of a user again. The link sent before stops working.');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('expiry', null, 'Expiry in days', 7);
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        if (empty((new Model())->getUser($login)['invite_token'])) {
            throw new \InvalidArgumentException("User $login has no invitation that is waiting to be accepted.");
        }

        APIUsersManager::getInstance()->resendInvite($login, (int) $this->getRequiredOption('expiry'));

        return "User $login invited again";
    }
}
