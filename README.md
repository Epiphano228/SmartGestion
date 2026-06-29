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

## Déploiement gratuit sur Render

Le fichier `render.yaml` crée automatiquement :

- un service Docker gratuit dans la région de Francfort ;
- une base PostgreSQL gratuite et privée ;
- les migrations et le compte administrateur au démarrage ;
- le déploiement uniquement après réussite des tests GitHub Actions.

### Première mise en ligne

1. Générez une clé sans modifier votre fichier local :

   ```bash
   php artisan key:generate --show
   ```

2. Dans Render, choisissez **New > Blueprint**.
3. Connectez le dépôt `Epiphano228/SmartGestion` et sélectionnez `main`.
4. Renseignez les secrets demandés :

   - `APP_KEY` : la clé `base64:...` générée à l’étape 1 ;
   - `ADMIN_EMAIL` : l’adresse du premier administrateur ;
   - `ADMIN_PASSWORD` : un mot de passe fort d’au moins 12 caractères.

5. Cliquez sur **Apply**, attendez que PostgreSQL et le service soient disponibles, puis ouvrez l’URL fournie par Render.

`APP_URL`, `ASSET_URL` et la connexion PostgreSQL sont configurés automatiquement. Ne copiez jamais votre `.env` local dans GitHub ou Render.

### Limites importantes du gratuit

- le service s’endort après 15 minutes sans visite et le premier réveil peut prendre environ une minute ;
- les logos et photos de profil téléversés sont temporaires et disparaissent lors d’un redémarrage, d’une mise en veille ou d’un redéploiement ; l’interface revient alors automatiquement aux initiales ;
- PostgreSQL est limité à 1 Go, sans sauvegarde, et expire 30 jours après sa création ;
- Render bloque les ports SMTP 25, 465 et 587 sur les services gratuits.

Pour tester l’envoi d’emails, utilisez un fournisseur autorisant le port `2525` :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=contact@example.com
MAIL_FROM_NAME=SmartGestion
```

### Déploiements suivants

Chaque push sur `main` lance GitHub Actions. Render déploie seulement après réussite des tests et de la compilation, puis le conteneur exécute les migrations de manière idempotente avant de démarrer l’application.

Pour une utilisation réelle durable, la première évolution recommandée sera une base PostgreSQL permanente et un stockage objet compatible S3 pour les logos et avatars.