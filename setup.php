<?php
/**
 * Plugin LinkDowntime para GLPI 10.0.16
 * SETUP COMPLETO COM ABA FUNCIONAL NOS CHAMADOS
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

// Versões suportadas
define("PLUGIN_LINKDOWNTIME_MIN_GLPI_VERSION", "10.0.0");
define("PLUGIN_LINKDOWNTIME_MAX_GLPI_VERSION", "10.0.99");
define("PLUGIN_LINKDOWNTIME_VERSION", "1.0.0");

/**
 * Função para retornar informações sobre o plugin
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
 * Verificação de pré-requisitos
 */
function plugin_linkdowntime_check_prerequisites() {
    if (version_compare(GLPI_VERSION, PLUGIN_LINKDOWNTIME_MIN_GLPI_VERSION, 'lt')
        || version_compare(GLPI_VERSION, PLUGIN_LINKDOWNTIME_MAX_GLPI_VERSION, 'gt')) {
        return false;
    }
    return true;
}

/**
 * Verificação de configuração
 */
function plugin_linkdowntime_check_config($verbose = false) {
    return true;
}

/**
 * Função de inicialização do plugin
 */
function plugin_init_linkdowntime() {
    global $PLUGIN_HOOKS;

    // Proteção CSRF
    $PLUGIN_HOOKS['csrf_compliant']['linkdowntime'] = true;
    
    // Registrar classes principais do plugin
    Plugin::registerClass('PluginLinkdowntimeDowntime', [
        'addtabon' => ['Ticket'],  // CORREÇÃO: Adicionar aba especificamente no Ticket
    ]);
    
    Plugin::registerClass('PluginLinkdowntimeMenu');
    Plugin::registerClass('PluginLinkdowntimeProfile');
    
    // Verificar se o usuário está logado e o plugin ativo
    if (Session::getLoginUserID() && Plugin::isPluginActive('linkdowntime')) {
        
        // Adicionar ao menu Tools
        $PLUGIN_HOOKS['menu_toadd']['linkdowntime'] = ['tools' => 'PluginLinkdowntimeMenu'];
        
        // CORREÇÃO PRINCIPAL: Hook correto para adicionar aba aos tickets
        $PLUGIN_HOOKS['item_add_targets']['linkdowntime'] = ['Ticket' => 'PluginLinkdowntimeDowntime'];
        
        // Hook para exibir direitos na interface de perfis
        $PLUGIN_HOOKS['display_profile']['linkdowntime'] = 'plugin_linkdowntime_display_profile';
        
        // Hook para salvar direitos dos perfis
        $PLUGIN_HOOKS['profile_form']['linkdowntime'] = 'plugin_linkdowntime_profile_form';
        
        // Hook para criar direitos em novos perfis
        $PLUGIN_HOOKS['profile_rights']['linkdowntime'] = 'plugin_linkdowntime_profile_rights';
        
        // Registrar direitos na seção Tools
        $PLUGIN_HOOKS['use_massive_action']['linkdowntime'] = 1;
    }
}

/**
 * Hook para exibir direitos na interface de perfis
 */
function plugin_linkdowntime_display_profile($profiles_id) {
    global $DB;
    
    $profile = new Profile();
    if ($profile->getFromDB($profiles_id)) {
        
        // Obter direitos atuais
        $rights = 0;
        $result = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $profiles_id,
                'name' => 'linkdowntime'
            ]
        ]);
        
        foreach ($result as $row) {
            $rights = $row['rights'];
            break;
        }
        
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>" . __('Link Downtime Manager', 'linkdowntime') . "</th>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Link Downtime Manager', 'linkdowntime') . "</td>";
        
        // Direito de Leitura
        echo "<td width='20%'>";
        echo "<input type='hidden' name='_linkdowntime[r]' value='0'>";
        echo "<input type='checkbox' name='_linkdowntime[r]' value='1' " . 
             (($rights & READ) ? 'checked' : '') . ">";
        echo " " . __('Read');
        echo "</td>";
        
        // Direito de Escrita
        echo "<td width='20%'>";
        echo "<input type='hidden' name='_linkdowntime[w]' value='0'>";
        echo "<input type='checkbox' name='_linkdowntime[w]' value='1' " . 
             (($rights & (CREATE | UPDATE | DELETE)) ? 'checked' : '') . ">";
        echo " " . __('Write');
        echo "</td>";
        
        // Direito de Exclusão Permanente
        echo "<td width='20%'>";
        echo "<input type='hidden' name='_linkdowntime[p]' value='0'>";
        echo "<input type='checkbox' name='_linkdowntime[p]' value='1' " . 
             (($rights & PURGE) ? 'checked' : '') . ">";
        echo " " . __('Delete permanently');
        echo "</td>";
        
        echo "</tr>";
        echo "</table>";
        echo "</div>";
    }
}

/**
 * Hook para processar formulário de perfis
 */
function plugin_linkdowntime_profile_form($item) {
    if (isset($_POST['_linkdowntime']) && is_array($_POST['_linkdowntime'])) {
        
        $rights = 0;
        
        if (isset($_POST['_linkdowntime']['r']) && $_POST['_linkdowntime']['r']) {
            $rights |= READ;
        }
        if (isset($_POST['_linkdowntime']['w']) && $_POST['_linkdowntime']['w']) {
            $rights |= CREATE | UPDATE | DELETE;
        }
        if (isset($_POST['_linkdowntime']['p']) && $_POST['_linkdowntime']['p']) {
            $rights |= PURGE;
        }
        
        ProfileRight::updateProfileRights($item->getID(), ['linkdowntime' => $rights]);
    }
}

/**
 * Hook para definir direitos padrão do plugin
 */
function plugin_linkdowntime_profile_rights($profiles_id) {
    return ['linkdowntime' => ALLSTANDARDRIGHTS];
}