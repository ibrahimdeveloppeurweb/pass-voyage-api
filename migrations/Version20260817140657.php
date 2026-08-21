<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817140657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE credit_policy (id INT AUTO_INCREMENT NOT NULL, new_user_limit INT NOT NULL, standard_limit INT NOT NULL, vip_limit INT NOT NULL, auto_approve_enabled TINYINT NOT NULL, auto_reject_blacklist_enabled TINYINT NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE passenger ADD profile_type VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE credit_policy');
        $this->addSql('ALTER TABLE passenger DROP profile_type');
    }
}
