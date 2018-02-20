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
    protected $secret;

    /**
     * Actually perform the check, allows disabling scratch codes usage
     *
     * @param string $code The code to be verified
     * @param boolean $acceptScratch if scratch codes should be accepted
     * @return boolean if verification succeeded
     */
    protected function doverifycode(string $code, $acceptScratch = true)
    {
    }

    /**
     * Creates a new Secret and sets as active one. Also performs expiration
     * of unconfirmed Secrets
     *
     * @return int ID of the created Secret AR object
     */
    public function create()
    {
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
    }

    /**
     * Returns ID os Secret object we are using
     *
     * @return int ID of Secret AR object
     */
    public function getSID()
    {
    }

    /**
     * Returns an array of scratch codes
     *
     * @return Array of strings each a scratch code available for use
     */
    public function getScratches()
    {
    }

    /**
     * Returns the Base32 encoded secret
     *
     * @return string Base32 encoded secret
     */
    public function getSecret()
    {
    }

    /**
     * Checks if the bound Secret is confirmed
     *
     * @return bool if Secret is confirmed
     */
    public function isConfirmed()
    {
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
    }

    /**
     * Generates an OTP for the curent object, based on bound Secret
     *
     * @return string current OTP
     */
    public function generate()
    {
    }

    /**
     * Invalidates Scratch codes
     *
     * @return boolean if operation was successful
     */
    public function invalidateScratches()
    {
    }

    /**
     * Forces removing all scratch codes and regenerates them
     *
     * @return boolean if operation was successful
     */
    public function regenerateScrathes()
    {
    }

    /**
     * Removes the bound Secrfet and Scratch codes from the DB
     *
     * @return boolean if operation was successful
     */
    public function forget()
    {
    }

    /**
     * Remove unconfirmed secrets older than $unconfirmedTimeout
     */
    public function cleanupUnconfirmed()
    {
    }
}
