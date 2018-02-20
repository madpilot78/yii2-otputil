<?php

namespace mad\otputil\tests;

use chillerlan\Authenticator\Authenticator;
use chillerlan\Authenticator\Base32;
use mad\otputil\components\OTP;

class OTPTest extends TestCase
{
    // Tests:

    public function testNewOTP()
    {
        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);

        $sid = $otp->getSID();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);

        $scratches = $otp->getScratches();
        $this->assertCount($otp->scratchnum, $scratches);
        foreach ($scratches as $s) {
            $this->assertInternalType('string', $s);
            $this->assertEquals(8, strlen($s));
        }

        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
    }

    public function testConfirm()
    {
        $auth = new Authenticator;

        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);
        $secret = $otp->getSecret();
        $this->assertNotTrue($otp->isConfirmed());

        $auth->setSecret($secret);
        $this->assertNotTrue($otp->confirm('000000'));
        $this->assertNotTrue($otp->isConfirmed());
        $this->assertTrue($otp->confirm($auth->code()));
        $this->assertTrue($otp->isConfirmed());
        $this->assertNotTrue($otp->confirm($auth->code()));
    }

    public function textNoConfirmScratch()
    {
        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);
        $this->assertNotTrue($otp->isConfirmed());

        $scratches = $otp->getScratches();
        $s = $scratches[rand(0, $otp->scratchnum - 1)];
        $this->assertNotTrue($otp->confirm($s));
        $scratches = $otp->getScratches();
        $this->assertCount($otp->scratchnum, $scratches);
    }

    public function testGetOTP()
    {
        $auth = new Authenticator;

        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);
        $sid = $otp->getSID();
        $secret = $otp->getSecret();

        $gototp = OTP::getOTP($sid);
        $this->assertInstanceOf(OTP::class, $gototp);

        $auth->setSecret($secret);
        $this->assertTrue($gototp->confirm($auth->code()));        
    }

    public function testVerifyOTP()
    {
        $auth = new Authenticator;

        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);
        $auth->setSecret($otp->getSecret());
        $this->assertTrue($otp->confirm($auth->code()));

        $this->assertNotTrue($otp->verify('000000'));
        $this->assertTrue($otp->verify($auth->code()));
    }

    public function testVerifyScratch()
    {
        $auth = new Authenticator;

        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);
        $auth->setSecret($otp->getSecret());
        $this->assertTrue($otp->confirm($auth->code()));

        $scratches = $otp->getScratches();
        $code = $scratches[rand(0, $otp->scratchnum - 1)];
        $this->assertTrue($otp->verify($code));

        $chk = $otp->getScratches();
        $this->assertCount($otp->scratchnum - 1, $chk);
        foreach ($chk as $s)
            $this->assertNotEquals($code, $s);
    }

    public function testGenerate()
    {
        $auth = new Authenticator;

        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);
        $auth->setSecret($otp->getSecret());
        $this->assertTrue($otp->confirm($auth->code()));
        $code = $otp->generate();
        $this->assertInternalType('string', $code);
        $this->assertEquals($otp->length, strlen($code));
        $this->assertTrue($auth->verify($code));
    }

    public function testForget()
    {
        $auth = new Authenticator;

        $otp = OTP::newOTP();
        $this->assertInstanceOf(OTP::class, $otp);
        $sid = $otp->getSID();
        $auth->setSecret($otp->getSecret());
        $this->assertTrue($otp->confirm($auth->code()));

        $otp->forget();

        $this->assertNotTrue(OTP::getOTP($sid));
    }
}
