<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250722132225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE sous_themes (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, theme_id INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE books ADD CONSTRAINT FK_4A1B2A92A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE books ADD CONSTRAINT FK_4A1B2A9259027487 FOREIGN KEY (theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4A1B2A92A76ED395 ON books (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4A1B2A9259027487 ON books (theme_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sous_themes
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE books DROP CONSTRAINT FK_4A1B2A92A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE books DROP CONSTRAINT FK_4A1B2A9259027487
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_4A1B2A92A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_4A1B2A9259027487
        SQL);
    }
}
