<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow examination sessions without a patient for incognito sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE examination_sessions CHANGE patient_id patient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE examination_sessions DROP FOREIGN KEY FK_48A3D9106B899279');
        $this->addSql('ALTER TABLE examination_sessions ADD CONSTRAINT FK_48A3D9106B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM examination_sessions WHERE patient_id IS NULL');
        $this->addSql('ALTER TABLE examination_sessions DROP FOREIGN KEY FK_48A3D9106B899279');
        $this->addSql('ALTER TABLE examination_sessions CHANGE patient_id patient_id INT NOT NULL');
        $this->addSql('ALTER TABLE examination_sessions ADD CONSTRAINT FK_48A3D9106B899279 FOREIGN KEY (patient_id) REFERENCES patients (id)');
    }
}
