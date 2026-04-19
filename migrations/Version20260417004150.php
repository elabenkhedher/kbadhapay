<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417004150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__infraction AS SELECT id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes FROM infraction');
        $this->addSql('DROP TABLE infraction');
        $this->addSql('CREATE TABLE infraction (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type_infraction VARCHAR(255) NOT NULL, montant_amende NUMERIC(10, 0) NOT NULL, lieu VARCHAR(255) NOT NULL, plaque_immat VARCHAR(20) NOT NULL, date_infraction DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, notes CLOB DEFAULT NULL, user_id INTEGER DEFAULT NULL, agent_id INTEGER DEFAULT NULL, CONSTRAINT FK_C1A458F5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C1A458F53414710B FOREIGN KEY (agent_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO infraction (id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes) SELECT id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes FROM __temp__infraction');
        $this->addSql('DROP TABLE __temp__infraction');
        $this->addSql('CREATE INDEX IDX_C1A458F5A76ED395 ON infraction (user_id)');
        $this->addSql('CREATE INDEX IDX_C1A458F53414710B ON infraction (agent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__infraction AS SELECT id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes FROM infraction');
        $this->addSql('DROP TABLE infraction');
        $this->addSql('CREATE TABLE infraction (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type_infraction VARCHAR(255) NOT NULL, montant_amende NUMERIC(10, 0) NOT NULL, lieu VARCHAR(255) NOT NULL, plaque_immat VARCHAR(20) NOT NULL, date_infraction DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, notes CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO infraction (id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes) SELECT id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes FROM __temp__infraction');
        $this->addSql('DROP TABLE __temp__infraction');
    }
}
