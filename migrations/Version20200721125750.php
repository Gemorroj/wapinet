<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200721125750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'init db';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `user` (
              `id` int NOT NULL AUTO_INCREMENT,
              `username` varchar(180) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `email` varchar(180) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `enabled` tinyint(1) NOT NULL DEFAULT '1',
              `salt` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `roles` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              `last_activity` datetime DEFAULT NULL,
              `sex` enum('m','f') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `birthday` date DEFAULT NULL,
              `info` varchar(5000) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `timezone` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `country` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `vk` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNIQ_8D93D649F85E0677` (`username`),
              UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");

        $this->addSql("
            CREATE TABLE `event` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `created_at` datetime NOT NULL,
              `subject` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `variables` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
              `template` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `need_email` tinyint(1) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `IDX_3BAE0AA7A76ED395` (`user_id`),
              CONSTRAINT `FK_3BAE0AA7A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");

        $this->addSql("
            CREATE TABLE `file` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int DEFAULT NULL,
              `ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `browser` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              `last_view_at` datetime DEFAULT NULL,
              `count_views` int NOT NULL,
              `salt` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `mime_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
              `file_size` int NOT NULL,
              `file_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `original_file_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `description` varchar(5000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `hash` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `meta` longblob COMMENT '(DC2Type:object)',
              `hidden` tinyint(1) NOT NULL DEFAULT '1',
              PRIMARY KEY (`id`),
              KEY `IDX_8C9F3610A76ED395` (`user_id`),
              KEY `hash_idx` (`hash`),
              CONSTRAINT `FK_8C9F3610A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");

        $this->addSql('
            CREATE TABLE `tag` (
              `id` int NOT NULL AUTO_INCREMENT,
              `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `count` int NOT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name_idx` (`name`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `file_tags` (
              `file_id` int NOT NULL,
              `tag_id` int NOT NULL,
              `id` int NOT NULL AUTO_INCREMENT,
              PRIMARY KEY (`id`),
              KEY `IDX_E640FC4893CB796C` (`file_id`),
              KEY `IDX_E640FC48BAD26311` (`tag_id`),
              CONSTRAINT `FK_E640FC4893CB796C` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`),
              CONSTRAINT `FK_E640FC48BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `gist` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `browser` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              `body` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `subject` varchar(5000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`),
              KEY `IDX_93C97F19A76ED395` (`user_id`),
              CONSTRAINT `FK_93C97F19A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `guestbook` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int DEFAULT NULL,
              `ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `browser` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              `message` varchar(5000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`),
              KEY `IDX_88704138A76ED395` (`user_id`),
              CONSTRAINT `FK_88704138A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `news` (
              `id` int NOT NULL AUTO_INCREMENT,
              `subject` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `body` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              `created_by_id` int DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `IDX_1DD39950B03A8386` (`created_by_id`),
              CONSTRAINT `FK_1DD39950B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `online` (
              `id` int NOT NULL AUTO_INCREMENT,
              `datetime` datetime NOT NULL,
              `ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `browser` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_idx` (`ip`,`browser`)
            ) ENGINE=MEMORY AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `user_friend` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int DEFAULT NULL,
              `friend_id` int DEFAULT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `IDX_30BCB75CA76ED395` (`user_id`),
              KEY `IDX_30BCB75C6A5458E8` (`friend_id`),
              CONSTRAINT `FK_30BCB75C6A5458E8` FOREIGN KEY (`friend_id`) REFERENCES `user` (`id`),
              CONSTRAINT `FK_30BCB75CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `user_panel` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `forum` tinyint(1) NOT NULL,
              `gist` tinyint(1) NOT NULL,
              `file` tinyint(1) NOT NULL,
              `archiver` tinyint(1) NOT NULL,
              `downloads` tinyint(1) NOT NULL,
              `utilities` tinyint(1) NOT NULL,
              `programming` tinyint(1) NOT NULL,
              `guestbook` tinyint(1) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNIQ_406F2857A76ED395` (`user_id`),
              CONSTRAINT `FK_406F2857A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $this->addSql('
            CREATE TABLE `user_subscriber` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `email_news` tinyint(1) NOT NULL,
              `email_friends` tinyint(1) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNIQ_A679D85A76ED395` (`user_id`),
              CONSTRAINT `FK_A679D85A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
