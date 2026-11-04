<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104085050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Movie: replace return (string) by year (integer)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__movie AS SELECT id, title, description, CAST(NULLIF(TRIM("return"), \'\') AS INTEGER) AS year FROM movie');
        $this->addSql('DROP TABLE movie');
        $this->addSql('CREATE TABLE movie (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, year INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO movie (id, title, description, year) SELECT id, title, description, year FROM __temp__movie');
        $this->addSql('DROP TABLE __temp__movie');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__movie AS SELECT id, title, description, COALESCE(CAST(year AS VARCHAR(255)), \'\') AS "return" FROM movie');
        $this->addSql('DROP TABLE movie');
        $this->addSql('CREATE TABLE movie (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, return VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO movie (id, title, description, return) SELECT id, title, description, "return" FROM __temp__movie');
        $this->addSql('DROP TABLE __temp__movie');
    }
}
