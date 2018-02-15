<?php

namespace mad\otputil\models;

use Yii;
use mad\otputil\models\Secret;

/**
 * Model class for OTP scratch codes.
 *
 * @property integer $id
 * @property string $code
 * @property integer $secret_id
 */
class Scratch extends \yii\db\ActiveRecord
{
    /**
     * @const DEFAULT_CODES Default number of codes to generate with automatic method
     */
    const DEFAULT_CODES = 5;

    /**
     * @var string the name of the DB connection to be used by this class
     *
     * NOTE: make configurable
     */
    private $db = 'db';

    /**
     * Requires a Secret id to link Scratch codes to
     *
     * @param int $secret_id
     */
    public function __construct(int $secret_id)
    {
        parent::__construct();
        // $secret_id mist be checked
        $this->secret_id = $secret_id;
    }

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
        return '{{%otputil_scodes}}';
    }

    public function rules()
    {
        return [
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

        return true;
    }
}
