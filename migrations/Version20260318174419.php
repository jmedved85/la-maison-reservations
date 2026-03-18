<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260318174419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE restaurant_table (id INT AUTO_INCREMENT NOT NULL, table_number VARCHAR(10) NOT NULL, capacity INT NOT NULL, table_type VARCHAR(50) NOT NULL, location VARCHAR(100) DEFAULT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_TABLE_TYPE (table_type), INDEX IDX_TABLE_NUMBER (table_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE time_slot (id INT AUTO_INCREMENT NOT NULL, time TIME NOT NULL COMMENT \'(DC2Type:time_immutable)\', slot_type VARCHAR(50) NOT NULL, min_capacity INT NOT NULL, max_capacity INT NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_TIME (time), INDEX IDX_SLOT_TYPE (slot_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE restaurant_table');
        $this->addSql('DROP TABLE time_slot');
    }
}
