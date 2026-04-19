<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417002619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reclamation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, description CLOB NOT NULL, date_soumission DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, user_id INTEGER NOT NULL, agent_id INTEGER DEFAULT NULL, CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE6064043414710B FOREIGN KEY (agent_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CE606404A76ED395 ON reclamation (user_id)');
        $this->addSql('CREATE INDEX IDX_CE6064043414710B ON reclamation (agent_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__paiement AS SELECT id, sujet, description, date_soumission, statut FROM paiement');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('CREATE TABLE paiement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, description CLOB NOT NULL, date_soumission DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, user_id INTEGER NOT NULL, taxe_id INTEGER DEFAULT NULL, infraction_id INTEGER DEFAULT NULL, encaisse_par_id INTEGER DEFAULT NULL, CONSTRAINT FK_B1DC7A1EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1E1AB947A4 FOREIGN KEY (taxe_id) REFERENCES taxe (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1E7697C467 FOREIGN KEY (infraction_id) REFERENCES infraction (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1EA4FBCD6F FOREIGN KEY (encaisse_par_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO paiement (id, sujet, description, date_soumission, statut) SELECT id, sujet, description, date_soumission, statut FROM __temp__paiement');
        $this->addSql('DROP TABLE __temp__paiement');
        $this->addSql('CREATE INDEX IDX_B1DC7A1EA76ED395 ON paiement (user_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1E1AB947A4 ON paiement (taxe_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1E7697C467 ON paiement (infraction_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1EA4FBCD6F ON paiement (encaisse_par_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE reclamation');
        $this->addSql('CREATE TEMPORARY TABLE __temp__paiement AS SELECT id, sujet, description, date_soumission, statut FROM paiement');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('CREATE TABLE paiement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, description CLOB NOT NULL, date_soumission DATETIME NOT NULL, statut VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO paiement (id, sujet, description, date_soumission, statut) SELECT id, sujet, description, date_soumission, statut FROM __temp__paiement');
        $this->addSql('DROP TABLE __temp__paiement');
    }
}
