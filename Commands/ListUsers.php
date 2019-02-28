<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Piwik\Plugins\UsersManager\API;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class ListUsers extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('user:list');
        $this->setDescription('List users');
    }

    /**
     * List users.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list_of_users = API::getInstance()->getUsers();

        foreach ($list_of_users as $user) {
            $super = false;
            if ($user['superuser_access'] == 1) {
                $super = " [super user]";
            }
            $user_login = $user['login'];
            $user_email = $user['email'];

            $message= "Username: <comment>$user_login ($user_email)</comment>$super";
            $output->writeln("<info>$message</info>");
        }
    }
}
