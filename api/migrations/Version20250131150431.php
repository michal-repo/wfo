<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131150431 extends AbstractMigration {
    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE wfo_days ( id INT auto_increment NOT NULL, defined_date DATETIME NOT NULL, CONSTRAINT id_PK PRIMARY KEY (id), CONSTRAINT defined_date_UNIQUE UNIQUE KEY (defined_date) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE wfo_days');
    }
}
