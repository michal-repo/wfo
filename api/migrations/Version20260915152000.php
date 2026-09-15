<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update user year holidays table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_cal.wfo_user_year_holidays ADD UNIQUE KEY `user_year_unique` (`user_id`, `year`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE wfo_cal.wfo_user_year_holidays DROP KEY `user_year_unique`");
    }
}
