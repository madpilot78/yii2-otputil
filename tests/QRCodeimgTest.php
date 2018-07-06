<?php

namespace madpilot78\otputil\tests;

use chillerlan\Authenticator\Base32;
use Zxing\QrReader;
use madpilot78\otputil\models\Secret;
use madpilot78\otputil\widgets\QRCodeimg;

class QRCodeimgTest extends TestCase
{
    /**
     * Data provider to test all options
     *
     * @return Array
     */
    public function optionProvider()
    {
        $ret = [];

        foreach (['totp', 'hotp'] as $type) {
            foreach (['baz', false] as $issuer) {
                $ret[] = [$type, $issuer];
            }
        }

        return $ret;
    }

    /**
     * Test generating one QRCode
     *
     * @param string $type
     * @param string $issuer
     * @return void
     *
     * @dataProvider optionProvider
     */
    public function testGenerateQRCode(string $type, string $issuer)
    {
        $base32 = new Base32();
        $testsecret = $base32->fromString(random_bytes(20));
        $exp = 'otpauth://' . $type . '/bar:foo?secret=' . $testsecret . '&algorithm=SHA1&digits=6';
        if ($issuer) {
            $exp .= '&issuer=' . $issuer;
        }
        if ($type == 'totp') {
            $exp .= '&period=30';
        } elseif ($type == 'hotp') {
            $exp .= '&counter=1';
        }

        $s = new Secret();
        $s->secret = $testsecret;
        $s->digits = 6;
        $s->mode = $type;
        $s->algo = 'SHA1';
        $s->period = 30;
        $s->save();

        $opts = [
            'sid' => $s->id,
            'username' => 'foo',
            'label' => 'bar'
        ];

        if ($issuer) {
            $opts['issuer'] = $issuer;
        }

        ob_start();
        $img = QRCodeimg::widget($opts);
        ob_end_clean();

        // Extract the image
        $imgdata = base64_decode(substr($img, 31, -15));

        $qrreader = new QrReader($imgdata, QrReader::SOURCE_TYPE_BLOB);

        $this->assertEquals($exp, $qrreader->text());
    }
}
