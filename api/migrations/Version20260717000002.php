<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add solde_conges and solde_rtt to utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD solde_conges INT NOT NULL DEFAULT 25');
        $this->addSql('ALTER TABLE utilisateur ADD solde_rtt INT NOT NULL DEFAULT 10');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP solde_conges');
        $this->addSql('ALTER TABLE utilisateur DROP solde_rtt');
    }
}
