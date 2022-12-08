<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221208081146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD panel_id INT DEFAULT NULL, ADD subscriber_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496F6FCB26 FOREIGN KEY (panel_id) REFERENCES user_panel (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6497808B1AD FOREIGN KEY (subscriber_id) REFERENCES user_subscriber (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6496F6FCB26 ON user (panel_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6497808B1AD ON user (subscriber_id)');

        $this->addSql('ALTER TABLE user_panel DROP FOREIGN KEY FK_406F2857A76ED395');
        $this->addSql('DROP INDEX UNIQ_406F2857A76ED395 ON user_panel');
        $this->addSql('ALTER TABLE user_subscriber DROP FOREIGN KEY FK_A679D85A76ED395');
        $this->addSql('DROP INDEX UNIQ_A679D85A76ED395 ON user_subscriber');

        $this->addSql('UPDATE user AS u SET u.panel_id = (SELECT p.id FROM user_panel AS p WHERE p.user_id = u.id)');
        $this->addSql('UPDATE user AS u SET u.subscriber_id = (SELECT s.id FROM user_subscriber AS s WHERE s.user_id = u.id)');

        $this->addSql('ALTER TABLE user_subscriber DROP user_id');
        $this->addSql('ALTER TABLE user_panel DROP user_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496F6FCB26');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497808B1AD');
        $this->addSql('DROP INDEX UNIQ_8D93D6496F6FCB26 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D6497808B1AD ON user');

        $this->addSql('ALTER TABLE user_panel ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_panel ADD CONSTRAINT FK_406F2857A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_406F2857A76ED395 ON user_panel (user_id)');
        $this->addSql('ALTER TABLE user_subscriber ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_subscriber ADD CONSTRAINT FK_A679D85A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A679D85A76ED395 ON user_subscriber (user_id)');

        $this->addSql('UPDATE user_panel AS p SET p.user_id = (SELECT u.id FROM user AS u WHERE u.panel_id = p.id)');
        $this->addSql('UPDATE user_subscriber AS s SET s.user_id = (SELECT u.id FROM user AS u WHERE u.subscriber_id = s.id)');

        $this->addSql('ALTER TABLE user DROP panel_id, DROP subscriber_id');
    }
}
