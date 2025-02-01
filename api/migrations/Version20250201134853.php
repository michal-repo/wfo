<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201134853 extends AbstractMigration {
    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {

        $this->addSql('CREATE TABLE `wfo_working_days` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `year` int(11) NOT NULL,
            `month` int(11) NOT NULL,
            `working_days` int(11) NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wfo_working_days_y_m_u_unique` (`year`,`month`, `working_days`,`user_id`),
            KEY `wfo_working_days_users_FK` (`user_id`),
            CONSTRAINT `wfo_working_days_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE wfo_working_days');
    }
}
