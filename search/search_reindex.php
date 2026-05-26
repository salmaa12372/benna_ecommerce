<?php
/**
 * search/search_reindex.php
 * Déclenche la ré-indexation Python depuis PHP.
 * Appelé après chaque add / update / delete produit dans produit_controller.php
 */

function reindex_products(): bool {
    $script  = realpath(__DIR__ . '/indexer.py');
    if (!$script) return false;

    $python  = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    $log     = __DIR__ . '/output/reindex.log';

    // Exécution asynchrone (n'attend pas la fin)
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen("start /B $python $script >> \"$log\" 2>&1", "r"));
    } else {
        exec("$python $script >> $log 2>&1 &");
    }
    return true;
}
