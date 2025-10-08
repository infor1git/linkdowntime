<?php

class PluginLinkdowntimeProfile extends CommonDBTM {

    static public $rightname = 'linkdowntime';

    static function getTypeName($nb = 0) {
        return __('Link Downtime', 'linkdowntime');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item instanceof Profile && $item->getField('id')) {
            return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item instanceof Profile && $item->getField('id')) {
            $profile = new PluginLinkdowntimeProfile();
            // CORRETO: use $item->fields['id'] ou $item->getField('id')
            return $profile->showForProfile($item->fields['id']);
        }
        return true;
    }


    static function getAllRights($all = false) {
        return [
            [
                'itemtype' => 'PluginLinkdowntimeDowntime',
                'label'    => __('Link Downtime', 'linkdowntime'),
                'field'    => 'linkdowntime'
            ]
        ];
    }

    function showForProfile($profiles_id = 0) {
        global $DB;
        $profile = new Profile();
        if (!$profile->getFromDB($profiles_id)) {
            return false;
        }

        $current = 0;
        $iterator = $DB->request([
            'FROM'  => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $profiles_id,
                'name'        => self::$rightname
            ],
            'LIMIT' => 1
        ]);
        foreach ($iterator as $row) {
            $current = (int)$row['rights'];
            break;
        }

        echo "<form method='post' action='" . Profile::getFormURL() . "'>";
        echo "<input type='hidden' name='id' value='$profiles_id'>";

        echo '<table class="tab_cadre_fixehov">';
        echo '<tr><th colspan="5" class="center">Direitos do Link Downtime</th></tr>';
       echo '<tr>
            <th class="center">Ação</th>
            <th class="center">Ler</th>
            <th class="center">Criar</th>
            <th class="center">Atualizar</th>
            <th class="center">Apagar</th>
            <th class="center">Excluir definitivo</th>
        </tr>';
        echo '<tr class="tab_bg_2">';
        echo '<td class="center">Link Downtime</td>';
        echo '<td class="center"><input type="checkbox" name="plugin_linkdowntime_rights[r]" value="1" ' . (($current & READ) ? "checked" : "") . '></td>';
        echo '<td class="center"><input type="checkbox" name="plugin_linkdowntime_rights[c]" value="1" ' . (($current & CREATE) ? "checked" : "") . '></td>';
        echo '<td class="center"><input type="checkbox" name="plugin_linkdowntime_rights[u]" value="1" ' . (($current & UPDATE) ? "checked" : "") . '></td>';
        echo '<td class="center"><input type="checkbox" name="plugin_linkdowntime_rights[x]" value="1" ' . (($current & DELETE) ? "checked" : "") . '></td>';
        echo '<td class="center"><input type="checkbox" name="plugin_linkdowntime_rights[d]" value="1" ' . (($current & PURGE) ? "checked" : "") . '></td>';
        echo '</tr>';
        echo '<tr><td colspan="5" class="center"><input type="submit" name="update" value="Salvar" class="btn btn-primary"></td></tr>';
        echo '</table>';
        Html::closeForm();

        return true;
    }

    static function getRightValue($rights_array) {
        $value = 0;
        if (isset($rights_array['r']) && $rights_array['r'] == 1) {
            $value += READ;
        }
        if (isset($rights_array['w']) && $rights_array['w'] == 1) {
            $value += CREATE + UPDATE;
        }
        if (isset($rights_array['d']) && $rights_array['d'] == 1) {
            $value += PURGE;
        }
        return $value;
    }

    static function changeProfile() {
        if (isset($_POST['id']) && isset($_POST['plugin_linkdowntime_rights'])) {
            $profile_id = intval($_POST['id']);
            $dados = $_POST['plugin_linkdowntime_rights'];
            $rights = 0;
            if (!empty($dados['r'])) $rights |= READ;
            if (!empty($dados['c'])) $rights |= CREATE;
            if (!empty($dados['u'])) $rights |= UPDATE;
            if (!empty($dados['x'])) $rights |= DELETE;
            if (!empty($dados['d'])) $rights |= PURGE;

            unset($_POST['plugin_linkdowntime_rights']);
            ProfileRight::updateProfileRights($profile_id, [self::$rightname => $rights]);
        }
    }

}
