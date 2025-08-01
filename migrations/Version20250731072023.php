<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250731072023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C1137ABCF');
        $this->addSql('DROP INDEX IDX_6A2CA10C1137ABCF ON media');
        $this->addSql('ALTER TABLE media DROP album_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media ADD album_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C1137ABCF FOREIGN KEY (album_id) REFERENCES album (id)');
        $this->addSql('CREATE INDEX IDX_6A2CA10C1137ABCF ON media (album_id)');
    }
}
