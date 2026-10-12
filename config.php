<?php
// ====== Configuration (adaptez ces valeurs à votre environnement WAMP) ======
const DB_HOST = 'localhost';
const DB_NAME = 'occasion';
const DB_USER = 'root';
const DB_PASS = 'password';

const SITE_NAME   = 'OccasionMarket';
const CURRENCY    = 'FCFA';
const UPLOAD_DIR  = __DIR__ . '/uploads/';
const MAX_IMG_SIZE = 3 * 1024 * 1024; // 3 Mo

const CATEGORIES = [
    'Électronique', 'Vêtements & Chaussures', 'Maison & Meubles', 'Livres & Loisirs',
    'Sport & Plein air', 'Véhicules & Accessoires', 'Jeux & Jouets', 'Autres',
];
const CONDITIONS = ['Comme neuf', 'Très bon état', 'Bon état', 'État correct', 'À réparer'];

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Africa/Douala');
