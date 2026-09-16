<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811200033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commissions (id INT AUTO_INCREMENT NOT NULL, montant_signature NUMERIC(10, 2) NOT NULL, retour_annuel NUMERIC(5, 2) NOT NULL, statut VARCHAR(20) NOT NULL, date_paiement DATE DEFAULT NULL, created_at DATETIME NOT NULL, contract_id INT NOT NULL, UNIQUE INDEX UNIQ_7EA273CC2576E0FD (contract_id), INDEX IDX_COMMISSIONS_STATUT (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contrats (id INT AUTO_INCREMENT NOT NULL, loyer_mensuel NUMERIC(10, 2) NOT NULL, duree_mois INT NOT NULL, date_signature DATE NOT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, lead_id INT NOT NULL, UNIQUE INDEX UNIQ_7268396C55458D (lead_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE leads (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, telephone VARCHAR(30) NOT NULL, email VARCHAR(150) NOT NULL, machine_recherchee VARCHAR(150) NOT NULL, source VARCHAR(20) NOT NULL, status VARCHAR(30) NOT NULL, code_promo VARCHAR(50) DEFAULT NULL, reference VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_17904552AEA34913 (reference), INDEX IDX_LEADS_SOURCE (source), INDEX IDX_LEADS_STATUS (status), INDEX IDX_LEADS_CREATED_AT (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offres (id INT AUTO_INCREMENT NOT NULL, premier_mois_gratuit TINYINT NOT NULL, commission_signature NUMERIC(10, 2) NOT NULL, retour_annuel NUMERIC(5, 2) NOT NULL, date_debut DATE NOT NULL, date_fin DATE DEFAULT NULL, active TINYINT NOT NULL, commission_equals_monthly_rent TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_USERS_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commissions ADD CONSTRAINT FK_7EA273CC2576E0FD FOREIGN KEY (contract_id) REFERENCES contrats (id)');
        $this->addSql('ALTER TABLE contrats ADD CONSTRAINT FK_7268396C55458D FOREIGN KEY (lead_id) REFERENCES leads (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commissions DROP FOREIGN KEY FK_7EA273CC2576E0FD');
        $this->addSql('ALTER TABLE contrats DROP FOREIGN KEY FK_7268396C55458D');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE commissions');
        $this->addSql('DROP TABLE contrats');
        $this->addSql('DROP TABLE leads');
        $this->addSql('DROP TABLE offres');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
