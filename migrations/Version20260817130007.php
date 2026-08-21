<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817130007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_request ADD travel_date DATETIME DEFAULT NULL, ADD return_date DATETIME DEFAULT NULL, ADD is_round_trip TINYINT NOT NULL, ADD passenger_count INT NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3979B1AD6 ON ticket (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_request DROP travel_date, DROP return_date, DROP is_round_trip, DROP passenger_count');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3979B1AD6');
        $this->addSql('DROP INDEX IDX_97A0ADA3979B1AD6 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP company_id');
    }
}
