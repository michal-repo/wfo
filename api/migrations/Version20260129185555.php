<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129185555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update maps table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `maps` ADD COLUMN `type` ENUM('office', 'parking') NOT NULL DEFAULT 'office' AFTER `name`");
        $this->addSql("ALTER TABLE `user_seats` ADD COLUMN `map_id` int(11) NOT NULL AFTER `user_id`");
        $this->addSql("ALTER TABLE `user_seats` DROP FOREIGN KEY `user_seats_users_FK`");
        $this->addSql("ALTER TABLE `user_seats` DROP FOREIGN KEY `user_seats_seats_FK`");
        $this->addSql("ALTER TABLE `user_seats` DROP INDEX `user_seats_unique`");
        $this->addSql("ALTER TABLE `user_seats` ADD UNIQUE KEY `user_seats_unique` (`seat_id`,`reservation_date`, `user_id`, `map_id`)");
        $this->addSql("ALTER TABLE `user_seats` ADD CONSTRAINT `user_seats_seats_FK` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE");
        $this->addSql("ALTER TABLE `user_seats` ADD CONSTRAINT `user_seats_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        }
        
        public function down(Schema $schema): void
        {
            $this->addSql("ALTER TABLE `maps` DROP COLUMN `type`");
            $this->addSql("ALTER TABLE `user_seats` DROP FOREIGN KEY `user_seats_users_FK`");
            $this->addSql("ALTER TABLE `user_seats` DROP FOREIGN KEY `user_seats_seats_FK`");
            $this->addSql("ALTER TABLE `user_seats` DROP INDEX `user_seats_unique`");
            $this->addSql("ALTER TABLE `user_seats` DROP COLUMN `map_id`");
            $this->addSql("ALTER TABLE `user_seats` ADD UNIQUE KEY `user_seats_unique` (`seat_id`,`reservation_date`, `user_id`)");
            $this->addSql("ALTER TABLE `user_seats` ADD CONSTRAINT `user_seats_seats_FK` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE");
            $this->addSql("ALTER TABLE `user_seats` ADD CONSTRAINT `user_seats_users_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE");
    }
}
