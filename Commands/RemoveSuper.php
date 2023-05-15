<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\UsersManager\API as APIUsersManager;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class RemoveSuper extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {
        $this->setName('user:remove-super');
        $this->setDescription('Removes super user privileges');
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
            $api->setSuperUserAccess($login, false);
        } else {
            $output->writeln("<error>User $login does not exist.</error>");
            return self::FAILURE;
        }
        $output->writeln("<info>User $login is no longer a super user.</info>");
        return self::SUCCESS;
    }
}
