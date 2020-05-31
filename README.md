<p align="center">
    <img align="center" src="https://cloud.githubusercontent.com/assets/4491532/21667795/e69dec6e-d2c9-11e6-8563-133291489ed3.png" width="45%">           
</p>

---
<p align="center"><i>Framework PHP élégant, simple, léger, plaisant et flexible</i></p>

---

[![Maintainability](https://api.codeclimate.com/v1/badges/6981c700b82a43834672/maintainability)](https://codeclimate.com/github/dadajuice/zephyrus/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/6981c700b82a43834672/test_coverage)](https://codeclimate.com/github/dadajuice/zephyrus/test_coverage)
[![codecov](https://codecov.io/gh/dadajuice/zephyrus/branch/master/graph/badge.svg)](https://codecov.io/gh/dadajuice/zephyrus)
[![Build Status](https://travis-ci.org/dadajuice/zephyrus.svg?branch=master)](https://travis-ci.org/dadajuice/zephyrus)
[![StyleCI](https://styleci.io/repos/77175312/shield?branch=master)](https://styleci.io/repos/77175312)
[![GitHub issues](https://img.shields.io/github/issues/dadajuice/zephyrus.svg)]()
[![GitHub release](https://img.shields.io/github/release/dadajuice/zephyrus.svg)]()

# Philosophie
Bienvenu dans le Framework Zephyrus! Ce framework est fondé sur un modèle pédagogique en s'orientant sur une structure MVC simple, une approche de programmation flexible laissant place à une extensibilité pour tous types de projet, une forte considération pour la sécurité applicative et une liberté de développement. Le tout offert depuis un noyeau orienté-objet élégant favorisant l'écriture d'un code de qualité propre et maintenable. Développement avec une philosophie de maintenir un plaisir à programmer en n'étant pas rigoureusement strict sur une utilisation figée où tout doit passer par une configuration et y être limité. Zephyrus s'insère à mi-chemin entre les plus petits frameworks et les monstres pour ainsi répondre aux besoins de la plupart des projets.

# Quelques caractéristiques générales
* Une **structure de projet simple et intuitive** basée sur une architecture Model-View-Controller. 
* Traitement des vues avec le préprocesseur HTML _[Pug](https://github.com/pug-php/pug)_ nativement intégré ou simplement du PHP natif.
* Approche pédagogique pour la conception élégante de classes et favorise une rétrocompatibilité avec les fonctionnalités natives de PHP comme l'utilisation des super-globales, de la session et autres.
* Routeur de requêtes simple et flexible basé sur des contrôleurs incluant une intégration facile de middlewares dans le flux d'une requête et d'un contrôleur du projet. Facilite la segmentation des responsabilités et la lecture d'une chaîne d'exécution.
* Plusieurs mécanismes de sécurité intégrés tel que les entêtes CSP, les jetons CSRF, protection XSS, détection d'intrusion (_[Expose](https://github.com/enygma/expose)_), mécanisme d'authorisation et plus encore!
* Philosophie d'accès aux données depuis des courtiers manuellement définis offrant un contôle complet sur la construction des requêtes SQL et, par conséquent, une facilité de maintenance et d'optimisation.
* Approche simple pour intégrer des recherches, tris et pagination sur les requêtes manuelles.
* Système de validation de formulaires élégant et facilement extensible offrant une multitude de règles nativement sur les nombres, les chaînes, les fichiers téléversés, les dates, etc.
* Moteur unique simple et optimisé pour la gestion des chaînes de caractères depuis une structure JSON, le tout facilement organisé pour offrir une internationalisation.
* Configuration d’un projet rapide et flexible permettant des paramètres personnalisées utilisables facilement. 
* Hautement extensibles facilement grâce à sa compatibilité avec les modules Composer.
* Plusieurs utilitaires rapides : cryptographie, validations, système de fichiers, gestionnaire d'erreurs, transport de messages, etc.
* Et plus encore !

# Installation
Zephyrus nécessite PHP 7.1 ou plus. Présentement, supporte uniquement Apache comme serveur web (pour un autre type de serveur, il suffirait d’adapter les fichiers .htaccess). Le gestionnaire de dépendance [Composer](https://getcomposer.org/) est également requis. La structure résultante de l’installation contient plusieurs exemples pour faciliter les premiers pas.

#### Option 1 : Installation depuis composer (recommandé)
```
$ composer create-project zephyrus/framework nom_projet
```

#### Option 2 : Depuis une archive
```
$ mkdir nom_projet
$ cd nom_projet
$ wget https://github.com/dadajuice/zephyrus-framework/archive/vx.y.z.tar.gz
$ tar -xvf vx.y.z.tar.gz --strip 1
$ composer install
```

#### Option 3 : Depuis les sources (version de développement pour faire un PR par exemple)
```
$ git clone https://github.com/dadajuice/zephyrus-framework.git
$ composer install  
```

# Utilisation
À venir ...

# Contribution

#### Sécurité
Veuillez communiquer en privé pour tout problème pouvant affecter la sécurité des applications créées avec ce framework.

#### Bogues et fonctionnalités
Pour rapporter des bogues, demander l’ajout de nouvelles fonctionnalités ou faire des recommandations, n’hésitez pas à utiliser l’[outil de gestion des problèmes](https://github.com/dadajuice/zephyrus-framework/issues) de GitHub.

#### Développement
Vous pouvez contribuer au développement de Zephyrus en soumettant des [PRs](https://github.com/dadajuice/zephyrus-framework/pulls).

# License
MIT (c) David Tucker
