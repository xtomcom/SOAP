<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211026133518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Host and subnet relation; Add hostname';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hosts ADD subnet_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', ADD hostname VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE hosts ADD CONSTRAINT FK_D8CD66B9C9CF9478 FOREIGN KEY (subnet_id) REFERENCES subnet (id)');
        $this->addSql('CREATE INDEX IDX_D8CD66B9C9CF9478 ON hosts (subnet_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE hosts DROP FOREIGN KEY FK_D8CD66B9C9CF9478');
        $this->addSql('DROP INDEX IDX_D8CD66B9C9CF9478 ON hosts');
        $this->addSql('ALTER TABLE hosts DROP subnet_id, DROP hostname');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
