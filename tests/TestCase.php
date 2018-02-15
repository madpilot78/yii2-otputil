<?php

/*
 * Taken from yiisoft/yii2-httpclient
 */

namespace mad\otputil\tests;

use yii\di\Container;
use yii\helpers\ArrayHelper;
use yii\console\controllers\MigrateController;
use Yii;
use chillerlan\Authenticator\Base32;
use mad\otputil\models\Secret;

/**
 * Filter to silence yii2 migration
 */
class discard_filter extends \php_user_filter {
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
        }
        return PSFS_PASS_ON;
    }
}

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
            ]
        ], $config));
    }

    /**
     * Setup database for tests
     */
    protected function runMigrations()
    {
        stream_filter_register('discard', '\mad\otputil\tests\discard_filter');
        $f = stream_filter_append(\STDOUT, "discard");

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
     */
    protected function populateSecret(Secret &$s, Array $data)
    {
        $s->secret = $data["secret"];
        $s->digits = $data["digits"];
        $s->mode = $data["mode"];
        $s->algo = $data["algo"];
        $s->period = $data["period"];
    }

    /**
     * Extract $data array froma retrived or created secret
     */
    protected function getSecretData(Secret $s, Array &$data)
    {
        $data["id"] = $s->id;
        $data["secret"] = $s->secret;
        $data["digits"] = $s->digits;
        $data["mode"] = $s->mode;
        $data["algo"] = $s->algo;
        $data["period"] = $s->period;
    }

    /**
     * Assert that data validation on a secret suceeds reporting errors,
     * if any, for analysis
     */
    protected function assertValidateSecret(Secret $s)
    {
        $r = $s->validate();
        if ($s->HasErrors())
            var_dump($s->getErrors());
        $this->assertTrue($r);
    }

    /**
     * Assert validation on secret failed and report errors
     */
    protected function assertNotValidateSecret(Secret $s)
    {
        $r = $s->validate();
        if ($s->HasErrors())
            var_dump($s->getErrors());
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
