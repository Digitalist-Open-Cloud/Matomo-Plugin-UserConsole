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
 * A command that reads or changes users. It ends with a message and a failure code, without a
 * stack trace, when Matomo refuses what was asked.
 */
abstract class UserCommand extends ConsoleCommand
{
    /**
     * @return string what was done, to tell the user
     * @throws \Exception if it could not be done
     */
    abstract protected function executeForUsers(): string;

    protected function doExecute(): int
    {
        try {
            $message = $this->executeForUsers();
        } catch (\Exception $e) {
            $this->getOutput()->writeln('<error>' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }

        $this->getOutput()->writeln("<info>$message</info>");

        return self::SUCCESS;
    }

    /**
     * @throws \InvalidArgumentException if the option was not given
     */
    protected function getRequiredOption(string $name): string
    {
        $value = trim((string) $this->getInput()->getOption($name));
        if ($value === '') {
            throw new \InvalidArgumentException("The option --$name is required.");
        }

        return $value;
    }

    /**
     * @return string the login of an existing user, from the option --login
     * @throws \InvalidArgumentException if there is no such user
     */
    protected function getLoginOfExistingUser(): string
    {
        $login = $this->getRequiredOption('login');
        if (!APIUsersManager::getInstance()->userExists($login)) {
            throw new \InvalidArgumentException("User $login does not exist.");
        }

        return $login;
    }
}
