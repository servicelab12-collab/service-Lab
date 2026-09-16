# Déploiement Railway (ServiceLab Partner Portal)

Stack : **Symfony 7 + PHP 8.3 + MySQL** (les migrations sont MySQL, pas Postgres).

## 1. Pousser le code sur GitHub

Le repo local n’a pas encore de remote. Crée un repo GitHub privé, puis :

```bash
cd ~/Projects/servicelab-partner-portal
git add .
git commit -m "Prepare Railway deployment"
# crée le repo sur GitHub, puis :
git remote add origin https://github.com/TON_USER/servicelab-partner-portal.git
git push -u origin master
```

Ne pousse **jamais** `.env.local` (déjà dans `.gitignore`).

## 2. Projet Railway

1. Va sur [railway.app](https://railway.app) → **New Project**
2. **Deploy from GitHub repo** → choisis ce repo
3. Sur le canvas : **Create** → **Database** → **Add MySQL**

## 3. Variables d’environnement (service app)

Dans le service de l’app → **Variables** :

| Variable | Valeur |
|----------|--------|
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `APP_SECRET` | génère avec `openssl rand -hex 16` |
| `COMPOSER_ALLOW_SUPERUSER` | `1` |
| `TRUSTED_PROXIES` | `*` |
| `TRUSTED_HEADERS` | `x-forwarded-for,x-forwarded-proto,x-forwarded-port,x-forwarded-host` |
| `DATABASE_URL` | voir ci-dessous |
| `DEFAULT_URI` | `https://TON_DOMAINE.up.railway.app` (après génération du domaine) |
| `MAILER_DSN` | `null://null` (ou un vrai SMTP plus tard) |

### `DATABASE_URL` (MySQL Railway)

Format Doctrine :

```text
mysql://${{MySQL.MYSQLUSER}}:${{MySQL.MYSQLPASSWORD}}@${{MySQL.MYSQLHOST}}:${{MySQL.MYSQLPORT}}/${{MySQL.MYSQLDATABASE}}?serverVersion=8.0&charset=utf8mb4
```

(Adapte le nom du service MySQL si Railway l’a nommé autrement.)

## 4. Domaine public

Service app → **Settings** → **Networking** → **Generate Domain**.

Remets cette URL dans `DEFAULT_URI`.

## 5. Compte admin (première fois)

Après le 1er déploiement réussi, ouvre un shell sur le service app (Railway UI → service → **Shell**) et lance :

```bash
php bin/console app:ensure-demo-users
```

Comptes créés :
- `admin@servicelab.ca` / `Admin123!`
- `commercial@servicelab.ca` / `Commercial123!`

Change ces mots de passe après la première connexion.

## 6. Vérifier

1. Ouvre `https://TON_DOMAINE.up.railway.app/login`
2. Connecte-toi avec ton compte admin
3. Si erreur 500 → **Deployments** → **View Logs**

## Notes

- Chaque redeploy lance les **migrations** automatiquement (entrypoint Docker).
- Adminer n’est **pas** déployé (exclu du Docker image).
- Coût : MySQL + app = usage Railway (crédits / plan payant selon usage).
