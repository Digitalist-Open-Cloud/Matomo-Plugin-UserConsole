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
use Piwik\Plugins\UsersManager\API;
use Piwik\Db;
use Piwik\Common;

/**
 * Resetting password.
 */
class ResetPassword extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('user:reset-password');
        $this->setDescription('Reset password for an user');
        $this->addOptionalValueOption(
            'login',
            null,
            'User login name',
            null
        );
        $this->addOptionalValueOption(
            'new-password',
            null,
            'New password for the user',
            null
        );
    }

    /**
     * Execute the reset password command.
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $login = $input->getOption('login');
        $password = $input->getOption('new-password');

        if (isset($login) && isset($password)) {
            $api =  API::getInstance();
            if ($api->userExists($login)) {
                $get_user = $api->getUser($login);
                $table =  Common::prefixTable('user');
                $passwordhash = password_hash(md5("$password"), PASSWORD_DEFAULT);
                $login = $get_user['login'];
                $email = $get_user['email'];
                $sql = "UPDATE `$table` SET `password` =\"$passwordhash\" WHERE `login` = '$login' AND `email` = '$email'";
                $update = Db::query($sql);
                $output->writeln("<info>User $login new password is set</info>");
            }
        } else {
            $output->writeln("<info>You must provide both login and new password to this command.</info>");
        }
        return self::SUCCESS;
    }
}
