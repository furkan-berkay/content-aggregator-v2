<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005133721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_published_at ON content');
        $this->addSql('DROP INDEX idx_tags ON content');
        $this->addSql('DROP INDEX idx_provider_type_score ON content');
        $this->addSql('DROP INDEX idx_type_score ON content');
        $this->addSql('DROP INDEX idx_title_fulltext ON content');
        $this->addSql('DROP INDEX idx_active ON provider');
        $this->addSql('DROP INDEX idx_name ON provider');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_published_at ON content (published_at)');
        $this->addSql('CREATE INDEX idx_tags ON content (tags)');
        $this->addSql('CREATE INDEX idx_provider_type_score ON content (provider_id, type, score)');
        $this->addSql('CREATE INDEX idx_type_score ON content (type, score)');
        $this->addSql('CREATE FULLTEXT INDEX idx_title_fulltext ON content (title)');
        $this->addSql('CREATE INDEX idx_active ON provider (active)');
        $this->addSql('CREATE INDEX idx_name ON provider (name)');
    }
}
