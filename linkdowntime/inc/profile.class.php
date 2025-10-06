<?php
/**
 * Classe para gerenciar direitos do plugin LinkDowntime
 * Necessária para aparecer na interface dos perfis no GLPI 10.x
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginLinkdowntimeProfile extends Profile {
    
    static $rightname = "profile";
    
    /**
     * Exibir direitos do plugin na interface de perfil
     */
    function showForm($ID, $options = []) {
        
        echo "<div class='spaced' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='4'>" . __('Link Downtime Manager', 'linkdowntime') . "</th>";
        echo "</tr>";
        
        $profile = new Profile();
        $profile->getFromDB($ID);
        
        if (isset($profile->fields['linkdowntime'])) {
            $rights = $profile->fields['linkdowntime'];
        } else {
            $rights = 0;
        }
        
        echo "<tr class='tab_bg_2'>";
        echo "<td width='20%'>" . __('Link Downtime Manager', 'linkdowntime') . "</td>";
        
        echo "<td>";
        Html::showCheckbox([
            'name'    => '_linkdowntime[r]',
            'checked' => ($rights & READ),
            'zero_on_empty' => false
        ]);
        echo " " . __('Read');
        echo "</td>";
        
        echo "<td>";
        Html::showCheckbox([
            'name'    => '_linkdowntime[w]', 
            'checked' => ($rights & (CREATE | UPDATE | DELETE)),
            'zero_on_empty' => false
        ]);
        echo " " . __('Write');
        echo "</td>";
        
        echo "<td>";
        Html::showCheckbox([
            'name'    => '_linkdowntime[p]',
            'checked' => ($rights & PURGE),
            'zero_on_empty' => false
        ]);
        echo " " . __('Delete permanently');
        echo "</td>";
        
        echo "</tr>";
        
        echo "</table>";
        echo "</div>";
    }
    
    /**
     * Criar direitos do plugin em todos os perfis
     */
    static function createFirstAccess($profiles_id) {
        // Adicionar direitos padrão para novos perfis
        self::addDefaultProfileInfos($profiles_id, ['linkdowntime' => 0]);
    }
    
    /**
     * Obter valor dos direitos do plugin
     */
    static function getRightValue($rights_array) {
        $rights = 0;
        
        if (isset($rights_array['r']) && $rights_array['r']) {
            $rights |= READ;
        }
        if (isset($rights_array['w']) && $rights_array['w']) {
            $rights |= CREATE | UPDATE | DELETE;
        }
        if (isset($rights_array['p']) && $rights_array['p']) {
            $rights |= PURGE;
        }
        
        return $rights;
    }
    
    /**
     * Processar dados do formulário de direitos
     */
    static function changeProfile() {
        if (isset($_POST['_linkdowntime'])) {
            $rights = self::getRightValue($_POST['_linkdowntime']);
            
            ProfileRight::updateProfileRights(
                $_POST['id'], 
                ['linkdowntime' => $rights]
            );
        }
    }
}