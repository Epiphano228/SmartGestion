# SmartGestion

Application professionnelle de gestion commerciale construite avec Laravel 12, Livewire 4, Tailwind CSS 4 et PostgreSQL.

## Fonctionnalités

- tableau de bord, chiffre d’affaires, encaissements, impayés et statistiques ;
- clients, catégories, produits/services, TVA et stock optionnel ;
- devis, proformas et factures avec PDF personnalisés ;
- conversion d’un devis ou d’un proforma en facture en un clic ;
- paiements partiels ou complets avec calcul du solde ;
- identité de l’entreprise, logo, NIF, mentions, devise et numérotation configurables ;
- comptes Administrateur/Gestionnaire avec photos de profil et journal d’activité ;
- interface responsive et déploiement Docker/Render.

## Développement local avec Docker

1. Générez une clé Laravel :

   ```bash
   php artisan key:generate --show
   ```

2. Placez-la dans `APP_KEY` de votre environnement, puis lancez :

   ```bash
   docker compose up --build
   docker compose exec app php artisan db:seed
   ```

3. Ouvrez `http://localhost:8080`.

Le compte initial par défaut est `admin@smartgestion.local` / `ChangeMe123!`. Définissez plutôt `ADMIN_EMAIL` et `ADMIN_PASSWORD` avant le premier seeding.

## Installation locale sans Docker

Prérequis : PHP 8.2+, Composer, Node.js 20+ et PostgreSQL.

```bash
cp .env.example .env
composer install
npm install
npm run build
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
php artisan serve
```

## Vérification

```bash
php artisan test
npm run build
```

## Déploiement sur Render

Le fichier `render.yaml` crée automatiquement :

- le service Docker `smartgestion` dans la région de Francfort ;
- une base PostgreSQL privée ;
- un disque persistant pour les logos et photos de profil ;
- les migrations avant chaque déploiement ;
- le compte administrateur lors du premier déploiement.

### Première mise en ligne

1. Générez une clé sans modifier votre fichier local :

   ```bash
   php artisan key:generate --show
   ```

2. Dans Render, choisissez **New > Blueprint**.
3. Connectez le dépôt `Epiphano228/SmartGestion` et sélectionnez la branche `main`.
4. Render détecte `render.yaml`. Renseignez les secrets demandés :

   - `APP_KEY` : la clé générée à l’étape 1, commençant par `base64:` ;
   - `ADMIN_EMAIL` : l’adresse du premier administrateur ;
   - `ADMIN_PASSWORD` : un mot de passe fort d’au moins 12 caractères.

5. Cliquez sur **Apply** et attendez que le service et PostgreSQL soient marqués disponibles.
6. Ouvrez l’URL `https://smartgestion.onrender.com` indiquée par Render et connectez-vous avec les identifiants saisis.

`APP_URL`, `ASSET_URL` et les identifiants PostgreSQL sont reliés automatiquement par le Blueprint. Ne copiez jamais votre `.env` local dans GitHub ou Render.

### Configuration des emails

Le premier déploiement utilise `MAIL_MAILER=log`. Pour envoyer réellement les PDF, ajoutez ensuite dans **Service > Environment** les variables SMTP de votre fournisseur :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=contact@example.com
MAIL_FROM_NAME=SmartGestion
```

Enregistrez avec **Save and deploy**.

### Déploiements suivants

Chaque push sur `main` lance d’abord GitHub Actions (tests Laravel et compilation Vite). Render déploie uniquement si ces contrôles passent, construit l’image, exécute `php artisan migrate --force`, vérifie `/up`, puis remplace la version active.

Le disque persistant impose une seule instance et quelques secondes d’indisponibilité pendant un déploiement. C’est le compromis nécessaire pour conserver localement les logos et avatars ; un stockage objet S3 permettrait ensuite de retirer cette limitation.