# 📚 Bibliothèque en ligne

![Symfony](https://img.shields.io/badge/Symfony-7.2-000000?logo=symfony&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-4169E1?logo=postgresql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)

Projet de **refonte complète** d'un ancien site en **PHP 5** avec base **MySQL**, migré vers **Symfony 6 + Twig** avec base **PostgreSQL** :

- Récupération et mise à jour d'une **grosse base de données existante** (plus de 3 000 livres)
- Refonte intégrale du site avec un code moderne, maintenable et sécurisé
- Ajout d'une interface utilisateur claire et d'une **API externe** pour les couvertures de livres
- CRUD complet sur les livres

> Projet développé avec l'assistance de **GitHub Copilot** pour optimiser la qualité du code et accélérer le développement.

---

## 🚀 Fonctionnalités

- **Authentification utilisateur** (inscription, connexion, déconnexion)
- **CRUD des livres** (ajout, édition, suppression, liste)
- **Export PDF des résultats de recherche** avec récapitulatif et liste formatée des livres
- Affichage des **couvertures de livres** via l'API :[https://covers.openlibrary.org/b/isbn/](https://covers.openlibrary.org/b/isbn/)
- Chaque livre fait partie d'un thèmes, voir d'un sous-Thème (catégories) et appartient à un utilisateur
- **CRUD des thèmes et sousThèmes** (ajout, édition, suppression, liste)
- **Système de recherche avancée** (par titre, auteur ou édition)
- Interface web générée avec **Twig** et design responsive avec **Bootstrap**
- Optimisation et **nettoyage de la base de données existante**

---

## 🔄 Contexte du projet

- Ancien site : **PHP 5 + MySQL** avec code spaghetti non maintenable
- Objectifs :

1. **Sauvegarder et nettoyer** la base existante (doublons...)
2. **Migrer les données** vers une structure compatible Doctrine
3. **Refondre complètement** le site avec Symfony, **PHP 8** et une architecture MVC claire

- Résultat : site moderne, sécurisé, et plus simple à faire évoluer

---

## 🛠️ Stack Technique

- [Symfony 6](https://symfony.com/)
- [Twig](https://twig.symfony.com/)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [DOMPDF](https://github.com/dompdf/dompdf) pour la génération de PDF
- V1 : Base de données **MySQL** MariaDB
- V2 : **PostgreSQL sur Supabase** (hébergement cloud, scalable, sécurisé)
- Déploiement : **Docker sur Render** (conteneurisation automatique)
- API Couverture des livres : `https://couverture.geobib.fr`

---

## 🐳 Déploiement

### Production (Render + Supabase)

1. **Fork/Clone** le projet sur votre GitHub
2. **Créer un compte** Supabase et PostgreSQL database
3. **Créer un service Web** sur Render :
   - Runtime: Docker
   - Repository: votre-repo-github
   - Variables d'environnement requises :
     ```
     APP_ENV=prod
     APP_DEBUG=0
     APP_SECRET=votre-secret-32-caracteres
     DATABASE_URL=postgresql://user:pass@host:5432/db?sslmode=require
     MERCURE_URL=https://mercure.rocks/.well-known/mercure
     MERCURE_PUBLIC_URL=https://mercure.rocks/.well-known/mercure
     MERCURE_JWT_SECRET=changeme
     ```

### Test local avec Docker (PostgreSQL)

```bash
# Construire et tester localement avec PostgreSQL
./test-local.sh

# Ou manuellement :
docker compose -f docker-compose.local.yml up --build
# Application: http://localhost:8080
```

---

## 📦 Installation

### 🔐 Configuration locale avec Docker

```bash
# 1. Cloner le projet
git clone https://github.com/SandrineFial/symfony_bibliotheque_public.git

# 2. Aller dans le dossier
cd symfony_bibliotheque_public

# 3. Copier les fichiers de configuration exemple
cp .env.example .env.local
cp .env.docker.example .env.docker

# 4. Éditer .env.docker et remplir les variables avec vos valeurs :
# - APP_SECRET : générer avec `php bin/console secrets:generate-keys` ou une chaîne aléatoire de 32 caractères
# - POSTGRES_PASSWORD : choisir un mot de passe sécurisé
# - MERCURE_JWT_SECRET : générer une clé secrète

# 5. Lancer Docker avec PostgreSQL
docker compose -f docker-compose.local.yml --env-file .env.docker up --build
# Application disponible sur : http://localhost:8080
```

### 🚀 Installation traditionnelle (sans Docker)

```bash
# 1. Cloner le projet
git clone https://github.com/SandrineFial/symfony_bibliotheque_public.git

# 2. Aller dans le dossier
cd symfony_bibliotheque_public

# 3. Installer les dépendances
composer install

# 4. Créer le fichier .env.local
cp .env.example .env.local
# Configurer la base de données dans .env.local :
# Pour Supabase v2 (PostgreSQL) :
# DATABASE_URL="postgresql://<user>:<password>@<host>:5432/<database>?sslmode=require"

# 5. Appliquer les migrations (la base doit être créée au préalable)
php bin/console doctrine:migrations:migrate

# 6. Lancer le serveur de développement
symfony serve -d

```

---

## 📄 Export PDF

L'application permet de générer des PDF avec la liste des livres trouvés lors d'une recherche.

### Fonctionnalités PDF

- **Bouton de téléchargement** automatiquement affiché après une recherche avec des résultats
- **Format compact** : tableau avec numérotation, titre et auteur
- **Récapitulatif de recherche** : terme recherché, type de recherche, nombre de livres et d'auteurs
- **Nettoyage des caractères** : suppression automatique des caractères d'échappement indésirables
- **Nom de fichier intelligent** : `bibliotheque-recherche-[terme]-[date].pdf`

### Utilisation

1. **Effectuer une recherche** sur la page principale
2. **Cliquer sur "Télécharger PDF"** dans les résultats
3. Le PDF se télécharge automatiquement avec la liste formatée

### Technologie

- **DOMPDF** pour la génération
- **Service dédié** `PdfGeneratorService` pour la logique
- **Extension Twig** `clean_text` pour le nettoyage des données
- **Route dédiée** `/books/export-pdf` avec paramètres de recherche

---

## Teste qualité du code en local

Linter Twig
`php bin/console lint:twig templates/`

Outils d'analyse statique
PHP Stan / Psalm
`php -d memory_limit=512M vendor/bin/phpstan analyse`

Démarrer SonarQube

`cd tests
./start-sonar.sh`
Analyser le projet (depuis le dossier tests)
`./analyze-project.sh`

# 📸 Aperçu

## Connexion utilisateur

![Connexion utilisateur](./screenshots/login.png)

👨‍💻 Auteur
Sandrine Fialon

- https://www.fialons-web.fr/
- Linkedin : https://www.linkedin.com/in/fialonsandrine/
