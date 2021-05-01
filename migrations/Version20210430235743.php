<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210430235743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE file CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE file_tags CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE gist CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE guestbook CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE news CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE online CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tag CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_friend CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_panel CONVERT TO CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_subscriber CONVERT TO CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
