<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814132000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add TZANET business fields: contract first free month + commission annual purchases';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contrats ADD premier_mois_gratuit TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("ALTER TABLE commissions ADD achats_annuel NUMERIC(12, 2) DEFAULT '0.00' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contrats DROP premier_mois_gratuit');
        $this->addSql('ALTER TABLE commissions DROP achats_annuel');
    }
}
