<?php

namespace mad\otputil\components;

use Yii;
use yii\base\Component;
use mad\otputil\models\Secret;
use mad\otputil\models\Scratch;
use chillerlan\Authenticator\Authenticator;
use chillerlan\Authenticator\Base32;

/**
 * OTP Component class
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
     * @var secret The Secret AR object we are using
     */
    protected $secret = null;

    /**
     * Creates a correctly configured Authenticator object
     *
     * @return Authenticator|null The Authenticator object configured as required
     */
    protected function getAuth()
    {
        if (is_null($this->secret))
            return null;

        $auth = new Authenticator;
        $auth->setDigits($this->digits);
        $auth->setMode($this->mode);
        $auth->setAlgorithm($this->algo);
        $auth->setPeriod($this->period);
        $auth->setSecret($this->secret->secret);

        return $auth;
    }

    /**
     * Actually perform the check, allows disabling scratch codes usage
     *
     * @param string $code The code to be verified
     * @param boolean $acceptScratch if scratch codes should be accepted
     * @return boolean if verification succeeded
     */
    protected function doverifycode(string $code, $acceptScratch = true)
    {
        if (is_null($this->secret))
            return false;

        if (strlen($code) < Secret::ALLOWED_DIGITS[0])
            return false;

        $auth = $this->getAuth();

        if ($auth->verify($code, $this->mode == 'totp' ? time() : $this->counter, $this->slip)) {
            if ($this->mode == 'hotp')
                $this->secret->incrementCounter();

            return true;
        }

        if ($acceptScratch && Scratch::verifyCode($code, $this->secret->id))
            return true;

        return false;
    }

    /**
     * Creates a new Secret and sets as active one. Also performs expiration
     * of unconfirmed Secrets
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
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->secret = $s;

        return $s->id;
    }

    /**
     * Make the object fetch and use the Secret with ID $sid. Also performs
     * expiration of unconfirmed Secrets.
     *
     * @param int $sid the Secret ID we are going to use
     * @return bool if the requested Secret was found
     */
    public function get(int $sid)
    {
        $this->secret = Secret::findOne($sid);

        return !is_null($this->secret);
    }

    /**
     * Returns ID os Secret object we are using
     *
     * @return int|null ID of Secret AR object, null if not set
     */
    public function getSID()
    {
        if (is_null($this->secret))
            return null;

        return $this->secret->id;
    }

    /**
     * Returns an array of scratch codes
     *
     * @return Array of strings each a scratch code available for use
     */
    public function getScratches()
    {
        if (is_null($this->secret))
            return null;

        $q = $this->secret->getScratches();
        $scratches = $q->all();

        $codes = [];
        foreach ($scratches as $s)
            $codes[] = $s->code;

        return $codes;
    }

    /**
     * Returns the Base32 encoded secret
     *
     * @return string|null Base32 encoded secret
     */
    public function getSecret()
    {
        if (is_null($this->secret))
            return null;

        return $this->secret->secret;
    }

    /**
     * Checks if the bound Secret is confirmed
     *
     * @return bool if Secret is confirmed
     */
    public function isConfirmed()
    {
        if (is_null($this->secret))
            return null;

        return $this->secret->isconfimed();
    }

    /**
     * Cofirms a secret, requires a valid OTP, scratch codes not accepted here
     *
     * Allows for slip as configured
     *
     * @param string $code The code to be checked while confirming
     * @return boolean if confirmation was successful
     */
    public function confirm(string $code)
    {
        if (is_null($this->secret))
            return false;

        if ($this->doverifycode($code, false))
            return $this->secret->confirm();

        return false;
    }

    /**
     * Verifies a code, both scratch and OTP, allowing for slip
     *
     * Scratch codes will be removed from DB once used
     *
     * @param string $code The code to be checked while confirming
     * @return boolean if $code was successfully verified
     */
    public function verify(string $code)
    {
        if (is_null($this->secret))
            return false;

        return $this->doverifycode($code, true);
    }

    /**
     * Generates an OTP for the curent object, based on bound Secret
     *
     * @return string|null current OTP
     */
    public function generate()
    {
        if (is_null($this->secret))
            return null;

        $auth = $this->getAuth();
        return $auth->code();
    }

    /**
     * Invalidates Scratch codes
     *
     * @return boolean if operation was successful
     */
    public function invalidateScratches()
    {
        if (is_null($this->secret))
            return false;

        return Scratch::remove($this->secret->id);
    }

    /**
     * Forces removing all scratch codes and regenerates them
     *
     * @return boolean if operation was successful
     */
    public function regenerateScrathes()
    {
        if (is_null($this->secret))
            return false;

        if(!$this->invalidateScratches())
            return false;

        Scratch::createScratches($this->secret->id);

        return true;
    }

    /**
     * Removes the bound Secret and Scratch codes from the DB
     *
     * @return boolean if operation was successful
     */
    public function forget()
    {
        if (is_null($this->secret))
            return false;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!Scratch::remove($this->secret->id)) throw new Exception("Failed to remove scratch codes");
            $this->secret->delete();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->secret = null;

        return true;
    }

    /**
     * Remove unconfirmed secrets older than $unconfirmedTimeout
     */
    public function cleanupUnconfirmed()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $secrets = Secret::find()
                ->where(['confirmed' => false])
                ->andWhere(['<', 'created_at', time() - $this->unconfirmedTimeout])
                ->all();
            foreach ($secrets as $s) {
                if (!Scratch::remove($s->id)) throw new Exception("Failed to remove scratch codes");
                $s->delete();
            }
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
