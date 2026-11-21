<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UserConsole\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\CoreAdminHome\Emails\TokenAuthDeletedEmail;
use Piwik\Plugins\UsersManager\Model;

class DeleteToken extends UserCommand
{
    protected function configure()
    {
        $this->setName('user:token-delete');
        $this->setDescription('Delete one API token of a user, or all of them');
        $this->addRequiredValueOption('login', null, 'User login name');
        $this->addRequiredValueOption('id', null, 'ID of the token, as shown by user:token-list');
        $this->addNoValueOption('all', null, 'Delete all API tokens of the user');
    }

    protected function executeForUsers(): string
    {
        $login = $this->getLoginOfExistingUser();
        $all = (bool) $this->getInput()->getOption('all');
        $model = new Model();

        if ($all) {
            $description = '';
            $model->deleteAllTokensForUser($login);
            $message = "All API tokens of user $login deleted";
        } else {
            $id = $this->getRequiredOption('id');
            $description = $model->getUserTokenDescriptionByIdTokenAuth($id, $login);
            if ($description === '') {
                throw new \InvalidArgumentException("User $login has no API token with the ID $id.");
            }
            $model->deleteToken($id, $login);
            $message = "API token $id of user $login deleted";
        }

        StaticContainer::getContainer()->make(TokenAuthDeletedEmail::class, [
            'login' => $login,
            'emailAddress' => $model->getUser($login)['email'],
            'tokenDescription' => $description,
            'all' => $all,
        ])->safeSend();

        return $message;
    }
}
