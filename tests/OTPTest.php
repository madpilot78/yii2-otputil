<?php

namespace madpilot78\otputil\tests;

use Yii;
use chillerlan\Authenticator\Authenticator;
use chillerlan\Authenticator\Base32;
use madpilot78\otputil\components\OTP;

class OTPTest extends TestCase
{
    // Tests:

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

    public function testConfirm()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator;

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

    public function testGet()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator;

        $sid = $otp->create();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);

        $nsid = $otp->create();
        $this->assertNotEquals($nsid, $sid);

        $this->assertTrue($otp->get($sid));

        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));
    }

    public function testVerify()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator;

        $sid = $otp->create();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));

        $this->assertNotTrue($otp->verify('000000'));
        $this->assertTrue($otp->verify($auth->code()));
    }

    public function testVerifyScratch()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator;

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

    public function testGenerate()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator;

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

    public function testInvalidateScratches()
    {
        $otp = Yii::$app->otp;
        $otp->create();

        $this->assertTrue($otp->invalidateScratches());
        $scratches = $otp->getScratches();
        $this->assertCount(0, $scratches);
    }

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

    public function testForget()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator;

        $otp->create();
        $sid = $otp->getSID();
        $secret = $otp->getSecret();
        $this->assertInternalType('string', $secret);
        $auth->setSecret($secret);
        $this->assertTrue($otp->confirm($auth->code()));

        $otp->forget();

        $this->assertNotTrue($otp->getSID($sid));
    }

    public function testCleanupUnconfirmed()
    {
        $otp = Yii::$app->otp;
        $auth = new Authenticator;
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
}
