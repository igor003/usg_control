<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430000500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add birth year to patients';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patients ADD birth_year INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patients DROP birth_year');
    }
}
