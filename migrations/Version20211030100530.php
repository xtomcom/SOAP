<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211030100530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add preseed, boot template and options';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hosts ADD preseed LONGTEXT DEFAULT NULL, ADD boot_template VARCHAR(255) DEFAULT NULL, ADD boot_template_options LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hosts DROP preseed, DROP boot_template, DROP boot_template_options');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
