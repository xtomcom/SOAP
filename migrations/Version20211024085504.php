<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211024085504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subnet';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE subnet (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', subnet_range BINARY(5) NOT NULL COMMENT \'(DC2Type:subnet_range)\', gateway BINARY(4) NOT NULL COMMENT \'(DC2Type:ip_address)\', dns LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_91C24216CE96A830 (subnet_range), UNIQUE INDEX UNIQ_91C242165E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE subnet');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
