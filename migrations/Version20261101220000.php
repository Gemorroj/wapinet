<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace user.sex ENUM with VARCHAR(1) for DBAL compatibility';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` CHANGE sex sex VARCHAR(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` CHANGE sex sex ENUM('m', 'f') DEFAULT NULL");
    }
}
