<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806095419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change wfo_month_target.target column to float';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_cal.wfo_month_target MODIFY COLUMN `target` float NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_cal.wfo_month_target MODIFY COLUMN `target` int(11) NOT NULL");
    }
}
