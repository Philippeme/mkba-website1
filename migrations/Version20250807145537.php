<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250807145537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE project_attachments (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, file_size INT NOT NULL, uploaded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_8F0B0D2D166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_links (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(500) NOT NULL, type VARCHAR(50) DEFAULT NULL, INDEX IDX_C2D7A886166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_members (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, role VARCHAR(100) DEFAULT NULL, INDEX IDX_D3BEDE9A166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project_translations (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, locale VARCHAR(5) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_EC103EE4166D1F9C (project_id), UNIQUE INDEX project_locale_unique (project_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) NOT NULL, category VARCHAR(50) NOT NULL, priority VARCHAR(20) NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, budget NUMERIC(12, 2) DEFAULT NULL, responsible VARCHAR(100) DEFAULT NULL, department VARCHAR(100) DEFAULT NULL, status VARCHAR(20) NOT NULL, display_order INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_5C93B3A477153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_attachments ADD CONSTRAINT FK_8F0B0D2D166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_links ADD CONSTRAINT FK_C2D7A886166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_members ADD CONSTRAINT FK_D3BEDE9A166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_translations ADD CONSTRAINT FK_EC103EE4166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE project_attachments DROP FOREIGN KEY FK_8F0B0D2D166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_links DROP FOREIGN KEY FK_C2D7A886166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_members DROP FOREIGN KEY FK_D3BEDE9A166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE project_translations DROP FOREIGN KEY FK_EC103EE4166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project_attachments
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project_links
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project_members
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project_translations
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projects
        SQL);
    }
}
