# Project Brief & AI Persona - HPA School Gestion

## 0. Rôles et Compétences (AI Persona)
Pour ce projet, j'adopte le rôle de **Architecte Logiciel Senior & Expert Full-Stack Laravel/Vue/Tailwind**.
**Skills activés depuis ma bibliothèque :**
- `backend-architect` : Analyse de l'architecture MVC, structuration des bases de données relationnelles, optimisation Eloquent.
- `frontend-design` : Gestion de l'interface utilisateur avec Tailwind CSS et Alpine.js, respect des bonnes pratiques responsives.
- `security-auditor` : Analyse des accès basés sur les rôles (RBAC) via Spatie Permissions et protection des routes.
- `database-design` : Rétro-ingénierie et documentation du schéma relationnel (Classes, Sessions, Paiements, Évaluations).

## 1. Vision du Projet
HPA School Gestion est un système d'information complet pour l'administration de la **High Performance Academy (HPA)**. Il centralise la gestion académique, la planification des cours, l'évaluation des étudiants et les flux financiers (rémunération des formateurs et suivi des paiements des étudiants).

## 2. Périmètre Fonctionnel (Core Domains)
L'application est divisée en 5 piliers majeurs :
1. **Administration et Structure Académique** : Gestion des Programmes, Niveaux, Classes (`CourseClass`), et Utilisateurs.
2. **Planification et Pédagogie** : Création de sessions de cours (`ClassSession`), suivi des présences (`Attendance`), et rapports de session (`SessionReport`).
3. **Évaluation et Devoirs** : Les formateurs créent des devoirs (`Assignment`), les étudiants soumettent leurs travaux (`Submission`), et reçoivent des notes (`Grade`).
4. **Gestion Financière** :
   - *Formateurs* : Calcul automatique de la rémunération basé sur des règles tarifaires (`PaymentRule`) liées aux sessions effectuées, aboutissant à la génération de fiches de paie (`Payment`).
   - *Étudiants* : Suivi des frais de scolarité (`StudentPayment`).
5. **Communication interne** : Messagerie intégrée (`Message`) et système de notifications.

## 3. Utilisateurs Cibles (RBAC)
- **Admin** : Superviseur global. Gère la configuration système, les utilisateurs, la modération des retours étudiants, et les validations financières.
- **Manager (Gestionnaire)** : Opérationnel quotidien. Assigne les classes, gère les plannings (sessions) et prépare les paiements des coachs.
- **Coach (Formateur)** : Animateur pédagogique. Déclare ses sessions de cours, soumet ses rapports, évalue les travaux et suit sa propre rémunération.
- **Student (Apprenant)** : Bénéficiaire. Consulte son emploi du temps, soumet ses devoirs, évalue les sessions et suit ses paiements de scolarité.

## 4. Modèle Économique / Valeur Métier
Cet outil interne vise à supprimer la charge administrative manuelle de HPA. Il garantit la transparence de la rémunération des formateurs et le suivi rigoureux des encaissements des étudiants pour sécuriser la trésorerie de l'académie.
