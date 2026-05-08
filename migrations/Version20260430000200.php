<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create many-to-many relation between organs and parameters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organ_parameters (organ_id INT NOT NULL, parameter_id INT NOT NULL, INDEX IDX_67EC126AB246C241 (organ_id), INDEX IDX_67EC126A701B8E4D (parameter_id), PRIMARY KEY(organ_id, parameter_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE organ_parameters ADD CONSTRAINT FK_67EC126AB246C241 FOREIGN KEY (organ_id) REFERENCES organs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organ_parameters ADD CONSTRAINT FK_67EC126A701B8E4D FOREIGN KEY (parameter_id) REFERENCES parameters (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organ_parameters');
    }
}
