<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817141632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE passenger ADD country_code VARCHAR(10) NOT NULL, ADD pin_code VARCHAR(255) DEFAULT NULL, ADD gender VARCHAR(10) DEFAULT NULL, ADD residence_address VARCHAR(255) DEFAULT NULL, ADD identity_type VARCHAR(50) DEFAULT NULL, ADD identity_recto_url LONGTEXT DEFAULT NULL, ADD identity_verso_url LONGTEXT DEFAULT NULL, ADD selfie_url LONGTEXT DEFAULT NULL, ADD identity_status VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE passenger DROP country_code, DROP pin_code, DROP gender, DROP residence_address, DROP identity_type, DROP identity_recto_url, DROP identity_verso_url, DROP selfie_url, DROP identity_status');
    }
}
