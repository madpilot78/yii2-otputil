<?php

namespace otputil\test\secret;

use chillerlan\Authenticator\Base32;
use mad\otputil\Secret;

class SecretTest extends TestCase
{
    protected $base32;

    protected function setUp()
    {
        parent::setUp();
        $this->base32 = new Base32();
    }

    protected function imagineSecret()
    {
        $faker = \Faker\Factory::create();

        $digitsValidator = function($digit) {
            return in_array($digit, [6, 8]);
        };

        return [
            'secret' => $this->base32->fromString(random_bytes(20)),
            'digits' => $faker->valid($digitValidator)->boolean(),
            'mode' => $faker->randomElement('totp', 'hotp'),
            'algo' => $faker->randomElement('SHA1', 'SHA256', 'SHA512'),
            'period' => $faker->numberBetween($min = 15, $max = 60)
        ];
    }

    // Tests:

    public function testCreatingSecret()
    {
        $s = new Secret();
    }

    public function testCreatingWithDefinedSecret()
    {
    }

    public function testCantCreateConfirmedSecret()
    {
    }

    public function testSetGetSecret()
    {
    }

    public function testConfirmSecret()
    {
    }

    public function testDeletingSecret()
    {
    }


/*
 * Keeping commented for reference, undergoing refactoring

    public function testGenGetSecret()
    {
        $sid = $this->s->genSecret();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $secret_encoded = $this->s->getSecret($sid); */
//        $this->assertRegExp('/[A-Z2-7=]*/', $secret_encoded);
/*
        $secret = $this->base32->fromString($secret_encoded);
        $this->assertInternalType('string', $secret);
        $this->assertNotEmpty($secret);
    }

    public function testGenSetSecretIs NewID()
    {
        $sid = $this->s->genSecret();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $secret = random_bytes(20);
        $secret_encoded = $this->base32->toString($secret);

        $newsid = $this->s->setSecret($secret_encoded);
        $this->assertInternalType('int', $newsid);
        $this->assertNotEquals($sid, $newsid);
    }

    public function testSetGetSecret()
    {
        $secret = random_bytes(20);
        $secret_encoded = $this->base32->toString($secret);

        $sid = $this->s->setSecret($secret_encoded);
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $secret_encoded = $this->s->getSecret($sid); */
//        $this->assertRegExp('/[A-Z2-7=]*/', $secret_encoded);
/*
        $gotsecret = $this->base32->fromString($secret_encoded);
        $this->assertInternalType('string', $gotsecret);
        $this->assertNotEmpty($gotsecret);
        $this->assertEquals($secret, $gotsecret);
    }

    public function testDelSecret()
    {
        $sid = $this->s->genSecret();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $this->s->delSecret($sid);
        $chksid = $this->s->getSecret($sid);
        $this->assertNull($chksid);
        $this->assertNotEquals($sid, $chksid);
    }

    public function testConfirmSecret()
    {
        $sid = $this->s->genSecret();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $chk = $this->s->isSecretConfirmed($sid);
        $this->assertNotTrue($chk);

        $this->s->confirmSecret($sid);
        $chk = $this->s->isSecretConfirmed($sid);
        $this->assertTrue($chk);
    }
*/
}
