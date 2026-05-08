<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sort_order to organs and initialize ordering';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs ADD sort_order INT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE organs SET sort_order = id WHERE sort_order = 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs DROP sort_order');
    }
}
