<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817130352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE city ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_request ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE route ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE setting ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE station ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE tariff ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE city DROP deleted_at');
        $this->addSql('ALTER TABLE credit_request DROP deleted_at');
        $this->addSql('ALTER TABLE payment DROP deleted_at');
        $this->addSql('ALTER TABLE route DROP deleted_at');
        $this->addSql('ALTER TABLE setting DROP deleted_at');
        $this->addSql('ALTER TABLE station DROP deleted_at');
        $this->addSql('ALTER TABLE tariff DROP deleted_at');
        $this->addSql('ALTER TABLE ticket DROP deleted_at');
    }
}
