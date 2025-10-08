<?php

define('PLUGIN_LINKDOWNTIME_VERSION', '1.0.0');
define('PLUGIN_LINKDOWNTIME_MIN_GLPI_VERSION', '10.0.0');
define('PLUGIN_LINKDOWNTIME_MAX_GLPI_VERSION', '10.1.0');

/**
 * Plugin install process
 */
function plugin_linkdowntime_install() {
    return PluginLinkdowntimeInstall::install();
}

/**
 * Plugin uninstall process
 */
function plugin_linkdowntime_uninstall() {
    return PluginLinkdowntimeInstall::uninstall();
}

/**
 * Plugin information
 */
function plugin_version_linkdowntime() {
    return [
        'name'           => 'Link Downtime Manager',
        'version'        => PLUGIN_LINKDOWNTIME_VERSION,
        'author'         => 'INFOR1',
        'license'        => 'GPL v3+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_LINKDOWNTIME_MIN_GLPI_VERSION,
                'max' => PLUGIN_LINKDOWNTIME_MAX_GLPI_VERSION,
            ]
        ]
    ];
}

/**
 * Check pre-requisites
 */
function plugin_linkdowntime_check_prerequisites() {
    return version_compare(GLPI_VERSION, PLUGIN_LINKDOWNTIME_MIN_GLPI_VERSION, '>=') &&
           version_compare(GLPI_VERSION, PLUGIN_LINKDOWNTIME_MAX_GLPI_VERSION, '<=');
}

/**
 * Check configuration
 */
function plugin_linkdowntime_check_config($verbose = false) {
    return true;
}

/**
 * Initialize plugin
 */
function plugin_init_linkdowntime() {
    global $PLUGIN_HOOKS;
    
    $PLUGIN_HOOKS['csrf_compliant']['linkdowntime'] = true;
    
    if (Session::getLoginUserID()) {
        // Registrar classes
        Plugin::registerClass('PluginLinkdowntimeDowntime', [
            'addtabon' => ['Ticket']
        ]);
        
        Plugin::registerClass('PluginLinkdowntimeMenu');
        
        // IMPORTANTE: Registrar a classe Profile para adicionar aba ao Profile
        Plugin::registerClass('PluginLinkdowntimeProfile', [
            'addtabon' => ['Profile']
        ]);
        
        // Menu
        if (PluginLinkdowntimeDowntime::canView()) {
            $PLUGIN_HOOKS['menu_toadd']['linkdowntime'] = ['tools' => 'PluginLinkdowntimeMenu'];
        }
        
        // Hook para processar formulários de perfil
        $PLUGIN_HOOKS['pre_item_update']['linkdowntime'] = ['Profile' => ['PluginLinkdowntimeProfile', 'changeProfile']];
    }
}
