<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

class UpdateUser extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:update');
        $this->setDescription('Change the email, the password or the super user access of a user');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('email', null, 'New email of the user');
        $this->addRequiredValueOption('password', null, 'New password of the user');
        $this->addRequiredValueOption('super', null, 'true to give super user access, false to take it away');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $input = $this->getInput();
        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $super = $input->getOption('super');

        if ($email === null && $password === null && $super === null) {
            throw new \InvalidArgumentException('Give at least one of the options --email, --password and --super.');
        }
        if ($email === '' || $password === '') {
            throw new \InvalidArgumentException('The email and the password can not be empty.');
        }
        if ($super !== null && !in_array($super, ['true', 'false'], true)) {
            throw new \InvalidArgumentException('The option --super has to be true or false.');
        }

        return $this->updateUser($login, $email, $password, $super === null ? null : $super === 'true');
    }
}
