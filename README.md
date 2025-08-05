# 📚 Bibliothèque Roudézet - 2025

Projet de **refonte complète** d'un ancien site en **PHP 5** avec base **MySQL**, migré vers **Symfony 6 + Twig** :

- Récupération et mise à jour d'une **grosse base de données existante**
- Refonte intégrale du site avec un code moderne, maintenable et sécurisé
- Ajout d'une interface utilisateur claire et d'une **API externe** pour les couvertures de livres
- CRUD complet sur les livres

---

## 🚀 Fonctionnalités

- **Authentification utilisateur** (inscription, connexion, déconnexion)
- **CRUD des livres** (ajout, édition, suppression, liste)
- Affichage des **couvertures de livres** via l’API :https://couverture.geobib.fr/api/v1/{{ book.isbn }}/small

- Interface web générée avec **Twig** et design responsive avec **Bootstrap**
- Optimisation et **nettoyage de la base de données existante**

---

## 🔄 Contexte du projet

- Ancien site : **PHP 5 + MySQL** avec code spaghetti non maintenable
- Objectifs :

1. **Sauvegarder et nettoyer** la base existante
2. **Migrer les données** vers une structure compatible Doctrine
3. **Refondre complètement** le site avec Symfony et une architecture MVC claire

- Résultat : site moderne, sécurisé, et plus simple à faire évoluer

---

## 🛠️ Stack Technique

- [Symfony 6](https://symfony.com/)
- [Twig](https://twig.symfony.com/)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- Base de données **MySQL**
- API Couverture des livres : `https://couverture.geobib.fr`

---

## 📦 Installation

```bash
# 1. Cloner le projet
git clone https://github.com/SandrineFial/symfony_bibliotheque_public.git

# 2. Aller dans le dossier
cd symfony_bibliotheque_public

# 3. Installer les dépendances
composer install

# 4. Créer le fichier .env.local
cp .env .env.local
# Configure ta base de données dans .env.local, ex :
# DATABASE_URL="mysql://user:password@127.0.0.1:3306/bibliotheque"

# 5. Créer la base de données et appliquer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Lancer le serveur de développement
symfony serve

```

---

👨‍💻 Auteur
Sandrine Fialon
https://www.fialons-web.fr/
Linkedin : https://www.linkedin.com/in/fialonsandrine/
