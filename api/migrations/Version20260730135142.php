<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260730135142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update wfo_year_target table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target ADD COLUMN `start_month` int(11) NOT NULL DEFAULT 1");
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target ADD COLUMN `end_month` int(11) NOT NULL DEFAULT 12");
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target ADD COLUMN `start_year` int(11) NOT NULL DEFAULT YEAR(CURDATE())");
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target ADD COLUMN `end_year` int(11) NOT NULL DEFAULT YEAR(CURDATE())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target DROP COLUMN `start_month`");
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target DROP COLUMN `end_month`");
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target DROP COLUMN `start_year`");
        $this->addSql("ALTER TABLE wfo_cal.wfo_year_target DROP COLUMN `end_year`");
    }
}
