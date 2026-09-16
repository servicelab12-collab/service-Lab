<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811221500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add lead canal to link public pages with admin tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE leads ADD canal VARCHAR(30) NOT NULL DEFAULT 'SITE_ACCUEIL'");
        $this->addSql("UPDATE leads SET canal = 'TZANET' WHERE source = 'TZANET'");
        $this->addSql("UPDATE leads SET canal = 'SITE_ACCUEIL' WHERE source = 'DIRECT' AND (code_promo IS NULL OR code_promo = '')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE leads DROP canal');
    }
}
