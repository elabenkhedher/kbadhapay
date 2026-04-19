<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418212930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, message CLOB NOT NULL, canal VARCHAR(20) NOT NULL, date_planifiee DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, type_lien VARCHAR(255) DEFAULT NULL, id_lien INTEGER DEFAULT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)');
        $this->addSql('ALTER TABLE infraction ADD COLUMN date_echeance DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN preferences_notification CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE notification');
        $this->addSql('CREATE TEMPORARY TABLE __temp__infraction AS SELECT id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes, user_id, agent_id FROM infraction');
        $this->addSql('DROP TABLE infraction');
        $this->addSql('CREATE TABLE infraction (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type_infraction VARCHAR(255) NOT NULL, montant_amende NUMERIC(10, 3) NOT NULL, lieu VARCHAR(255) NOT NULL, plaque_immat VARCHAR(20) NOT NULL, date_infraction DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, notes CLOB DEFAULT NULL, user_id INTEGER DEFAULT NULL, agent_id INTEGER DEFAULT NULL, CONSTRAINT FK_C1A458F5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C1A458F53414710B FOREIGN KEY (agent_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO infraction (id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes, user_id, agent_id) SELECT id, type_infraction, montant_amende, lieu, plaque_immat, date_infraction, statut, notes, user_id, agent_id FROM __temp__infraction');
        $this->addSql('DROP TABLE __temp__infraction');
        $this->addSql('CREATE INDEX IDX_C1A458F5A76ED395 ON infraction (user_id)');
        $this->addSql('CREATE INDEX IDX_C1A458F53414710B ON infraction (agent_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, cin, roles, password FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cin VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO "user" (id, cin, roles, password) SELECT id, cin, roles, password FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_CIN ON "user" (cin)');
    }
}
