<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429203123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize cities district index name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities RENAME INDEX idx_d95db16bae80f5df TO IDX_D95DB16BB08FA272');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities RENAME INDEX IDX_D95DB16BB08FA272 TO idx_d95db16bae80f5df');
    }
}
