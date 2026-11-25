<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Plugins\UsersManager\Model;
use Symfony\Component\Console\Formatter\OutputFormatter;

class ListTokens extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:token-list');
        $this->setDescription('List the API tokens of a user. The tokens themselves are not stored and cannot be shown.');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addFormatOption();
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();

        $tokens = (new Model())->getAllNonSystemTokensForLogin($login);

        if ($this->isDataWanted()) {
            return $this->toData(array_map(fn (array $token): array => [
                'id' => (int) $token['idusertokenauth'],
                'description' => $token['description'],
                'date_created' => $token['date_created'],
                'last_used' => $token['last_used'],
                'date_expired' => $token['date_expired'],
                'secure_only' => (bool) $token['secure_only'],
            ], $tokens));
        }

        $lines = [];
        foreach ($tokens as $token) {
            $lines[] = sprintf(
                'ID: <comment>%d</comment> %s (created %s, last used %s, expires %s)%s',
                $token['idusertokenauth'],
                OutputFormatter::escape($token['description']),
                $token['date_created'],
                $token['last_used'] ?: 'never',
                $token['date_expired'] ?: 'never',
                $token['secure_only'] ? ' [secure only]' : ''
            );
        }

        return $lines ? implode("\n", $lines) : "User $login has no API tokens";
    }
}
