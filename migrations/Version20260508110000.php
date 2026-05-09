<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ultrasound types and link organs to ultrasound type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ultrasound_types (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_C0A8C8A15E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE organs ADD ultrasound_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organs ADD CONSTRAINT FK_56B2FA6844DBA2B2 FOREIGN KEY (ultrasound_type_id) REFERENCES ultrasound_types (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_56B2FA6844DBA2B2 ON organs (ultrasound_type_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs DROP FOREIGN KEY FK_56B2FA6844DBA2B2');
        $this->addSql('DROP TABLE ultrasound_types');
        $this->addSql('DROP INDEX IDX_56B2FA6844DBA2B2 ON organs');
        $this->addSql('ALTER TABLE organs DROP ultrasound_type_id');
    }
}
