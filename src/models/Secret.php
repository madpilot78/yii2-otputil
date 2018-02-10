<?php

namespace mad\otputil\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Connection;
use yii\di\Instance;
use chillerlan\Authenticator\Base32;

/**
 * Model class for OTP secrets.
 *
 * @property integer $id
 * @property string $secret
 * @property integer $digits
 * @property string mode
 * @property string algo
 * @property integer period
 * @property integer counter
 * @property boolean confirmed
 */
class Secret extends \yii\db\ActiveRecord
{
    /**
     * @const DEFAULT_DIGITS Default number of digits per OTP
     */
    const DEFAULT_DIGITS = 6;
    /**
     * @const ALLOWED_DIGITS Allowed OTP sizes
     */
    const ALLOWED_DIGITS = [6, 8];
    /**
     * @const DEFAULT_MODE Default mode of operation
     */
    const DEFAULT_MODE = 'totp';
    /**
     * @const ALLOWED_MODES Allowed modes of operation
     */
    const ALLOWED_MODES = ['totp', 'hotp'];
    /**
     * @const DEFAULT_ALGO Default algorithm to use
     */
    const DEFAULT_ALGO = 'SHA1';
    /**
     * @const ALLOWED_ALGOS Allowed algorithms
     */
    const ALLOWED_ALGOS = ['SHA1', 'SHA256', 'SHA512'];
    /**
     * @const DEFAULT_PERIOD Default period for time based OTPs
     */
    const DEFAULT_PERIOD = 30;
    /**
     * @const ALLOWED_PERIODS Allowed periods range for time based OTPs
     */
    const ALLOWED_PERIODS = [15, 60];

    /**
     * @var string the name of the DB connection to be used by this class
     *
     * NOTE: make configurable
     */
    private $db = 'db';

    /**
     * Initializes the application component.
     * This method overrides the parent implementation by establishing the database connection.
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%otputil_secrets}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * Validate confirmed, which is forced to be false
     */
    public function validateConfirmed($attribute, $params, $validator)
    {
        if($this->$attribute) {
            $this->addError($attribute, 'Creating already confirmed secrets is not allowed');
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['secret', 'default', 'value' => function ($model, $attribute) {
                $base32 = new Base32();

                return $base32->fromString(random_bytes(20));
            }],
            ['secret', 'string', 'min' => 3, 'max' => 128],
            ['secret', 'match', 'pattern' => '/^[A-Z2-7]*$/i'],
            ['digits', 'default', 'value' => self::DEFAULT_DIGITS],
            ['digits', 'in', 'range' => self::ALLOWED_DIGITS],
            ['mode', 'default', 'value' => self::DEFAULT_MODE],
            ['mode', 'in', 'range' => self::ALLOWED_MODES],
            ['algo', 'default', 'value' => self::DEFAULT_ALGO],
            ['algo', 'in', 'range' => self::ALLOWED_ALGOS],
            ['period', 'default', 'value' => self::DEFAULT_PERIOD],
            ['period', 'integer', 'min' => self::ALLOWED_PERIODS[0], 'max' => self::ALLOWED_PERIODS[1]],
            ['confirmed', 'default', 'value' => false],
            ['confirmed', 'validateConfirmed'],
        ];
    }

    /**
     * Forbid modifying records
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!$insert) {
            return false;
        }

        if ($insert && $this->confirmed) {
            return false;
        }

        return true;
    }

    /**
     * Marks a secret as confirmed
     */
    public function confirm()
    {
        $this->confirmed = true;
        return $this->save();
    }

    /**
     * Returns confirmation status
     */
    public function isconfimed()
    {
        if ($this->confirmed)
            return true;

        return false;
    }

    /**
     * Increments counter for HOTPs
     */
    public function incrementCounter()
    {
        if ($this->mode !== 'hotp')
            return false;

        if (!$this->updateCounters(['counter' => 1]))
            return false;

        return $this->counter;
    }
}
