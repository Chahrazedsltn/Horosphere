<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du schéma initial Horosphere (utilisateur, site, pointage, demande, document, alerte)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE utilisateur (
                id            INT UNSIGNED AUTO_INCREMENT NOT NULL,
                email         VARCHAR(180)  NOT NULL,
                mot_de_passe  VARCHAR(255)  NOT NULL,
                prenom        VARCHAR(100)  NOT NULL,
                nom           VARCHAR(100)  NOT NULL,
                role          ENUM('AGENT','RH','ADMIN') NOT NULL DEFAULT 'AGENT',
                departement   VARCHAR(100)  DEFAULT NULL,
                date_creation DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                consentement_rgpd TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE site (
                id               INT UNSIGNED AUTO_INCREMENT NOT NULL,
                nom              VARCHAR(100)    NOT NULL,
                adresse          VARCHAR(255)    NOT NULL,
                latitude         DECIMAL(10, 8)  NOT NULL,
                longitude        DECIMAL(11, 8)  NOT NULL,
                rayon_metres     INT UNSIGNED    NOT NULL DEFAULT 200,
                geofencing_actif TINYINT(1)      NOT NULL DEFAULT 1,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE pointage (
                id               INT UNSIGNED AUTO_INCREMENT NOT NULL,
                utilisateur_id   INT UNSIGNED     NOT NULL,
                site_id          INT UNSIGNED     DEFAULT NULL,
                date_jour        DATE             NOT NULL,
                heure_arrivee    DATETIME         NOT NULL,
                heure_depart     DATETIME         DEFAULT NULL,
                statut           ENUM('EN_COURS','VALIDE','HORS_ZONE','ANOMALIE') NOT NULL DEFAULT 'EN_COURS',
                coordonnees_gps  VARCHAR(255)     DEFAULT NULL,
                est_anomalie     TINYINT(1)       NOT NULL DEFAULT 0,
                INDEX IDX_DA540494FB88E14F (utilisateur_id),
                INDEX IDX_DA540494F6BD1646 (site_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE demande (
                id              INT UNSIGNED AUTO_INCREMENT NOT NULL,
                utilisateur_id  INT UNSIGNED NOT NULL,
                type_demande    ENUM('CONGE','CORRECTION','ABSENCE','AUTRE') NOT NULL,
                statut          ENUM('EN_ATTENTE','APPROUVEE','REJETEE') NOT NULL DEFAULT 'EN_ATTENTE',
                date_debut      DATE NOT NULL,
                date_fin        DATE NOT NULL,
                motif           LONGTEXT DEFAULT NULL,
                date_creation   DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_2694D7A5FB88E14F (utilisateur_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE document (
                id              INT UNSIGNED AUTO_INCREMENT NOT NULL,
                utilisateur_id  INT UNSIGNED NOT NULL,
                type_document   ENUM('CSV','PDF') NOT NULL,
                chemin_fichier  VARCHAR(255) NOT NULL,
                date_creation   DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_D8698A76FB88E14F (utilisateur_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE alerte (
                id              INT UNSIGNED AUTO_INCREMENT NOT NULL,
                utilisateur_id  INT UNSIGNED NOT NULL,
                pointage_id     INT UNSIGNED DEFAULT NULL,
                type_alerte     ENUM('OUBLI_DEPART','HORS_ZONE','ECART_HORAIRE') NOT NULL,
                message         LONGTEXT NOT NULL,
                date_creation   DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                est_lue         TINYINT(1) NOT NULL DEFAULT 0,
                INDEX IDX_3EF5F6A1FB88E14F (utilisateur_id),
                INDEX IDX_3EF5F6A1C40A3667 (pointage_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        // Contraintes FK
        $this->addSql('ALTER TABLE pointage ADD CONSTRAINT FK_DA540494FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE pointage ADD CONSTRAINT FK_DA540494F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3EF5F6A1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3EF5F6A1C40A3667 FOREIGN KEY (pointage_id) REFERENCES pointage (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3EF5F6A1C40A3667');
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3EF5F6A1FB88E14F');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76FB88E14F');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5FB88E14F');
        $this->addSql('ALTER TABLE pointage DROP FOREIGN KEY FK_DA540494F6BD1646');
        $this->addSql('ALTER TABLE pointage DROP FOREIGN KEY FK_DA540494FB88E14F');
        $this->addSql('DROP TABLE alerte');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE pointage');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE utilisateur');
    }
}
