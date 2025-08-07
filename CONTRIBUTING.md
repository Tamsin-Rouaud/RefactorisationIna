# 🤝 CONTRIBUTING.md

Merci de votre intérêt pour ce projet ! Afin de garantir une qualité de code constante et un travail collaboratif efficace, merci de suivre ces recommandations.

---

## 🔀 Nommage des branches

Utilisez ce format :

```
[type]/nom-de-la-tâche
```

Exemples :

| Type     | Exemple                           |
|----------|-----------------------------------|
| feat     | `feat/ajout-gestion-invites`      |
| fix      | `fix/verification-upload`         |
| refacto  | `refacto/migration-symfony6`      |
| test     | `test/ajout-tests-utilisateur`    |
| docs     | `docs/readme-et-contributing`     |
| perf     | `perf/optimisation-page-invites`  |
| ci       | `ci/github-actions-setup`         |

---

## ✏️ Conventions de commits

Format recommandé :

```
[type]: sujet du commit à l’infinitif
```

Exemples :

- `Feat: ajouter la gestion des invités`
- `Fix: corriger la vérification des fichiers`
- `Refacto: nettoyer le contrôleur MediaController`
- `Test: ajouter des tests fonctionnels sur la suppression d’un invité`
- `Docs: rédiger la documentation de contribution`
- `Perf: améliorer le temps de chargement de la page invités`
- `Ci: ajouter pipeline GitHub Actions`

---

## ✅ Étapes pour contribuer

### 🧑‍💻 En solo (merge direct autorisé)

Si vous travaillez **seul(e)** sur le projet, vous pouvez :

1. Créer une branche depuis `main` :

```bash
git checkout main
git pull origin main
git checkout -b feat/ma-fonctionnalite
```

2. Faire vos modifications et commits :

```bash
git add .
git commit -m "Feat: ajouter la gestion des invités"
```

3. Pousser la branche :

```bash
git push origin feat/ma-fonctionnalite
```

4. Merger localement dans `main` :

```bash
git checkout main
git pull origin main
git merge feat/ma-fonctionnalite
git push origin main
```

---

### 👥 En équipe (Pull Request obligatoire)

Si vous travaillez à **plusieurs**, merci de :

1. Créer une branche dédiée
2. Pousser vos changements
3. Ouvrir une **Pull Request (PR)** vers `main` depuis GitHub
4. Attendre au **minimum une relecture avant de merger**
5. Mener la revue technique (tests, CI, lisibilité)

---

## 🧪 Bonnes pratiques

- Ajouter des **tests** pour toute nouvelle fonctionnalité
- Utiliser **PHPStan niveau 9** pour l’analyse statique
- Respecter les **standards PSR-12**
- Ne laisser aucun `dump()`, `dd()` ou code mort
- Vérifier que la **CI passe sans erreur**

---

## 💾 Données de test

Pour lancer le projet avec un jeu de données réaliste :

- Télécharger le backup via le lien dans le `README.md`
- Placer `uploads/` dans `public/uploads`
- Importer le fichier `.sql` dans votre base locale

---

## 🚥 Politique de validation

Une contribution peut être mergée si :

- Elle est relue (si travail d'équipe)
- Tous les tests passent
- La CI est verte
- Elle apporte une **valeur claire** au projet

---

## 💬 Besoin d'aide ou question ?

Merci d’ouvrir une **Issue** si vous avez :

- Une proposition de fonctionnalité
- Un bug à signaler
- Une question technique liée au projet

---

Merci pour votre contribution 🙌
