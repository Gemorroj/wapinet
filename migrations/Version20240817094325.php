<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240817094325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file CHANGE browser browser VARCHAR(500) NOT NULL, CHANGE meta meta json DEFAULT NULL');
        $this->addSql('ALTER TABLE gist CHANGE browser browser VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE guestbook CHANGE browser browser VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE online CHANGE browser browser VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT DEFAULT NULL, CHANGE sex sex ENUM(\'m\', \'f\') DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file CHANGE browser browser VARCHAR(255) NOT NULL, CHANGE meta meta JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE gist CHANGE browser browser VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE guestbook CHANGE browser browser VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE online CHANGE browser browser VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE sex sex VARCHAR(0) DEFAULT NULL');
    }
}
