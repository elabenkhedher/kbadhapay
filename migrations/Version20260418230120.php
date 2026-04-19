<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418230120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__taxe AS SELECT id, nom_taxe, description, montant, actif FROM taxe');
        $this->addSql('DROP TABLE taxe');
        $this->addSql('CREATE TABLE taxe (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_taxe VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, montant NUMERIC(10, 3) NOT NULL, actif BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO taxe (id, nom_taxe, description, montant, actif) SELECT id, nom_taxe, description, montant, actif FROM __temp__taxe');
        $this->addSql('DROP TABLE __temp__taxe');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__taxe AS SELECT id, nom_taxe, description, montant, actif FROM taxe');
        $this->addSql('DROP TABLE taxe');
        $this->addSql('CREATE TABLE taxe (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom_taxe VARCHAR(255) NOT NULL, description CLOB NOT NULL, montant NUMERIC(10, 3) NOT NULL, actif BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO taxe (id, nom_taxe, description, montant, actif) SELECT id, nom_taxe, description, montant, actif FROM __temp__taxe');
        $this->addSql('DROP TABLE __temp__taxe');
    }
}
