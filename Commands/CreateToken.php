<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\CoreAdminHome\Emails\TokenAuthCreatedEmail;
use Piwik\Plugins\UsersManager\Model;

class CreateToken extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:token-create');
        $this->setDescription('Create an API token for a user. Prints the token, which cannot be shown again.');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('description', null, 'What the token is for');
        $this->addRequiredValueOption('expire-date', null, 'Last day the token works, as YYYY-MM-DD. Without it the token does not expire.');
        $this->addNoValueOption('secure-only', null, 'The token is only accepted in POST requests');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $description = $this->getRequiredOption('description');
        $expireDate = $this->getExpireDate();

        $model = new Model();
        $token = $model->generateRandomTokenAuth();
        $model->addTokenAuth(
            $login,
            $token,
            $description,
            Date::now()->getDatetime(),
            $expireDate,
            false,
            (bool) $this->getInput()->getOption('secure-only')
        );

        StaticContainer::getContainer()->make(TokenAuthCreatedEmail::class, [
            'login' => $login,
            'emailAddress' => $model->getUser($login)['email'],
            'tokenDescription' => $description,
        ])->safeSend();

        return $token;
    }

    /**
     * @throws \InvalidArgumentException if the date is not a day after today
     */
    private function getExpireDate(): ?string
    {
        $date = trim((string) $this->getInput()->getOption('expire-date'));
        if ($date === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || !Date::factory($date)->isLater(Date::today())) {
            throw new \InvalidArgumentException('The option --expire-date has to be a day after today, as YYYY-MM-DD.');
        }

        return $date;
    }
}
