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

    // Tests:

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
