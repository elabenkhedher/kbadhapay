<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417005544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__paiement AS SELECT id, sujet, description, date_soumission, statut, user_id, taxe_id, infraction_id, encaisse_par_id FROM paiement');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('CREATE TABLE paiement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, date_soumission DATETIME DEFAULT NULL, statut VARCHAR(255) NOT NULL, user_id INTEGER NOT NULL, taxe_id INTEGER DEFAULT NULL, infraction_id INTEGER DEFAULT NULL, encaisse_par_id INTEGER DEFAULT NULL, reference VARCHAR(50) DEFAULT NULL, mode_paiement VARCHAR(50) DEFAULT NULL, montant NUMERIC(10, 3) DEFAULT NULL, date_paiement DATETIME DEFAULT NULL, CONSTRAINT FK_B1DC7A1EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1E1AB947A4 FOREIGN KEY (taxe_id) REFERENCES taxe (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1E7697C467 FOREIGN KEY (infraction_id) REFERENCES infraction (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1EA4FBCD6F FOREIGN KEY (encaisse_par_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO paiement (id, sujet, description, date_soumission, statut, user_id, taxe_id, infraction_id, encaisse_par_id) SELECT id, sujet, description, date_soumission, statut, user_id, taxe_id, infraction_id, encaisse_par_id FROM __temp__paiement');
        $this->addSql('DROP TABLE __temp__paiement');
        $this->addSql('CREATE INDEX IDX_B1DC7A1EA4FBCD6F ON paiement (encaisse_par_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1E7697C467 ON paiement (infraction_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1E1AB947A4 ON paiement (taxe_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1EA76ED395 ON paiement (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B1DC7A1EAEA34913 ON paiement (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__paiement AS SELECT id, sujet, description, date_soumission, statut, user_id, taxe_id, infraction_id, encaisse_par_id FROM paiement');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('CREATE TABLE paiement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sujet VARCHAR(255) NOT NULL, description CLOB NOT NULL, date_soumission DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, user_id INTEGER NOT NULL, taxe_id INTEGER DEFAULT NULL, infraction_id INTEGER DEFAULT NULL, encaisse_par_id INTEGER DEFAULT NULL, CONSTRAINT FK_B1DC7A1EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1E1AB947A4 FOREIGN KEY (taxe_id) REFERENCES taxe (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1E7697C467 FOREIGN KEY (infraction_id) REFERENCES infraction (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1DC7A1EA4FBCD6F FOREIGN KEY (encaisse_par_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO paiement (id, sujet, description, date_soumission, statut, user_id, taxe_id, infraction_id, encaisse_par_id) SELECT id, sujet, description, date_soumission, statut, user_id, taxe_id, infraction_id, encaisse_par_id FROM __temp__paiement');
        $this->addSql('DROP TABLE __temp__paiement');
        $this->addSql('CREATE INDEX IDX_B1DC7A1EA76ED395 ON paiement (user_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1E1AB947A4 ON paiement (taxe_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1E7697C467 ON paiement (infraction_id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1EA4FBCD6F ON paiement (encaisse_par_id)');
    }
}
