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
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class MakeSuper extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {
        $this->setName('user:make-super');
        $this->setDescription('Make user a super user');
        $this->addOptionalValueOption(
            'login',
            null,
            'User login name',
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

        $api = APIUsersManager::getInstance();
        if ($api->userExists($login)) {
                $api->setSuperUserAccess($login, true);
        } else {
            $output->writeln("<error>User $login does not exist.</error>");
            exit;
        }
        $output->writeln("<info>User $login is now a super user.</info>");
        return self::SUCCESS;
    }
}
