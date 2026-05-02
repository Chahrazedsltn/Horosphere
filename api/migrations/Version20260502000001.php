<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout : pause dans pointage, password_reset_token, audit_log, messenger_messages';
    }

    public function up(Schema $schema): void
    {
        // ─── Pause sur pointage ───────────────────────────────────────────────
        $this->addSql(<<<'SQL'
            ALTER TABLE pointage
                MODIFY COLUMN statut ENUM('EN_COURS','EN_PAUSE','VALIDE','HORS_ZONE','ANOMALIE')
                    NOT NULL DEFAULT 'EN_COURS',
                ADD COLUMN heure_pause_debut DATETIME DEFAULT NULL AFTER heure_depart,
                ADD COLUMN durees_pause_minutes INT UNSIGNED NOT NULL DEFAULT 0 AFTER heure_pause_debut
        SQL);

        // ─── Tokens de réinitialisation de mot de passe ───────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE password_reset_token (
                id         INT UNSIGNED AUTO_INCREMENT NOT NULL,
                email      VARCHAR(180)             NOT NULL,
                token      VARCHAR(64)              NOT NULL,
                expires_at DATETIME                 NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                used_at    DATETIME                 DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at DATETIME                 NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_PRT_TOKEN (token),
                INDEX idx_reset_token (token),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        // ─── Journal d'audit ──────────────────────────────────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE audit_log (
                id                 INT UNSIGNED AUTO_INCREMENT NOT NULL,
                action             VARCHAR(60)   NOT NULL,
                utilisateur_id     INT UNSIGNED  DEFAULT NULL,
                utilisateur_email  VARCHAR(100)  DEFAULT NULL,
                cible_type         VARCHAR(60)   DEFAULT NULL,
                cible_id           INT UNSIGNED  DEFAULT NULL,
                details            JSON          DEFAULT NULL,
                ip_address         VARCHAR(45)   DEFAULT NULL,
                created_at         DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_audit_action (action),
                INDEX idx_audit_user (utilisateur_id),
                INDEX idx_audit_date (created_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        // ─── Table Messenger (transport Doctrine async) ───────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS messenger_messages (
                id            BIGINT AUTO_INCREMENT NOT NULL,
                body          LONGTEXT NOT NULL,
                headers       LONGTEXT NOT NULL,
                queue_name    VARCHAR(190) NOT NULL,
                created_at    DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                available_at  DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                delivered_at  DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_75EA56E016BA31DB (delivered_at),
                INDEX IDX_75EA56E0FB7336F0 (queue_name),
                INDEX IDX_75EA56E0E3BD61CE (available_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
        $this->addSql('DROP TABLE IF EXISTS audit_log');
        $this->addSql('DROP TABLE IF EXISTS password_reset_token');
        $this->addSql(<<<'SQL'
            ALTER TABLE pointage
                MODIFY COLUMN statut ENUM('EN_COURS','VALIDE','HORS_ZONE','ANOMALIE')
                    NOT NULL DEFAULT 'EN_COURS',
                DROP COLUMN heure_pause_debut,
                DROP COLUMN durees_pause_minutes
        SQL);
    }
}
