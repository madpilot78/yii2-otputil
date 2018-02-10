<?php

namespace mad\otputil\tests;

use chillerlan\Authenticator\Base32;
use mad\otputil\models\Secret;

class SecretTest extends TestCase
{
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

    protected function populateSecret(Secret &$s, Array $data)
    {
        $s->secret = $data["secret"];
        $s->digits = $data["digits"];
        $s->mode = $data["mode"];
        $s->algo = $data["algo"];
        $s->period = $data["period"];
    }

    protected function getSecretData(Secret $s, Array &$data)
    {
        $data["id"] = $s->id;
        $data["secret"] = $s->secret;
        $data["digits"] = $s->digits;
        $data["mode"] = $s->mode;
        $data["algo"] = $s->algo;
        $data["period"] = $s->period;
    }

    protected function assertValidate($s)
    {
        $r = $s->validate();
        if ($s->HasErrors())
            var_dump($s->getErrors());
        $this->assertTrue($r);
    }

    protected function assertNotValidate($s)
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
        $this->assertValidate($s);
        $this->assertTrue($s->save());
        return $s;
    }

    protected function assertEqualSecrets(Secret $exp, Secret $act)
    {
        $this->assertEquals($exp->id, $act->id);
        $this->assertEquals($exp->secret, $act->secret);
        $this->assertEquals($exp->digits, $act->digits);
        $this->assertEquals($exp->mode, $act->mode);
        $this->assertEquals($exp->algo, $act->algo);
        $this->assertEquals($exp->period, $act->period);
    }

    protected function assertSecretEqualsData(Array $data, Secret $act)
    {
        $this->assertEquals($data['id'], $act->id);
        $this->assertEquals($data['secret'], $act->secret);
        $this->assertEquals($data['digits'], $act->digits);
        $this->assertEquals($data['mode'], $act->mode);
        $this->assertEquals($data['algo'], $act->algo);
        $this->assertEquals($data['period'], $act->period);
    }

    // Tests:

    public function testCreatingSecret()
    {
        $s = $this->createRandomSecret();

        $ss = Secret::findOne($s->id);
        $this->assertNotNull($ss);
        $this->assertEqualSecrets($s, $ss);
    }

    public function testCreateDefaultSecret()
    {
        $s = new Secret();
        $this->assertValidate($s);
        $this->assertTrue($s->save());
        $ss = Secret::findOne($s->id);
        $this->assertNotNull($ss);
        $this->assertEquals($ss->digits, Secret::DEFAULT_DIGITS);
        $this->assertEquals($ss->mode, Secret::DEFAULT_MODE);
        $this->assertEquals($ss->algo, Secret::DEFAULT_ALGO);
        $this->assertEquals($ss->period, Secret::DEFAULT_PERIOD);
    }

    public function testCannotModifySecret()
    {
        $s = $this->createRandomSecret();
        $data = [];
        $this->getSecretData($s, $data);

        $ndata = $this->imagineSecret();
        $this->populateSecret($s, $ndata);
        $this->assertValidate($s);
        $this->assertNotTrue($s->save());

        $ss = Secret::findOne($s->id);
        $this->assertNotNull($ss);
        $this->assertSecretEqualsData($data, $s);
    }

    public function testCantCreateConfirmedSecret()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $this->populateSecret($s, $data);
        $s->confirmed = true;
        $this->assertNotValidate($s);
        $this->assertNotTrue($s->save());
    }

    public function testConfirmSecret()
    {
        $s = $this->createRandomSecret();

        $this->assertNotTrue($s->isconfimed());
        $this->assertTrue($s->confirm());
        $ss = Secret::findOne($s->id);
        $this->assertNotNull($ss);
        $this->assertTrue($ss->isconfimed());
    }

    public function testSecretIncrementCounter()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'hotp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());

        $cnt = $s->counter;

        $this->assertInternalType('int', $cnt);
        $this->assertEquals(1, $cnt);

        $ncnt = $s->incrementCounter();

        $this->assertInternalType('int', $ncnt);
        $this->assertEquals(2, $ncnt);

        $chk = $s->counter;

        $this->assertInternalType('int', $chk);
        $this->assertEquals($ncnt, $chk);
    }

    public function testSecretUpdateCounter()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'hotp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());

        $cnt = $s->counter;

        $this->assertInternalType('int', $cnt);
        $this->assertEquals(1, $cnt);

        $ncnt = $s->updateCounter(42);

        $this->assertInternalType('int', $ncnt);
        $this->assertEquals(42, $ncnt);

        $chk = $s->counter;

        $this->assertInternalType('int', $chk);
        $this->assertEquals($ncnt, $chk);
    }

    public function testCantIncrementCounterTOTP()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'totp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());

        $cnt = $s->counter;

        $this->assertInternalType('int', $cnt);
        $this->assertEquals(1, $cnt);

        $ncnt = $s->incrementCounter();

        $this->assertNotTrue($ncnt);

        $chk = $s->counter;

        $this->assertInternalType('int', $chk);
        $this->assertEquals($cnt, $chk);
    }

    public function testCantUpdateCounterTOTP()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'totp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());

        $cnt = $s->counter;

        $this->assertInternalType('int', $cnt);
        $this->assertEquals(1, $cnt);

        $ncnt = $s->updateCounter(42);

        $this->assertNotTrue($ncnt);

        $chk = $s->counter;

        $this->assertInternalType('int', $chk);
        $this->assertEquals($cnt, $chk);
    }

    public function testDeletingSecret()
    {
        $s = $this->createRandomSecret();
        $this->assertTrue($s->delete());
        $this->assertNull(Secret::findOne($s->id));
    }
}
