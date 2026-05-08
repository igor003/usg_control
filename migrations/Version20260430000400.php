<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430000400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create examination session result tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE examination_sessions (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, session_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', session_note LONGTEXT DEFAULT NULL, session_conclusion LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_48A3D9106B899279 (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE examination_session_organs (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, organ_id INT DEFAULT NULL, organ_name VARCHAR(255) NOT NULL, organ_image_path VARCHAR(255) DEFAULT NULL, side VARCHAR(20) NOT NULL, organ_note LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B41E6ED3613FECDF (session_id), INDEX IDX_B41E6ED3B246C241 (organ_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE examination_session_parameter_results (id INT AUTO_INCREMENT NOT NULL, session_organ_id INT NOT NULL, parameter_id INT DEFAULT NULL, parameter_name VARCHAR(255) NOT NULL, parameter_value_type VARCHAR(20) NOT NULL, parameter_value_content JSON DEFAULT NULL, value LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F6013E3DD33B43E5 (session_organ_id), INDEX IDX_F6013E3D701B8E4D (parameter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE examination_sessions ADD CONSTRAINT FK_48A3D9106B899279 FOREIGN KEY (patient_id) REFERENCES patients (id)');
        $this->addSql('ALTER TABLE examination_session_organs ADD CONSTRAINT FK_B41E6ED3613FECDF FOREIGN KEY (session_id) REFERENCES examination_sessions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE examination_session_organs ADD CONSTRAINT FK_B41E6ED3B246C241 FOREIGN KEY (organ_id) REFERENCES organs (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE examination_session_parameter_results ADD CONSTRAINT FK_F6013E3DD33B43E5 FOREIGN KEY (session_organ_id) REFERENCES examination_session_organs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE examination_session_parameter_results ADD CONSTRAINT FK_F6013E3D701B8E4D FOREIGN KEY (parameter_id) REFERENCES parameters (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE examination_session_parameter_results DROP FOREIGN KEY FK_F6013E3DD33B43E5');
        $this->addSql('ALTER TABLE examination_session_parameter_results DROP FOREIGN KEY FK_F6013E3D701B8E4D');
        $this->addSql('ALTER TABLE examination_session_organs DROP FOREIGN KEY FK_B41E6ED3613FECDF');
        $this->addSql('ALTER TABLE examination_session_organs DROP FOREIGN KEY FK_B41E6ED3B246C241');
        $this->addSql('ALTER TABLE examination_sessions DROP FOREIGN KEY FK_48A3D9106B899279');
        $this->addSql('DROP TABLE examination_session_parameter_results');
        $this->addSql('DROP TABLE examination_session_organs');
        $this->addSql('DROP TABLE examination_sessions');
    }
}
