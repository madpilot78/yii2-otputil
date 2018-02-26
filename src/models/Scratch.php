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
     * @const SCRATCH_LENGTH Digits in a scratch code.
     */
    const SCRATCH_LENGTH = 8;

    /**
     * Requires a Secret id to link Scratch codes to
     *
     * @param int $secret_id
     */
    public function __construct(int $secret_id = null, $config = [])
    {
        parent::__construct($config);
        if(!is_null($secret_id))
            $this->secret_id = $secret_id;
    }

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();
        $this->code = $this->generateCode();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%otputil_scodes}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['code', 'string', 'length' => self::SCRATCH_LENGTH],
            ['secret_id', 'exist', 'targetClass' => '\mad\otputil\models\Secret', 'targetAttribute' => 'id']
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

    /**
     * Relation method to Secret model
     */
    public function getSecret()
    {
        return $this->hasOne(Secret::class, ['id' => 'secret_id']);
    }

    /**
     * Generates a scratch code.
     *
     * @return string The scratch code generated
     */
    protected function generateCode()
    {
        $code = '';

        for ($i = 1; $i <= self::SCRATCH_LENGTH; $i++)
            $code .= (string)random_int(0, 9);

        return $code;
    }

    /**
     * Utility function to validate Secret IDs.
     *
     * @param int $sid The Secret ID to be validated
     * @return bool if validation succeeded
     */
    protected static function validateSID(int $sid)
    {
        $validator = new \yii\validators\ExistValidator();
        $validator->targetClass = '\mad\otputil\models\Secret';
        $validator->targetAttribute = 'id';

        return $validator->validate($sid, $error);
    }

    /**
     * Return Scratches bound to specified Secret.
     *
     * @param int ID of Secret
     * @return array of Secret AR objects
     */
    public static function findBySecretID(int $sid)
    {
        if (!self::validateSID($sid)) {
            return [];
        }

        return self::findAll(['secret_id' => $sid]);
    }

    /**
     * Creates $num scratch codes.
     *
     * @param int $sid ID to assign the codes to
     * @param int $num Number of codes to generate
     * @return array of created AR objects
     */
    public static function createScratches(int $sid, int $num = self::DEFAULT_CODES)
    {
        if (!self::validateSID($sid)) {
            return false;
        }

        $ret = [];
        while ($num > 0) {
            $t = new self($sid);
            $t->save();
            $ret[] = $t;
            $num--;
        }

        return $ret;
    }

    /**
     * Verify a scratch code.
     *
     * @param int $sid ID of secret to validate against
     * @param string $code Scratch code to validate
     * @param bool $del if the code should be deleted after successful check
     * @return bool if verification was successful
     */
    public static function verifyCode(string $code, int $sid, bool $del = true)
    {
        $ret = false;
        $valid_codes = self::findBySecretID($sid);

        foreach ($valid_codes as $c) {
            if ($c->code === $code)
            {
                $ret = true;
                if ($del)
                    $c->delete();
            }
        }

        return $ret;
    }

    /**
     * Validate a scratch code using the static method, just populate the sid from the object.
     *
     * @param int $sid ID of secret to validate against
     * @param string $code Scratch code to validate
     * @param bool $del if the code should be deleted after successful check
     * @return bool if verification was successful
     */
    public function verify(string $code, bool $del = true)
    {
        if (!self::validateSID($this->secret_id))
            return false;

        return self::verifyCode($code, $this->secret_id, $del);
    }

    /**
     * Remove all codes assigned to a secret.
     *
     * @param int $sid pass to remove all codes bound to this secret ID
     * @return bool success/failure
     */
    public static function remove(int $sid)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $codes = self::findBySecretID($sid);

            foreach ($codes as $c) {
                $c->delete();
            }

            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }
}
