<?php

namespace Piwik\Plugins\UserConsole\tests\Integration;

use Piwik\Access;
use Piwik\Common;
use Piwik\Date;
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
        foreach (['user', 'access', 'user_token_auth'] as $table) {
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

    public function testAccessCanBeSetTogetherWithCapabilities()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'write, tagmanager_publish_live_container', '--sites' => '1']), $this->getDisplay());

        $this->assertEqualsCanonicalizing(['write', 'tagmanager_publish_live_container'], $this->getAccess('anna', 1));
    }

    public function testAccessOfOnlyACapabilityFailsAndTellsHowToGiveIt()
    {
        $this->addUser('anna');
        $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'view', '--sites' => '1']);

        $this->assertSame(1, $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'tagmanager_write', '--sites' => '1']));

        $this->assertStringContainsString('user:capability', $this->getDisplay());
        $this->assertEquals(['view'], $this->getAccess('anna', 1));
    }

    public function testAccessFailsForTwoLevelsAtOnce()
    {
        $this->addUser('anna');

        $this->assertSame(1, $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'view,write', '--sites' => '1']));

        $this->assertSame([], $this->getAccess('anna', 1));
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

    public function testTokenCreatePrintsATokenThatAuthenticatesTheUser()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'importer']));

        $token = trim($this->getDisplay());
        $this->assertSame('anna', (new Model())->getUserByTokenAuth($token)['login']);
    }

    public function testTokenCreateKeepsTheExpireDateAndSecureOnly()
    {
        $this->addUser('anna');
        $date = date('Y-m-d', strtotime('+30 days'));

        $this->assertSame(0, $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'importer', '--expire-date' => $date, '--secure-only' => true]));

        $token = (new Model())->getAllNonSystemTokensForLogin('anna')[0];
        $this->assertStringStartsWith($date, $token['date_expired']);
        $this->assertSame('1', (string) $token['secure_only']);
    }

    /**
     * @dataProvider getBadExpireDates
     */
    public function testTokenCreateRefusesAnExpireDateThatIsNotALaterDay(string $date)
    {
        $this->addUser('anna');

        $this->assertSame(1, $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'importer', '--expire-date' => $date]));

        $this->assertStringContainsString('--expire-date', $this->getDisplay());
        $this->assertSame([], (new Model())->getAllNonSystemTokensForLogin('anna'));
    }

    public function getBadExpireDates(): array
    {
        return [['2020-01-01'], ['tomorrow'], ['2099-02-31']];
    }

    public function testTokenCreateFailsWithoutADescriptionOrForAnUnknownUser()
    {
        $this->addUser('anna');

        $this->assertSame(1, $this->runCommand('user:token-create', ['--login' => 'anna']));
        $this->assertSame(1, $this->runCommand('user:token-create', ['--login' => 'nobody', '--description' => 'importer']));
    }

    public function testTokenListShowsTheTokensOfTheUserOnlyAndNotTheirSecrets()
    {
        $this->addUser('anna');
        $this->addUser('bertil');
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'importer']);
        $token = trim($this->getDisplay());
        $this->runCommand('user:token-create', ['--login' => 'bertil', '--description' => 'dashboard']);

        $this->assertSame(0, $this->runCommand('user:token-list', ['--login' => 'anna']));

        $this->assertStringContainsString('importer', $this->getDisplay());
        $this->assertStringNotContainsString('dashboard', $this->getDisplay());
        $this->assertStringNotContainsString($token, $this->getDisplay());
        $this->assertStringNotContainsString((new Model())->hashTokenAuth($token), $this->getDisplay());
    }

    public function testTokenDeleteRemovesTheGivenTokenOnly()
    {
        $this->addUser('anna');
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'importer']);
        $deleted = trim($this->getDisplay());
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'dashboard']);
        $kept = trim($this->getDisplay());
        $model = new Model();
        $id = $model->getAllNonSystemTokensForLogin('anna')[0]['idusertokenauth'];

        $this->assertSame(0, $this->runCommand('user:token-delete', ['--login' => 'anna', '--id' => $id]));

        $this->assertEmpty($model->getUserByTokenAuth($deleted));
        $this->assertNotEmpty($model->getUserByTokenAuth($kept));
    }

    public function testTokenDeleteCannotRemoveTheTokenOfAnotherUser()
    {
        $this->addUser('anna');
        $this->addUser('bertil');
        $this->runCommand('user:token-create', ['--login' => 'bertil', '--description' => 'dashboard']);
        $model = new Model();
        $id = $model->getAllNonSystemTokensForLogin('bertil')[0]['idusertokenauth'];

        $this->assertSame(1, $this->runCommand('user:token-delete', ['--login' => 'anna', '--id' => $id]));

        $this->assertCount(1, $model->getAllNonSystemTokensForLogin('bertil'));
    }

    public function testTokenDeleteAllRemovesEveryTokenOfTheUser()
    {
        $this->addUser('anna');
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'importer']);
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'dashboard']);

        $this->assertSame(0, $this->runCommand('user:token-delete', ['--login' => 'anna', '--all' => true]));

        $this->assertSame([], (new Model())->getAllNonSystemTokensForLogin('anna'));
    }

    public function testTokenDeleteFailsWithoutAnIdOrAll()
    {
        $this->addUser('anna');

        $this->assertSame(1, $this->runCommand('user:token-delete', ['--login' => 'anna']));
    }

    public function testCreateWithUpdateBringsAnExistingUserInLine()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'new@example.org', '--password' => 'another-long-secret-2', '--super' => true, '--update' => true]));

        $user = $this->getUser('anna');
        $this->assertSame('new@example.org', $user['email']);
        $this->assertSame('1', (string) $user['superuser_access']);
        $this->assertTrue($this->isPassword('anna', 'another-long-secret-2'));
    }

    public function testCreateWithUpdateTakesSuperUserAccessAwayWhenNotAskedFor()
    {
        $this->addUser('anna');
        $this->runCommand('user:make-super', ['--login' => 'anna']);

        $this->assertSame(0, $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'anna@example.org', '--password' => self::PASSWORD, '--update' => true]));

        $this->assertSame('0', (string) $this->getUser('anna')['superuser_access']);
    }

    public function testCreateWithUpdateKeepsTheUserLoggedInWhenNothingDiffers()
    {
        $this->addUser('anna');
        $before = $this->getUser('anna');

        $this->assertSame(0, $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'anna@example.org', '--password' => self::PASSWORD, '--update' => true]));

        $this->assertStringContainsString('Nothing was changed', $this->getDisplay());
        $this->assertSame($before, $this->getUser('anna'));
    }

    public function testCreateWithUpdateAddsAUserThatDoesNotExist()
    {
        $this->assertSame(0, $this->runCommand('user:create', ['--login' => 'anna', '--email' => 'anna@example.org', '--password' => self::PASSWORD, '--update' => true]));

        $this->assertTrue($this->isPassword('anna', self::PASSWORD));
    }

    public function testUpdateChangesTheEmailAndKeepsThePassword()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:update', ['--login' => 'anna', '--email' => 'new@example.org']));

        $this->assertSame('new@example.org', $this->getUser('anna')['email']);
        $this->assertTrue($this->isPassword('anna', self::PASSWORD));
    }

    public function testUpdateRefusesAnEmailOfAnotherUserAndOneThatIsNotAnEmail()
    {
        $this->addUser('anna');
        $this->addUser('bertil');

        $this->assertSame(1, $this->runCommand('user:update', ['--login' => 'anna', '--email' => 'bertil@example.org']));
        $this->assertSame(1, $this->runCommand('user:update', ['--login' => 'anna', '--email' => 'not-an-email']));

        $this->assertSame('anna@example.org', $this->getUser('anna')['email']);
    }

    public function testUpdateChangesOnlyWhatWasGiven()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:update', ['--login' => 'anna', '--password' => 'another-long-secret-2']), $this->getDisplay());
        $this->assertTrue($this->isPassword('anna', 'another-long-secret-2'));
        $this->assertSame('anna@example.org', $this->getUser('anna')['email']);

        $this->assertSame(0, $this->runCommand('user:update', ['--login' => 'anna', '--super' => 'true']), $this->getDisplay());
        $this->assertSame('1', (string) $this->getUser('anna')['superuser_access']);

        $this->assertSame(0, $this->runCommand('user:update', ['--login' => 'anna', '--email' => 'new@example.org', '--super' => 'false']), $this->getDisplay());
        $user = $this->getUser('anna');
        $this->assertSame(['new@example.org', '0'], [$user['email'], (string) $user['superuser_access']]);
        $this->assertTrue($this->isPassword('anna', 'another-long-secret-2'));
    }

    /**
     * @dataProvider getUpdatesThatAreRefused
     */
    public function testUpdateRefusesWhatItCanNotDo(array $options)
    {
        $this->addUser('anna');
        $before = $this->getUser('anna');

        $this->assertSame(1, $this->runCommand('user:update', ['--login' => 'anna'] + $options));

        $this->assertSame($before, $this->getUser('anna'));
    }

    public function getUpdatesThatAreRefused(): array
    {
        return [
            'nothing to change' => [[]],
            'empty password' => [['--password' => '']],
            'weak password' => [['--password' => 'abc']],
            'super that is not true or false' => [['--super' => 'yes']],
        ];
    }

    public function testUpdateLeavesTheLastSuperUserItsAccess()
    {
        $this->assertSame(1, $this->runCommand('user:update', ['--login' => Fixture::ADMIN_USER_LOGIN, '--super' => 'false']));

        $this->assertSame('1', (string) $this->getUser(Fixture::ADMIN_USER_LOGIN)['superuser_access']);
    }

    public function testLogoutEndsTheSessionsOfTheUserAndKeepsPasswordAndTokens()
    {
        $this->addUser('anna');
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'importer']);
        $token = trim($this->getDisplay());
        $sessionStart = time() - 3600;
        Db::query('UPDATE ' . Common::prefixTable('user') . ' SET ts_password_modified = ? WHERE login = ?', ['2020-01-01 00:00:00', 'anna']);

        $this->assertSame(0, $this->runCommand('user:logout', ['--login' => 'anna']));

        // Matomo ends a session that started before this time.
        $this->assertGreaterThan($sessionStart, Date::factory($this->getUser('anna')['ts_password_modified'])->getTimestampUTC());
        $this->assertTrue($this->isPassword('anna', self::PASSWORD));
        $this->assertNotEmpty((new Model())->getUserByTokenAuth($token));
    }

    public function testListAsJsonTellsAccessSuperUserAndInvitation()
    {
        $this->addUser('anna');
        $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'write', '--sites' => '2']);
        $this->runCommand('user:invite', ['--login' => 'bertil', '--email' => 'bertil@example.org', '--site' => '1']);

        $this->assertSame(0, $this->runCommand('user:list', ['--format' => 'json']));

        $users = array_column(json_decode($this->getDisplay(), true, 512, JSON_THROW_ON_ERROR), null, 'login');
        $this->assertSame('anna@example.org', $users['anna']['email']);
        $this->assertFalse($users['anna']['superuser']);
        $this->assertSame('active', $users['anna']['invite']);
        $this->assertEquals([['site' => 2, 'access' => 'write']], $users['anna']['access']);
        $this->assertSame('pending', $users['bertil']['invite']);
        $this->assertTrue($users[Fixture::ADMIN_USER_LOGIN]['superuser']);
        $this->assertArrayNotHasKey('password', $users['anna']);
    }

    public function testListAsYamlHasTheSameAsJson()
    {
        $this->addUser('anna');
        $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'write', '--sites' => '2']);
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'the <info>importer</info>: "x"']);

        foreach (['user:list' => [], 'user:show' => ['--login' => 'anna'], 'user:token-list' => ['--login' => 'anna']] as $command => $options) {
            $this->assertSame(0, $this->runCommand($command, $options + ['--format' => 'json']));
            $json = json_decode($this->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(0, $this->runCommand($command, $options + ['--format' => 'yaml']));
            $this->assertSame($json, \Symfony\Component\Yaml\Yaml::parse($this->getDisplay()), $command);
        }
    }

    public function testListFailsForAFormatItDoesNotKnow()
    {
        $this->assertSame(1, $this->runCommand('user:list', ['--format' => 'xml']));
    }

    public function testTokenListAsJsonKeepsADescriptionThatLooksLikeAConsoleStyle()
    {
        $this->addUser('anna');
        $this->runCommand('user:token-create', ['--login' => 'anna', '--description' => 'the <info>importer</info>', '--secure-only' => true]);

        $this->assertSame(0, $this->runCommand('user:token-list', ['--login' => 'anna', '--format' => 'json']));

        $tokens = json_decode($this->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $tokens);
        $this->assertSame('the <info>importer</info>', $tokens[0]['description']);
        $this->assertTrue($tokens[0]['secure_only']);
        $this->assertSame(['id', 'description', 'date_created', 'last_used', 'date_expired', 'secure_only'], array_keys($tokens[0]));
    }

    public function testDeleteAsksAPersonAndKeepsTheUserOnNo()
    {
        $this->addUser('anna');
        $this->applicationTester->setInputs(["n\n"]);

        $this->assertSame(0, $this->applicationTester->run(['command' => 'user:delete', '--login' => 'anna'], ['interactive' => true]));

        $this->assertStringContainsString('was not deleted', $this->getDisplay());
        $this->assertNotNull($this->getUser('anna'));
    }

    public function testDeleteAsksAPersonAndDeletesOnYes()
    {
        $this->addUser('anna');
        $this->applicationTester->setInputs(["y\n"]);

        $this->assertSame(0, $this->applicationTester->run(['command' => 'user:delete', '--login' => 'anna'], ['interactive' => true]));

        $this->assertNull($this->getUser('anna'));
    }

    public function testDeleteWithForceDoesNotAsk()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->applicationTester->run(['command' => 'user:delete', '--login' => 'anna', '--force' => true], ['interactive' => true]));

        $this->assertNull($this->getUser('anna'));
    }

    public function testInviteResendGivesThePendingUserANewInvitation()
    {
        $this->runCommand('user:invite', ['--login' => 'anna', '--email' => 'anna@example.org', '--site' => '1']);
        $before = $this->getUser('anna')['invite_token'];

        $this->assertSame(0, $this->runCommand('user:invite-resend', ['--login' => 'anna']));

        $after = $this->getUser('anna')['invite_token'];
        $this->assertNotEmpty($after);
        $this->assertNotSame($before, $after);
    }

    public function testInviteResendFailsForAUserThatIsNotWaitingToAccept()
    {
        $this->addUser('anna');

        $this->assertSame(1, $this->runCommand('user:invite-resend', ['--login' => 'anna']));

        $this->assertStringContainsString('no invitation', $this->getDisplay());
    }

    public function testListMarksUsersThatHaveNotAcceptedTheirInvitation()
    {
        $this->addUser('anna');
        $this->runCommand('user:invite', ['--login' => 'bertil', '--email' => 'bertil@example.org', '--site' => '1']);

        $this->runCommand('user:list', []);

        $this->assertRegExp('/bertil .*\[invitation pending\]/', $this->getDisplay());
        $this->assertNotRegExp('/anna .*invitation/', $this->getDisplay());
    }

    public function testShowTellsAboutOneUser()
    {
        $this->addUser('anna');
        $this->addUser('bertil');
        $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'write', '--sites' => '2']);

        $this->assertSame(0, $this->runCommand('user:show', ['--login' => 'anna']));

        $this->assertStringContainsString('anna@example.org', $this->getDisplay());
        $this->assertStringContainsString('Website 2: write', $this->getDisplay());
        $this->assertStringNotContainsString('bertil', $this->getDisplay());
    }

    public function testShowAsJsonHasTheSameFieldsAsTheList()
    {
        $this->addUser('anna');

        $this->assertSame(0, $this->runCommand('user:show', ['--login' => 'anna', '--format' => 'json']));
        $user = json_decode($this->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->runCommand('user:list', ['--format' => 'json']);
        $users = array_column(json_decode($this->getDisplay(), true, 512, JSON_THROW_ON_ERROR), null, 'login');

        $this->assertSame('anna', $user['login']);
        $this->assertSame($users['anna'], $user);
    }

    public function testShowFailsForAUserThatDoesNotExist()
    {
        $this->assertSame(1, $this->runCommand('user:show', ['--login' => 'nobody']));
    }

    public function testCapabilityIsGivenAndTakenAwayOnTheGivenWebsites()
    {
        $this->addUser('anna');
        $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'view', '--sites' => '1,2']);

        $this->assertSame(0, $this->runCommand('user:capability', ['--login' => 'anna', '--capability' => 'tagmanager_write', '--sites' => '2']));
        $this->assertEquals(['view'], $this->getAccess('anna', 1));
        $this->assertEqualsCanonicalizing(['view', 'tagmanager_write'], $this->getAccess('anna', 2));

        $this->assertSame(0, $this->runCommand('user:capability', ['--login' => 'anna', '--capability' => 'tagmanager_write', '--sites' => '2', '--remove' => true]));
        $this->assertEquals(['view'], $this->getAccess('anna', 2));
    }

    public function testCapabilityFailsForOneMatomoDoesNotKnow()
    {
        $this->addUser('anna');
        $this->runCommand('user:access', ['--login' => 'anna', '--access' => 'view', '--sites' => '1']);

        $this->assertSame(1, $this->runCommand('user:capability', ['--login' => 'anna', '--capability' => 'admin', '--sites' => '1']));
        $this->assertStringContainsString('tagmanager_write', $this->getDisplay());

        $this->assertEquals(['view'], $this->getAccess('anna', 1));
    }

    private function getAccess(string $login, int $idSite): array
    {
        return Db::get()->fetchCol('SELECT access FROM ' . Common::prefixTable('access') . ' WHERE login = ? AND idsite = ?', [$login, $idSite]);
    }

    private function runCommand(string $command, array $options): int
    {
        return $this->applicationTester->run(['command' => $command] + $options, ['interactive' => false]);
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
