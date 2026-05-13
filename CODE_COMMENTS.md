# Commentaires équipe — OmnesEvent (code existant)

Document d’aide à la compréhension. Objectif : que chaque membre sache **ce que fait** le code actuel et **ce qu’il faut ajouter**.

---

## 1) `includes/db.php`
**Rôle :** couche DB PDO.

- Définit la connexion PDO via variables d’env:
  - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (defaults: localhost / omnes_event / root / '')
- Helpers :
  - `db_query(string $sql, array $params = []): array`
  - `db_execute(string $sql, array $params = []): int`
  - `db_single(string $sql, array $params = []): ?array`
- `e(string $s): string` : échappement HTML.

✅ Déjà OK pour le MVP.

⚠️ À ajouter plus tard (non existant actuellement) :
- transaction helper si on implémente un verrouillage/capacité en réservation.

---

## 2) `includes/header.php` / `includes/footer.php`
**Rôle :** gabarits HTML + navbar.

### `includes/header.php`
- Utilise `<?= isset($pageTitle) ? e($pageTitle) : 'OmnesEvent' ?>`.
- Navbar contient :
  - lien “Événements”
  - lien “Mon profil”
  - lien “Connexion” ou “Déconnexion” selon `!empty($currentUser)`.

⚠️ IMPORTANT :
- `includes/header.php` suppose qu’une variable `$currentUser` existe dans chaque page.
- Aujourd’hui, la plupart des pages définissent `$currentUser = null;` (phase 0/squelette), donc la navbar ne sait pas réellement si l’utilisateur est connecté.

➡️ À faire Phase A : créer `includes/init.php` et charger `$currentUser` depuis `$_SESSION`.

---

## 3) `pages/index.php`
**Rôle :** page d’accueil (liste des événements à venir).

- Require `includes/db.php`.
- Définit `currentUser = null` (placeholder).
- Charge les 10 prochains événements :
  - Requête `SELECT ... WHERE e.date_event >= NOW() ORDER BY e.date_event ASC LIMIT 10`
  - Compte les réservations via sous-requête `(SELECT COUNT(*) FROM reservations r ...) AS nb_reservations`.
- Affiche les cartes + bouton “Voir”.

⚠️ À faire :
- Remplacer `require_once ../includes/db.php` + placeholder par `includes/init.php`.
- Une fois auth faite :
  - proposer “Mes billets” si connecté
  - éventuellement actions selon rôle.

---

## 4) `pages/events.php`
**Rôle :** catalogue complet + filtres.

- Définit `currentUser = null` (placeholder).
- Filtres GET :
  - `q` (titre/lieu/description en LIKE)
  - `date` (jour entier via >= d0 et < d1)
  - `category` (e.category_id)
  - `association` (e.association_id)
- Affiche la capacité restante : `max(0, jauge_max - nb_reservations)`.

⚠️ À faire : bootstrap auth + génération de bouton d’action (phase C).

---

## 5) `pages/event_detail.php`
**Rôle :** page de détail d’un événement.

- Paramètre : `GET id`.
- Valide : `if ($id <= 0) => 400`.
- Query : jointures `associations` + `categories` + compte réservations.
- Calcule : places restantes.
- UI : affiche affiche_path (ou default), date, lieu, catégorie, association, description.

⚠️ Inscription :
- Contenu actuellement :
  - bouton `Inscription bientôt` désactivé (placeholder MVP Phase A/C).

➡️ À faire Phase C :
- si utilisateur connecté et role participant : bouton “S’inscrire”
- si complet : désactiver.
- si l’utilisateur est l’organisateur de l’événement ou admin : afficher actions (modifier/supprimer) (Phase B).

---

## 6) `pages/register.php`
**Rôle :** inscription (UI + backend en Phase A).

- Définit `$currentUser = null` (placeholder).
- En POST : **actuellement ne fait rien** sauf `$errors[] = "Inscription non implémentée (Phase A)."`.

➡️ À faire pour rendre la création de compte fonctionnelle :
- validation champs `nom/email/password/role`
- `password_hash()`
- INSERT dans `users` avec rôle choisi.
- gérer `UNIQUE (email)`.
- redirection vers `login.php` (ou connexion automatique).

---

## 7) `pages/login.php`
**Rôle :** connexion.

- Définit `$currentUser = null` (placeholder).
- En POST : **actuellement placeholder**
  - `$errors[] = "Authentification non implémentée (Phase A)."`.

➡️ À faire :
- SELECT user by email
- `password_verify()`
- `session_start()` + affectation `$_SESSION` (user_id/role/association_id)
- redirect par rôle.

---

## 8) `pages/logout.php`
**Rôle :** déconnexion.

- `@session_start()`
- `$_SESSION = []` puis `@session_destroy()`
- redirige vers `/pages/index.php`.

✅ Côté destruction : déjà “fonctionnel” en l’état.

⚠️ Mais à aligner : dès qu’on ajoutera un `includes/init.php`, il faudra s’assurer que le même modèle de session est utilisé partout.

---

## 9) `pages/profile.php`
**Rôle :** profil (Phase A).

- Définit `$currentUser = null` (placeholder).
- Contenu : bloc UI indiquant que profil/édition/change password sont à implémenter.

➡️ À faire :
- `require_login` (une fois `includes/auth_check.php` ajoutée)
- charger user courant depuis `users`
- afficher infos + formulaire modification
- changer password (password hashing + update)

---

## 10) `db/schema.sql`
**Rôle :** schéma MySQL.

Tables :
- `users` (role enum, association_id, password_hash, is_organisateur_validated...)
- `associations`
- `categories`
- `events` (organizer_id, association_id, category_id, jauge_max, affiche_path)
- `reservations` (event_id, participant_id, presence_status enum)

Contrainte importante :
- `reservations` a une UNIQUE clé `(event_id, participant_id)`
- capacité gérée via `events.jauge_max` et le nombre de réservations.

---

## 11) `implementation_plan.md`, `README_PHASE_A.txt`, `TODO.md`
- `implementation_plan.md` : plan global (MVP → phases).
- `README_PHASE_A.txt` : conventions + checklist Phase A.
- `TODO.md` : checklist par phases.

---

# État actuel (utile pour l’équipe)
- Catalog + détail événements : ✅ affichage UI OK.
- Auth : ❌ placeholder (register/login/profil backend non faits).
- Inscription événement : ❌ placeholder (bouton désactivé dans `event_detail.php`).

# Prochaine étape technique minimale
1) Ajouter `includes/init.php` (session + `$currentUser`).
2) Implémenter `POST` `pages/register.php`.
3) Implémenter `POST` `pages/login.php`.
4) Ajouter une page profil minimale (lecture du user).

