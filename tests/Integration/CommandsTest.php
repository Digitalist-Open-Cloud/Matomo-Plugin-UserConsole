<?php

namespace Piwik\Plugins\UserConsole\tests\Integration;

use Piwik\Access;
use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\UsersManager\UsersManager;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group UserConsole
 * @group Plugins
 */
class CommandsTest extends ConsoleCommandTestCase
{
    private const PASSWORD = 'a-long-secret-1';

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);

        $fixture->createSuperUser = true;
        $fixture->extraPluginsToLoad = ['UserConsole'];
    }

    public function setUp(): void
    {
        parent::setUp();

        Access::doAsSuperUser(function () {
            if (!Fixture::siteCreated(2)) {
                Fixture::createWebsite('2024-01-01 00:00:00');
                Fixture::createWebsite('2024-01-01 00:00:00');
            }
        });

        // The database is not reset between the tests of a console command.
        foreach (['user', 'access'] as $table) {
            Db::query('DELETE FROM ' . Common::prefixTable($table) . ' WHERE login NOT IN (?, ?)', ['anonymous', Fixture::ADMIN_USER_LOGIN]);
        }
    }

    public function testCreateAddsAUserThatCanLogIn()
    {
        $this->assertSame(0, $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'anna@example.org', '--password' => self::PASSWORD]));

        $this->assertStringContainsString('User anna created', $this->getDisplay());
        $user = $this->getUser('anna');
        $this->assertSame('anna@example.org', $user['email']);
        $this->assertSame('0', (string) $user['superuser_access']);
        $this->assertTrue($this->isPassword('anna', self::PASSWORD));
    }

    public function testCreateCanMakeTheUserASuperUser()
    {
        $this->assertSame(0, $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'anna@example.org', '--password' => self::PASSWORD, '--super' => true]));

        $this->assertSame('1', (string) $this->getUser('anna')['superuser_access']);
    }

    public function testCreateLeavesAnExistingUserAloneWithoutFailing()
    {
        $this->addUser('anna');

        $code = $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'other@example.org', '--password' => 'another-long-secret-2']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('already exists', $this->getDisplay());
        $this->assertTrue($this->isPassword('anna', self::PASSWORD));
    }

    /**
     * @dataProvider getIncompleteOptions
     */
    public function testACommandFailsWhenAnOptionItNeedsIsMissing(string $command, array $options, string $missing)
    {
        $this->assertSame(1, $this->runCommand($command, $options));

        $this->assertStringContainsString("The option --$missing is required.", $this->getDisplay());
    }

    public function getIncompleteOptions(): array
    {
        return [
            ['user:create', ['--email' => 'anna@example.org', '--password' => self::PASSWORD], 'login'],
            ['user:create', ['--login' => 'anna', '--password' => self::PASSWORD], 'email'],
            ['user:create', ['--login' => 'anna', '--email' => 'anna@example.org'], 'password'],
            ['user:delete', [], 'login'],
            ['user:make-super', [], 'login'],
            ['user:remove-super', [], 'login'],
            ['user:reset-password', ['--new-password' => self::PASSWORD], 'login'],
            ['user:access', ['--access' => 'view', '--sites' => '1'], 'login'],
            ['user:invite', ['--login' => 'anna', '--email' => 'anna@example.org'], 'site'],
        ];
    }

    /**
     * @dataProvider getCommandsForAnExistingUser
     */
    public function testACommandFailsForAUserThatDoesNotExist(string $command, array $options)
    {
        $this->assertSame(1, $this->runCommand($command, ['--login' => 'nobody'] + $options));

        $this->assertStringContainsString('User nobody does not exist.', $this->getDisplay());
    }

    public function getCommandsForAnExistingUser(): array
    {
        return [
            ['user:delete', []],
            ['user:make-super', []],
            ['user:remove-super', []],
            ['user:reset-password', ['--new-password' => self::PASSWORD]],
            ['user:access', ['--access' => 'view', '--sites' => '1']],
        ];
    }

    public function testCreateFailsWithMatomosReasonWhenThePasswordIsTooWeak()
    {
        $this->assertSame(1, $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'anna@example.org', '--password' => 'abc']));

        $this->assertNull($this->getUser('anna'));
        $this->assertStringNotContainsString('created', $this->getDisplay());
    }

    public function testDeleteRemovesTheUser()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:delete', ['--login' => 'anna']));

        $this->assertNull($this->getUser('anna'));
    }

    public function testListShowsUsersAndMarksSuperUsers()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:list', []));

        $this->assertStringContainsString('anna (anna@example.org)', $this->getDisplay());
        $this->assertSame(1, preg_match('/' . preg_quote(Fixture::ADMIN_USER_LOGIN, '/') . ' \(.*\) \[super user\]/', $this->getDisplay()));
        $this->assertStringNotContainsString('anna (anna@example.org) [super user]', $this->getDisplay());
    }

    public function testSuperUserAccessCanBeGivenAndTakenAway()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:make-super', ['--login' => 'anna']), $this->getDisplay());
        $this->assertSame('1', (string) $this->getUser('anna')['superuser_access']);

        $this->assertSame(0, $this->runCommand('user:remove-super', ['--login' => 'anna']), $this->getDisplay());
        $this->assertSame('0', (string) $this->getUser('anna')['superuser_access']);
    }

    public function testTheLastSuperUserKeepsTheAccess()
    {
        $this->assertSame(1, $this->runCommand('user:remove-super', ['--login' => Fixture::ADMIN_USER_LOGIN]));

        $this->assertSame('1', (string) $this->getUser(Fixture::ADMIN_USER_LOGIN)['superuser_access']);
    }

    public function testResetPasswordSetsAPasswordTheUserCanLogInWith()
    {
        $this->addUser('anna');
        $changedBefore = $this->getUser('anna')['ts_password_modified'];
        sleep(1);

        $this->assertSame(0, $this->runCommand('user:reset-password', ['--login' => 'anna', '--new-password' => 'another-long-secret-2']));

        $this->assertTrue($this->isPassword('anna', 'another-long-secret-2'));
        $this->assertFalse($this->isPassword('anna', self::PASSWORD));
        // Matomo ends the sessions that were started before the password changed.
        $this->assertGreaterThan($changedBefore, $this->getUser('anna')['ts_password_modified']);
    }

    public function testResetPasswordRefusesAPasswordThatBreaksMatomosRules()
    {
        $this->addUser('anna');

        $this->assertSame(1, $this->runCommand('user:reset-password', ['--login' => 'anna', '--new-password' => 'abc']));

        $this->assertTrue($this->isPassword('anna', self::PASSWORD));
    }

    public function testResetPasswordKeepsQuotesInThePasswordOutOfTheQuery()
    {
        $this->addUser('anna');
        $password = "it's \"quoted\"; -- x";

        $this->assertSame(0, $this->runCommand('user:reset-password', ['--login' => 'anna', '--new-password' => $password]));

        $this->assertTrue($this->isPassword('anna', $password));
    }

    public function testAccessIsSetForTheGivenWebsites()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'write', '--sites' => '1, 2']));

        $access = Access::doAsSuperUser(fn () => APIUsersManager::getInstance()->getSitesAccessFromUser('anna'));
        $this->assertEquals(
            [['site' => 1, 'access' => 'write'], ['site' => 2, 'access' => 'write']],
            array_map(fn (array $row) => ['site' => (int) $row['site'], 'access' => $row['access']], $access)
        );
    }

    public function testAccessCanBeSetForAllWebsitesAtOnce()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'view', '--sites' => 'all']), $this->getDisplay());

        $access = Access::doAsSuperUser(fn () => APIUsersManager::getInstance()->getSitesAccessFromUser('anna'));
        $this->assertSame(['view', 'view'], array_column($access, 'access'));
    }

    public function testAccessFailsForALevelMatomoDoesNotKnow()
    {
        $this->addUser('anna');

        $this->assertSame(1, $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'owner', '--sites' => '1']));
    }

    public function testInviteAddsAPendingUser()
    {
        $this->assertSame(0, $this->runCommand('user:invite', ['--login' => 'anna', '--email' => 'anna@example.org', '--site' => '1']));

        $this->assertStringContainsString('User anna invited', $this->getDisplay());
        $this->assertNotEmpty($this->getUser('anna')['invite_token']);
    }

    private function runCommand(string $command, array $options): int
    {
        return $this->applicationTester->run(['command' => $command] + $options);
    }

    private function getDisplay(): string
    {
        return $this->applicationTester->getDisplay();
    }

    private function addUser(string $login): void
    {
        Access::doAsSuperUser(fn () => APIUsersManager::getInstance()->addUser($login, self::PASSWORD, "$login@example.org"));
    }

    private function getUser(string $login): ?array
    {
        $user = Db::fetchRow('SELECT * FROM ' . Common::prefixTable('user') . ' WHERE login = ?', [$login]);

        return $user ?: null;
    }

    private function isPassword(string $login, string $password): bool
    {
        return password_verify(UsersManager::getPasswordHash($password), (new Model())->getUser($login)['password']);
    }
}
