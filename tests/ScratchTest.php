<?php

namespace madpilot78\otputil\tests;

use madpilot78\otputil\models\Secret;
use madpilot78\otputil\models\Scratch;

class ScratchTest extends TestCase
{
    /**
     * Extracts code from an array of Scratch objects
     *
     * @param Array $cc array of scratch objects
     * @return Array Array of codes
     */
    protected function flattenScratchCodes(array $cc)
    {
        $codes = [];
        foreach ($cc as $c) {
            $codes[] = $c->code;
        }

        return $codes;
    }

    /**
     * Assert validation succeeds for a scratch code and report errors,
     * if any, for debugging
     *
     * @param Scratch $c Scratch code to be validated
     */
    protected function assertValidateScratch(Scratch $c)
    {
        $r = $c->validate();
        if ($c->HasErrors()) {
            var_dump($c->getErrors());
        }
        $this->assertTrue($r);
    }

    /**
     * Create a single scratch code for an ID using native AR methods
     *
     * @param int $id Secret ID to which to bind the generated Scratch code
     * @return Scratch Generated AR object
     */
    protected function createScratchForID(int $id)
    {
        $c = new Scratch($id);
        $this->assertValidateScratch($c);
        $this->assertTrue($c->save());
        return $c;
    }

    /**
     * Test creating a scratch code
     *
     * @return void
     */
    public function testCreateScrathCode()
    {
        $s = $this->createRandomSecret();
        $c = $this->createScratchForID($s->id);

        $cc = Scratch::findOne($c->id);
        $this->assertEquals($c->code, $cc->code);
    }

    /**
     * Test creating a group of scratch codes using the defaults
     *
     * @return void
     */
    public function testCreateScratchesDefault()
    {
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertInternalType('array', $codes);
        $this->assertCount(Scratch::DEFAULT_CODES, $codes);
    }

    /**
     * Test creating a group of scratch codes using random non default
     * input
     *
     * @return void
     */
    public function testCreateScratchesRandom()
    {
        $faker = \Faker\Factory::create();

        $n = $faker->numberBetween($min = 1, $max = 10);

        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id, $n);

        $this->assertInternalType('array', $codes);
        $this->assertCount($n, $codes);
    }

    /**
     * Test creating a group of scratch codes with invalid input fails
     *
     * @return void
     */
    public function testCreateScratchesWrongFails()
    {
        $codes = Scratch::createScratches(42);
        $this->assertInternalType('boolean', $codes);
        $this->assertNotTrue($codes);
    }

    /**
     * Test looking up scratch codes by secret ID
     *
     * @return void
     */
    public function testScratchFindBySecretID()
    {
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(Scratch::DEFAULT_CODES, $chk);

        $ccodes = $this->flattenScratchCodes($codes);
        $cchk = $this->flattenScratchCodes($chk);

        $this->assertCount(0, array_diff($ccodes, $cchk));
    }

    /**
     * Test Verifying a non existend scratch code fails
     *
     * @return void
     */
    public function testVerifyAbsentScratchCode()
    {
        $this->assertNotTrue(Scratch::verifyCode(99, '12345678'));
    }

    /**
     * Test Verifying a wrong scratch code fails
     *
     * @return void
     */
    public function testVerifyWrongScratchCode()
    {
        $s = $this->createRandomSecret();
        $c = $this->createScratchForID($s->id);

        $this->assertNotTrue(Scratch::verifyCode('12345678', $s->id));
    }

    /**
     * Test Verifying a correct scratch code succeeds and removes it
     * from list
     *
     * Performed using static methods
     *
     * @return void
     */
    public function testVerifyScratchCodeAndDelete()
    {
        $faker = \Faker\Factory::create();

        $n = $faker->numberBetween($min = 0, $max = Scratch::DEFAULT_CODES - 1);
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertTrue(Scratch::verifyCode($codes[$n]->code, $s->id));

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(Scratch::DEFAULT_CODES - 1, $chk);

        foreach ($chk as $c) {
            $this->assertNotEquals($codes[$n], $c->code);
        }
    }

    /**
     * Test Verifying a correct scratch code succeeds and removes it
     * from list
     *
     * Performed acting on the single code instance
     *
     * @return void
     */
    public function testVerifyScratchCodeAndDeleteOnInstance()
    {
        $faker = \Faker\Factory::create();

        $n = $faker->numberBetween($min = 0, $max = Scratch::DEFAULT_CODES - 1);
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $c = $codes[$faker->numberBetween($min = 0, $max = Scratch::DEFAULT_CODES - 1)];
        $this->assertTrue($c->verify($codes[$n]->code));

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(Scratch::DEFAULT_CODES - 1, $chk);

        foreach ($chk as $c) {
            $this->assertNotEquals($codes[$n], $c->code);
        }
    }

    /**
     * Test verifying Scratch with invalid Secret ID
     *
     * @return void
     */
    public function testVerifyCratchCodeWithInvalidSIDOnInstance()
    {
        $c = new Scratch(42);
        $this->assertNotTrue($c->verify('00000000'));
    }

    /**
     * Test Verifying a scratch code requesting not to delete it suceeds
     * and does not delete the code
     *
     * @return void
     */
    public function testVerifyScratchCodeNotDelete()
    {
        $faker = \Faker\Factory::create();

        $n = $faker->numberBetween($min = 0, $max = Scratch::DEFAULT_CODES - 1);
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertTrue(Scratch::verifyCode($codes[$n]->code, $s->id, false));

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(Scratch::DEFAULT_CODES, $chk);

        $ccodes = $this->flattenScratchCodes($codes);
        $cchk = $this->flattenScratchCodes($chk);

        $this->assertCount(0, array_diff($ccodes, $cchk));
    }

    /**
     * Test creating aand removing scratch codes
     *
     * @return void
     */
    public function testCreateAndRemoveScratchCodes()
    {
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertTrue(Scratch::remove($s->id));

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(0, $chk);
    }

    /**
     * Test Scratch->Secret relation
     *
     * @return void
     */
    public function testGetSecret()
    {
        $s = $this->createRandomSecret();
        $c = $this->createScratchForID($s->id);

        $q = $c->getSecret();

        $this->assertEqualSecrets($s, $q->one());
    }
}
