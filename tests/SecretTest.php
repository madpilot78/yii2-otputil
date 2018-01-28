<?php

namespace yii2-otputil\test\secret;

use chillerlan\Authenticator\Base32;
use mad\yii2-otputil\Secret;

class SecretTest extends TestCase
{
    protected $base32;
    protected $s;

    protected function setUp()
    {
        $this->mockApplication();
        $this->base32 = new Base32();
        $this->s = new Secret();
    }

    // Tests:

    public function testGenGetSecret()
    {
        $sid = $this->s->genSecret();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $secret_coded = $this->s->getSecret($sid);
        $this->assertRegExp('/[A-Z2-7=]*/', $secret_coded);

        $secret = $this->base32->fromString($secret);
        $this->assertInternalType('string', $secret);
        $this->assertNotEmpty($secret);
    }

    public function testGenSetSecretIs NewID()
    {
        $sid = $this->s->genSecret();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $secret = random_bytes(20);
        $secret_coded = $this->base32->toString($secret_coded);

        $newsid = $this->s->setSecret();
        $this->assertInternalType('int', $newsid);
        $this->assertNotEquals($sid, $newsid);
    }

    public function testSetGetSecret()
    {
        $secret = random_bytes(20);
        $secret_coded = $this->base32->toString($secret_coded);

        $sid = $this->s->setSecret();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $secret_coded = $this->s->getSecret($sid);
        $this->assertRegExp('/[A-Z2-7=]*/', $secret_coded);

        $gotsecret = $this->base32->fromString($gotsecret);
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
        $chksid = $secret_coded = $this->s->getSecret($sid);
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
}
