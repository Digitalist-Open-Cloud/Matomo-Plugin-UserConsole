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
 * Working with access for users.
 */
class SetAccess extends ConsoleCommand
{
    /**
     * Configure options.
     */
    protected function configure()
    {
        $this->setName('user:access');
        $this->setDescription('Manage user access');
        $this->addOption(
            'login',
            null,
            InputOption::VALUE_OPTIONAL,
            'User login name',
            null
        );
        $this->addOption(
            'access',
            null,
            InputOption::VALUE_REQUIRED,
            'Which access (options: noaccess, view, write, admin)',
            null
        );
        $this->addOption(
            'sites',
            null,
            InputOption::VALUE_REQUIRED,
            'ID(s) of sites, comma separated',
            null
        );
    }

    /**
     * Sets access for a single user.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $login = $input->getOption('login');
        $access = trim($input->getOption('access'));
        $sites = trim($input->getOption('sites'));
        $all_sites = explode(",", $sites);

        $api = APIUsersManager::getInstance();
        if ($api->userExists($login)) {
            $api->setUserAccess($login, $access, $all_sites);
        } else {
            $output->writeln("<error>User $login does not exist.</error>");
            exit;
        }
        $output->writeln("<info>Users $login access has been updated.</info>");
    }
}
