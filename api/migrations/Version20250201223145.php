<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201223145 extends AbstractMigration {
    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {

        $this->addSql('ALTER TABLE wfo_cal.wfo_working_days DROP KEY wfo_working_days_y_m_u_unique');
        $this->addSql('ALTER TABLE wfo_cal.wfo_working_days ADD CONSTRAINT wfo_working_days_y_m_u_unique UNIQUE KEY (`year`,`month`,user_id)');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE wfo_cal.wfo_working_days DROP KEY wfo_working_days_y_m_u_unique');
        $this->addSql('ALTER TABLE wfo_cal.wfo_working_days ADD CONSTRAINT wfo_working_days_y_m_u_unique UNIQUE KEY (`year`,`month`, `working_days`,`user_id`)');
    }
}
