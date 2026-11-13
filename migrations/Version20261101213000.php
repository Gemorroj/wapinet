<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create api_token table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
                CREATE TABLE api_token (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    token_hash VARCHAR(64) NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    created_at DATETIME NOT NULL,
                    expires_at DATETIME NOT NULL,
                    last_used_at DATETIME DEFAULT NULL,
                    UNIQUE INDEX UNIQ_API_TOKEN_HASH (token_hash),
                    INDEX IDX_API_TOKEN_USER (user_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_API_TOKEN_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_token');
    }
}
