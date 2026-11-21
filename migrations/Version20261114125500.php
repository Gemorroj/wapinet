<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aligns the infrastructure tables (created with the old default collation) with the rest of the schema.
 */
final class Version20261114125500 extends AbstractMigration
{
    private const string COLLATION = 'utf8mb4_0900_ai_ci';
    private const array TABLES = ['api_token', 'messenger_messages', 'migration_versions'];

    public function getDescription(): string
    {
        return 'Align the collation of the infrastructure tables with the rest of the schema';
    }

    public function up(Schema $schema): void
    {
        $this->convert(self::COLLATION);
    }

    public function down(Schema $schema): void
    {
        $this->convert('utf8mb4_unicode_ci');
    }

    private function convert(string $collation): void
    {
        foreach (self::TABLES as $table) {
            $current = $this->connection->fetchOne(
                'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table],
            );

            if (false === $current || $collation === $current) {
                continue;
            }

            $this->addSql(\sprintf('ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE %s', $table, $collation));
        }
    }
}
