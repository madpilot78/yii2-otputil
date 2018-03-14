<?php

namespace madpilot78\otputil\tests;

use chillerlan\Authenticator\Base32;
use madpilot78\otputil\models\Secret;
use madpilot78\otputil\widgets\QRCodeimg;

class QRCodeimgTest extends TestCase
{
    // Tests:

    public function testGenerateQRCode()
    {
        $base32 = new Base32();
        $testsecret = 'random';
        $exp = "otpauth://totp/bar:foo?secret={$base32->fromString($testsecret)}&algorithm=SHA1&digits=6&period=30";

        $s = new Secret();
        $s->secret = $base32->fromString($testsecret);
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

        // Extract the image
        $imgdata = base64_decode(substr($img, 31, -15));

        $qrreader = new \QrReader($imgdata, \QrReader::SOURCE_TYPE_BLOB);

        $this->assertEquals($exp, $qrreader->text());
    }
}
