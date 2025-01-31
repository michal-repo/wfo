<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131193721 extends AbstractMigration {
    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE `wfo_days` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `defined_date` datetime NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wfo_days_unique` (`defined_date`,`user_id`),
            KEY `wfo_days_users_FK` (`user_id`),
            CONSTRAINT `wfo_days_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addSql('CREATE TABLE `wfo_month_target` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `month_of_target` int(11) NOT NULL,
            `year_of_target` int(11) NOT NULL,
            `target` int(11) NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wfo_month_target_unique` (`month_of_target`,`year_of_target`,`user_id`),
            KEY `wfo_month_target_users_FK` (`user_id`),
            CONSTRAINT `wfo_month_target_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->addSql('CREATE TABLE `wfo_year_target` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `year_of_target` int(11) NOT NULL,
            `target` int(11) NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wfo_year_target_unique` (`year_of_target`,`target`,`user_id`),
            KEY `wfo_year_target_users_FK` (`user_id`),
            CONSTRAINT `wfo_year_target_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE wfo_days');
        $this->addSql('DROP TABLE wfo_month_target');
        $this->addSql('DROP TABLE wfo_year_target');
    }
}
