<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230508214132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_left_id INTEGER NOT NULL, player_right_id INTEGER DEFAULT NULL, state VARCHAR(255) NOT NULL, play_left VARCHAR(255) DEFAULT NULL, play_right VARCHAR(255) DEFAULT NULL, result VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_232B318C83983147 FOREIGN KEY (player_left_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_232B318C524029D5 FOREIGN KEY (player_right_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_232B318C83983147 ON game (player_left_id)');
        $this->addSql('CREATE INDEX IDX_232B318C524029D5 ON game (player_right_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE game');
    }
}
