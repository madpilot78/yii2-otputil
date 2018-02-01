<?php

namespace app\migrations;

use Yii;
use yii\db\Migration;

/**
 * Class m180128_141512_init
 */
class m180128_141512_init extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        /*
         * updated_at will coincide with confirmation time.
         * Plan is to only update record at confirmation, it
         * will be deleted for any other modification
         */
        $this->createTable('{{%otputil_secrets}}', [
            'id' => $this->primaryKey(),
            'secret' => $this->string()->null(),
            'counter' => $this->integer()->notNull()->defaultValue(1),
            'confirmed' => $this->boolean()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()
        ]);

        $this->createTable('{{%otputil_scodes}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string()->notNull(),
            'secret_id' => $this->integer()->notNull()
        ]);

        $this->createIndex(
            'idx-otputil_scodes-secret_id',
            '{{%otputil_scodes}}',
            'secret_id'
        );

        $this->addForeignKey(
            'fk-otputil_scodes-secret_id',
            '{{%otputil_scodes}}',
            'secret_id',
            '{{%otputil_secrets}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-otputil_scodes-secret_id',
            '{{%otputil_scodes}}'
        );

        $this->dropIndex(
            'idx-otputil_scodes-secret_id',
            '{{%otputil_scodes}}'
        );

        $this->dropTable('{{%otputil_scodes}}');

        $this->dropTable('{{%otputil_secrets}}');
    }
}
