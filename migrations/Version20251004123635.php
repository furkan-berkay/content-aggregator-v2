<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251004123635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content (id INT AUTO_INCREMENT NOT NULL, provider_id INT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, views INT DEFAULT NULL, likes INT DEFAULT NULL, reactions INT DEFAULT NULL, comments INT DEFAULT NULL, reading_time INT DEFAULT NULL, published_at DATETIME DEFAULT NULL, tags JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', score DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FEC530A9A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE provider (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, url VARCHAR(255) NOT NULL, format VARCHAR(10) NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE content ADD CONSTRAINT FK_FEC530A9A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
        $this->addSql('ALTER TABLE contents DROP FOREIGN KEY contents_ibfk_1');
        $this->addSql('DROP TABLE contents');
        $this->addSql('DROP TABLE providers');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contents (ai_id BIGINT AUTO_INCREMENT NOT NULL, provider_id INT NOT NULL, provider_item_id VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, views INT DEFAULT 0, likes INT DEFAULT 0, reactions INT DEFAULT 0, comments INT DEFAULT 0, reading_time INT DEFAULT 0, published_at DATETIME DEFAULT NULL, tags JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', score DOUBLE PRECISION DEFAULT \'0\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_type (type), INDEX idx_provider_item_id (provider_item_id), INDEX idx_score (score), INDEX idx_provider_id (provider_id), INDEX idx_title (title), PRIMARY KEY(ai_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE providers (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, format VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, active TINYINT(1) DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE contents ADD CONSTRAINT contents_ibfk_1 FOREIGN KEY (provider_id) REFERENCES providers (id)');
        $this->addSql('ALTER TABLE content DROP FOREIGN KEY FK_FEC530A9A53A8AA');
        $this->addSql('DROP TABLE content');
        $this->addSql('DROP TABLE provider');
    }
}
