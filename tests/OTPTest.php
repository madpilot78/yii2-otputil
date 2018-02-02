<?php

namespace yii2-otputil\test\OTP;

use chillerlan\Authenticator\Authenticator;
use chillerlan\Authenticator\Base32;
use mad\otputil\OTP;
use mad\otputil\Secret;

class SecretTest extends TestCase
{
    protected $auth;
    protected $base32;
    protected $s;
    protected $o;

    protected function setUp()
    {
        parent::setUp();
        $this->auth = new Authenticator();
        $this->base32 = new Base32();
        $this->s = new Secret();
        $this->otp = new OTP();
    }

    // Tests:

    public function testGetOTP()
    {
        $sid = $this->s->genSecret();
        $secret = $this->s->getSecret($sid);

        $totp = $this->otp->getTOTP($sid);
        $this->assertEquals(6, strlen($totp));
        $this->assertStringMatchesFormat('%d', $totp);

        $this->auth->setSecret($secret);
        $code = $this->auth->code();
        $this->assertEquals($code, $totp);

        $chk = $this->auth->verify($totp);
        $this->assertTrue($chk);
    }

    public function testCheckOTP()
    {
        $this->auth->createSecret();
        $secret = $this->auth->getSecret();
        $totp = $this->auth->code();
        $sid = $this->s->setSecret($secret);
        $chk = $this->otp->checkTOTP($sid, $totp);
        $this->assertTrue($chk);

        $totp = '012345';
        $chk = $this->otp->checkTOTP($totp);
        $this->assertNotTrue($chk);
    }
}
