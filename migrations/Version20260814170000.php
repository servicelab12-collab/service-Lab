<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link contracts to the partner offer applied at signature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contrats ADD offer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contrats ADD CONSTRAINT FK_CONTRATS_OFFER FOREIGN KEY (offer_id) REFERENCES offres (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_CONTRATS_OFFER ON contrats (offer_id)');

        // Backfill existing contracts with the latest known offer
        $this->addSql('UPDATE contrats SET offer_id = (SELECT id FROM (SELECT id FROM offres ORDER BY active DESC, date_debut DESC, id DESC LIMIT 1) AS latest_offer)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contrats DROP FOREIGN KEY FK_CONTRATS_OFFER');
        $this->addSql('DROP INDEX IDX_CONTRATS_OFFER ON contrats');
        $this->addSql('ALTER TABLE contrats DROP offer_id');
    }
}
