<?php
/**
 * Funções de instalação/desinstalação do plugin LinkDowntime
 * VERSÃO COM SUPORTE A TICKETS
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Função de instalação
 */
function plugin_linkdowntime_install() {
    global $DB;
    
    $migration = new Migration(110); // Versão atualizada
    
    // Criar tabela principal se não existir
    if (!$DB->tableExists('glpi_plugin_linkdowntime_downtimes')) {
        $query = "CREATE TABLE `glpi_plugin_linkdowntime_downtimes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `locations_id` int(11) NOT NULL DEFAULT '0',
            `suppliers_id` int(11) NOT NULL DEFAULT '0',
            `tickets_id` int(11) NOT NULL DEFAULT '0',
            `start_datetime` datetime DEFAULT NULL,
            `end_datetime` datetime DEFAULT NULL,
            `communication_datetime` datetime DEFAULT NULL,
            `observation` text,
            `entities_id` int(11) NOT NULL DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            `date_creation` datetime DEFAULT NULL,
            `date_mod` datetime DEFAULT NULL,
            `users_id` int(11) NOT NULL DEFAULT '0',
            `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `locations_id` (`locations_id`),
            KEY `suppliers_id` (`suppliers_id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->queryOrDie($query, $DB->error());
    } else {
        // Verificar se precisa adicionar o campo tickets_id
        if (!$DB->fieldExists('glpi_plugin_linkdowntime_downtimes', 'tickets_id')) {
            $migration->addField('glpi_plugin_linkdowntime_downtimes', 'tickets_id', 
                'int(11) NOT NULL DEFAULT 0 AFTER suppliers_id');
            $migration->addKey('glpi_plugin_linkdowntime_downtimes', 'tickets_id');
        }
    }
    
    // Criar direitos usando o sistema correto do GLPI 10
    ProfileRight::addProfileRights(['linkdowntime']);
    
    // Para garantir que apareça na seção Tools, vamos inserir manualmente nos perfis principais
    $profiles_rights = [
        4 => 31,  // Super-Admin - todos os direitos
        2 => 31,  // Admin - todos os direitos
        6 => 23,  // Technician - criar, ler, atualizar, excluir
        3 => 7,   // Normal - criar, ler, atualizar
        1 => 2    // Self-Service - apenas leitura
    ];
    
    foreach ($profiles_rights as $profile_id => $rights_value) {
        // Verificar se o perfil existe
        if (countElementsInTable('glpi_profiles', ['id' => $profile_id]) > 0) {
            // Verificar se já existem direitos
            $existing = countElementsInTable('glpi_profilerights', [
                'profiles_id' => $profile_id,
                'name' => 'linkdowntime'
            ]);
            
            if ($existing == 0) {
                $DB->insert('glpi_profilerights', [
                    'profiles_id' => $profile_id,
                    'name' => 'linkdowntime',
                    'rights' => $rights_value
                ]);
            }
        }
    }
    
    $migration->executeMigration();
    return true;
}

/**
 * Função de desinstalação
 */
function plugin_linkdowntime_uninstall() {
    global $DB;
    
    // Remover tabela
    if ($DB->tableExists('glpi_plugin_linkdowntime_downtimes')) {
        $DB->queryOrDie("DROP TABLE `glpi_plugin_linkdowntime_downtimes`", $DB->error());
    }
    
    // Remover direitos de todos os perfis
    $DB->delete('glpi_profilerights', ['name' => 'linkdowntime']);
    
    return true;
}