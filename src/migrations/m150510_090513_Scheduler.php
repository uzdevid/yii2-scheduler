<?php
namespace uzdevid\scheduler\migrations;

use yii\db\Expression;
use yii\db\Migration;

class m150510_090513_Scheduler extends Migration {
    /**
     * @return void
     */
    public function safeUp(): void {
        $this->createTable('scheduler_log', [
            'id' => $this->primaryKey(),
            'scheduler_task_id' => $this->integer(11)->notNull(),
            'started_at' => $this->timestamp()->notNull()->defaultValue(new Expression('CURRENT_TIMESTAMP')),
            'ended_at' => $this->timestamp()->null()->defaultValue(null),
            'output' => $this->text()->notNull(),
            'error' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->createTable('scheduler_task', [
            'id' => $this->primaryKey(),
            'name' => $this->string(45)->notNull()->unique(),
            'schedule' => $this->string(45)->notNull(),
            'description' => $this->text()->notNull(),
            'status_id' => $this->integer(11)->notNull(),
            'started_at' => $this->timestamp()->null()->defaultValue(null),
            'last_run' => $this->timestamp()->null()->defaultValue(null),
            'next_run' => $this->timestamp()->null()->defaultValue(null),
            'active' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->addForeignKey('fk_scheduler_log_scheduler_task_id', 'scheduler_log', 'scheduler_task_id', 'scheduler_task', 'id');
    }

    /**
     * @return bool
     */
    public function safeDown(): bool {
        $this->dropTable('scheduler_log');
        $this->dropTable('scheduler_task');

        return true;
    }
}
