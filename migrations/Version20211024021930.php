<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211024021930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hosts (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', registration_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', deletion_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', mac_address BINARY(6) NOT NULL COMMENT \'(DC2Type:mac_address)\', ip_address BINARY(4) NOT NULL COMMENT \'(DC2Type:ip_address)\', ipxe_script LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, reaches_to_expire INT DEFAULT NULL, expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:timestamp)\', UNIQUE INDEX UNIQ_D8CD66B922FFD58C (ip_address), UNIQUE INDEX UNIQ_D8CD66B9833D8F43 (registration_id), UNIQUE INDEX UNIQ_D8CD66B9F05F9129 (deletion_id), UNIQUE INDEX UNIQ_D8CD66B9B728E969 (mac_address), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE operations (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', host_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', dispatched_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:timestamp)\', handled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:timestamp)\', message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:object)\', INDEX IDX_281453481FB8D185 (host_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE users (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', username VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE hosts');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE operations');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE users');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
