<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417001415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE infraction (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type_infraction VARCHAR(255) NOT NULL, montant_amende NUMERIC(10, 0) NOT NULL, lieu VARCHAR(255) NOT NULL, plaque_immat VARCHAR(20) NOT NULL, date_infraction DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, notes CLOB DEFAULT NULL)');
        $this->addSql('CREATE TABLE paiement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, description CLOB NOT NULL, date_soumission DATETIME NOT NULL, statut VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE taxe (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_taxe VARCHAR(255) NOT NULL, description CLOB NOT NULL, montant NUMERIC(10, 0) NOT NULL, actif BOOLEAN NOT NULL)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cin VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_CIN ON "user" (cin)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE infraction');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE taxe');
        $this->addSql('DROP TABLE "user"');
    }
}
