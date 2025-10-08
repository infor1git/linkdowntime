<?php

class PluginLinkdowntimeInstall {
    
    static function install() {
        global $DB;
        
        // Criar tabela principal
        if (!$DB->tableExists('glpi_plugin_linkdowntime_downtimes')) {
            $query = "CREATE TABLE `glpi_plugin_linkdowntime_downtimes` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL DEFAULT '',
                `locations_id` int(11) NOT NULL DEFAULT '0',
                `suppliers_id` int(11) NOT NULL DEFAULT '0',
                `tickets_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
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
            
            $DB->queryOrDie($query, 'Error creating table glpi_plugin_linkdowntime_downtimes');
        }
        
        // Adicionar direitos aos perfis
        self::addRightsToProfiles();
        
        return true;
    }
    
    static function uninstall() {
        global $DB;
        
        // Remover tabelas
        $tables = ['glpi_plugin_linkdowntime_downtimes'];
        foreach ($tables as $table) {
            if ($DB->tableExists($table)) {
                $DB->queryOrDie("DROP TABLE `$table`", 'Error dropping table ' . $table);
            }
        }
        
        // Remover direitos
        ProfileRight::deleteProfileRights(['linkdowntime']);
        
        return true;
    }
    
    /**
     * Adiciona direitos aos perfis existentes
     */
    static function addRightsToProfiles() {
        // Primeiro, registra o direito no sistema
        ProfileRight::addProfileRights(['linkdowntime']);
        
        // Define direitos padrão para perfis existentes
        $profiles_rights = [
            4 => CREATE | READ | UPDATE | DELETE | PURGE, // Super-Admin
            2 => CREATE | READ | UPDATE | PURGE, // Admin  
            6 => CREATE | READ | UPDATE,         // Technician
            3 => READ,                           // Normal
            1 => 0                              // Self-Service
        ];
        
        foreach ($profiles_rights as $profile_id => $rights) {
            // Verificar se o perfil existe
            $profile = new Profile();
            if ($profile->getFromDB($profile_id)) {
                // Atualizar direitos
                ProfileRight::updateProfileRights($profile_id, ['linkdowntime' => $rights]);
            }
        }
    }
}