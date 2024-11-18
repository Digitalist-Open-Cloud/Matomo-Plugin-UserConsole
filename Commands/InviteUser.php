<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\UsersManager\API as APIUsersManager;

/**
 * Invite a user.
 */
class InviteUser extends ConsoleCommand
{
    /**
     * Options for user creation.
     */
    protected function configure()
    {
        $this->setName('user:invite');
        $this->setDescription('Invite user');
        $this->addRequiredValueOption(
            'login',
            null,
            'User login name',
            null
        );
        $this->addRequiredValueOption(
            'email',
            null,
            'User email',
            null
        );
        $this->addRequiredValueOption(
            'site',
            null,
            'ID of the initial site',
            null
        );
        $this->addOptionalValueOption(
            'expiry',
            null,
            'Expiry in days',
            null
        );
    }

    /**
     * Create an user, with option to create super user.
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $login = $input->getOption('login');
        $email = $input->getOption('email');
        $site = $input->getOption('site');
        $expiry = $input->getOption('expiry');
        $api = APIUsersManager::getInstance();

        if ($api->userExists($login) || $api->userEmailExists($email)) {
            $output->writeln("<error>User with login name $login or/and email $email already exists.</error>");

            return self::SUCCESS;
        }

        $api->inviteUser($login, $email, $site, $expiry);

        $output->writeln("<info>User $login invited</info>");

        return self::SUCCESS;
    }
}
