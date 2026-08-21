<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817153640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agent (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, country_code VARCHAR(10) NOT NULL, gender VARCHAR(10) DEFAULT NULL, residence_address VARCHAR(255) DEFAULT NULL, is_activated TINYINT NOT NULL, is_active TINYINT NOT NULL, deleted_at DATETIME DEFAULT NULL, company_id INT DEFAULT NULL, station_assigned_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_268B9C9D6B01BC5B (phone_number), INDEX IDX_268B9C9D979B1AD6 (company_id), INDEX IDX_268B9C9DE26C1F44 (station_assigned_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE agent ADD CONSTRAINT FK_268B9C9DE26C1F44 FOREIGN KEY (station_assigned_id) REFERENCES station (id)');
        $this->addSql('ALTER TABLE user ADD agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6493414710B FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6493414710B ON user (agent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agent DROP FOREIGN KEY FK_268B9C9D979B1AD6');
        $this->addSql('ALTER TABLE agent DROP FOREIGN KEY FK_268B9C9DE26C1F44');
        $this->addSql('DROP TABLE agent');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6493414710B');
        $this->addSql('DROP INDEX UNIQ_8D93D6493414710B ON `user`');
        $this->addSql('ALTER TABLE `user` DROP agent_id');
    }
}
