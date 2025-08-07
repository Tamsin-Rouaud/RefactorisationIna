![CI Symfony](https://github.com/tamsin-rouaud/RefactorisationIna/actions/workflows/ci.yml/badge.svg)
![PHP](https://img.shields.io/badge/php-8.2-blue)
![Symfony](https://img.shields.io/badge/symfony-7.2-black)
![Coverage ≥95%](https://img.shields.io/badge/coverage-95%25-brightgreen)
![License](https://img.shields.io/badge/license-MIT-lightgrey)

# 📸 Projet Ina Zaoui - Refactorisation Symfony

Ce projet vise à refactoriser le site web de la photographe Ina Zaoui. L’objectif est de moderniser le code, corriger les anomalies existantes, implémenter de nouvelles fonctionnalités, renforcer la qualité logicielle, optimiser les performances et mettre en place une intégration continue.

---

## 🧰 Pré-requis

Assurez-vous d’avoir les outils suivants installés :

- PHP ≥ 8.2
- Composer
- Symfony CLI
- MySQL ou MariaDB
- Un serveur local (Symfony CLI, XAMPP, Laragon…)

---

## 🚀 Installation

1. **Cloner le projet** :

```bash
git clone https://github.com/Tamsin-Rouaud/RefactorisationIna.git
cd RefactorisationIna
```

2. **Installer les dépendances PHP** :

```bash
composer install
```

3. **Configurer l’environnement** :

```bash
cp .env .env.local
```

> Modifiez `.env.local` pour configurer votre `DATABASE_URL`.

4. **Créer la base de données et exécuter les migrations** :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Charger les données de test (fixtures)** :

```bash
php bin/console doctrine:fixtures:load
```

6. **Lancer le serveur Symfony** :

```bash
symfony server:start
```

---

## 💾 Données de développement

Un fichier `backup.zip` est fourni pour travailler dans des conditions proches de la production.

👉 **[Télécharger le backup (1 Go)](https://drive.google.com/file/d/1dIq7TLrdnZuXoJUGXnyaSu1fY5t8vWRt/view?usp=drive_link)**

### Contenu :
- Le dossier `public/uploads/` (photos, albums, avatars…)
- Un dump SQL anonymisé

### Instructions :
1. Décompressez l’archive.
2. Placez le dossier `uploads/` dans `public/uploads`.
3. Importez le fichier `.sql` dans votre base de données locale (`phpMyAdmin`, DBeaver, etc.).

---

## 🔐 Identifiants de démonstration

Des utilisateurs sont pré-créés pour les tests fonctionnels. Voici les accès fictifs :

- **Administrateur (Ina)**  
  Nom : `Inatest Zaoui`  
  Mot de passe : `password`

- **Invité actif**  
  Nom : `Jean Dupont`  
  Mot de passe : `password`

- **Invité bloqué**  
  Nom : `Marie Durand`  
  Mot de passe : `password`

---

## 🧪 Tests

Les tests incluent à la fois des vérifications unitaires/fonctionnelles **et** de l’analyse statique approfondie avec PHPStan (niveau 9).

Les tests sont réalisés avec **PHPUnit** et **PHPStan**.

### Lancer les tests :

```bash
php bin/phpunit
```

```bash
php vendor/bin/phpstan analyse
```

### Générer et ouvrir le rapport de couverture :

```bash
php bin/phpunit --coverage-html var/coverage
```

Ouvrez le fichier suivant dans un navigateur :

```bash
php var/coverage/index.html
```
ou clic droit sur le fichier index.html dans le dossier "var/coverage" et "Ouvrir avec votre navigateur"

🎯 Objectif de couverture : **≥ 70 %**  
✅ Couverture atteinte : **≥ 95 %**

---

## 🖼️ Fonctionnalités principales

- ✅ Migration vers Symfony 7.2
- ✅ Authentification via base de données
- ✅ Gestion des invités (ajout, blocage, suppression)
- ✅ Vérification avancée des fichiers uploadés (type + taille max 2 Mo)
- ✅ Refactorisation du code existant
- ✅ Implémentation de tests unitaires et fonctionnels
- ✅ Rapport de couverture de tests ≥ 95 %
- ✅ Optimisation des performances (notamment la page “Invités”)
- ✅ Rapport de performance fourni
- ✅ Intégration continue via GitHub Actions
- ✅ Documentation claire : README & CONTRIBUTING

---

## ⚙️ Outils & technologies

- Symfony 7.3
- PHP 8.2.12
- Doctrine ORM
- PHPUnit
- GitHub Actions (CI)
- PHPStan / PHP CS Fixer
- Web Profiler

---

## 📘 Documentation complémentaire

Pour contribuer efficacement au projet, merci de consulter le fichier [`CONTRIBUTING.md`](CONTRIBUTING.md).

---

## 📄 Licence

Projet réalisé dans le cadre d’une mission fictive pour Ina Zaoui, photographe. Ce projet fait partie de la formation développeur web OpenClassrooms.
