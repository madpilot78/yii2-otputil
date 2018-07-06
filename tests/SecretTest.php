<?php

namespace madpilot78\otputil\tests;

use chillerlan\Authenticator\Base32;
use madpilot78\otputil\models\Secret;
use madpilot78\otputil\models\Scratch;

class SecretTest extends TestCase
{
    /**
     * Test creating and validating a Secret
     *
     * @return void
     */
    public function testCreatingSecret()
    {
        $s = $this->createRandomSecret();

        $ss = Secret::findOne($s->id);
        $this->assertNotNull($ss);
        $this->assertEqualSecrets($s, $ss);
    }

    /**
     * Assert secret returned without options has default values
     *
     * @return void
     */
    public function testCreateDefaultSecret()
    {
        $s = new Secret();
        $this->assertValidateSecret($s);
        $this->assertTrue($s->save());
        $ss = Secret::findOne($s->id);
        $this->assertNotNull($ss);
        $this->assertEquals(Secret::DEFAULT_DIGITS, $ss->digits);
        $this->assertEquals(Secret::DEFAULT_MODE, $ss->mode);
        $this->assertEquals(Secret::DEFAULT_ALGO, $ss->algo);
        $this->assertEquals(Secret::DEFAULT_PERIOD, $ss->period);
    }

    /**
     * Test Secret cannot be modified
     *
     * @return void
     */
    public function testCannotModifySecret()
    {
        $s = $this->createRandomSecret();
        $data = [];
        $this->getSecretData($s, $data);

        $ndata = $this->imagineSecret();
        $this->populateSecret($s, $ndata);
        $this->assertValidateSecret($s);
        $this->assertNotTrue($s->save());

        $ss = Secret::findOne($data['id']);
        $this->assertNotNull($ss);
        $this->assertSecretEqualsData($data, $ss);
    }

    /**
     * Test that creating a Secret with confirmed == true fails
     *
     * @return void
     */
    public function testCantCreateConfirmedSecret()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $this->populateSecret($s, $data);
        $s->confirmed = true;
        $this->assertValidateSecret($s);
        $this->assertNotTrue($s->save());
    }

    /**
     * Test confirming a Secret
     *
     * @return void
     */
    public function testConfirmSecret()
    {
        $s = $this->createRandomSecret();

        $this->assertNotTrue($s->isconfimed());
        $this->assertTrue($s->confirm());
        $ss = Secret::findOne($s->id);
        $this->assertNotNull($ss);
        $this->assertTrue($ss->isconfimed());
        $this->assertNotTrue($ss->confirm());
    }

    /**
     * Test incrementing the counter on a Secret
     *
     * @return void
     */
    public function testSecretIncrementCounter()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'hotp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());
        $this->assertTrue($s->refresh());

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

    /**
     * Test updating the counter on a Secret
     *
     * @return void
     */
    public function testSecretUpdateCounter()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'hotp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());
        $this->assertTrue($s->refresh());

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

    /**
     * Test incrementing the counter on a TOTP Secret fails
     *
     * @return void
     */
    public function testCantIncrementCounterTOTP()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'totp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());
        $this->assertTrue($s->refresh());

        $cnt = $s->counter;

        $this->assertInternalType('int', $cnt);
        $this->assertEquals(1, $cnt);

        $ncnt = $s->incrementCounter();

        $this->assertNotTrue($ncnt);

        $chk = $s->counter;

        $this->assertInternalType('int', $chk);
        $this->assertEquals($cnt, $chk);
    }

    /**
     * Test updating the counter on a TOTP Secret fails
     *
     * @return void
     */
    public function testCantUpdateCounterTOTP()
    {
        $s = new Secret();
        $data = $this->imagineSecret();
        $data['mode'] = 'totp';
        $this->populateSecret($s, $data);
        $this->assertTrue($s->save());
        $this->assertTrue($s->refresh());

        $cnt = $s->counter;

        $this->assertInternalType('int', $cnt);
        $this->assertEquals(1, $cnt);

        $ncnt = $s->updateCounter(42);

        $this->assertNotTrue($ncnt);

        $chk = $s->counter;

        $this->assertInternalType('int', $chk);
        $this->assertEquals($cnt, $chk);
    }

    /**
     * Test Secret can be deleted
     *
     * @return void
     */
    public function testDeletingSecret()
    {
        $s = $this->createRandomSecret();
        $this->assertEquals(1, $s->delete());
        $this->assertNull(Secret::findOne($s->id));
    }

    /**
     * Test Secret->Scratch relation
     *
     * @return void
     */
    public function testGetScratches()
    {
        $s = $this->createRandomSecret();
        $codes = Scratch::createScratches($s->id);

        $q = $s->getScratches();

        $this->assertEquals(Scratch::DEFAULT_CODES, $q->count());
        $scratches = $q->all();
        $this->assertInstanceOf(Scratch::class, $scratches[0]);
    }
}
