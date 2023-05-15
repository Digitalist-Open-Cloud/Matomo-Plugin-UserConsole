<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputOption;
use Piwik\Plugins\UsersManager\API as APIUsersManager;

/**
 * Create a user.
 */
class CreateUser extends ConsoleCommand
{
    /**
     * Options for user creation.
     */
    protected function configure()
    {
        $this->setName('user:create');
        $this->setDescription('Create user');
        $this->addNoValueOption(
            'super',
            null,
            'Create the user as a super user'
        );
        $this->addOptionalValueOption(
            'login',
            null,
            'User login name',
            null
        );
        $this->addOptionalValueOption(
            'email',
            null,
            'User email',
            null
        );
        $this->addOptionalValueOption(
            'password',
            null,
            'User password',
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
        $super = $input->getOption('super') ? true : false;
        $login = $input->getOption('login');
        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $api = APIUsersManager::getInstance();

        if (!$api->userExists($login) && !$api->userEmailExists($email)) {
            $api->addUser(
                $login,
                $password,
                $email
            );
            if ($super === true) {
                $api->setSuperUserAccess($login, true);
            }
        } else {
            $output->writeln("<error>User with login name $login or/and email $email already exists.</error>");
            return self::SUCCESS;
        }
        $output->writeln("<info>User $login created</info>");
        return self::SUCCESS;
    }
}
