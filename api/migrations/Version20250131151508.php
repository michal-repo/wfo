<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131151508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE wfo_month_target ( id INT auto_increment NOT NULL, 	month_of_target INT NOT NULL, year_of_target INT NOT NULL, target INT NOT NULL, CONSTRAINT id_PK PRIMARY KEY (id), CONSTRAINT month_year_unique UNIQUE KEY (month_of_target,year_of_target) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE wfo_month_target');
    }
}
