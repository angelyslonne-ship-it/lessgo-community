# LessGo Community — site nouvelle génération

Projet séparé en deux dossiers :
- `frontend/` : site public HTML/CSS/JS
- `backend/` : API PHP, MySQL, uploads et administration

## Installation XAMPP
1. Copier `lessgo-community` dans `C:\xampp\htdocs\`
2. Démarrer Apache et MySQL.
3. Ouvrir `http://localhost/phpmyadmin`
4. Importer `backend/database/lessgo.sql`
5. Ouvrir `http://localhost/lessgo-community/frontend/`
6. Administration : `http://localhost/lessgo-community/backend/admin/login.php`

Compte initial :
- Email : `admin@lessgo.cm`
- Mot de passe : `LessGo2026!`

## Gestion de contenu
Depuis le dashboard, vous pouvez ajouter autant de formations, images de galerie, témoignages et partenaires que nécessaire. Les éléments sont enregistrés en base MySQL et apparaissent sur le site.

## Uploads
Les images de galerie sont envoyées vers `backend/uploads/`. Le dossier doit être accessible en écriture par Apache.

## Important
Le compte administrateur et le mot de passe fournis sont des identifiants de démonstration. Changez-les avant une mise en production.


## Nouveau contrôle administrateur
Le dashboard permet maintenant de modifier les formations, importer/supprimer des images, gérer les témoignages et partenaires, modifier les coordonnées et textes principaux, et changer les statuts des inscriptions/messages. La galerie et les choix de formations du formulaire sont chargés dynamiquement depuis MySQL.
