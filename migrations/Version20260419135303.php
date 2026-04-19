<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419135303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE amende');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amende (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(100) NOT NULL COLLATE "BINARY", type VARCHAR(100) NOT NULL COLLATE "BINARY", description CLOB DEFAULT NULL COLLATE "BINARY", montant_dt NUMERIC(10, 3) NOT NULL, penalite_dt NUMERIC(10, 3) DEFAULT NULL, date_infraction DATE NOT NULL, date_echeance DATE DEFAULT NULL, statut VARCHAR(30) NOT NULL COLLATE "BINARY", source VARCHAR(255) DEFAULT NULL COLLATE "BINARY", synced_at DATETIME DEFAULT NULL, numero_quittance VARCHAR(100) DEFAULT NULL COLLATE "BINARY", citoyen_id INTEGER NOT NULL, CONSTRAINT FK_613014CF43787BBA FOREIGN KEY (citoyen_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AMENDE_REFERENCE ON amende (reference)');
        $this->addSql('CREATE INDEX IDX_613014CF43787BBA ON amende (citoyen_id)');
    }
}
