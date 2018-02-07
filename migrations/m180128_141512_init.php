<?php

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
        $this->createTable('{{%otputil_secrets}}', [
            'id' => $this->primaryKey(),
            'secret' => $this->string()->null(),
            'digits' => $this->integer()->notNull(),
            'mode' => $this->string()->notNull(),
            'algo' => $this->string()->notNull(),
            'period' => $this->integer()->notNull(),
            'counter' => $this->integer()->notNull(),
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

        if ($this->db->driverName !== 'sqlite') {
            $this->addForeignKey(
                'fk-otputil_scodes-secret_id',
                '{{%otputil_scodes}}',
                'secret_id',
                '{{%otputil_secrets}}',
                'id',
                'CASCADE'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey(
                'fk-otputil_scodes-secret_id',
                '{{%otputil_scodes}}'
            );
        }

        $this->dropIndex(
            'idx-otputil_scodes-secret_id',
            '{{%otputil_scodes}}'
        );

        $this->dropTable('{{%otputil_scodes}}');

        $this->dropTable('{{%otputil_secrets}}');
    }
}
