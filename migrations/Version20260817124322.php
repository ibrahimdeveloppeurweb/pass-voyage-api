<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817124322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE passenger (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, identity_card_number VARCHAR(255) DEFAULT NULL, credit_score INT DEFAULT NULL, is_blacklisted TINYINT NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_3BEFE8DD6B01BC5B (phone_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, amount INT NOT NULL, payment_method VARCHAR(255) NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, payment_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, credit_request_id INT NOT NULL, passenger_id INT NOT NULL, INDEX IDX_6D28840DB106031B (credit_request_id), INDEX IDX_6D28840D4502E565 (passenger_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE route (id INT AUTO_INCREMENT NOT NULL, is_active TINYINT NOT NULL, departure_station_id INT NOT NULL, arrival_station_id INT NOT NULL, INDEX IDX_2C42079FF134AA1 (departure_station_id), INDEX IDX_2C42079766102BE (arrival_station_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE setting (id INT AUTO_INCREMENT NOT NULL, setting_key VARCHAR(255) NOT NULL, setting_value LONGTEXT DEFAULT NULL, setting_group VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_9F74B8985FA1E697 (setting_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE station (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, city_id INT NOT NULL, INDEX IDX_9F39F8B18BAC62AF (city_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tariff (id INT AUTO_INCREMENT NOT NULL, price INT NOT NULL, is_active TINYINT NOT NULL, route_id INT NOT NULL, company_id INT NOT NULL, INDEX IDX_9465207D34ECB4E6 (route_id), INDEX IDX_9465207D979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, ticket_number VARCHAR(255) NOT NULL, seat_number VARCHAR(255) DEFAULT NULL, qr_code_content LONGTEXT NOT NULL, is_used TINYINT NOT NULL, validated_at DATETIME DEFAULT NULL, credit_request_id INT NOT NULL, UNIQUE INDEX UNIQ_97A0ADA3ECD2759F (ticket_number), UNIQUE INDEX UNIQ_97A0ADA3B106031B (credit_request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DB106031B FOREIGN KEY (credit_request_id) REFERENCES credit_request (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D4502E565 FOREIGN KEY (passenger_id) REFERENCES passenger (id)');
        $this->addSql('ALTER TABLE route ADD CONSTRAINT FK_2C42079FF134AA1 FOREIGN KEY (departure_station_id) REFERENCES station (id)');
        $this->addSql('ALTER TABLE route ADD CONSTRAINT FK_2C42079766102BE FOREIGN KEY (arrival_station_id) REFERENCES station (id)');
        $this->addSql('ALTER TABLE station ADD CONSTRAINT FK_9F39F8B18BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE tariff ADD CONSTRAINT FK_9465207D34ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id)');
        $this->addSql('ALTER TABLE tariff ADD CONSTRAINT FK_9465207D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3B106031B FOREIGN KEY (credit_request_id) REFERENCES credit_request (id)');
        $this->addSql('ALTER TABLE company ADD logo VARCHAR(255) DEFAULT NULL, ADD is_active TINYINT NOT NULL, DROP contact_email, DROP contact_phone, DROP address');
        $this->addSql('ALTER TABLE credit_request ADD amount_requested INT NOT NULL, ADD service_fee INT NOT NULL, ADD total_amount INT NOT NULL, ADD passenger_id INT NOT NULL, ADD route_id INT NOT NULL, DROP amount, DROP motif, DROP deleted_at, DROP code, DROP qr_code');
        $this->addSql('ALTER TABLE credit_request ADD CONSTRAINT FK_113E8B04502E565 FOREIGN KEY (passenger_id) REFERENCES passenger (id)');
        $this->addSql('ALTER TABLE credit_request ADD CONSTRAINT FK_113E8B034ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id)');
        $this->addSql('CREATE INDEX IDX_113E8B04502E565 ON credit_request (passenger_id)');
        $this->addSql('CREATE INDEX IDX_113E8B034ECB4E6 ON credit_request (route_id)');
        $this->addSql('ALTER TABLE user ADD station_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64921BDB235 FOREIGN KEY (station_id) REFERENCES station (id)');
        $this->addSql('CREATE INDEX IDX_8D93D64921BDB235 ON user (station_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DB106031B');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D4502E565');
        $this->addSql('ALTER TABLE route DROP FOREIGN KEY FK_2C42079FF134AA1');
        $this->addSql('ALTER TABLE route DROP FOREIGN KEY FK_2C42079766102BE');
        $this->addSql('ALTER TABLE station DROP FOREIGN KEY FK_9F39F8B18BAC62AF');
        $this->addSql('ALTER TABLE tariff DROP FOREIGN KEY FK_9465207D34ECB4E6');
        $this->addSql('ALTER TABLE tariff DROP FOREIGN KEY FK_9465207D979B1AD6');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3B106031B');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE passenger');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE route');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE station');
        $this->addSql('DROP TABLE tariff');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('ALTER TABLE company ADD contact_phone VARCHAR(255) DEFAULT NULL, ADD address VARCHAR(255) DEFAULT NULL, DROP is_active, CHANGE logo contact_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_request DROP FOREIGN KEY FK_113E8B04502E565');
        $this->addSql('ALTER TABLE credit_request DROP FOREIGN KEY FK_113E8B034ECB4E6');
        $this->addSql('DROP INDEX IDX_113E8B04502E565 ON credit_request');
        $this->addSql('DROP INDEX IDX_113E8B034ECB4E6 ON credit_request');
        $this->addSql('ALTER TABLE credit_request ADD amount DOUBLE PRECISION NOT NULL, ADD motif LONGTEXT DEFAULT NULL, ADD deleted_at DATETIME DEFAULT NULL, ADD code VARCHAR(255) DEFAULT NULL, ADD qr_code LONGTEXT DEFAULT NULL, DROP amount_requested, DROP service_fee, DROP total_amount, DROP passenger_id, DROP route_id');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64921BDB235');
        $this->addSql('DROP INDEX IDX_8D93D64921BDB235 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP station_id');
    }
}
