<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429192649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image path to organs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs ADD image_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs DROP image_path');
    }
}
