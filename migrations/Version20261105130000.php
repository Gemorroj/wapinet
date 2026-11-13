<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the legacy user panel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496F6FCB26');
        $this->addSql('DROP INDEX UNIQ_8D93D6496F6FCB26 ON user');
        $this->addSql('ALTER TABLE user DROP panel_id');
        $this->addSql('DROP TABLE user_panel');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_panel (
                id INT AUTO_INCREMENT NOT NULL,
                forum TINYINT(1) DEFAULT 1 NOT NULL,
                gist TINYINT(1) DEFAULT 1 NOT NULL,
                file TINYINT(1) DEFAULT 1 NOT NULL,
                archiver TINYINT(1) DEFAULT 0 NOT NULL,
                guestbook TINYINT(1) DEFAULT 0 NOT NULL,
                http TINYINT(1) DEFAULT 0 NOT NULL,
                whois TINYINT(1) DEFAULT 0 NOT NULL,
                php_validator TINYINT(1) DEFAULT 0 NOT NULL,
                html_validator TINYINT(1) DEFAULT 0 NOT NULL,
                css_validator TINYINT(1) DEFAULT 0 NOT NULL,
                php_obfuscator TINYINT(1) DEFAULT 0 NOT NULL,
                audio_tags TINYINT(1) DEFAULT 0 NOT NULL,
                rename TINYINT(1) DEFAULT 0 NOT NULL,
                email TINYINT(1) DEFAULT 0 NOT NULL,
                browser_info TINYINT(1) DEFAULT 0 NOT NULL,
                hash TINYINT(1) DEFAULT 0 NOT NULL,
                code TINYINT(1) DEFAULT 0 NOT NULL,
                unicode TINYINT(1) DEFAULT 0 NOT NULL,
                unicode_icons TINYINT(1) DEFAULT 0 NOT NULL,
                politics TINYINT(1) DEFAULT 0 NOT NULL,
                rates TINYINT(1) DEFAULT 0 NOT NULL,
                mobile_code TINYINT(1) DEFAULT 0 NOT NULL,
                open_source TINYINT(1) DEFAULT 0 NOT NULL,
                textbook TINYINT(1) DEFAULT 0 NOT NULL,
                video_courses TINYINT(1) DEFAULT 0 NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE user ADD panel_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6496F6FCB26 ON user (panel_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496F6FCB26 FOREIGN KEY (panel_id) REFERENCES user_panel (id)');
    }
}
