![CI Symfony](https://github.com/tamsin-rouaud/RefactorisationIna/actions/workflows/ci.yml/badge.svg)

# 📸 Projet Ina Zaoui - Refactorisation Symfony

Ce projet vise à refactoriser le site web de la photographe Ina Zaoui. L’objectif est de moderniser le code, corriger les anomalies existantes, implémenter de nouvelles fonctionnalités, renforcer la qualité logicielle, optimiser les performances et mettre en place une intégration continue.

---

## 🧰 Pré-requis

Assurez-vous d’avoir les outils suivants installés :

- PHP ≥ 8.1
- Composer
- Symfony CLI
- MySQL ou MariaDB
- Node.js & npm (si assets à compiler)
- Un serveur local (Symfony CLI, XAMPP, Laragon…)

---


## 📁 Ressources supplémentaires

Le dossier `public/uploads/` (contenant les images des utilisateurs, médias, albums, etc.) n'est **pas versionné dans Git**, conformément aux bonnes pratiques.

Pour exécuter le projet dans des conditions proches de la production, vous pouvez télécharger le fichier `backup.zip` (≈ 1 Go) contenant :

- Le dossier complet `public/uploads/`
- Un dump SQL anonymisé de la base de données (au format `.sql`)

👉 **[Télécharger le fichier backup.zip](https://drive.google.com/file/d/1XgcYqDxyAQdvi7EirP2GAk_6OshSDz9N/view?usp=sharing)**

> ℹ️ Placez le contenu du dossier `uploads/` dans `public/uploads`  
> et importez le fichier `.sql` dans votre base de données locale si nécessaire.

---

## 🔐 Identifiants de connexion de démonstration

Des utilisateurs sont pré-créés dans les fixtures pour les besoins des tests fonctionnels. Voici les accès de démonstration :

- **Compte administrateur (Ina)**  
  Email : `ina@example.com`  
  Mot de passe : `demoIna123`

- **Invité actif**  
  Email : `invite1@example.com`  
  Mot de passe : `inviteDemo`

- **Invité bloqué**  
  Email : `invite2@example.com`  
  Mot de passe : `inviteDemo`

> Ces identifiants sont fictifs et utilisés uniquement dans un contexte de test ou de démonstration locale.

## 🚀 Installation

1. **Cloner le projet** :

```bash
git clone https://github.com/Tamsin-Rouaud/RefactorisationIna
cd projet-photo
```

2. **Installer les dépendances PHP** :

```bash
composer install
```

3. **Configurer l’environnement** :

```bash
cp .env .env.local
```

> Modifier `.env.local` pour configurer votre `DATABASE_URL`.

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

## 🖼️ Fonctionnalités principales

- ✅ Migration vers Symfony 7.2
- ✅ Authentification sécurisée via base de données
- ✅ Gestion des invités (par Ina uniquement)
  - Ajouter / Bloquer / Supprimer
- ✅ Vérification avancée des fichiers uploadés
  - Types MIME validés
  - Taille max : 2 Mo
- ✅ Refactorisation du code existant
- ✅ Implémentation de tests (unitaires & fonctionnels)
- ✅ Rapport de couverture de tests ≥ 70%
- ✅ Optimisation des performances (notamment la page “Invités”)
- ✅ Rapport de performance fourni
- ✅ Pipeline CI : tests + analyse statique automatisés
- ✅ Documentation claire : README & CONTRIBUTING

---

## 🧪 Tests

Les tests sont réalisés avec PHPUnit.

Lancer les tests :

```bash
php bin/phpunit
```

Générer le rapport de couverture :

```bash
php bin/phpunit --coverage-html var/coverage
```

Objectif : **≥ 70 %**
Atteint : **≥ 95 %**

---

## ⚙️ Outils & technologies

- Symfony 7.2
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

Projet réalisé dans le cadre d’une mission fictive pour Ina Zaoui, photographe. Ce projet fait partie de la formation développeur web.
