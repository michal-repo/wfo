<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250226141247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }
    public function up(Schema $schema): void {

        $this->addSql('CREATE TABLE `wfo_sickleave` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `defined_date` datetime NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wfo_sickleave_unique` (`defined_date`,`user_id`),
            KEY `wfo_sickleave_users_FK` (`user_id`),
            CONSTRAINT `wfo_sickleave_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE wfo_sickleave');
    }
}
