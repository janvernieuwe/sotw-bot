<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180414204544 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            'CREATE TABLE rewatch_winner (id INT AUTO_INCREMENT NOT NULL, member_id BIGINT NOT NULL, anime_id INT NOT NULL, votes INT NOT NULL, display_name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, title VARCHAR(255) NOT NULL, episodes INT NOT NULL, aired VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F9053D9F794BBE89 (anime_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP TABLE rewatch_winner');
    }
}
