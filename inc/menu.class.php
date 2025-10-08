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
        $dashboard_image = '<i class="fas fa-chart-bar"
                                title="' . __('Dashboard', 'formcreator') . '"></i>&nbsp; Dashboard';
        
        $downtime_image = '<i class="fas fa-clipboard-list"
                                title="' . __('Dashboard', 'formcreator') . '"></i>&nbsp; Registros';
        
        $menu = [
            'title' => self::getMenuName(),
            'page'  => Plugin::getWebDir('linkdowntime') . '/front/downtime.php',
            'icon'  => 'fab fa-cloudsmith',
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
                    'add'    => Plugin::getWebDir('linkdowntime') . '/front/downtime.form.php',
                    $dashboard_image => Plugin::getWebDir('linkdowntime') . '/front/dashboard.php',
                ]
            ];
            
            $menu['options']['dashboard'] = [
                'title' => __('Dashboard', 'linkdowntime'),
                'page'  => Plugin::getWebDir('linkdowntime') . '/front/dashboard.php',
                'links' => [
                    $downtime_image => Plugin::getWebDir('linkdowntime') . '/front/downtime.php',
                    'add'    => Plugin::getWebDir('linkdowntime') . '/front/downtime.form.php'
                ]
            ];
        }
        
        return $menu;
    }
}
