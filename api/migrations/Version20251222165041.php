<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251222165041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates wfo_api_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `wfo_api_tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `selector` varchar(255) NOT NULL,
            `hashed_validator` varchar(255) NOT NULL,
            `token_name` varchar(100) NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wfo_api_tokens_unique` (`selector`, `hashed_validator`,`user_id`),
            KEY `wfo_api_tokens_users_FK` (`user_id`),
            CONSTRAINT `wfo_api_tokens_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `wfo_api_tokens`');
    }
}
