# 📚 Bibliothèque Roudézet - 2025

Une application web développée avec **Symfony + Twig** permettant la gestion d'une bibliothèque en ligne :

- Connexion utilisateur
- CRUD complet sur les livres
- Connexion à une base de données SQL
- Intégration d'une **API externe** pour récupérer les couvertures de livres à partir de leur ISBN

---

## 🚀 Fonctionnalités

- **Authentification utilisateur** (inscription, connexion, déconnexion)
- **CRUD des livres** (ajout, édition, suppression, liste)
- Affichage des **couvertures de livres** via l’API :https://couverture.geobib.fr/api/v1/{{ book.isbn }}/small

- Interface web générée avec **Twig** et **Bootstrap** (optionnel)

---

## 🛠️ Stack Technique

- [Symfony 6](https://symfony.com/)
- [Twig](https://twig.symfony.com/)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- Base de données **MySQL**
- API Couverture des livres : `https://couverture.geobib.fr/api/v1/`

---

## 📦 Installation

```bash
# 1. Cloner le projet
git clone https://github.com/SandrineFial/bibliotheque_sfy_public.git

# 2. Aller dans le dossier
cd symfony-bibliotheque-roudezet

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
