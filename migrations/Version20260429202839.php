<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429202839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add districts and cities tables and link patients to cities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE districts (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_DISTRICTS_NAME (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cities (id INT AUTO_INCREMENT NOT NULL, district_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_D95DB16BAE80F5DF (district_id), UNIQUE INDEX UNIQ_CITIES_DISTRICT_NAME (district_id, name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cities ADD CONSTRAINT FK_D95DB16BAE80F5DF FOREIGN KEY (district_id) REFERENCES districts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patients ADD city_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2C8BAC62AF FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2CCC2E2C8BAC62AF ON patients (city_id)');
        $this->addSql('ALTER TABLE patients DROP city');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE patients ADD city VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patients DROP FOREIGN KEY FK_2CCC2E2C8BAC62AF');
        $this->addSql('DROP INDEX IDX_2CCC2E2C8BAC62AF ON patients');
        $this->addSql('ALTER TABLE patients DROP city_id');
        $this->addSql('ALTER TABLE cities DROP FOREIGN KEY FK_D95DB16BAE80F5DF');
        $this->addSql('DROP TABLE cities');
        $this->addSql('DROP TABLE districts');
    }
}
