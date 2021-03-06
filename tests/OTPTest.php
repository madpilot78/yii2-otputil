<?php

namespace madpilot78\otputil\tests;

use chillerlan\Authenticator\Authenticator;
use madpilot78\otputil\components\OTP;
use Yii;

class OTPTest extends TestCase
{
    /**
     * Test creating a new OTP object returns object with all related parts.
     *
     * @return void
     */
    public function testCreate()
    {
        $otp = Yii::$app->otp;

        $sid = $otp->create();
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

    /**
     * Test getSID() works.
     *
     * @return void
     */
    public function testGetSID()
    {
        $otp = Yii::$app->otp;

        $sid = $otp->create();
        $this->assertInternalType('int', $sid);
        $this->assertNotEquals(0, $sid);
        $nsid = $otp->getSID();
        $this->assertInternalType('int', $sid);
        $this->assertEquals($sid, $nsid);
    }

    /**
     * Test OTP secret can be confirmed only using correct OTP.
     *
     * @return void
     */
    public function testConfirm()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator();

        $otp->create();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $this->assertNotTrue($otp->isConfirmed());

        $auth->setSecret($secret);
        $this->assertNotTrue($otp->confirm('000000'));
        $this->assertNotTrue($otp->isConfirmed());
        $this->assertTrue($otp->confirm($auth->code()));
        $this->assertTrue($otp->isConfirmed());
        $this->assertNotTrue($otp->confirm($auth->code()));
    }

    /**
     * Test OTP can't be confirmed using scratch code.
     *
     * @return void
     */
    public function textNoConfirmScratch()
    {
        $otp = Yii::$app->otp;

        $otp->create();
        $this->assertNotTrue($otp->isConfirmed());

        $scratches = $otp->getScratches();
        $s = $scratches[rand(0, $otp->scratchnum - 1)];
        $this->assertNotTrue($otp->confirm($s));
        $scratches = $otp->getScratches();
        $this->assertCount($otp->scratchnum, $scratches);
    }

    /**
     * Test Get allows changing the Secret currently in use.
     *
     * @return void
     */
    public function testGet()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator();

        $sid = $otp->create();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);

        $nsid = $otp->create();
        $this->assertNotEquals($nsid, $sid);

        $this->assertTrue($otp->get($sid));

        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));
    }

    /**
     * Data provider to test all OTP types.
     *
     * @return array
     */
    public function OTPTypes()
    {
        return [
            ['totp'],
            ['hotp']
        ];
    }

    /**
     * Test Verifying an OTP works only with correct OTP.
     *
     * @param $mode (totp|hotp)
     *
     * @return void
     *
     * @dataProvider OTPTypes
     */
    public function testVerify(string $mode)
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator();
        $auth->setMode($mode);

        $otp->mode = $mode;
        $sid = $otp->create();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));

        $this->assertNotTrue($otp->verify('000000'));
        $this->assertTrue($otp->verify($auth->code()));
    }

    /**
     * Test verifying a scrach code works and removes the scratch code.
     *
     * @return void
     */
    public function testVerifyScratch()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator();

        $otp->create();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));

        $scratches = $otp->getScratches();
        $code = $scratches[rand(0, $otp->scratchnum - 1)];
        $this->assertTrue($otp->verify($code));

        $chk = $otp->getScratches();
        $this->assertCount($otp->scratchnum - 1, $chk);
        foreach ($chk as $s) {
            $this->assertNotEquals($code, $s);
        }
    }

    /**
     * Check OTP object is able to generate valid OTPs.
     *
     * @return void
     */
    public function testGenerate()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator();

        $otp->create();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));
        $code = $otp->generate();
        $this->assertInternalType('string', $code);
        $this->assertEquals($otp->digits, strlen($code));
        $this->assertTrue($auth->verify($code));
    }

    /**
     * Test invalidating all scratch codes relate to an OTP to actually
     * remove all scratch codes.
     *
     * @return void
     */
    public function testInvalidateScratches()
    {
        $otp = Yii::$app->otp;
        $otp->create();

        $this->assertTrue($otp->invalidateScratches());
        $scratches = $otp->getScratches();
        $this->assertCount(0, $scratches);
    }

    /**
     * Test regenerating all Scratch codes actually works.
     *
     * @return void
     */
    public function testRegenrateScratches()
    {
        $otp = Yii::$app->otp;
        $otp->create();

        $oldscratches = $otp->getScratches();

        $this->assertTrue($otp->regenerateScrathes());
        $newscratches = $otp->getScratches();
        $this->assertCount($otp->scratchnum, $newscratches);

        $this->assertCount($otp->scratchnum, array_diff($oldscratches, $newscratches));
    }

    /**
     * Test Forget() removes the secret.
     *
     * @return void
     */
    public function testForget()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator();

        $otp->create();
        $sid = $otp->getSID();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));

        $otp->forget();

        $this->assertNotTrue($otp->getSID($sid));
    }

    /**
     * Test old unconfirmed secrets are cleaned up from the DB, while
     * confirmed ones are not removed.
     *
     * To check this condition I modify the timestamps in the DB before
     * triggering the checks
     *
     * @return void
     */
    public function testCleanupUnconfirmed()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator();
        $sid = [];

        $ts = time() - (60 * 30);

        $otp->create();
        $sid[] = $otp->getSID();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));

        $otp->create();
        $sid[] = $otp->getSID();

        $otp->create();
        $sid[] = $otp->getSID();

        // Hack, modify times in the DB to test various conditions
        Yii::$app->db->createCommand()->update(
            'otputil_secrets',
            [
                'created_at' => $ts,
                'updated_at' => $ts
            ],
            'id = :sida or id = :sidb',
            [
                ':sida' => $sid[0],
                ':sidb' => $sid[1]
            ]
        )->execute();

        $otp->cleanupUnconfirmed();

        $chk = $otp->get($sid[0]);
        $this->assertEquals($sid[0], $chk);

        $chk = $otp->get($sid[1]);
        $this->assertNotTrue($chk);

        $chk = $otp->get($sid[2]);
        $this->assertEquals($sid[2], $chk);
    }

    /**
     * Test that OTP methods fail when not having set a Secret to work on.
     *
     * This means if either of OTP::create() or OTP::get() is not called
     * before using the object
     *
     * @return void
     */
    public function testMethodsWithNullSecretFail()
    {
        $otp = Yii::$app->otp;

        $this->assertNotTrue($otp->getScratches());
        $this->assertNotTrue($otp->getSecret());
        $this->assertNotTrue($otp->isConfirmed());
        $this->assertNotTrue($otp->confirm('00000000'));
        $this->assertNotTrue($otp->verify('00000000'));
        $this->assertNotTrue($otp->generate());
        $this->assertNotTrue($otp->invalidateScratches());
        $this->assertNotTrue($otp->regenerateScrathes());
        $this->assertNotTrue($otp->forget());
    }

    /**
     * Test OTP validation with invalid OTPs.
     *
     * @return void
     */
    public function testInvalidOTPs()
    {
        $otp = Yii::$app->otp;

        $otp->create();
        $auth = new Authenticator();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);

        $this->assertNotTrue($otp->confirm('00'));
        $this->assertNotTrue($otp->confirm('foobar'));

        $this->assertTrue($otp->confirm($auth->code()));

        $this->assertNotTrue($otp->verify('00'));
        $this->assertNotTrue($otp->verify('foobar'));
    }
}
