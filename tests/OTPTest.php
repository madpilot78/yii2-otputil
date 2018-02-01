<?php

namespace yii2-otputil\test\OTP;

use chillerlan\Authenticator\Base32;
use mad\otputil\OTP;
use mad\otputil\Secret;

class SecretTest extends TestCase
{
    protected $base32;
    protected $s;
    protected $p;

    protected function setUp()
    {
        $this->mockApplication();
        $this->base32 = new Base32();
        $this->s = new Secret();
        $this->p = new OTP();
    }

    // Tests:

    public function testGetCheckOTP()
    {
        // to be implemented:
        // generate one OTP and check it
    }
}
