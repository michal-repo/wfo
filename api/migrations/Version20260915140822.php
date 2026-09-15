<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915140822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user year holidays table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE wfo_cal.wfo_user_year_holidays (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `year` INT(4) NOT NULL,
            `holidays` INT(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE wfo_cal.wfo_user_year_holidays");
    }
}
