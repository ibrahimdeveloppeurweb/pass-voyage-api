<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817131102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE general_setting ADD setting_key VARCHAR(255) NOT NULL, ADD setting_value LONGTEXT DEFAULT NULL, ADD setting_group VARCHAR(255) DEFAULT NULL, ADD description VARCHAR(255) DEFAULT NULL, DROP frais_dossier, DROP penalite_retard_journaliere, DROP delai_grace_penalite, DROP duree_contrat_defaut_mois, DROP apport_initial_pourcentage');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EE5415EC5FA1E697 ON general_setting (setting_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_EE5415EC5FA1E697 ON general_setting');
        $this->addSql('ALTER TABLE general_setting ADD frais_dossier INT DEFAULT NULL, ADD penalite_retard_journaliere DOUBLE PRECISION DEFAULT NULL, ADD delai_grace_penalite INT DEFAULT NULL, ADD duree_contrat_defaut_mois INT DEFAULT NULL, ADD apport_initial_pourcentage DOUBLE PRECISION DEFAULT NULL, DROP setting_key, DROP setting_value, DROP setting_group, DROP description');
    }
}
