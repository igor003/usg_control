<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add display order to organ controls';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organ_parameters ADD sort_order INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organ_parameters DROP sort_order');
    }
}
