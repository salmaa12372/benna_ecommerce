-- =====================================================
--  Benna — Schéma complet v3
-- =====================================================
CREATE DATABASE IF NOT EXISTS BENNA_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE BENNA_db;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('client','admin','nutritionniste','usine','livreur') DEFAULT 'client',
    telephone  VARCHAR(20),
    adresse    TEXT,
    avatar     VARCHAR(255),
    actif      TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    description TEXT,
    icone       VARCHAR(10) DEFAULT '🌿'
);

CREATE TABLE IF NOT EXISTS allergenes (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    nom   VARCHAR(100) NOT NULL,
    icone VARCHAR(10) DEFAULT '⚠️'
);

CREATE TABLE IF NOT EXISTS produits (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nom            VARCHAR(150) NOT NULL,
    description    TEXT,
    prix           DECIMAL(10,3) NOT NULL,
    stock          INT DEFAULT 0,
    image          VARCHAR(255),
    categorie_id   INT,
    regime         VARCHAR(200),
    calories       INT DEFAULT 0,
    proteines      DECIMAL(5,2) DEFAULT 0,
    glucides       DECIMAL(5,2) DEFAULT 0,
    lipides        DECIMAL(5,2) DEFAULT 0,
    note_moyenne   DECIMAL(3,2) DEFAULT 0,
    nb_avis        INT DEFAULT 0,
    est_actif      TINYINT(1) DEFAULT 1,
    est_valide     TINYINT(1) DEFAULT 0,
    est_nouveau    TINYINT(1) DEFAULT 0,
    est_bestseller TINYINT(1) DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS produit_allergenes (
    produit_id   INT,
    allergene_id INT,
    PRIMARY KEY (produit_id, allergene_id),
    FOREIGN KEY (produit_id)   REFERENCES produits(id)   ON DELETE CASCADE,
    FOREIGN KEY (allergene_id) REFERENCES allergenes(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS panier (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    produit_id INT NOT NULL,
    quantite   INT DEFAULT 1,
    UNIQUE KEY unique_item (user_id, produit_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS commandes (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    user_id                INT NOT NULL,
    total                  DECIMAL(10,3) NOT NULL,
    statut                 ENUM('en_attente','confirmee','en_preparation','expedie','en_livraison','livre','annulee') DEFAULT 'en_attente',
    adresse_livraison      TEXT NOT NULL,
    note_client            TEXT,
    paiement_statut        ENUM('en_attente','paye','rembourse') DEFAULT 'en_attente',
    paiement_methode       ENUM('carte','virement','cash') DEFAULT 'cash',
    date_commande          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_livraison_estimee DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS commande_details (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    commande_id   INT NOT NULL,
    produit_id    INT NOT NULL,
    quantite      INT NOT NULL,
    prix_unitaire DECIMAL(10,3) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS livraisons (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    commande_id  INT NOT NULL UNIQUE,
    livreur_id   INT,
    statut       ENUM('assignee','acceptee','en_cours','livree','echec') DEFAULT 'assignee',
    latitude     DECIMAL(10,8),
    longitude    DECIMAL(11,8),
    note_livreur TEXT,
    probleme     TEXT,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (livreur_id)  REFERENCES users(id)     ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS stock (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    produit_id    INT NOT NULL UNIQUE,
    quantite      INT DEFAULT 0,
    seuil_alerte  INT DEFAULT 20,
    en_production INT DEFAULT 0,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ordres_production (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    produit_id  INT NOT NULL,
    quantite    INT NOT NULL,
    statut      ENUM('demande','en_cours','termine') DEFAULT 'demande',
    demande_par INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    termine_at  TIMESTAMP NULL,
    FOREIGN KEY (produit_id)  REFERENCES produits(id) ON DELETE CASCADE,
    FOREIGN KEY (demande_par) REFERENCES users(id)    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS avis (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    produit_id  INT NOT NULL,
    note        TINYINT CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    valide      TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY un_avis (user_id, produit_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reclamations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    commande_id INT,
    sujet       VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    statut      ENUM('ouverte','en_cours','transmise_usine','resolue','rejetee') DEFAULT 'ouverte',
    reponse     TEXT,
    repondu_par INT,
    transmis_usine TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE SET NULL,
    FOREIGN KEY (repondu_par) REFERENCES users(id)     ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS conseils (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nutritionniste_id INT NOT NULL,
    produit_id        INT,
    titre             VARCHAR(200) NOT NULL,
    contenu           TEXT NOT NULL,
    type              ENUM('conseil','recette','recommandation','plan_alimentaire') DEFAULT 'conseil',
    public            TINYINT(1) DEFAULT 1,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nutritionniste_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (produit_id)        REFERENCES produits(id) ON DELETE SET NULL
);

-- Plans alimentaires (nutritionniste → client)
CREATE TABLE IF NOT EXISTS plans_alimentaires (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nutritionniste_id INT NOT NULL,
    client_id         INT NOT NULL,
    titre             VARCHAR(200) NOT NULL,
    objectif          TEXT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nutritionniste_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id)         REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS plan_repas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    plan_id     INT NOT NULL,
    jour        VARCHAR(20) NOT NULL,
    moment      ENUM('matin','midi','soir','collation') NOT NULL,
    description TEXT NOT NULL,
    calories    INT DEFAULT 0,
    FOREIGN KEY (plan_id) REFERENCES plans_alimentaires(id) ON DELETE CASCADE
);

-- Alertes nutritionnelles
CREATE TABLE IF NOT EXISTS alertes_nutritionnelles (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nutritionniste_id INT NOT NULL,
    client_id         INT,
    titre             VARCHAR(200) NOT NULL,
    message           TEXT NOT NULL,
    gravite           ENUM('info','attention','urgent') DEFAULT 'info',
    lu                TINYINT(1) DEFAULT 0,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nutritionniste_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id)         REFERENCES users(id) ON DELETE SET NULL
);

-- Chatbot messages
CREATE TABLE IF NOT EXISTS chatbot_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    user_id    INT,
    message    TEXT NOT NULL,
    reponse    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ═══════════════════════════════════════════════════
-- DONNÉES DE DÉMARRAGE
-- ═══════════════════════════════════════════════════

-- Nouvelles catégories demandées
INSERT INTO categories (nom, description, icone) VALUES
('Boissons',             'Jus, infusions & boissons naturelles',       '🥤'),
('Snacks & biscuits',    'Biscuits artisanaux et snacks sains',        '🍪'),
('Fruits secs & snacks salés', 'Fruits secs, noix et en-cas salés',  '🥜'),
('Pain & boulangerie',   'Pains au levain, galettes & viennoiseries', '🍞'),
('Plats healthy',        'Plats équilibrés prêts à consommer',        '🥗'),
('Produits sportifs',    'Barres, protéines & nutrition sportive',    '💪'),
('Petit déjeuner',       'Céréales, granolas & produits du matin',    '🌅'),
('Pâtisserie healthy',   'Gâteaux et desserts allégés',               '🧁'),
('Préparations maison',  'Mélanges et bases pour cuisiner chez soi',  '🏠'),
('Produits végétaux',    'Alternatives végétales & produits vegan',   '🌱');

INSERT INTO allergenes (nom, icone) VALUES
('Gluten','🌾'),('Lactose','🥛'),('Arachides','🥜'),
('Soja','🫘'),('Oeufs','🥚'),('Noix','🌰'),('Sesame','🫙'),('Moutarde','🟡');

-- Comptes par défaut (password = "password")
INSERT INTO users (nom, email, password, role) VALUES
('Admin Benna',        'admin@benna.tn',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. Sana Ben Ali',   'nutri@benna.tn',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nutritionniste'),
('Usine Sousse',       'usine@benna.tn',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usine'),
('Ahmed Livreur',      'livreur@benna.tn',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'livreur'),
('Client Test',        'client@benna.tn',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client');

-- Produits avec les nouvelles catégories (est_valide=1 pour qu'ils soient visibles)
INSERT INTO produits (nom, description, prix, stock, image, categorie_id, regime, calories, proteines, glucides, lipides, est_bestseller, est_nouveau, est_valide) VALUES
('Honey & Sesame Biscuits',   'Wild thyme honey, toasted sesame, sea salt — une bouchée vraie recette méditerranéenne.',  12.900, 50, 'pics/product-honey-sesame.jpg',   2, 'sans-gluten,bio',        320, 6.2, 42.0, 14.5, 1, 0, 1),
('Olive & Rosemary Crackers', 'Extra virgin olive oil, fresh rosemary from Zaghouan, cracked black pepper.',              14.500, 35, 'pics/product-olive-rosemary.jpg', 3, 'sans-lactose,bio,vegan', 280, 4.8, 35.0, 16.0, 0, 0, 1),
('Fig & Walnut Bar',          'Barres de dattes Djerba, noix concassées, cannelle et eau de fleur d\'oranger.',           16.900, 28, 'pics/1.jpg',                      6, 'sans-gluten,bio,vegan',  380, 7.5, 48.0, 18.2, 0, 0, 1),
('Spiced Orange Cookies',     'Zeste d\'orange, clou de girofle et anis étoilé — une célébration saisonnière.',           15.900, 40, 'pics/product-spiced-orange.jpg',  2, 'sans-gluten,vegan',      295, 3.9, 44.0, 12.0, 0, 1, 1),
('Date & Almond Bread',       'Pain artisanal aux dattes de Kébili, amandes, levain naturel — sans additifs.',            18.500, 15, 'pics/gallery-1.jpg',              4, 'sans-lactose,bio',       350, 9.0, 50.0, 12.5, 0, 1, 1),
('Zaatar Granola Bowl',       'Granola artisanal au zaatar tunisien, graines et miel de montagne.',                       13.500, 30, 'pics/gallery-2.jpg',              7, 'bio,vegan',              260, 5.1, 30.0, 15.0, 0, 0, 1),
('Green Detox Smoothie',      'Mélange épinards, concombre, gingembre et menthe fraîche — boisson détoxifiante.',         9.900,  25, 'pics/gallery-3.jpg',              1, 'vegan,bio',              85,  2.1, 18.0, 0.5,  0, 1, 1),
('Protein Almond Bar',        'Barre protéinée aux amandes, whey bio et miel — idéale après l\'effort.',                  11.900, 45, 'pics/gallery-4.jpg',              6, 'sans-gluten',            280, 18.0,22.0, 9.0,  0, 0, 1),
('Baklawa Healthy',           'Pâtisserie tunisienne revisitée — sans sucre ajouté, au sirop de datte.',                  19.900, 20, 'pics/product-honey-sesame.jpg',   8, 'sans-sucre',             310, 6.0, 35.0, 16.0, 1, 0, 1),
('Harissa Veggie Wrap',       'Base de wrap végétalien maison avec harissa douce et légumes grillés.',                    14.900, 18, 'pics/1.jpg',                      5, 'vegan,bio',              220, 8.0, 28.0, 7.0,  0, 1, 1),
('Préparation Couscous Bio',  'Mélange semoule bio et épices tunisiennes — prêt en 10 minutes.',                          8.500,  60, 'pics/product-olive-rosemary.jpg', 9, 'bio,vegan',              180, 5.5, 38.0, 1.5,  0, 0, 1),
('Lait de Coco Artisanal',    'Boisson végétale à la noix de coco fraîche, sans conservateurs ni additifs.',              12.500, 40, 'pics/gallery-1.jpg',              10, 'sans-lactose,vegan,bio', 150, 1.5, 8.0,  13.0, 0, 1, 1);

-- Stock initial
INSERT INTO stock (produit_id, quantite, seuil_alerte) VALUES
(1,50,15),(2,35,10),(3,28,10),(4,40,15),(5,15,8),(6,30,10),(7,25,10),(8,45,15),(9,20,8),(10,18,8),(11,60,20),(12,40,12);




-- =====================================================
-- PRODUITS BENNA (EXTENSION PROPRE)
-- =====================================================
INSERT INTO produits (nom, description, prix, stock, image, categorie_id, regime, calories, proteines, glucides, lipides, est_bestseller, est_nouveau, est_valide) VALUES

-- ================= BOISSONS
('Lait sans lactose 1L','Lait digestif',4.200,50,'pics/lait_sans_lactose.jpg',1,'sans-lactose',120,8.0,10.0,4.0,0,1,1),
('Lait amande 1L','Boisson végétale',5.500,40,'pics/lait_amande.jpg',1,'vegan',110,3.0,12.0,5.0,0,1,1),
('Lait avoine 1L','Riche fibres',5.200,35,'pics/lait_avoine.jpg',1,'vegan,high-fiber',130,4.0,18.0,4.5,0,0,1),
('Lait soja 1L','Protéines végétales',4.800,45,'pics/lait_soja.jpg',1,'vegan,protein-rich',125,7.0,9.0,4.0,0,0,1),
('Jus orange naturel','100% frais',3.800,60,'pics/jus_orange.jpg',1,'natural,vegan',90,1.2,20.0,0.5,1,0,1),
('Smoothie fruits rouges','Antioxydants',6.200,25,'pics/smoothie_fruits_rouges.jpg',1,'vegan,natural',150,2.0,30.0,1.0,0,0,1),
('Eau citronnée detox','Boisson detox',2.500,70,'pics/eau_citronnee.jpg',1,'vegan,low-calorie',10,0.5,2.0,0.0,0,0,1),
('Jus carotte naturel','100% frais',3.900,50,'pics/jus_carotte.jpg',1,'natural,vegan',80,1.0,18.0,0.2,0,0,1),
('Smoothie protéiné banane','Après sport',6.500,30,'pics/smoothie_proteine.jpg',1,'protein-rich,low-calorie',180,15.0,25.0,2.0,0,0,1),

-- ================= COOKIES & DESSERTS
('Cookies sans gluten chocolat','Healthy cookies',6.900,50,'pics/cookies_sans_gluten.jpg',2,'sans-gluten',280,5.0,35.0,12.0,1,0,1),
('Cookies sans sucre','Adapté diabète',7.500,45,'pics/cookies_sans_sucre.jpg',2,'sans-sucre',260,4.5,30.0,10.0,0,1,1),
('Cookies avoine banane','Snack sain',5.800,60,'pics/cookies_avoine.jpg',2,'vegan,high-fiber',250,4.0,38.0,8.0,0,1,1),
('Cookies chocolat noir','70% cacao',6.500,50,'pics/cookies_choco_noir.jpg',2,'gourmet',270,5.0,32.0,11.0,0,0,1),
('Cookies coco vegan','Sans produits animaux',6.200,55,'pics/cookies_coco.jpg',2,'vegan',260,3.0,28.0,14.0,0,0,1),
('Cookies dattes tunisiennes','Énergie naturelle',5.900,60,'pics/cookies_dattes.jpg',2,'vegan,natural',240,3.0,40.0,7.0,0,0,1),
('Yaourt végétal coco','Sans lactose',4.200,40,'pics/yaourt_coco.jpg',2,'vegan,sans-lactose,natural',150,3.0,10.0,6.0,0,1,1),
('Cake amande sans gluten','Dessert léger',6.800,20,'pics/cake_amande.jpg',2,'sans-gluten,vegetarian',300,6.0,30.0,15.0,0,0,1),
('Muffin sans sucre','Healthy snack',5.500,30,'pics/muffin.jpg',2,'sans-sucre,low-calorie',240,5.0,28.0,8.0,0,1,1),
('Pancakes sans gluten','Petit déjeuner',6.200,25,'pics/pancakes.jpg',2,'sans-gluten,vegetarian',280,6.0,35.0,9.0,0,0,1),
('Compote pomme sans sucre','Dessert naturel',3.500,40,'pics/compote_pomme.jpg',2,'sans-sucre,natural',100,0.5,25.0,0.2,0,0,1),
('Yaourt grec light','Faible calories',4.500,40,'pics/yaourt_grec.jpg',2,'low-calorie,protein-rich',120,10.0,6.0,2.0,0,0,1),
('Makroud healthy','Dessert premium',6.900,20,'pics/makroud.jpg',2,'traditional,gourmet,sans-sucre',220,4.0,35.0,8.0,0,0,1),

-- ================= SNACKS
('Barre énergie dattes','Snack naturel',3.200,70,'pics/barre_dattes.jpg',3,'vegan,natural',180,3.0,30.0,6.0,1,0,1),
('Barre chocolat noir','Énergie rapide',3.900,60,'pics/barre_choco.jpg',3,'gourmet',200,3.0,25.0,10.0,0,0,1),
('Barre amande noisette','Riche protéines',4.500,50,'pics/barre_amande.jpg',3,'protein-rich',210,7.0,18.0,12.0,0,0,1),
('Chips patate douce','Snack sain',3.500,60,'pics/chips_patate_douce.jpg',3,'vegan,low-calorie',200,2.0,25.0,8.0,0,0,1),
('Pois chiches grillés','Protéines végétales',3.000,80,'pics/pois_chiches.jpg',3,'vegan,protein-rich',160,8.0,20.0,5.0,0,0,1),
('Mix noix grillées','Énergie naturelle',5.200,50,'pics/mix_noix.jpg',3,'protein-rich',300,10.0,15.0,22.0,0,0,1),
('Chips patate douce spicy','Snack sain épicé',3.500,0,'pics/chips_spicy.jpg',3,'vegan,low-calorie',200,2.0,25.0,8.0,0,0,0),
('Salade quinoa bio','Repas équilibré',8.500,30,'pics/salade_quinoa.jpg',3,'vegan,vegetarian,bio,high-fiber',320,10.0,45.0,9.0,1,0,1),
('Wrap falafel maison','Street food healthy',7.800,25,'pics/wrap_falafel.jpg',3,'vegan,homemade,protein-rich',400,12.0,50.0,10.0,0,1,1),

('Soupe légumes maison','Recette simple',4.800,35,'pics/soupe_legumes.jpg',3,'homemade,natural,low-calorie',120,4.0,20.0,2.0,0,0,1),
('Bol lentilles healthy','Riche fibres',7.200,30,'pics/bol_lentilles.jpg',3,'vegan,high-fiber,protein-rich',350,15.0,50.0,5.0,0,0,1),
('Lablabi healthy','Recette tunisienne revisitée',5.800,40,'pics/lablabi.jpg',3,'traditional,vegan,high-fiber',300,12.0,55.0,4.0,0,0,1),
('Ojja végétarienne','Plat tunisien',7.500,35,'pics/ojja.jpg',3,'traditional,vegetarian',250,10.0,20.0,12.0,0,0,1),
('Salade concombre light','Très léger',3.800,50,'pics/salade_concombre.jpg',3,'low-calorie,vegan',60,2.0,10.0,1.0,0,0,1),
('Energy bowl complet','Repas complet healthy',9.500,20,'pics/energy_bowl.jpg',3,'vegan,high-fiber,protein-rich,natural',450,18.0,60.0,12.0,0,0,1),
('Snack detox premium','Healthy gourmet',6.700,25,'pics/snack_detox.jpg',3,'gourmet,low-calorie,natural',150,4.0,25.0,3.0,0,0,1),
('Energy balls dattes & noix','Snack naturel',5.500,40,'pics/energy_balls.jpg',5,'natural,high-fiber',190,5.0,28.0,7.0,0,1,1),


-- ================= PAIN
('Pain complet bio','Artisanal',3.500,40,'pics/pain_complet.jpg',4,'bio',220,7.0,40.0,3.0,0,0,1),
('Pain sans gluten','Alternative saine',4.800,30,'pics/pain_sans_gluten.jpg',4,'sans-gluten',210,6.0,38.0,3.5,0,1,1),
('Pain avoine','Digestion facile',3.700,40,'pics/pain_avoine.jpg',4,'vegan,high-fiber',230,8.0,42.0,3.0,0,0,1),
('Pain multicéréales','Équilibré',4.100,35,'pics/pain_multi.jpg',4,'bio,high-fiber',240,9.0,40.0,4.0,0,0,1),
('Pain quinoa','Riche protéines',4.900,25,'pics/pain_quinoa.jpg',4,'sans-gluten,protein-rich',250,10.0,35.0,5.0,0,0,1),

-- ================= FITNESS
('Shake protéiné chocolat','Sport nutrition',7.900,40,'pics/shake_proteine.jpg',5,'protein-rich',220,25.0,15.0,6.0,1,0,1),
('Shake protéiné vanille','Musculation',7.800,40,'pics/shake_vanille.jpg',5,'protein-rich',210,25.0,14.0,5.5,0,0,1),
('Granola fitness','Énergie matin',5.900,50,'pics/granola.jpg',5,'high-fiber',300,10.0,40.0,8.0,0,1,1),
('Barre protéinée sport','Performance',5.800,35,'pics/barre_proteine.jpg',5,'protein-rich',200,20.0,15.0,7.0,0,0,1),
('Mix énergie sport','Endurance',6.200,45,'pics/mix_energie.jpg',5,'protein-rich',350,12.0,45.0,10.0,0,0,1),
('Granola quinoa vegan','Petit déjeuner healthy',7.200,12,'pics/granola_quinoa.jpg',5,'vegan,sans-gluten,high-fiber',320,8.0,45.0,9.0,0,0,1),
('Omelette protéinée','Repas fitness',6.900,25,'pics/omelette.jpg',5,'protein-rich,vegetarian',250,20.0,5.0,15.0,0,0,1),

-- ================= PRÉPARATIONS
('Préparation fricassé','Mix cuisson healthy',6.500,60,'pics/prep_fricassee.jpg',6,'homemade',300,8.0,40.0,10.0,0,0,1),
('Préparation fricassé léger','Moins gras',6.200,65,'pics/prep_fricassee_light.jpg',6,'low-calorie',250,8.0,35.0,6.0,0,0,1),
('Préparation cookies maison','Mix cookies',7.200,80,'pics/prep_cookies.jpg',6,'homemade',350,5.0,50.0,15.0,0,0,1),
('Préparation cookies sans sucre','Diabète friendly',7.500,70,'pics/prep_cookies_ss.jpg',6,'sans-sucre',300,6.0,40.0,12.0,0,0,1),
('Préparation brownies','Chocolat maison',8.500,60,'pics/prep_brownies.jpg',6,'homemade,gourmet',400,6.0,45.0,20.0,0,0,1),
('Préparation brownies sans sucre','Healthy dessert',8.900,55,'pics/prep_brownies_ss.jpg',6,'sans-sucre,low-calorie',320,7.0,35.0,15.0,0,0,1),
('Préparation mlewi','Farine complète',5.800,90,'pics/prep_mlewi.jpg',6,'homemade',280,7.0,45.0,8.0,0,0,1),
('Préparation mlewi sans gluten','Alternative saine',6.900,50,'pics/prep_mlewi_sg.jpg',6,'sans-gluten',270,6.0,40.0,9.0,0,0,1),
('Préparation mlewi avoine','Riche fibres',6.300,60,'pics/prep_mlewi_avoine.jpg',6,'vegan,high-fiber',280,8.0,42.0,7.0,0,0,1);


-- Lier produits aux allergènes
INSERT INTO produit_allergenes VALUES (1,7),(2,7),(3,6),(3,4),(4,1),(8,6);

-- ═══════════════════════════════════════════════════
-- SYSTÈME VIP & CONSULTATIONS
-- ═══════════════════════════════════════════════════

-- Abonnements VIP
CREATE TABLE IF NOT EXISTS vip_abonnements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    niveau          ENUM('basic','premium','elite') NOT NULL,
    prix_mensuel    DECIMAL(8,3) NOT NULL,
    date_debut      DATE NOT NULL,
    date_fin        DATE NOT NULL,
    actif           TINYINT(1) DEFAULT 1,
    renouvellement  TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Paiements abonnements
CREATE TABLE IF NOT EXISTS vip_paiements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    abonnement_id   INT NOT NULL,
    montant         DECIMAL(8,3) NOT NULL,
    methode         ENUM('carte','virement','cash') DEFAULT 'carte',
    statut          ENUM('en_attente','paye','echoue') DEFAULT 'en_attente',
    reference       VARCHAR(50),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)       REFERENCES users(id)          ON DELETE CASCADE,
    FOREIGN KEY (abonnement_id) REFERENCES vip_abonnements(id) ON DELETE CASCADE
);

-- Consultations (sessions 1-à-1 nutritionniste ↔ client VIP)
CREATE TABLE IF NOT EXISTS consultations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nutritionniste_id INT NOT NULL,
    client_id         INT NOT NULL,
    titre             VARCHAR(200) NOT NULL DEFAULT 'Consultation nutritionnelle',
    date_heure        DATETIME NOT NULL,
    duree_min         INT DEFAULT 30,
    type              ENUM('chat','visio') DEFAULT 'visio',
    statut            ENUM('planifiee','en_cours','terminee','annulee') DEFAULT 'planifiee',
    lien_visio        VARCHAR(500),
    notes_avant       TEXT,
    notes_apres       TEXT,
    objectifs         TEXT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nutritionniste_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id)         REFERENCES users(id) ON DELETE CASCADE
);

-- Messagerie VIP (chat nutritionniste ↔ client)
CREATE TABLE IF NOT EXISTS vip_messages (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    expediteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    contenu       TEXT NOT NULL,
    lu            TINYINT(1) DEFAULT 0,
    consultation_id INT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id)    REFERENCES users(id)         ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id)  REFERENCES users(id)         ON DELETE CASCADE,
    FOREIGN KEY (consultation_id)  REFERENCES consultations(id) ON DELETE SET NULL
);

-- Objectifs de suivi VIP
CREATE TABLE IF NOT EXISTS vip_objectifs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    nutri_id    INT NOT NULL,
    titre       VARCHAR(200) NOT NULL,
    valeur_cible DECIMAL(8,2),
    valeur_actuelle DECIMAL(8,2),
    unite       VARCHAR(20) DEFAULT 'kg',
    deadline    DATE,
    atteint     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (nutri_id)  REFERENCES users(id) ON DELETE CASCADE
);  ALTER TABLE produits 
ADD CONSTRAINT uk_produit_nom UNIQUE (nom);
ALTER TABLE `vip_messages` 
ADD COLUMN `lu_at` DATETIME NULL DEFAULT NULL AFTER `lu`;