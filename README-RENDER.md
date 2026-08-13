# LessGo Community — Render + PostgreSQL

## Base de données
La version Render utilise PostgreSQL via `DATABASE_URL`. Au démarrage du conteneur, `backend/database/migrate.php` crée les tables et insère les données initiales automatiquement.

## Compte administrateur initial
Email: `admin@lessgo.cm`
Mot de passe: `LessGo2026!`
Changez ce mot de passe après la première connexion.

## Render
1. Créer une base PostgreSQL Render.
2. Créer le Web Service depuis GitHub.
3. Dans Environment, ajouter `DATABASE_URL` avec l'Internal Database URL de la base PostgreSQL Render.
4. Déployer.

## XAMPP
XAMPP continue d'utiliser MySQL si `DATABASE_URL` n'est pas défini. Importer `backend/database/lessgo.sql` dans phpMyAdmin.

## Fichiers persistants
Les uploads dans `backend/uploads` sont stockés sur le disque du conteneur. Sur un hébergement à système de fichiers éphémère, les nouveaux uploads peuvent disparaître après un redéploiement. Pour la production, utiliser un stockage objet ou un disque persistant Render.

## Ordre recommandé de déploiement

1. Tester XAMPP + MySQL avec `backend/database/lessgo.sql`.
2. Pousser le dépôt GitHub.
3. Créer Render Postgres dans la même région que le Web Service.
4. Copier l'Internal Database URL de Render dans `DATABASE_URL` du Web Service.
5. Déployer le Web Service avec le `Dockerfile`.
6. Le conteneur exécute `backend/database/migrate.php` au démarrage et crée les tables/jeux de données PostgreSQL.
7. Tester `/`, `/backend/admin/login.php`, les formations, inscriptions, contacts et galerie.

## Attention aux fichiers uploadés

Le dossier `backend/uploads` est utilisé par l'administration. Le stockage local d'un Web Service n'est pas à considérer comme un stockage permanent. Pour la production, configurez un stockage persistant/objet pour les médias.
