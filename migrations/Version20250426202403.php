<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426202403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
                ALTER TABLE event CHANGE need_email need_email TINYINT(1) DEFAULT 0 NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
                ALTER TABLE user_panel ADD http TINYINT(1) DEFAULT 0 NOT NULL, ADD whois TINYINT(1) DEFAULT 0 NOT NULL, ADD php_validator TINYINT(1) DEFAULT 0 NOT NULL, ADD html_validator TINYINT(1) DEFAULT 0 NOT NULL, ADD css_validator TINYINT(1) DEFAULT 0 NOT NULL, ADD php_obfuscator TINYINT(1) DEFAULT 0 NOT NULL, ADD audio_tags TINYINT(1) DEFAULT 0 NOT NULL, ADD `rename` TINYINT(1) DEFAULT 0 NOT NULL, ADD email TINYINT(1) DEFAULT 0 NOT NULL, ADD browser_info TINYINT(1) DEFAULT 0 NOT NULL, ADD hash TINYINT(1) DEFAULT 0 NOT NULL, ADD code TINYINT(1) DEFAULT 0 NOT NULL, ADD unicode TINYINT(1) DEFAULT 0 NOT NULL, ADD unicode_icons TINYINT(1) DEFAULT 0 NOT NULL, ADD mass_media TINYINT(1) DEFAULT 0 NOT NULL, ADD rates TINYINT(1) DEFAULT 0 NOT NULL, ADD mobile_code TINYINT(1) DEFAULT 0 NOT NULL, ADD open_source TINYINT(1) DEFAULT 0 NOT NULL, ADD textbook TINYINT(1) DEFAULT 0 NOT NULL, ADD video_courses TINYINT(1) DEFAULT 0 NOT NULL, DROP downloads, DROP utilities, DROP programming, CHANGE forum forum TINYINT(1) DEFAULT 1 NOT NULL, CHANGE gist gist TINYINT(1) DEFAULT 1 NOT NULL, CHANGE file file TINYINT(1) DEFAULT 1 NOT NULL, CHANGE archiver archiver TINYINT(1) DEFAULT 0 NOT NULL, CHANGE guestbook guestbook TINYINT(1) DEFAULT 0 NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
                ALTER TABLE user_subscriber CHANGE email_news email_news TINYINT(1) DEFAULT 1 NOT NULL, CHANGE email_friends email_friends TINYINT(1) DEFAULT 1 NOT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
                ALTER TABLE event CHANGE need_email need_email TINYINT(1) NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
                ALTER TABLE user_panel ADD downloads TINYINT(1) NOT NULL, ADD utilities TINYINT(1) NOT NULL, ADD programming TINYINT(1) NOT NULL, DROP http, DROP whois, DROP php_validator, DROP html_validator, DROP css_validator, DROP php_obfuscator, DROP audio_tags, DROP `rename`, DROP email, DROP browser_info, DROP hash, DROP code, DROP unicode, DROP unicode_icons, DROP mass_media, DROP rates, DROP mobile_code, DROP open_source, DROP textbook, DROP video_courses, CHANGE forum forum TINYINT(1) NOT NULL, CHANGE guestbook guestbook TINYINT(1) NOT NULL, CHANGE gist gist TINYINT(1) NOT NULL, CHANGE file file TINYINT(1) NOT NULL, CHANGE archiver archiver TINYINT(1) NOT NULL
            SQL);
        $this->addSql(<<<'SQL'
                ALTER TABLE user_subscriber CHANGE email_news email_news TINYINT(1) NOT NULL, CHANGE email_friends email_friends TINYINT(1) NOT NULL
            SQL);
    }
}
