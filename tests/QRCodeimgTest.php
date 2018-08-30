<?php

namespace madpilot78\otputil\tests;

use chillerlan\Authenticator\Base32;
use madpilot78\otputil\models\Secret;
use madpilot78\otputil\widgets\QRCodeimg;
use yii\web\ServerErrorHttpException;
use Zxing\QrReader;

class QRCodeimgTest extends TestCase
{
    /**
     * Data provider to test all options.
     *
     * @return array
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
     * Test generating one QRCode.
     *
     * @param string $type
     * @param string $issuer
     *
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
            'sid'      => $s->id,
            'username' => 'foo',
            'label'    => 'bar'
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

    /**
     * Test wrong image format causes exception.
     *
     * @return void
     */
    public function testWrongImageFormatException()
    {
        $this->expectException(ServerErrorHttpException::class);

        $base32 = new Base32();
        $testsecret = $base32->fromString(random_bytes(20));

        $s = new Secret();
        $s->secret = $testsecret;
        $s->digits = 6;
        $s->mode = 'totp';
        $s->algo = 'SHA1';
        $s->period = 30;
        $s->save();

        $img = QRCodeimg::widget([
            'sid'      => $s->id,
            'fmt'      => 'unkn',
            'username' => 'foo',
            'label'    => 'bar'
        ]);
    }

    /**
     * Test wrong error correction level causes exception.
     *
     * @return void
     */
    public function testWrongecLevelException()
    {
        $this->expectException(ServerErrorHttpException::class);

        $base32 = new Base32();
        $testsecret = $base32->fromString(random_bytes(20));

        $s = new Secret();
        $s->secret = $testsecret;
        $s->digits = 6;
        $s->mode = 'totp';
        $s->algo = 'SHA1';
        $s->period = 30;
        $s->save();

        $img = QRCodeimg::widget([
            'sid'      => $s->id,
            'ecLevel'  => 'X',
            'username' => 'foo',
            'label'    => 'bar'
        ]);
    }

    /**
     * Test username with colon causes exception.
     *
     * @return void
     */
    public function testColonInUsernameException()
    {
        $this->expectException(ServerErrorHttpException::class);

        $base32 = new Base32();
        $testsecret = $base32->fromString(random_bytes(20));

        $s = new Secret();
        $s->secret = $testsecret;
        $s->digits = 6;
        $s->mode = 'totp';
        $s->algo = 'SHA1';
        $s->period = 30;
        $s->save();

        $img = QRCodeimg::widget([
            'sid'      => $s->id,
            'username' => 'f:oo',
            'label'    => 'bar'
        ]);
    }

    /**
     * Test label with colon causes exception.
     *
     * @return void
     */
    public function testColonInLabelException()
    {
        $this->expectException(ServerErrorHttpException::class);

        $base32 = new Base32();
        $testsecret = $base32->fromString(random_bytes(20));

        $s = new Secret();
        $s->secret = $testsecret;
        $s->digits = 6;
        $s->mode = 'totp';
        $s->algo = 'SHA1';
        $s->period = 30;
        $s->save();

        $img = QRCodeimg::widget([
            'sid'      => $s->id,
            'username' => 'foo',
            'label'    => 'ba:r'
        ]);
    }

    /**
     * Test wrong sid causes exception.
     *
     * @return void
     */
    public function testWrongSidException()
    {
        $this->expectException(ServerErrorHttpException::class);

        $img = QRCodeimg::widget([
            'sid'      => 42,
            'username' => 'foo',
            'label'    => 'bar'
        ]);
    }

    /**
     * Test null sid causes exception.
     *
     * @return void
     */
    public function testNullSidException()
    {
        $this->expectException(ServerErrorHttpException::class);

        $img = QRCodeimg::widget([
            'username' => 'foo',
            'label'    => 'bar'
        ]);
    }
}
