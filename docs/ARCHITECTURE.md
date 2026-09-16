# Architecture — ServiceLab Partner Portal

## Objectif

Application SaaS Symfony pour le partenariat **ServiceLab × TZANET** :

- **Client** : scan QR → landing `/partner/tzanet` → formulaire → lead `source=TZANET`
- **Admin / Commercial** : dashboard, leads, contrats, commissions, offres

## Stack

- PHP 8.3+ / Symfony 7.4
- Doctrine ORM + MySQL (`servicelab_tzanet`)
- Twig + Asset Mapper + Stimulus
- Security (ROLE_ADMIN, ROLE_COMMERCIAL)
- SymfonyCasts Reset Password

## Structure

```
src/
  Controller/
    Admin/          # Espace interne sécurisé
    Security/       # Login / reset password
    Partner/        # (Phase 2) Landing + formulaire public
  Entity/           # User, Lead, Contract, Offer, Commission
  Enum/             # LeadSource, LeadStatus, ContractStatus, CommissionStatus
  Form/
  Repository/
  Service/          # LeadManager, CommissionCalculatorService, …
  DataFixtures/
```

## Règles métier clés

| Source   | Offre partenaire | Commission TZANET      |
|----------|------------------|------------------------|
| TZANET   | Oui              | Oui (à la signature)   |
| DIRECT   | Non              | Non                    |

La commission est calculée uniquement dans `CommissionCalculatorService` à partir de l’offre active.

## Phases

1. **Phase 1 (faite)** — architecture, entités, migrations, auth, dashboard shell
2. **Phase 2** — landing TZANET + formulaire client (maquettes fournies)
3. **Phase 3** — gestion leads + pipeline
4. **Phase 4** — contrats, commissions, offres
5. **Phase 5** — analytics Chart.js
6. **Phase 6** — polish UX / a11y / perf
7. **Phase 7** — tests + security review

## Comptes de démo (fixtures)

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@servicelab.ca | Admin123! | ADMIN |
| commercial@servicelab.ca | Commercial123! | COMMERCIAL |
