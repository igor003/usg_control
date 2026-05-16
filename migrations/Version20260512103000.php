<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert ultrasound type relation to many-to-many and store selected ultrasound type on sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ultrasound_type_organs (ultrasound_type_id INT NOT NULL, organ_id INT NOT NULL, sort_order INT NOT NULL, INDEX IDX_B2F118744DBA2B2 (ultrasound_type_id), INDEX IDX_B2F11874F7D8D16F (organ_id), PRIMARY KEY(ultrasound_type_id, organ_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ultrasound_type_organs ADD CONSTRAINT FK_B2F118744DBA2B2 FOREIGN KEY (ultrasound_type_id) REFERENCES ultrasound_types (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ultrasound_type_organs ADD CONSTRAINT FK_B2F11874F7D8D16F FOREIGN KEY (organ_id) REFERENCES organs (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO ultrasound_type_organs (ultrasound_type_id, organ_id, sort_order) SELECT ultrasound_type_id, id, sort_order FROM organs WHERE ultrasound_type_id IS NOT NULL');
        $this->addSql('ALTER TABLE examination_sessions ADD ultrasound_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE examination_sessions ADD CONSTRAINT FK_2C9B1A7944DBA2B2 FOREIGN KEY (ultrasound_type_id) REFERENCES ultrasound_types (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2C9B1A7944DBA2B2 ON examination_sessions (ultrasound_type_id)');
        $this->addSql('ALTER TABLE organs DROP FOREIGN KEY FK_56B2FA6844DBA2B2');
        $this->addSql('DROP INDEX IDX_56B2FA6844DBA2B2 ON organs');
        $this->addSql('ALTER TABLE organs DROP ultrasound_type_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organs ADD ultrasound_type_id INT DEFAULT NULL');
        $this->addSql('UPDATE organs o SET ultrasound_type_id = (SELECT uto.ultrasound_type_id FROM ultrasound_type_organs uto WHERE uto.organ_id = o.id ORDER BY uto.sort_order ASC, uto.ultrasound_type_id ASC LIMIT 1)');
        $this->addSql('ALTER TABLE organs ADD CONSTRAINT FK_56B2FA6844DBA2B2 FOREIGN KEY (ultrasound_type_id) REFERENCES ultrasound_types (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_56B2FA6844DBA2B2 ON organs (ultrasound_type_id)');
        $this->addSql('ALTER TABLE examination_sessions DROP FOREIGN KEY FK_2C9B1A7944DBA2B2');
        $this->addSql('DROP INDEX IDX_2C9B1A7944DBA2B2 ON examination_sessions');
        $this->addSql('ALTER TABLE examination_sessions DROP ultrasound_type_id');
        $this->addSql('DROP TABLE ultrasound_type_organs');
    }
}
