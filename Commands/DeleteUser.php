<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\API as APIUsersManager;

class DeleteUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:delete');
        $this->setDescription('Delete user');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addNoValueOption('force', null, 'Do not ask before deleting');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();

        // A script has nobody to answer, so only a person at a terminal is asked.
        $ask = $this->getInput()->isInteractive() && !$this->getInput()->getOption('force');
        if ($ask && !$this->askForConfirmation("Delete the user $login? (y/N) ", false)) {
            return "User $login was not deleted";
        }

        APIUsersManager::getInstance()->deleteUser($login);

        return "User $login deleted";
    }
}
