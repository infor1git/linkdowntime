<?php
/**
 * Classe para gerenciamento do menu
 */
class PluginLinkdowntimeMenu extends CommonGLPI {
    
    /**
     * Nome do menu
     */
    static function getMenuName() {
        return __('Link Downtime Manager', 'linkdowntime');
    }
    
    /**
     * Conteúdo do menu
     */
    static function getMenuContent() {
        $menu = [
            'title' => self::getMenuName(),
            'page'  => Plugin::getWebDir('linkdowntime') . '/front/downtime.php',
            'icon'  => 'fas fa-network-wired',
            'links' => [
                'search' => Plugin::getWebDir('linkdowntime') . '/front/downtime.php',
                'add'    => Plugin::getWebDir('linkdowntime') . '/front/downtime.form.php',
                'dashboard' => Plugin::getWebDir('linkdowntime') . '/front/dashboard.php',
            ]
        ];
        
        if (PluginLinkdowntimeDowntime::canView()) {
            $menu['options']['downtime'] = [
                'title' => __('Downtime Records', 'linkdowntime'),
                'page'  => Plugin::getWebDir('linkdowntime') . '/front/downtime.php',
                'links' => [
                    'search' => Plugin::getWebDir('linkdowntime') . '/front/downtime.php',
                    'add'    => Plugin::getWebDir('linkdowntime') . '/front/downtime.form.php'
                ]
            ];
            
            $menu['options']['dashboard'] = [
                'title' => __('Dashboard', 'linkdowntime'),
                'page'  => Plugin::getWebDir('linkdowntime') . '/front/dashboard.php'
            ];
        }
        
        return $menu;
    }
}
