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
     * Factory function to generate a new OTP object populating Secret
     * and Scratch codes
     *
     * @return OTP object created
     */
    public static function newOTP()
    {
    }

    /**
     * Factory method returning an OTP object populated with Secret $sid
     * and relates Scratch codes
     *
     * @param int $sid the Secret ID to be retrived to populate the new OTP object
     * @return OTP object created
     */
    public static function getOTP(int $sid)
    {
    }

    /**
     * Returns the Secret ID of the bound Secret AR object
     *
     * @return int Secret ID
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
}
