# WP Starter Pack

Plugin WordPress permettant d’installer une sélection d’extensions gratuites depuis une page d’administration : Wordfence, Elementor, Yoast SEO, WPForms Lite et Site Kit par Google.

## Installation

1. Compressez le dossier du plugin en ZIP, avec `wp-starter-pack.php`, `includes/`, `templates/` et `assets/` dans un même dossier racine.
2. Dans WordPress, ouvrez **Extensions → Ajouter une extension → Téléverser une extension**.
3. Téléversez l’archive, installez puis activez **WP Starter Pack**.
4. Ouvrez **Starter Pack** dans le menu d’administration, sélectionnez vos modules et cliquez sur **Installer les modules**.

Les modules sont **installés sans activation automatique**. Activez-les ensuite dans **Extensions**, puis effectuez les éventuelles configurations, connexions de compte ou inscriptions demandées par chacun.

## Suppression automatique

La case située entre la liste et le bouton d’installation est décochée par défaut. Cochée, elle autorise la désactivation puis la suppression du seul dossier de Starter Pack, après vérification côté serveur de toutes les installations sélectionnées. Les modules installés restent présents.

Si une installation échoue, Starter Pack reste disponible. Les installations déjà réussies ne sont pas annulées ; réessayez les modules en échec. Si une requête est interrompue, rechargez la page pour connaître l’état réel avant de relancer. En cas d’échec de suppression, vérifiez l’état de Starter Pack dans Extensions.

Les modules déjà installés sont signalés et ne sont ni réinstallés ni mis à jour. Si tous les modules sont déjà présents, gérez ou supprimez Starter Pack depuis Extensions.

## Prérequis et limites

- WordPress 6.5 ou ultérieur et PHP 7.4 ou ultérieur pour Starter Pack. Les prérequis courants de chaque extension sont vérifiés avant son téléchargement.
- Compte autorisé à installer des extensions ; droits de suppression et d’activation également nécessaires pour la suppression automatique.
- Accès de l’hébergement à WordPress.org et accès direct en écriture de WordPress au dossier des extensions. Les hébergements exigeant une saisie FTP/SSH ne sont pas pris en charge par cet assistant AJAX ; un message invite à utiliser l’installateur WordPress habituel.
- En multisite, le menu est accessible dans l’administration du réseau. L’installation rend les extensions disponibles sur le réseau sans les activer. La suppression de Starter Pack concerne tout le réseau.
- Gardez l’onglet ouvert pendant l’installation. Il n’existe pas de tâche d’installation en arrière-plan.

## Structure

```text
wp-starter-pack.php                           Point d’entrée et métadonnées
includes/class-plugin-catalog.php             Catalogue fermé des cinq modules
includes/class-admin-page.php                 Menu et chargement des ressources
includes/class-installation-controller.php    Installation AJAX et suppression
templates/admin-page.php                      Vue de la page d’administration
assets/css/admin.css                          Styles limités à la page du plugin
assets/js/admin.js                            Sélection, progression et résultats
```

Les requêtes sont protégées par les permissions WordPress et un nonce. La sélection est conservée temporairement côté serveur, associée à l’utilisateur et à un identifiant aléatoire, avec expiration au bout d’une heure. La vérification finale repose sur les fichiers réellement reconnus par WordPress et non sur un bilan envoyé par JavaScript.

Les téléchargements passent par `plugins_api()` et `Plugin_Upgrader`. Aucune URL de paquet n’est acceptée depuis le navigateur. Les images officielles sont chargées depuis `ps.w.org` ; le navigateur contacte donc ce service lors de l’affichage de la page. Une initiale remplace une image indisponible. Les liens « En savoir plus » ouvrent les fiches françaises de WordPress.org.

Licence : GPL-2.0-or-later.

## Vérifications de développement

```sh
php tests/installation-controller-test.php
node tests/admin-script-test.js
```

Ces tests utilisent des doublures WordPress et DOM pour vérifier les contrôles d’accès, les conditions de suppression et les parcours d’installation. Ils ne remplacent pas un essai sur un site WordPress de test avec téléchargement réel, permissions de fichiers et configuration multisite si nécessaire.
