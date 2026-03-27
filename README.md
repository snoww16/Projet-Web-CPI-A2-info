# CESI Ton Stage - Plateforme de Mise en Relation pour Stages et Alternances

**CESI Ton Stage** est une application web développée dans le cadre du projet de fin d'année du cycle d'ingénieur CESI (Bloc 4 - Développement Web). Cette plateforme a pour objectif de faciliter la mise en relation entre les étudiants à la recherche d'un stage ou d'une alternance, les entreprises proposant des offres, et les pilotes de formation assurant le suivi pédagogique.

Ce projet a été conçu en respectant un cahier des charges strict, détaillant des spécifications fonctionnelles (SFx) et techniques (STx) précises, tout en s'imposant la contrainte de n'utiliser aucun framework web complet (tel que Symfony, Laravel ou React) afin de démontrer une maîtrise approfondie des langages et architectures fondamentaux.

---

## Fonctionnalités Principales (Spécifications Fonctionnelles)

Le système repose sur une matrice de permissions définissant les droits d'accès selon quatre rôles distincts : Administrateur, Pilote, Étudiant et Utilisateur Anonyme.

### 1. Gestion des Utilisateurs et Authentification (SFx 1, 12-19)
* **Système de connexion sécurisé** : Authentification via email et mot de passe haché.
* **Routage protégé** : Restriction d'accès aux pages et aux actions selon le rôle de l'utilisateur connecté.
* **Gestion des comptes** : Les administrateurs peuvent créer, modifier et supprimer des comptes pour les étudiants et les pilotes. 
* **Espaces Profils** : Chaque utilisateur dispose d'un espace personnel adapté à son rôle (gestion de l'avatar, mise à jour des informations de contact).

### 2. Gestion des Entreprises (SFx 2-6)
* **Annuaire des entreprises** : Recherche multicritères d'entreprises partenaires.
* **Administration** : Création, modification et suppression des fiches entreprises par les administrateurs et les pilotes.
* **Évaluation** : Système de notation et de commentaires permettant d'évaluer la qualité de l'accueil en stage.

### 3. Gestion des Offres de Stage (SFx 7-11)
* **Moteur de recherche avancé** : Filtrage des offres par mots-clés, localisation géographique, durée, type de contrat (Stage/Alternance) et niveau d'études requis.
* **Publication et cycle de vie** : Création, édition et suppression des offres, rattachées à une entreprise spécifique.
* **Statistiques et Dashboard** : Consultation d'indicateurs clés en temps réel (nombre total d'offres, moyenne de postulants, répartition par durée, top des offres les plus plébiscitées).

### 4. Candidatures et Suivi (SFx 20-22)
* **Postuler en ligne** : Les étudiants peuvent soumettre leur candidature à une offre en téléversant leur Curriculum Vitae et leur Lettre de Motivation (formats PDF et PNG acceptés et validés côté serveur).
* **Historique des candidatures** : Consultation par l'étudiant de l'ensemble de ses candidatures envoyées.
* **Supervision par les Pilotes** : Les pilotes de formation ont un accès direct à la liste de leurs étudiants assignés et peuvent consulter l'état de leurs recherches ainsi que les candidatures envoyées.

### 5. Liste de Souhaits / Wishlist (SFx 23-25)
* **Mise en favoris** : Ajout et retrait d'offres dans une liste de souhaits personnelle.
* **Interface asynchrone** : Gestion des favoris sans rechargement de page grâce à des requêtes AJAX (JavaScript Vanilla).

---

## Architecture et Choix Techniques (Spécifications Techniques)

Conformément aux exigences techniques (STx), l'application a été construite "from scratch" en respectant les standards de l'industrie.

* **Architecture MVC** : Séparation stricte des responsabilités entre les Modèles (interactions avec la base de données), les Vues (affichage) et les Contrôleurs (logique métier).
* **Backend** : PHP 8+ orienté objet (POO).
* **Frontend** : HTML5 sémantique et CSS3. Conception "Responsive Design" assurant une compatibilité avec les terminaux mobiles (Media Queries, Flexbox, Grid).
* **Base de données** : MySQL / MariaDB. Modèle relationnel strict utilisant des clés étrangères pour garantir l'intégrité référentielle. Intégration via l'API PDO avec requêtes systématiquement préparées.
* **Moteur de Template** : Utilisation de Twig pour générer dynamiquement les vues, permettant l'inclusion de fragments (layouts, composants) et la sécurisation automatique des affichages.

---

## Sécurité et Optimisation SEO

### Sécurité (STx 11)
* **Injections SQL** : Prévention garantie par l'utilisation exclusive de requêtes préparées PDO.
* **Failles XSS** : Échappement automatique des variables de sortie géré par le moteur Twig.
* **Mots de passe** : Utilisation de l'algorithme `BCRYPT` via `password_hash()` pour le stockage en base de données.
* **Contrôle d'accès** : Validation systématique du rôle de la session en amont de chaque contrôleur sensible.
* **Upload de fichiers** : Vérification stricte des extensions MIME côté serveur pour prévenir l'exécution de scripts malveillants.

### SEO - Optimisation pour les Moteurs de Recherche (STx 12 & 13)
* **Routage et URL Rewriting** : Utilisation d'un point d'entrée unique (`public/index.php`) et d'un fichier `.htaccess` pour générer des URL propres, lisibles et hiérarchisées (ex: `/offre/12`).
* **Balisage** : Utilisation pertinente des balises sémantiques (Hn, attributs alt).
* **Indexation** : Présence d'un fichier `robots.txt` pour bloquer l'indexation des espaces privés, et d'un fichier `sitemap.xml` pour cartographier les pages publiques.

---

## Guide d'Installation et de Déploiement

### 1. Prérequis
* Un serveur web (Apache HTTP Server).
* PHP 8.0 ou supérieur.
* Un serveur de base de données (MySQL ou MariaDB).
* Composer (Gestionnaire de dépendances PHP).

### 2. Installation locale
1. Clonez ce dépôt dans la racine de votre serveur web (ex: `/var/www/html/` ou `htdocs`).
2. Ouvrez un terminal à la racine du projet et installez les dépendances requises (notamment Twig et PHPUnit) :
   ```bash
   composer install
