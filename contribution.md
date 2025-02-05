## Guide des Bonnes Pratiques Git

### 🌱 Création et gestion des branches

- **Main Branches** :
    - `main` : Contient la version stable du projet.
    - `develop` : Intégration des nouvelles fonctionnalités avant d’être fusionnée dans `main`.
- **Branches de travail** :
    - `feature/nom-fonctionnalité` : Ajout de nouvelles fonctionnalités.
    - `bugfix/nom-correction` : Correction de bugs mineurs.
    - `hotfix/nom-correction-urgente` : Correction de bugs en production.

### 📝 Rédaction des commits

Un bon commit suit cette convention :

```bash
git commit -m "feat: Ajout de la fonctionnalité X"

```

Exemples :

- `feat: Ajout du module d'authentification`
- `fix: Correction du bug d'affichage sur mobile`
- `docs: Mise à jour du README`

### 🔄 Workflow Git

1. **Créer une branche** :
    
    ```bash
    git checkout -b feature/nom-fonctionnalité
    
    ```
    
2. **Faire des commits clairs et réguliers** :
    
    ```bash
    git add .
    git commit -m "feat: Description courte"
    
    ```
    
3. **Pousser la branche sur le dépôt distant** :
    
    ```bash
    git push origin feature/nom-fonctionnalité
    
    ```
    
4. **Créer une Pull Request (PR) sur GitHub** :
    ```bash
    - Sélectionner `develop` comme branche de destination.
    - Décrire les modifications apportées.
    - Attendre la revue avant de fusionner.
    ```

5. **Gérer les conflits éventuels** :
    ```bash
    - git pull origin develop --rebase
    - git push origin feature/nom-fonctionnalité  
    ```