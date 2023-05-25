# Projet de refactorisation - Symfony 6

## Sommaire

 * [A propos](#a-propos)
 * [Installation](#installation)
 * [Objectif](#objectif)
 * [Tests](#tests)

## A propos

Ceci est un repository pour s'exercer à la refactorisation d'une API simple, écrite en Symfony 6 et php 8. Il permettra de mettre à l'épreuve vos connaissances des bonnes pratiques générales de développement, votre soucis du design pattern, vos connaissances du langage PHP ainsi que des subtilités et fonctionnalités du framework Symfony 6.

## Installation

__Pré-requis :__

 * docker engine
 * docker-compose

Pour initialiser le projet, veuillez faire un fork du repository sur votre compte. Puis, lancer les commandes suivantes :

```bash
git clone https://github.com/<your-username>/refactorisation_projet

cd refactorisation_projet

docker-compose up -d --build

docker-compose exec -u 1000 refacto-projet-php bash

composer install

composer reset-db
```

## Objectif

Vous êtes dans la peau d'un développeur freelance, récemment débauché par une entreprise qui souhaite refactoriser le code de son application : une API destinée à lancer des parties de shifumi (pierre-papier-ciseaux) entre deux joueurs.

L'application a été développée par différentes équipes de niveaux technique hétérogènes et avec une implication variable par le passé. Ils n'ont pas pris la peine de respecter les bonnes pratiques de développement ni de s'intéresser au fonctionnement du framework utilisé. Leur seul objectif était de livrer de la fonctionnalité.

L'application est entièrement fonctionnelle. Les équipes précédentes ont pris la peine d'écrire des tests fonctionnels pour s'assurer que leur code correspondait aux attentes de leurs managers et chefs de projets.

Votre objectif est de refactoriser le code pour rendre l'application plus maintenable, plus lisible et plus facilement testable. Il sera attendu de vous que vous appliquez les bonnes pratiques de développement ainsi que vous utilisez au mieux les outils fournis par le framework. L'ajout de tests unitaires supplémentaires et de documentation est un plus appréciable. La seule contrainte que l'entreprise vous impose est de conserver toutes les fonctionnalités existantes de l'application. Les tests déjà présents doivent toujours être valides après votre refactorisation.

Une fois votre travail effectué, créez une pull request vers le projet d'origine.

## Tests

Des tests fonctionnels ont été écrits pour garantir la non régression de l'application. Pour lancer les tests :

```bash

# Pour réinitialiser la base de données avec le jeu de tests. Le script va supprimer la base existante, en créer une fraiche, lancer les migrations doctrine puis sauvegarder les fixtures avec alice
composer reset-db

# Lance tous les tests disponibles. Le script s'arrête lorsqu'une erreur est rencontrée. Ne pas prendre en compte les warnings
bin/phpunit
```
