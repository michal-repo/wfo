<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107235000 extends AbstractMigration {
    public function getDescription(): string {
        return 'Creates settings, groups, and user_groups tables';
    }

    public function up(Schema $schema): void {
        $this->addSql("CREATE TABLE `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `days_to_show` varchar(255) NOT NULL,
            `language` varchar(255) NOT NULL DEFAULT 'en',
            `is_global_admin` tinyint(1) NOT NULL DEFAULT 0,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `settings_unique` (`user_id`),
            KEY `settings_users_FK` (`user_id`),
            CONSTRAINT `settings_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE `groups` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE `user_groups` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `group_id` int(11) NOT NULL,
            `is_admin` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_groups_unique` (`user_id`, `group_id`),
            KEY `user_groups_users_FK` (`user_id`),
            CONSTRAINT `user_groups_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            KEY `user_groups_groups_FK` (`group_id`),
            CONSTRAINT `user_groups_groups_FK` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE `settings`');
        $this->addSql('DROP TABLE `groups`');
        $this->addSql('DROP TABLE `user_groups`');
    }
}
