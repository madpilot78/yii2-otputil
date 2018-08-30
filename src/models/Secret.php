<?php

namespace madpilot78\otputil\models;

use chillerlan\Authenticator\Base32;
use yii\behaviors\TimestampBehavior;

/**
 * Model class for OTP secrets.
 *
 * @property int $id
 * @property string $secret
 * @property int $digits
 * @property string $mode
 * @property string $algo
 * @property int $period
 * @property int $counter
 * @property bool $confirmed
 * @property int created_at
 * @property int updated_at
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
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%otputil_secrets}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['secret', 'default', 'value' => function ($model, $attribute) {
                $base32 = new Base32();

                return $base32->fromString(random_bytes(20));
            }],
            ['secret', 'string', 'length' => [3, 128]],
            ['secret', 'match', 'pattern' => '/^[A-Z2-7]*$/i'],
            ['digits', 'default', 'value' => self::DEFAULT_DIGITS],
            ['digits', 'in', 'range' => self::ALLOWED_DIGITS],
            ['mode', 'default', 'value' => self::DEFAULT_MODE],
            ['mode', 'in', 'range' => self::ALLOWED_MODES],
            ['algo', 'default', 'value' => self::DEFAULT_ALGO],
            ['algo', 'in', 'range' => self::ALLOWED_ALGOS],
            ['period', 'default', 'value' => self::DEFAULT_PERIOD],
            ['period', 'integer', 'min' => self::ALLOWED_PERIODS[0], 'max' => self::ALLOWED_PERIODS[1]],
            ['confirmed', 'default', 'value' => false]
        ];
    }

    /**
     * Forbid modifying records.
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false; // @codeCoverageIgnore
        }

        if (!$insert) {
            $forbidden = ['secret', 'digits', 'mode', 'algo', 'period'];
            foreach (array_keys($this->getDirtyAttributes()) as $a) {
                if (in_array($a, $forbidden)) {
                    return false;
                }
            }
        }

        if ($insert && $this->confirmed) {
            return false;
        }

        return true;
    }

    /**
     * Relation method to Scratch model.
     */
    public function getScratches()
    {
        return $this->hasMany(Scratch::class, ['secret_id' => 'id']);
    }

    /**
     * Marks a secret as confirmed.
     */
    public function confirm()
    {
        if ($this->confirmed) {
            return false;
        }

        $this->confirmed = true;

        return $this->save();
    }

    /**
     * Returns confirmation status.
     */
    public function isconfimed()
    {
        if ($this->confirmed) {
            return true;
        }

        return false;
    }

    /**
     * Increments counter for HOTPs.
     */
    public function incrementCounter()
    {
        if ($this->mode !== 'hotp') {
            return false;
        }

        if (!$this->updateCounters(['counter' => 1])) {
            return false; // @codeCoverageIgnore
        }

        return $this->counter;
    }

    /**
     * Updates counter for HOTPs.
     */
    public function updateCounter(int $v)
    {
        if ($this->mode !== 'hotp') {
            return false;
        }

        $this->counter = $v;
        if (!$this->save()) {
            return false; // @codeCoverageIgnore
        }

        return $this->counter;
    }
}
