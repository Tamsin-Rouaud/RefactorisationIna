# 🤝 CONTRIBUTING.md

Merci de votre intérêt pour ce projet ! Afin de garantir une qualité de code constante et un travail collaboratif efficace, merci de suivre ces recommandations.

---

## 🔀 Nommage des branches

Utilisez ce format :

```
[type]/nom-de-la-tâche-en-kebab-case
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

- `feat: ajouter la gestion des invités`
- `fix: corriger la vérification des fichiers`
- `refacto: nettoyer le contrôleur MediaController`
- `test: ajouter des tests fonctionnels sur la suppression d’un invité`
- `docs: rédiger la documentation de contribution`
- `perf: améliorer le temps de chargement de la page invités`
- `ci: ajouter pipeline GitHub Actions`

---

## ✅ Étapes pour contribuer

1. **Forkez** ce dépôt si vous êtes externe.
2. **Créez une branche** à partir de `main`.
3. **Développez** votre fonctionnalité ou correctif.
4. **Testez** votre code avant de le soumettre.
5. **Soumettez une Pull Request (PR)** vers `main` :
   - Titre clair
   - Description explicite : objectif, modifications, lien issue

---

## 🧪 Bonnes pratiques

- Toujours **accompagner les nouvelles fonctionnalités de tests**.
- Utiliser des **outils d’analyse statique** (`phpstan`, `php-cs-fixer`).
- S’assurer que la **pipeline CI** passe sans erreurs.
- Respecter les **standards PSR-12**.
- Supprimer tout `dump()`, `dd()` ou code mort.
- Documenter les classes ou services complexes.

---

## 🚥 Politique de validation

Une PR ne pourra être fusionnée que si :

- Elle a été **relue** par au moins une personne.
- Elle respecte les **tests et la CI**.
- Elle apporte une **valeur claire** au projet.

---

Merci pour vos contributions, vos retours et votre implication ! 🙌
