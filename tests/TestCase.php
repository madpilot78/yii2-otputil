<?php

/*
 * Originally taken from yiisoft/yii2-httpclient
 */

namespace madpilot78\otputil\tests;

use Yii;
use yii\di\Container;
use yii\helpers\ArrayHelper;
use yii\console\controllers\MigrateController;
use chillerlan\Authenticator\Base32;
use madpilot78\otputil\models\Secret;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Common setup code
     */
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
        $this->runMigrations();
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => dirname(__DIR__),
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite::memory:',
                ],
                'otp' => [
                    'class' => 'madpilot78\otputil\components\OTP',
                ],
            ]
        ], $config));
    }

    /**
     * Setup database for tests
     */
    protected function runMigrations()
    {
        stream_filter_register('discard', '\madpilot78\otputil\tests\DiscardFilter');
        $f = stream_filter_append(\STDOUT, 'discard');

        $migration = new MigrateController('migrate', Yii::$app);
        $migration->interactive = false;
        $migration->compact = true;
        $migration->migrationPath = dirname(__DIR__) . '/src/migrations';
        $migration->run('up');

        stream_filter_remove($f);
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
        Yii::$container = new Container();
    }

    /**
     * Use faker to create a secret for testing
     *
     * @return array Random data to be put in a Secret object
     */
    protected function imagineSecret()
    {
        $faker = \Faker\Factory::create();
        $base32 = new Base32();

        return [
            'secret' => $base32->fromString(random_bytes(20)),
            'digits' => $faker->randomElement(Secret::ALLOWED_DIGITS),
            'mode' => $faker->randomElement(Secret::ALLOWED_MODES),
            'algo' => $faker->randomElement(Secret::ALLOWED_ALGOS),
            'period' => $faker->numberBetween($min = Secret::ALLOWED_PERIODS[0], $max = Secret::ALLOWED_PERIODS[1])
        ];
    }

    /**
     * Populate a new secret AR
     *
     * @param Secret &$s The secret to be populated/overwritten
     * @param Array $data The array containing the data to populate the Secret
     */
    protected function populateSecret(Secret &$s, array $data)
    {
        $s->secret = $data['secret'];
        $s->digits = $data['digits'];
        $s->mode = $data['mode'];
        $s->algo = $data['algo'];
        $s->period = $data['period'];
    }

    /**
     * Extract $data array froma retrived or created secret
     *
     * @param Secret $s The secret to be read
     * @param Array &$data The array to contain the data gotten from the secret
     */
    protected function getSecretData(Secret $s, array &$data)
    {
        $data['id'] = $s->id;
        $data['secret'] = $s->secret;
        $data['digits'] = $s->digits;
        $data['mode'] = $s->mode;
        $data['algo'] = $s->algo;
        $data['period'] = $s->period;
    }

    /**
     * Assert that data validation on a secret suceeds reporting errors,
     * if any, for analysis
     *
     * @param Secret $s The secret to be validated
     */
    protected function assertValidateSecret(Secret $s)
    {
        $r = $s->validate();
        if ($s->HasErrors()) {
            var_dump($s->getErrors());
        }
        $this->assertTrue($r);
    }

    /**
     * Assert validation on secret failed and report errors
     *
     * @param Secret $s The secret to be validated
     */
    protected function assertNotValidateSecret(Secret $s)
    {
        $r = $s->validate();
        if ($s->HasErrors()) {
            var_dump($s->getErrors());
        }
        $this->assertNotTrue($r);
    }

    protected function createRandomSecret()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $this->populateSecret($s, $data);
        $this->assertValidateSecret($s);
        $this->assertTrue($s->save());
        return $s;
    }

    /**
     * Asserts that two secret objects are the same
     *
     * @param madpilot78\otputil\models\Secret expected Secret
     * @param madpilot78\otputil\models\Secret Secret object to be checked
     */
    protected function assertEqualSecrets(Secret $exp, Secret $act)
    {
        $this->assertEquals($exp->id, $act->id);
        $this->assertEquals($exp->secret, $act->secret);
        $this->assertEquals($exp->digits, $act->digits);
        $this->assertEquals($exp->mode, $act->mode);
        $this->assertEquals($exp->algo, $act->algo);
        $this->assertEquals($exp->period, $act->period);
    }

    /**
     * Asserts that data inside a secret is the same as the provided array
     *
     * @param array $data expected content
     * @param madpilot78\otputil\models\Secret The Secret object to be checked
     */
    protected function assertSecretEqualsData(array $data, Secret $act)
    {
        $this->assertEquals($data['id'], $act->id);
        $this->assertEquals($data['secret'], $act->secret);
        $this->assertEquals($data['digits'], $act->digits);
        $this->assertEquals($data['mode'], $act->mode);
        $this->assertEquals($data['algo'], $act->algo);
        $this->assertEquals($data['period'], $act->period);
    }

    /**
     * Asserting two strings equality ignoring line endings
     *
     * @param string $expected
     * @param string $actual
     */
    public function assertEqualsWithoutLE($expected, $actual)
    {
        $expected = str_replace(["\r", "\n"], '', $expected);
        $actual = str_replace(["\r", "\n"], '', $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Invokes object method, even if it is private or protected.
     *
     * @param object $object object.
     * @param string $method method name.
     * @param array $args method arguments
     * @return mixed method result
     */
    protected function invoke($object, $method, array $args = [])
    {
        $classReflection = new \ReflectionClass(get_class($object));
        $methodReflection = $classReflection->getMethod($method);
        $methodReflection->setAccessible(true);
        $result = $methodReflection->invokeArgs($object, $args);
        $methodReflection->setAccessible(false);
        return $result;
    }
}
