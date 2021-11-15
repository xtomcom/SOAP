<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211030135738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add boot template';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE boot_template (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, ipxe_script LONGTEXT DEFAULT NULL, preseed LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_583AD3BF5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hosts ADD boot_template_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:ulid)\', DROP boot_template_options, DROP boot_template, ADD root_password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE hosts ADD CONSTRAINT FK_D8CD66B9C45A332B FOREIGN KEY (boot_template_id) REFERENCES boot_template (id)');
        $this->addSql('CREATE INDEX IDX_D8CD66B9C45A332B ON hosts (boot_template_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hosts DROP FOREIGN KEY FK_D8CD66B9C45A332B');
        $this->addSql('DROP TABLE boot_template');
        $this->addSql('DROP INDEX IDX_D8CD66B9C45A332B ON hosts');
        $this->addSql('ALTER TABLE hosts ADD boot_template_options LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', DROP boot_template_id, DROP root_password, ADD boot_template VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
