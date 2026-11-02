<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108081352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update wfo_days table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_days ADD COLUMN `custom_color` varchar(12) DEFAULT NULL AFTER `defined_date`");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_days DROP COLUMN `custom_color`");
    }
}
