<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427220417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD COLUMN nom VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN prenom VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, cin, roles, password, email, preferences_notification, telephone, push_subscription, id_document_path, passport_path FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cin VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, preferences_notification CLOB DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, push_subscription CLOB DEFAULT NULL, id_document_path VARCHAR(255) DEFAULT NULL, passport_path VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, cin, roles, password, email, preferences_notification, telephone, push_subscription, id_document_path, passport_path) SELECT id, cin, roles, password, email, preferences_notification, telephone, push_subscription, id_document_path, passport_path FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_CIN ON "user" (cin)');
    }
}
