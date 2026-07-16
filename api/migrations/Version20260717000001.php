<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add justificatif column to demande table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande ADD justificatif VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande DROP justificatif');
    }
}
