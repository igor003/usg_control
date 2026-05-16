<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gender applicability to organs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE organs ADD gender_applicability VARCHAR(20) DEFAULT 'any' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs DROP gender_applicability');
    }
}
