<?php

/*
 * Taken from yiisoft/yii2-httpclient
 */

namespace mad\otputil\tests;

use yii\di\Container;
use yii\helpers\ArrayHelper;
use yii\console\controllers\MigrateController;
use Yii;

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
        $migration = new MigrateController('migrate', Yii::$app);
        $migration->run('up', [
            'interactive' => false,
            'compact' => true,
            'migrationPath' => dirname(__DIR__) . '/migrations'
        ]);
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
