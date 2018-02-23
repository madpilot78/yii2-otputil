<?php

namespace mad\otputil\tests;

use chillerlan\Authenticator\Base32;
use mad\otputil\models\Secret;
use mad\otputil\widgets\QRCodeimg;

class QRCodeimgTest extends TestCase
{
    // Tests:

    public function testGenerateQRCode()
    {
        $base32 = new Base32();
        $exp = '<img src="data:image/png;base64,ZZZ" alt="QRCode">';

        $s = new Secret();
        $s->secret = $base32->fromString('random');
        $s->digits = 6;
        $s->mode = 'totp';
        $s->algo = 'SHA1';
        $s->period = 30;
        $s->save();

        ob_start();
        $img = QRCodeimg::widget([
                    'sid' => $s->id,
                    'username' => 'foo',
                    'label' => 'bar'
                ]);
        ob_end_clean();

        $this->assertEquals($exp, $img);
    }
}
