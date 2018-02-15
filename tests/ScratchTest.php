<?php

namespace mad\otputil\tests;

use mad\otputil\models\Secret;
use mad\otputil\models\Scratch;

class ScratchTest extends TestCase
{
    /**
     * Assert validation succeeds for a scratch code and report errors,
     * if any, for debugging
     */
    protected function assertValidateScratch(Scratch $c)
    {
        $r = $c->validate();
        if ($c->HasErrors())
            var_dump($c->getErrors());
        $this->assertTrue($r);
    }

    /**
     * Create a single scratch code for an ID using native AR methods
     */
    protected function createScratchForID(int $id)
    {
        $c = new Scratch($id);
        $this->assertValidateScratch($c);
        $this->assertTrue($c->save());
        return $c;
    }

    // Tests:

    public function testCreateScrathCode()
    {
        $s = $this->createRandomSecret();
        $c = $this->createScratchForID($s->id);

        $cc = Scratch::findOne($c->id);
        $this->assertEquals($c->code, $cc->code);
    }

    public function testCreateScratchesDefault()
    {
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertInternalType('array', $codes);
        $this->assertCount(Scratch::DEFAULT_CODES, $codes);
    }

    public function testCreateScratchesRandom()
    {
        $faker = \Faker\Factory::create();

        $n = $faker->numberBetween($min = 1, $max = 10);

        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id, $n);

        $this->assertInternalType('array', $codes);
        $this->assertCount($n, $codes);
    }

    public function testScratchFindBySecretID()
    {
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(Scratch::DEFAULT_CODES, $chk);

        // Should check for the arrays to contain same codes
        // but database could reorder them, for now I will skip this check
    }


    public function testVerifyAbsentScratchCode()
    {
        $this->assertNotTrue(Scratch::validateCode(99, '12345678'));
    }

    public function testVerifyWrongScratchCode()
    {
        $s = $this->createRandomSecret();
        $c = $this->createScratchForID($s->id);

        $this->assertNotTrue(Scratch::validateCode($s->id, '12345678'));
    }

    public function testVerifyScratchCodeAndDelete()
    {
        $faker = \Faker\Factory::create();

        $n = $faker->numberBetween($min = 0, $max = Scratch::DEFAULT_CODES - 1);
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertTrue(Scratch::validateCode($s->id, $codes[$n]->code));

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(Scratch::DEFAULT_CODES - 1, $chk);
    }

    public function testVerifyScratchCodeNotDelete()
    {
        $faker = \Faker\Factory::create();

        $n = $faker->numberBetween($min = 0, $max = Scratch::DEFAULT_CODES - 1);
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertTrue(Scratch::validateCode($s->id, $codes[$n]->code, false));

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(Scratch::DEFAULT_CODES, $chk);

        foreach($chk as $c)
            $this->assertNotEqual($codes[$n], $c->code);
    }

    public function testCreateAndRemoveScratchCodes()
    {
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $this->assertTrue(Scratch::remove($s->id));

        $chk = Scratch::findBySecretID($s->id);
        $this->assertCount(0, $chk);
    }
}
