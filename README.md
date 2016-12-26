![Zephyrus](https://cloud.githubusercontent.com/assets/4491532/21475508/82430ca8-cafa-11e6-8310-0683459d5f21.png)

Framework PHP léger orienté MVC avec mécanisme de routes REST destiné à être utilisé pour les projets de développement Web de Vovan Tucker.

# Prérequis
Zephyrus fonctionne avec PHP 7 intégré avec le serveur web Apache2. Il nécessite uniquement le gestionnaire de dépendances [composer](https://getcomposer.org/). Pour procéder à son installation dans un environnement Debian, suivez les instructions ci-dessous. Pour tout autre environnement, référez-vous à la documentation officielle.   

Dans un premier temps, placez-vous dans un répertoire de travail et lancer le téléchargement du script d'installation. Prenez soin de vérifier le haché du fichier reçu. 
```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '61069fe8c6436a4468d0371454cf38a812e451a14ab1691543f25a9627b97ff96d8753d92a00654c21e2212a5ae1ff36') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
```

Ensuite, exécuter le script pour obtenir le composer.phar et supprimer le script d'installation.
```
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

Finalement, pour installer composer de façon globale (recommandée), déplacer le composer.phar dans les binaires.
```
mv composer.phar /usr/local/bin/composer
```

# Installation
Téléchargez le framework Zephyrus depuis l'archive GitHub ou utiliser la commande `git`.
```
wget https://github.com/dadajuice/zephyrus/archive/master.zip
unzip master.zip -d repertoire_nom_projet
```

Dirigez-vous dans le répertoire du projet et lancez l'installation des dépendances du framework avec composer.
```
composer install
```
