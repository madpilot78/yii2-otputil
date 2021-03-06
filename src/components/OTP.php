<?php

namespace madpilot78\otputil\components;

use chillerlan\Authenticator\Authenticator;
use chillerlan\Authenticator\Base32;
use madpilot78\otputil\models\Scratch;
use madpilot78\otputil\models\Secret;
use Yii;
use yii\base\Component;

/**
 * OTP Component class.
 */
class OTP extends Component
{
    /**
     * @const DEFAULT_SLIP Default slip allowed
     */
    const DEFAULT_SLIP = 2;

    /**
     * @const DEFAULT_TIMEOUT Default timeout for unconfirmed secrets
     */
    const DEFAULT_TIMEOUT = 900;

    /**
     * @const DEFAULT_GCCHANCE Default chance in percent point of garbage
     * collection to run
     */
    const DEFAULT_GCCHANCE = 5;

    /**
     * @var int digits length of OTPs
     */
    public $digits = Secret::DEFAULT_DIGITS;

    /**
     * @var string OTP mode of operation (totp or hotp)
     */
    public $mode = Secret::DEFAULT_MODE;

    /**
     * @var string algorithm to be used to derive OTPs
     */
    public $algo = Secret::DEFAULT_ALGO;

    /**
     * @var int period seconds a TOTP is valid
     */
    public $period = Secret::DEFAULT_PERIOD;

    /**
     * @var int number of scratch codes to be generated
     */
    public $scratchnum = Scratch::DEFAULT_CODES;

    /**
     * @var int slip allowed, odd numbers get in the past
     */
    public $slip = self::DEFAULT_SLIP;

    /**
     * @var int timout for unconfirmed secrets, seconds
     */
    public $unconfirmedTimeout = self::DEFAULT_TIMEOUT;

    /**
     * @var int percent of chance of unconfirmed Secret GC to run
     */
    public $gcChance = self::DEFAULT_GCCHANCE;

    /**
     * @var Secret The Secret AR object we are using
     */
    protected $secret = null;

    /**
     * Creates a correctly configured Authenticator object.
     *
     * @return Authenticator|null The Authenticator object configured as required
     */
    protected function getAuth()
    {
        if (is_null($this->secret)) {
            return; // @codeCoverageIgnore
        }

        $auth = new Authenticator();
        $auth->setDigits($this->digits);
        $auth->setMode($this->mode);
        $auth->setAlgorithm($this->algo);
        $auth->setPeriod($this->period);
        $auth->setSecret($this->secret->secret);

        return $auth;
    }

    /**
     * Decides whether to garbage collect old unconfirmed Secrets.
     *
     * First simple implementation, just use random chance.
     *
     * @return
     */
    protected function willGC()
    {
        return rand(0, 99) < $this->gcChance;
    }

    /**
     * Actually perform the check, allows disabling scratch codes usage.
     *
     * @param string $code          The code to be verified
     * @param bool   $acceptScratch if scratch codes should be accepted
     *
     * @return bool if verification succeeded
     */
    protected function doverifycode(string $code, $acceptScratch = true)
    {
        if (is_null($this->secret)) {
            return false; // @codeCoverageIgnore
        }

        if (strlen($code) != $this->secret->digits && strlen($code) != Scratch::SCRATCH_LENGTH) {
            return false;
        }

        if (!ctype_digit($code)) {
            return false;
        }

        $auth = $this->getAuth();

        if ($auth->verify($code, $this->mode == 'totp' ? time() : $this->secret->counter, $this->slip)) {
            if ($this->mode == 'hotp') {
                $this->secret->incrementCounter();
            }

            return true;
        }

        if ($acceptScratch && Scratch::verifyCode($code, $this->secret->id)) {
            return true;
        }

        return false;
    }

    /**
     * Creates a new Secret and sets as active one. Also performs expiration
     * of unconfirmed Secrets.
     *
     * @return int ID of the created Secret AR object
     */
    public function create()
    {
        $base32 = new Base32();

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $s = new Secret();
            $s->secret = $base32->fromString(random_bytes(20));
            $s->digits = $this->digits;
            $s->mode = $this->mode;
            $s->algo = $this->algo;
            $s->period = $this->period;
            $s->save();

            Scratch::createScratches($s->id);

            $transaction->commit();
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
        // @codeCoverageIgnoreEnd

        $this->secret = $s;

        if ($this->willGC()) {
            $this->cleanupUnconfirmed(); // @codeCoverageIgnore
        }

        return $s->id;
    }

    /**
     * Make the object fetch and use the Secret with ID $sid. Also performs
     * expiration of unconfirmed Secrets.
     *
     * @param int $sid the Secret ID we are going to use
     *
     * @return bool if the requested Secret was found
     */
    public function get(int $sid)
    {
        $this->secret = Secret::findOne($sid);

        if ($this->willGC()) {
            $this->cleanupUnconfirmed(); // @codeCoverageIgnore
        }

        return !is_null($this->secret);
    }

    /**
     * Returns ID os Secret object we are using.
     *
     * @return int|null ID of Secret AR object, null if not set
     */
    public function getSID()
    {
        if (is_null($this->secret)) {
            return;
        }

        return $this->secret->id;
    }

    /**
     * Returns an array of scratch codes.
     *
     * @return array of strings each a scratch code available for use
     */
    public function getScratches()
    {
        if (is_null($this->secret)) {
            return;
        }

        $q = $this->secret->getScratches();
        $scratches = $q->all();

        $codes = [];
        foreach ($scratches as $s) {
            $codes[] = $s->code;
        }

        return $codes;
    }

    /**
     * Returns the Base32 encoded secret.
     *
     * @return string|null Base32 encoded secret
     */
    public function getSecret()
    {
        if (is_null($this->secret)) {
            return;
        }

        return $this->secret->secret;
    }

    /**
     * Checks if the bound Secret is confirmed.
     *
     * @return bool if Secret is confirmed
     */
    public function isConfirmed()
    {
        if (is_null($this->secret)) {
            return false;
        }

        return $this->secret->isconfimed();
    }

    /**
     * Cofirms a secret, requires a valid OTP, scratch codes not accepted here.
     *
     * Allows for slip as configured.
     *
     * @param string $code The code to be checked while confirming
     *
     * @return bool if confirmation was successful
     */
    public function confirm(string $code)
    {
        if (is_null($this->secret)) {
            return false;
        }

        if ($this->doverifycode($code, false)) {
            return $this->secret->confirm();
        }

        return false;
    }

    /**
     * Verifies a code, both scratch and OTP, allowing for slip.
     *
     * Scratch codes will be removed from DB once used.
     *
     * @param string $code The code to be checked while confirming
     *
     * @return bool if $code was successfully verified
     */
    public function verify(string $code)
    {
        if (is_null($this->secret)) {
            return false;
        }

        return $this->doverifycode($code, true);
    }

    /**
     * Generates an OTP for the curent object, based on bound Secret.
     *
     * @return string|null current OTP
     */
    public function generate()
    {
        if (is_null($this->secret)) {
            return;
        }

        $auth = $this->getAuth();

        return $auth->code();
    }

    /**
     * Invalidates Scratch codes.
     *
     * @return bool if operation was successful
     */
    public function invalidateScratches()
    {
        if (is_null($this->secret)) {
            return false;
        }

        return Scratch::remove($this->secret->id);
    }

    /**
     * Forces removing all scratch codes and regenerates them.
     *
     * @return bool if operation was successful
     */
    public function regenerateScrathes()
    {
        if (is_null($this->secret)) {
            return false;
        }

        if (!$this->invalidateScratches()) {
            return false; // @codeCoverageIgnore
        }

        Scratch::createScratches($this->secret->id);

        return true;
    }

    /**
     * Removes the bound Secret and Scratch codes from the DB.
     *
     * @return bool if operation was successful
     */
    public function forget()
    {
        if (is_null($this->secret)) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!Scratch::remove($this->secret->id)) {
                throw new Exception('Failed to remove scratch codes'); // @codeCoverageIgnore
            }
            $this->secret->delete();
            $transaction->commit();
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
        // @codeCoverageIgnoreEnd

        $this->secret = null;

        return true;
    }

    /**
     * Remove unconfirmed secrets older than $unconfirmedTimeout.
     */
    public function cleanupUnconfirmed()
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $secrets = Secret::find()
                ->where(['<', 'created_at', time() - $this->unconfirmedTimeout])
                ->andWhere(['confirmed' => false])
                ->all();

            foreach ($secrets as $s) {
                if (!Scratch::remove($s->id)) {
                    throw new Exception('Failed to remove scratch codes'); // @codeCoverageIgnore
                }
                $s->delete();
            }

            $transaction->commit();
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
        // @codeCoverageIgnoreEnd
    }
}
