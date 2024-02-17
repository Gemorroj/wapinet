<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240217125230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE event');
        $this->addSql('ALTER TABLE event CHANGE variables variables JSON DEFAULT NULL');

        $this->addSql('UPDATE file SET meta = NULL');
        $this->addSql('ALTER TABLE file CHANGE meta meta json DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE event');
        $this->addSql('ALTER TABLE event CHANGE variables variables LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');

        $this->addSql('UPDATE file SET meta = NULL');
        $this->addSql('ALTER TABLE file CHANGE meta meta LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\'');
    }
}
