<?php
/**
 * Logger Global - Écrit des logs dans des fichiers datés
 * 
 * Usage:
 * log_message("Ceci est un message d'information", "INFO");
 * log_error("Ceci est une erreur");
 */

// Vérification et création du dossier de logs
if (!file_exists('./logs')) {
    mkdir('./logs', 0755, true);
}

/**
 * Écrit un message dans le fichier de log du jour
 * 
 * @param string $message Le message à logger
 * @param string $level Niveau de log (INFO, WARNING, ERROR, etc.)
 * @param string $context Contexte supplémentaire
 * @return bool True si réussi, False sinon
 */
function log_message(string $message, string $level = "INFO", string $context = ""): bool
{
    $date = date('Y-m-d');
    $datetime = date('Y-m-d H:i:s');
    $logFile = "./logs/{$date}.log";
    
    // Format du message
    $logMessage = "[{$datetime}] [{$level}]";
    if (!empty($context)) {
        $logMessage .= " [{$context}]";
    }
    $logMessage .= " - {$message}" . PHP_EOL;
    
    // Écriture dans le fichier
    return file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Enregistre une erreur (niveau ERROR)
 * 
 * @param string $message Message d'erreur
 * @param string $context Contexte supplémentaire
 * @return bool True si réussi, False sinon
 */
function log_error(string $message, string $context = "", $variable = ""): bool
{
    return log_message($message, "ERROR", $context . " - " . json_encode($variable));
}

/**
 * Enregistre un avertissement (niveau WARNING)
 * 
 * @param string $message Message d'avertissement
 * @param string $context Contexte supplémentaire
 * @return bool True si réussi, False sinon
 */
function log_warning(string $message, string $context = "", $variable = ""): bool
{
    return log_message($message, "WARNING", $context . " - " . json_encode($variable));
}

/**
 * Enregistre une information (niveau INFO)
 * 
 * @param string $message Message d'information
 * @param string $context Contexte supplémentaire
 * @return bool True si réussi, False sinon
 */
function log_info(string $message, string $context = "", $variable = ""): bool
{
    return log_message($message, "INFO", $context . " - " . json_encode($variable));
}

/**
 * Enregistre un message de debug (niveau DEBUG)
 * 
 * @param string $message Message de debug
 * @param string $context Contexte supplémentaire
 * @return bool True si réussi, False sinon
 */
function log_debug(string $message, string $context = "", $variable = ""): bool
{
    return log_message($message, "DEBUG", $context . " - " . json_encode($variable));
}

/**
 * Nettoie les anciens fichiers de log (plus de $days jours)
 * 
 * @param int $days Nombre de jours à conserver
 * @return array Liste des fichiers supprimés
 */
function clean_old_logs(int $days = 30): array
{
    $deleted = [];
    $files = glob('./logs/*.log');
    $cutoff = strtotime("-{$days} days");
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            if (unlink($file)) {
                $deleted[] = basename($file);
            }
        }
    }
    
    return $deleted;
}