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
use Piwik\Plugins\UsersManager\UserUpdater;
use Piwik\Plugins\UsersManager\UsersManager;

class CreateUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:create');
        $this->setDescription('Create user');
        $this->addNoValueOption('super', null, 'Create the user as a super user');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('email', null, 'User email');
        $this->addRequiredValueOption('password', null, 'User password');
        $this->addNoValueOption('update', null, 'If the user exists, give it the email, the password and the super user access that were asked for');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getRequiredOption('login');
        $email = $this->getRequiredOption('email');
        $password = $this->getRequiredOption('password');

        $api = APIUsersManager::getInstance();
        if ($this->getInput()->getOption('update') && $api->userExists($login)) {
            return $this->update($login, $email, $password);
        }

        if ($api->userExists($login) || $api->userEmailExists($email)) {
            // Not a failure, so that a setup can be run again.
            return "User with login name $login or/and email $email already exists. Nothing was changed.";
        }

        $api->addUser($login, $password, $email);
        if ($this->getInput()->getOption('super')) {
            (new UserUpdater())->setSuperUserAccessWithoutCurrentPassword($login, 1);
        }

        return "User $login created";
    }

    private function update(
        string $login,
        string $email,
        #[\SensitiveParameter]
        string $password
    ): string {
        $user = (new Model())->getUser($login);
        $super = (bool) $this->getInput()->getOption('super');
        $updater = new UserUpdater();
        $changed = [];

        if (mb_strtolower($email) !== mb_strtolower($user['email'])) {
            // @phpstan-ignore-next-line Matomo documents the email as a boolean, but it is the new email
            $updater->updateUserWithoutCurrentPassword($login, false, $email);
            $changed[] = 'email';
        }

        // Only when it differs, as a new password logs the user out everywhere.
        if (!password_verify(UsersManager::getPasswordHash($password), $user['password'])) {
            // @phpstan-ignore-next-line Matomo documents the password as a boolean, but it is the new password
            $updater->updateUserWithoutCurrentPassword($login, $password);
            $changed[] = 'password';
        }

        if ($super !== ($user['superuser_access'] == 1)) {
            $updater->setSuperUserAccessWithoutCurrentPassword($login, (int) $super);
            $changed[] = 'super user access';
        }

        return $changed ? "User $login updated: " . implode(', ', $changed) : "User $login is already as asked for. Nothing was changed.";
    }
}
