<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201224659 extends AbstractMigration {
    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE wfo_cal.wfo_year_target DROP KEY wfo_year_target_unique');
        $this->addSql('ALTER TABLE wfo_cal.wfo_year_target ADD CONSTRAINT wfo_year_target_unique UNIQUE KEY (`year_of_target`,`user_id`)');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE wfo_cal.wfo_year_target DROP KEY wfo_year_target_unique');
        $this->addSql('ALTER TABLE wfo_cal.wfo_year_target ADD CONSTRAINT wfo_year_target_unique UNIQUE KEY (`year_of_target`,`target`,`user_id`)');
    }
}
