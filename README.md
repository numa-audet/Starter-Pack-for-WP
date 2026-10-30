# WP Starter Pack

Plugin WordPress permettant d’installer une sélection d’extensions et de thèmes depuis une page d’administration : Wordfence, Elementor, Yoast SEO, WPForms Lite, Site Kit par Google et WooCommerce, ainsi que des compléments facultatifs.

## Catalogue et informations WordPress.org

L’interrupteur **Tout sélectionner** en haut de chaque section sélectionne ou désélectionne tous les éléments disponibles de cette section. Celui des modules inclut les addons. Les deux sections sont indépendantes ; les éléments déjà installés ou non autorisés sont ignorés. Les interrupteurs se synchronisent avec les choix individuels et sont désactivés pendant une installation ou une activation.

- Sous Elementor, un panneau dépliant propose Essential Addons, Premium Addons, ElementsKit Lite et Ultimate Addons for Elementor (anciennement Header Footer Elementor).
- Sous WPForms Lite, un panneau dépliant propose WP Mail SMTP. Il peut être installé indépendamment de WPForms.
- La colonne des thèmes propose Hello Elementor, Hello Biz, Hello Commerce et Astra. Plusieurs thèmes peuvent être installés ; aucun n’est activé automatiquement. Le thème actif n’est jamais changé par Starter Pack. Retrouvez vos thèmes dans **Apparence → Thèmes**. En multisite, leur autorisation et activation restent à gérer depuis WordPress.
- Chaque ligne affiche la note sur cinq, la version disponible sur WordPress.org et le nombre estimé d’installations actives fourni par le répertoire. La version disponible peut différer de celle déjà installée sur le site.
- Ces informations et les images sont chargées après l’affichage de la page, avec trois requêtes simultanées au maximum et un cache de six heures. En cas d’erreur du répertoire, un message remplace les données manquantes ; une nouvelle tentative est possible après cinq minutes. Une absence de données n’empêche pas de sélectionner les éléments.

Cocher un addon Elementor sélectionne aussi Elementor si nécessaire. Décocher Elementor désélectionne ses addons encore à installer. Le serveur inclut également Elementor dans la sélection et exige sa présence avant l’installation d’un addon. Pour l’activation, activez Elementor avant ses addons ; aucune autre extension n’est activée implicitement.

## Installation

1. Compressez le dossier du plugin en ZIP, avec `wp-starter-pack.php`, `includes/`, `templates/`, `assets/` et `img/` dans un même dossier racine.
2. Dans WordPress, ouvrez **Extensions → Ajouter une extension → Téléverser une extension**.
3. Téléversez l’archive, installez puis activez **WP Starter Pack**.
4. Ouvrez **Starter Pack** dans le menu d’administration, sélectionnez vos modules et cliquez sur **Installer les modules**.

Les modules sont **installés sans activation automatique**. Si vous conservez Starter Pack, chaque module installé propose un bouton **Activer et configurer** : il active uniquement ce module puis ouvre sa page d’administration dans le même onglet. Pour un module déjà actif, le bouton devient **Configurer**. Revenez à Starter Pack pour passer au module suivant. Les éventuelles configurations, connexions de compte ou inscriptions restent à effectuer dans l’extension concernée.

Les boutons sont disponibles également pour les modules déjà installés, et pour les installations réussies si une autre installation échoue. Pendant une installation, ils sont temporairement désactivés. Si Starter Pack est supprimé, utilisez le menu Extensions de WordPress.

## Suppression automatique

La case située entre les listes et le bouton d’installation est décochée par défaut. Cochée, elle autorise la désactivation puis la suppression du seul dossier de Starter Pack, après vérification côté serveur de tous les modules, addons et thèmes sélectionnés. Les éléments installés restent présents. L’échec d’un thème bloque la suppression au même titre que l’échec d’un module.

Si une installation échoue, Starter Pack reste disponible. Les installations déjà réussies ne sont pas annulées ; réessayez les modules en échec. Si une requête est interrompue, rechargez la page pour connaître l’état réel avant de relancer. En cas d’échec de suppression, vérifiez l’état de Starter Pack dans Extensions.

Les modules déjà installés sont signalés et ne sont ni réinstallés ni mis à jour. Si tous les modules sont déjà présents, gérez ou supprimez Starter Pack depuis Extensions.

## Prérequis et limites

- WordPress 6.5 ou ultérieur et PHP 7.4 ou ultérieur pour Starter Pack. Les prérequis courants de chaque extension sont vérifiés avant son téléchargement.
- Compte autorisé à installer des extensions ; droits de suppression et d’activation également nécessaires pour la suppression automatique.
- Le droit `install_themes` et l’accès direct en écriture au dossier des thèmes sont également nécessaires pour installer des thèmes.
- Accès de l’hébergement à WordPress.org et accès direct en écriture de WordPress au dossier des extensions. Les hébergements exigeant une saisie FTP/SSH ne sont pas pris en charge par cet assistant AJAX ; un message invite à utiliser l’installateur WordPress habituel.
- En multisite, le menu est accessible dans l’administration du réseau. L’installation rend les extensions disponibles sur le réseau sans les activer. La suppression de Starter Pack concerne tout le réseau.
- Le bouton **Activer et configurer** exige le droit d’activer le module. En multisite, il exige également les droits de gestion des extensions du réseau, active le module pour tout le réseau et ouvre sa configuration dans l’administration du site courant.
- Gardez l’onglet ouvert pendant l’installation. Il n’existe pas de tâche d’installation en arrière-plan.

## Structure

```text
wp-starter-pack.php                           Point d’entrée et métadonnées
includes/class-plugin-catalog.php             Catalogue des modules et compléments
includes/class-theme-catalog.php              Catalogue des thèmes
includes/class-directory-metadata.php         Métadonnées WordPress.org et cache
includes/class-admin-page.php                 Menu et chargement des ressources
includes/class-installation-controller.php    Installation AJAX et suppression
includes/class-activation-controller.php      Activation explicite et configuration
templates/admin-page.php                      Vue de la page d’administration
templates/catalog-item.php                    Ligne de module, addon ou thème
assets/css/admin.css                          Styles limités à la page du plugin
assets/js/admin.js                            Sélection, progression et résultats
assets/js/directory-metadata.js                Chargement progressif des métadonnées
```

Les requêtes sont protégées par les permissions WordPress et un nonce. La sélection est conservée temporairement côté serveur, associée à l’utilisateur et à un identifiant aléatoire, avec expiration au bout d’une heure. La vérification finale repose sur les fichiers réellement reconnus par WordPress et non sur un bilan envoyé par JavaScript.

Les téléchargements passent par `plugins_api()` et `Plugin_Upgrader`, ou `themes_api()` et `Theme_Upgrader` pour les thèmes. Aucune URL de paquet n’est acceptée depuis le navigateur. Les images officielles sont chargées depuis les services WordPress.org ; le navigateur contacte donc ces services lors de l’affichage de la page. Une initiale remplace une image indisponible. Les liens « En savoir plus » ouvrent les fiches WordPress.org correspondantes.

Licence : GPL-2.0-or-later.

## Vérifications de développement

```sh
php tests/installation-controller-test.php
node tests/admin-script-test.js
node tests/directory-metadata-test.js
```

Ces tests utilisent des doublures WordPress et DOM pour vérifier les contrôles d’accès, les conditions de suppression et les parcours d’installation. Ils ne remplacent pas un essai sur un site WordPress de test avec téléchargement réel, permissions de fichiers et configuration multisite si nécessaire.
