<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211107032155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move subnet to host';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hosts DROP FOREIGN KEY FK_D8CD66B9C9CF9478');
        $this->addSql('DROP INDEX IDX_D8CD66B9C9CF9478 ON hosts');
        $this->addSql('ALTER TABLE hosts ADD subnet_range BINARY(5) NOT NULL COMMENT \'(DC2Type:subnet_range)\', ADD gateway BINARY(4) NOT NULL COMMENT \'(DC2Type:ip_address)\', ADD dns LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', DROP subnet_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hosts ADD subnet_id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', DROP subnet_range, DROP gateway, DROP dns');
        $this->addSql('ALTER TABLE hosts ADD CONSTRAINT FK_D8CD66B9C9CF9478 FOREIGN KEY (subnet_id) REFERENCES subnet (id)');
        $this->addSql('CREATE INDEX IDX_D8CD66B9C9CF9478 ON hosts (subnet_id)');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
