# ♻️ OccasionMarket — Site de vente d'objets d'occasion

Application web en **HTML, CSS, JavaScript et PHP (PDO/MySQL)**, sans aucun framework.

## Fonctionnalités
- **Interface visiteur** : page d'accueil (présentation, dernières annonces). Le détail, l'achat et le chat exigent un compte.
- **Compte obligatoire** : inscription / connexion / déconnexion (mots de passe hachés avec `password_hash`).
- **Boutique** : recherche, filtre par catégorie et prix, tri (récent, prix, mieux notés), pagination.
- **Publier une annonce** : titre, description, prix, catégorie, état, photo (JPG/PNG/WebP, 3 Mo max).
- **Mes annonces** : liste, statut (en ligne / vendu), suppression.
- **Panier** (ajout/retrait en AJAX) et **validation de commande** (transaction SQL : les articles passent « vendus » et sortent de tous les paniers).
- **Chat** acheteur ↔ vendeur par annonce (rafraîchissement automatique toutes les 3 s, badge de messages non lus).
- **Notes de 1 à 5 étoiles** par produit (une note par utilisateur, modifiable ; on ne peut pas noter son propre article).
- Historique des **achats** et des **ventes**.

## Installation (WAMP / Windows)
1. Copiez le dossier `occasion` dans `C:\wamp64\www\`.
2. Ouvrez **phpMyAdmin** → onglet **Importer** → choisissez `database.sql` → Exécuter
   (cela crée la base `occasion_db` et ses tables).
3. Vérifiez `config.php` (hôte, utilisateur `root`, mot de passe vide par défaut sous WAMP).
4. Ouvrez **http://localhost/occasion/**
5. Assurez-vous que le dossier `uploads/` est accessible en écriture.

Prérequis : PHP 8.0+ avec les extensions `pdo_mysql`, `mbstring`, `fileinfo` (activées par défaut dans WAMP), MySQL/MariaDB.

## Structure
```
occasion/
├── config.php            Paramètres (base, devise, catégories, états)
├── database.sql          Schéma de la base
├── index.php             Accueil visiteur
├── register.php / login.php / logout.php
├── shop.php              Boutique (recherche, filtres)
├── product.php           Fiche produit (panier, chat, étoiles)
├── sell.php              Publier une annonce
├── my_products.php       Mes annonces
├── cart.php              Panier + validation de commande
├── orders.php            Mes achats / mes ventes
├── chat.php              Messagerie
├── api/                  Endpoints JSON : cart.php, rate.php, chat.php
├── includes/             functions.php, header.php, footer.php
├── assets/css/style.css
├── assets/js/            app.js (panier, étoiles…), chat.js (messagerie)
└── uploads/              Images des annonces
```

## Sécurité mise en place
Requêtes préparées PDO (anti-injection SQL), échappement systématique des sorties (anti-XSS), jeton CSRF sur tous les formulaires et appels AJAX, `session_regenerate_id` à la connexion, validation du type réel des images (`finfo` + `getimagesize`) avec noms de fichiers aléatoires, exécution de scripts interdite dans `uploads/`, contrôle d'accès sur chaque conversation de chat.

## Pistes d'évolution
Paiement mobile réel (Mobile Money, etc.), avis textuels, favoris, notifications e-mail, panneau d'administration, limitation des tentatives de connexion.
