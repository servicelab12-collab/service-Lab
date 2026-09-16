# ServiceLab Partner Portal

Plateforme web de référencement partenaire ServiceLab × TZANET, suivi des leads et gestion des commissions.

## Prérequis

- PHP 8.3+
- Composer
- MySQL 8
- Symfony CLI (optionnel)

## Installation

```bash
cd ~/Projects/servicelab-partner-portal
composer install
# .env.local déjà configuré pour MySQL local (root sans mot de passe)
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony server:start -d
```

Ouvrir : http://127.0.0.1:8000/login

## Comptes démo

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@servicelab.ca | Admin123! |
| Commercial | commercial@servicelab.ca | Commercial123! |

## Documentation

- Architecture : [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- Déploiement Railway : [docs/DEPLOY_RAILWAY.md](docs/DEPLOY_RAILWAY.md)
