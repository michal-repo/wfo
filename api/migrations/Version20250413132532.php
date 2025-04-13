<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413132532 extends AbstractMigration {
    public function getDescription(): string {
        return '';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE `wfo_custom_command_generator` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `command` varchar(600) NOT NULL,
            `days_in_advance` int(10) unsigned NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `wfo_custom_command_generator_users_FK` (`user_id`),
            CONSTRAINT `wfo_custom_command_generator_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE wfo_custom_command_generator');
    }
}
