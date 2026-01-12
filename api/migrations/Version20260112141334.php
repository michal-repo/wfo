<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260112141334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates maps table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE `maps` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `map` MEDIUMBLOB NOT NULL,
            `name` varchar(255) NOT NULL,
            `imageBoundsY` DOUBLE NOT NULL,
            `imageBoundsX` DOUBLE NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `maps_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE `seats` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `map_id` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` TEXT NOT NULL,
            `bookable` TINYINT(1) NOT NULL,
            `x_coordinate` DOUBLE NOT NULL,
            `y_coordinate` DOUBLE NOT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `seats_maps_FK` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->addSql("CREATE TABLE `user_seats` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `seat_id` int(11) NOT NULL,
            `user_id` int(10) unsigned NOT NULL,
            `reservation_date` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_seats_unique` (`seat_id`,`reservation_date`, `user_id`),
            CONSTRAINT `user_seats_seats_FK` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE,
            CONSTRAINT `user_seats_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `user_seats`');
        $this->addSql('DROP TABLE `seats`');
        $this->addSql('DROP TABLE `maps`');
    }
}
