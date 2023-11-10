<?php

use App\Kernel;

// Inclut le fichier autoload_runtime.php à partir du répertoire du projet
require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

/**
 * Crée et retourne une instance du noyau Symfony.
 *
 * @param array $context Le contexte d'exécution de l'application.
 * @return Kernel L'instance du noyau Symfony initialisée.
 */
return function (array $context) {
    // Crée une nouvelle instance du noyau en fonction de l'environnement et du mode de débogage.
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
