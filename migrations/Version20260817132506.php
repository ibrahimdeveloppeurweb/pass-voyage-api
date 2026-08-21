<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817132506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_request ADD departure_city VARCHAR(255) DEFAULT NULL, ADD arrival_city VARCHAR(255) DEFAULT NULL, ADD unit_price INT NOT NULL, ADD repayment_status VARCHAR(255) NOT NULL, CHANGE route_id route_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket DROP INDEX UNIQ_97A0ADA3B106031B, ADD INDEX IDX_97A0ADA3B106031B (credit_request_id)');
        $this->addSql('ALTER TABLE ticket ADD ticket_index INT NOT NULL, ADD unit_price INT NOT NULL, ADD status VARCHAR(255) NOT NULL, ADD used_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_request DROP departure_city, DROP arrival_city, DROP unit_price, DROP repayment_status, CHANGE route_id route_id INT NOT NULL');
        $this->addSql('ALTER TABLE ticket DROP INDEX IDX_97A0ADA3B106031B, ADD UNIQUE INDEX UNIQ_97A0ADA3B106031B (credit_request_id)');
        $this->addSql('ALTER TABLE ticket DROP ticket_index, DROP unit_price, DROP status, DROP used_at');
    }
}
