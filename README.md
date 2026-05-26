# 🌿 benna / Benna — Guide d'installation
### Projet E-commerce PHP MVC · 5 acteurs · 2ème année Génie Logiciel

---

## ⚡ Installation rapide (3 étapes)

### 1. Placer le projet
```
Copier le dossier benna/ dans C:/xampp/htdocs/benna/
```

### 2. Créer la base de données
- Ouvrir http://localhost/phpmyadmin
- Créer une base `benna_db`
- Importer `config/schema.sql`

### 3. Accéder au projet
```
http://localhost/benna/
```
→ Redirige automatiquement vers la page de connexion.

---

## 🔑 Comptes de démonstration (mot de passe : `password`)

| Rôle | Email | Dashboard |
|------|-------|-----------|
| Admin | admin@benna.tn | /view/admin/dashboard.php |
| Nutritionniste | nutri@benna.tn | /view/nutritionniste/dashboard.php |
| Usine | usine@benna.tn | /view/usine/dashboard.php |
| Livreur | livreur@benna.tn | /view/livreur/dashboard.php |
| Client | client@benna.tn | /view/client/home.php |

---

## 📁 Architecture complète MVC

```
benna/
├── index.php                    ← Point d'entrée (redirige selon rôle)
├── .htaccess                    ← Config Apache (sécurité, cache)
│
├── config/
│   ├── app.php                  ← Détection URL de base (BASE constant)
│   ├── database.php             ← Connexion PDO MySQL
│   └── schema.sql               ← Toutes les tables + données de test
│
├── model/
│   ├── User.php
│   ├── Produit.php
│   └── Commande.php
│
├── controller/
│   ├── traitement.php           ← Toutes les fonctions métier
│   ├── auth_controller.php      ← Login / Register / Logout
│   ├── produit_controller.php   ← CRUD produits
│   ├── panier_controller.php    ← Panier + commande
│   ├── commande_controller.php  ← Statuts + livraisons
│   ├── reclamation_controller.php
│   ├── stock_controller.php     ← Stock + production
│   └── conseil_controller.php  ← Conseils + avis
│
├── view/
│   ├── client/
│   │   ├── signup.php           ← Connexion / Inscription
│   │   ├── home.php             ← Accueil (= index.html converti PHP)
│   │   ├── produits.php         ← Boutique avec filtres dynamiques
│   │   ├── produit_detail.php   ← Fiche produit + valeurs nutritives + avis
│   │   ├── panier.php           ← Panier + validation commande
│   │   ├── mes_commandes.php    ← Historique + barre progression + GPS
│   │   ├── mes_reclamations.php ← Réclamations
│   │   ├── conseils.php         ← Conseils nutritionnels publics
│   │   ├── profil.php           ← Profil + modifier infos
│   │   └── partials/
│   │       ├── header.php       ← Navbar Benna réutilisable
│   │       └── footer.php
│   │
│   ├── admin/
│   │   ├── dashboard.php        ← Stats + alertes stock
│   │   ├── produits.php         ← CRUD produits + allergènes
│   │   ├── commandes.php        ← Gestion statuts + assigner livreur
│   │   ├── users.php            ← Gestion tous les utilisateurs
│   │   ├── reclamations.php     ← Répondre aux réclamations
│   │   ├── avis.php             ← Valider/supprimer avis
│   │   ├── livraisons.php       ← Suivi livraisons
│   │   ├── production.php       ← Créer ordres de production
│   │   └── partials/
│   │       ├── admin_header.php ← Sidebar admin
│   │       └── admin_footer.php
│   │
│   ├── nutritionniste/
│   │   ├── dashboard.php        ← Vue rapide
│   │   ├── conseils.php         ← Créer/gérer conseils
│   │   ├── avis.php             ← Valider avis clients
│   │   └── partials/
│   │       ├── nutri_header.php
│   │       └── nutri_footer.php
│   │
│   ├── usine/
│   │   ├── dashboard.php        ← Stats stock + ordres actifs
│   │   ├── stock.php            ← Modifier niveaux de stock
│   │   ├── production.php       ← Démarrer/terminer ordres
│   │   ├── commandes.php        ← Commandes à préparer
│   │   └── partials/
│   │       ├── usine_header.php
│   │       └── usine_footer.php
│   │
│   └── livreur/
│       └── dashboard.php        ← Livraisons + GPS + statuts
│
└── public/
    ├── css/
    │   ├── style.css            ← Design Benna (votre fichier)
    │   ├── stylePro.css         ← Page boutique (votre fichier)
    │   └── stylepanier.css      ← Page panier (votre fichier)
    ├── js/
    │   ├── script.js            ← Script principal (votre fichier)
    │   └── scriptP.js           ← Script boutique + filtres (modifié)
    └── uploads/produits/        ← Images uploadées par l'admin
```

---

## 🗄️ Base de données — Tables

| Table | Description |
|-------|-------------|
| `users` | Tous les acteurs (client, admin, nutritionniste, usine, livreur) |
| `categories` | Catégories produits |
| `allergenes` | Référentiel allergènes |
| `produits` | Catalogue + valeurs nutritives |
| `produit_allergenes` | Liaison produit ↔ allergènes (many-to-many) |
| `panier` | Panier persisté en base de données |
| `commandes` | En-têtes commandes |
| `commande_details` | Lignes de commande |
| `livraisons` | Suivi GPS + statut livreur |
| `stock` | Niveaux stock + seuils d'alerte |
| `ordres_production` | Ordres de fabrication (usine) |
| `avis` | Avis clients modérés |
| `reclamations` | SAV client → réponse admin |
| `conseils` | Conseils nutritionnels |

---

## 🔄 Flux complet d'une commande

```
1. Client passe commande (panier.php)
        ↓
2. Admin confirme + assigne livreur (admin/commandes.php)
        ↓
3. Usine prépare (usine/commandes.php → statut: expedié)
        ↓
4. Livreur démarre + envoie GPS (livreur/dashboard.php)
        ↓
5. Client voit position en temps réel (mes_commandes.php)
        ↓
6. Livreur confirme livraison → statut: livré
        ↓
7. Client laisse un avis ⭐ (depuis mes_commandes.php)
        ↓
8. Nutritionniste valide l'avis (nutritionniste/avis.php)
```

---

## 🔐 Sécurité

- ✅ Mots de passe hashés bcrypt (`password_hash`)
- ✅ Requêtes PDO préparées (anti-injection SQL)
- ✅ `htmlspecialchars()` sur toutes les sorties (anti-XSS)
- ✅ Vérification de rôle sur chaque page protégée
- ✅ `config/app.php` — URLs absolues (corrige les 404 Apache)
- ✅ `.htaccess` — protège les fichiers SQL et logs

---

## 💡 Clé technique — Pourquoi BASE ?

Apache ne comprend pas les redirections relatives comme `../../view/admin/`.
Le fichier `config/app.php` détecte automatiquement l'URL complète :

```php
// Détecte : http://localhost/benna
define('BASE', baseUrl());

// Utilisé partout :
header("Location: " . BASE . "/view/admin/dashboard.php");
<form action="<?= BASE ?>/controller/panier_controller.php?action=add">
```

Cela fonctionne sur n'importe quel serveur XAMPP/LAMP sans configuration.

---

*© 2026 Benna / benna — Projet académique Génie Logiciel*
