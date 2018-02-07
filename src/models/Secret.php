<?php

namespace mad\otputil\models\mail;

use Yii;

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
     * @var string the name of the DB connection to be used by this class
     *
     * NOTE: make configurable
     */
    private $db = 'db';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'otputil_secrets';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get($this->db);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        ];
    }
}
