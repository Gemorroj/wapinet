<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210206132333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_tags DROP FOREIGN KEY FK_E640FC4893CB796C');
        $this->addSql('ALTER TABLE file_tags DROP FOREIGN KEY FK_E640FC48BAD26311');
        $this->addSql('ALTER TABLE file_tags ADD CONSTRAINT FK_E640FC4893CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE file_tags ADD CONSTRAINT FK_E640FC48BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_tags DROP FOREIGN KEY FK_E640FC4893CB796C');
        $this->addSql('ALTER TABLE file_tags DROP FOREIGN KEY FK_E640FC48BAD26311');
        $this->addSql('ALTER TABLE file_tags ADD CONSTRAINT FK_E640FC4893CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE file_tags ADD CONSTRAINT FK_E640FC48BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
