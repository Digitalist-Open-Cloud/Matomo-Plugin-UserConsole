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
use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\UsersManager\UsersManager;
use Piwik\Plugins\UsersManager\UserUpdater;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Yaml\Yaml;

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

    protected function addFormatOption(): void
    {
        $this->addRequiredValueOption('format', null, 'Output format: text, json or yaml', 'text');
    }

    /**
     * @return bool whether the option --format asks for data instead of text
     * @throws \InvalidArgumentException if the option --format is not a known format
     */
    protected function isDataWanted(): bool
    {
        $format = $this->getInput()->getOption('format');
        if (!in_array($format, ['text', 'json', 'yaml'], true)) {
            throw new \InvalidArgumentException('The option --format has to be text, json or yaml.');
        }

        return $format !== 'text';
    }

    /**
     * @param array<mixed> $data
     * @return string the data in the format of the option --format
     */
    protected function toData(array $data): string
    {
        $text = $this->getInput()->getOption('format') === 'yaml'
            ? rtrim(Yaml::dump($data, 4, 2))
            : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        // Escaped, or the console would read "<...>" in a value as a style.
        return OutputFormatter::escape($text);
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

    /**
     * @return int[]|string the websites of the option --sites, or "all"
     */
    protected function getSites()
    {
        $sites = $this->getRequiredOption('sites');
        if ($sites === 'all') {
            return $sites;
        }

        return array_values(array_filter(array_map('intval', explode(',', $sites))));
    }

    /**
     * Changes what differs from the values given. A value that is null is left as it is.
     *
     * @return string what was changed, to tell the user
     */
    protected function updateUser(
        string $login,
        ?string $email,
        #[\SensitiveParameter]
        ?string $password,
        ?bool $super
    ): string {
        $user = (new Model())->getUser($login);
        $updater = new UserUpdater();
        $changed = [];

        if ($email !== null && mb_strtolower($email) !== mb_strtolower($user['email'])) {
            // @phpstan-ignore-next-line Matomo documents the email as a boolean, but it is the new email
            $updater->updateUserWithoutCurrentPassword($login, false, $email);
            $changed[] = 'email';
        }

        // Only when it differs, as a new password logs the user out everywhere.
        if ($password !== null && !password_verify(UsersManager::getPasswordHash($password), $user['password'])) {
            // @phpstan-ignore-next-line Matomo documents the password as a boolean, but it is the new password
            $updater->updateUserWithoutCurrentPassword($login, $password);
            $changed[] = 'password';
        }

        if ($super !== null && $super !== ($user['superuser_access'] == 1)) {
            $updater->setSuperUserAccessWithoutCurrentPassword($login, (int) $super);
            $changed[] = 'super user access';
        }

        return $changed ? "User $login updated: " . implode(', ', $changed) : "User $login is already as asked for. Nothing was changed.";
    }

    /**
     * @param array<string, mixed> $user as Matomo's API returns it
     * @return array<string, mixed>
     */
    protected function describeUser(array $user): array
    {
        // Matomo gives the days left of an invitation that is still open.
        $invite = in_array($user['invite_status'], ['active', 'expired'], true) ? $user['invite_status'] : 'pending';

        return [
            'login' => $user['login'],
            'email' => $user['email'],
            'superuser' => $user['superuser_access'] == 1,
            'invite' => $invite,
            'date_registered' => $user['date_registered'],
            'last_seen' => $user['last_seen'] ?? null,
            'access' => (new Model())->getSitesAccessFromUser($user['login']),
        ];
    }
}
