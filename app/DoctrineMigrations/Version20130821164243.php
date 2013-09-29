<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20130821164243 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE User ADD last_login_at DATETIME NOT NULL, CHANGE api_id api_id VARCHAR(255) NOT NULL, CHANGE api_key_hash api_key_hash VARCHAR(255) NOT NULL, CHANGE api_created_at api_created_at DATETIME NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE User DROP last_login_at, CHANGE api_id api_id VARCHAR(255) DEFAULT NULL, CHANGE api_key_hash api_key_hash VARCHAR(255) DEFAULT NULL, CHANGE api_created_at api_created_at VARCHAR(255) DEFAULT NULL");
    }
}
