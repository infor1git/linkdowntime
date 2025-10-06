<?php
/**
 * Classe principal para gerenciamento de downtime
 * VERSÃO CORRIGIDA COM ABA FUNCIONAL E DROPDOWN PERSONALIZADO
 */
use Glpi\Application\View\TemplateRenderer;

class PluginLinkdowntimeDowntime extends CommonDBTM {
    
    static $rightname = 'linkdowntime';
    
    static function getTypeName($nb = 0) {
        return _n('Link Downtime', 'Link Downtimes', $nb, 'linkdowntime');
    }
    
    /**
     * CORREÇÃO: Implementar aba corretamente para Tickets
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item instanceof Ticket) {
            if (PluginLinkdowntimeDowntime::canView()) {
                $nb = countElementsInTable($this->getTable(), [
                    'tickets_id' => $item->getID(),
                    'is_deleted' => 0
                ]);
                return self::createTabEntry(__('Link Downtime', 'linkdowntime'), $nb);
            }
        }
        return '';
    }
    
    /**
     * CORREÇÃO: Exibir conteúdo da aba corretamente
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item instanceof Ticket) {
            self::showForTicket($item, $withtemplate);
            return true;
        }
        return false;
    }

    function display($options = []) {
        // Se tem tickets_id no GET, preencher automaticamente
        if (isset($_GET['tickets_id']) && $_GET['tickets_id'] > 0) {
            $ticket_id = (int)$_GET['tickets_id'];
            $ticket = new Ticket();
            
            if ($ticket->getFromDB($ticket_id)) {
                $this->fields['tickets_id'] = $ticket_id;
                
                // Preencher localização se o ticket tiver
                if (empty($this->fields['locations_id']) && $ticket->fields['locations_id'] > 0) {
                    $this->fields['locations_id'] = $ticket->fields['locations_id'];
                }
                
                // Preencher entidade do ticket
                if (empty($this->fields['entities_id'])) {
                    $this->fields['entities_id'] = $ticket->fields['entities_id'];
                }
            }
        }
        
        $options = array_merge($options, ['tickets_id' => isset($_GET['tickets_id']) ? (int)$_GET['tickets_id'] : 0]);
        
        return $this->showForm($this->getID(), $options);
    }
    
    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }
    
    function showForm($ID, $options = []) {
        global $CFG_GLPI;
        
        $this->initForm($ID, $options);
        
        TemplateRenderer::getInstance()->display('@linkdowntime/downtime_form.html.twig', [
            'item' => $this,
            'params' => $options,
            'no_header' => true,
            'no_inventory_footer' => true,
            'get' => $_GET  // Passar $_GET para o template
        ]);
        
        return true;
    }
    
    /**
     * NOVO: Obter lista de tickets formatada para dropdown
     */
    public static function getTicketsDropdown() {
        global $DB;
        
        $tickets = [];
        
        $result = $DB->request([
            'SELECT' => ['id', 'name', 'content'],
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                'is_deleted' => 0,
                'entities_id' => $_SESSION['glpiactive_entity']
            ],
            'ORDER' => 'id DESC',
            'LIMIT' => 100  // Limitar para não sobrecarregar
        ]);
        
        foreach ($result as $ticket) {
            $title = !empty($ticket['name']) ? $ticket['name'] : 'Sem título';
            // Limitar tamanho do título
            if (strlen($title) > 50) {
                $title = substr($title, 0, 47) . '...';
            }
            $tickets[$ticket['id']] = sprintf('#%05d - %s', $ticket['id'], $title);
        }
        
        return $tickets;
    }
    
    /**
     * Exibir Link Downtimes relacionados a um ticket
     */
    static function showForTicket(Ticket $ticket, $withtemplate = 0) {
        global $DB;
        
        $ticket_id = $ticket->getID();
        $can_create = PluginLinkdowntimeDowntime::canCreate();
        $can_update = PluginLinkdowntimeDowntime::canUpdate();
        $can_delete = PluginLinkdowntimeDowntime::canDelete();
        $can_view = PluginLinkdowntimeDowntime::canView();
        
        if (!$can_view) {
            return;
        }
        
        echo "<div class='spaced'>";
        
        // Cabeçalho da aba
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2'>";
        echo "<i class='fas fa-network-wired me-2'></i>";
        echo __('Link Downtimes associated with this ticket', 'linkdowntime');
        echo "</th>";
        echo "</tr>";
        echo "</table>";
        echo "</div>";
        
        // Botão para adicionar novo
        if ($can_create) {
            echo "<div class='center' style='margin: 15px 0;'>";
            echo "<a href='" . Plugin::getWebDir('linkdowntime') . "/front/downtime.form.php?tickets_id=$ticket_id' class='btn btn-primary'>";
            echo "<i class='fas fa-plus me-1'></i>";
            echo __('Add Link Downtime for this ticket', 'linkdowntime');
            echo "</a>";
            echo "</div>";
        }
        
        // Listar downtimes existentes
        $downtimes = $DB->request([
            'SELECT' => [
                'd.*',
                'l.name as location_name',
                's.name as supplier_name'
            ],
            'FROM' => 'glpi_plugin_linkdowntime_downtimes as d',
            'LEFT JOIN' => [
                'glpi_locations as l' => [
                    'ON' => ['d' => 'locations_id', 'l' => 'id']
                ],
                'glpi_suppliers as s' => [
                    'ON' => ['d' => 'suppliers_id', 's' => 'id']
                ]
            ],
            'WHERE' => [
                'd.tickets_id' => $ticket_id,
                'd.is_deleted' => 0
            ],
            'ORDER' => 'd.start_datetime DESC'
        ]);
        
        if (count($downtimes) > 0) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<th>" . __('Name') . "</th>";
            echo "<th>" . __('Location') . "</th>";
            echo "<th>" . __('Supplier') . "</th>";
            echo "<th>" . __('Start Date/Time', 'linkdowntime') . "</th>";
            echo "<th>" . __('End Date/Time', 'linkdowntime') . "</th>";
            echo "<th>" . __('Duration', 'linkdowntime') . "</th>";
            
            if ($can_update || $can_delete) {
                echo "<th>" . __('Actions') . "</th>";
            }
            echo "</tr>";
            
            foreach ($downtimes as $downtime) {
                $duration = '';
                $duration_class = 'text-success';
                
                if (!empty($downtime['start_datetime']) && !empty($downtime['end_datetime'])) {
                    $start = strtotime($downtime['start_datetime']);
                    $end = strtotime($downtime['end_datetime']);
                    $minutes = round(($end - $start) / 60);
                    
                    if ($minutes > 0) {
                        $hours = floor($minutes / 60);
                        $mins = $minutes % 60;
                        $duration = $hours . "h " . $mins . "m";
                        $duration_class = $minutes > 60 ? 'text-danger' : 'text-warning';
                    }
                } else if (!empty($downtime['start_datetime'])) {
                    $duration = '<em>' . __('Ongoing', 'linkdowntime') . '</em>';
                    $duration_class = 'text-warning';
                }
                
                echo "<tr class='tab_bg_2'>";
                echo "<td>";
                if ($can_update) {
                    echo "<a href='" . Plugin::getWebDir('linkdowntime') . "/front/downtime.form.php?id=" . $downtime['id'] . "'>";
                    echo "<strong>" . $downtime['name'] . "</strong>";
                    echo "</a>";
                } else {
                    echo "<strong>" . $downtime['name'] . "</strong>";
                }
                echo "</td>";
                echo "<td>" . ($downtime['location_name'] ?: '-') . "</td>";
                echo "<td>" . ($downtime['supplier_name'] ?: '-') . "</td>";
                echo "<td><small>" . Html::convDateTime($downtime['start_datetime']) . "</small></td>";
                echo "<td><small>" . ($downtime['end_datetime'] ? Html::convDateTime($downtime['end_datetime']) : '<em>' . __('Not finished', 'linkdowntime') . '</em>') . "</small></td>";
                echo "<td><span class='$duration_class'><strong>$duration</strong></span></td>";
                
                if ($can_update || $can_delete) {
                    echo "<td class='text-center'>";
                    if ($can_update) {
                        echo "<a href='" . Plugin::getWebDir('linkdowntime') . "/front/downtime.form.php?id=" . $downtime['id'] . "' ";
                        echo "title='" . __('Edit') . "' class='btn btn-sm btn-outline-primary me-1'>";
                        echo "<i class='fas fa-edit'></i>";
                        echo "</a>";
                    }
                    
                    if ($can_delete) {
                        echo "<form method='post' action='" . Plugin::getWebDir('linkdowntime') . "/front/downtime.form.php' style='display: inline;'>";
                        echo "<input type='hidden' name='id' value='" . $downtime['id'] . "'>";
                        echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
                        echo "<button type='submit' name='delete' value='1' class='btn btn-sm btn-outline-danger' ";
                        echo "onclick='return confirm(\"" . __('Confirm the final deletion?') . "\")' ";
                        echo "title='" . __('Delete permanently') . "'>";
                        echo "<i class='fas fa-trash'></i>";
                        echo "</button>";
                        echo "</form>";
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='center'>";
            echo "<div class='alert alert-info'>";
            echo "<i class='fas fa-info-circle me-2'></i>";
            echo __('No link downtime associated with this ticket', 'linkdowntime');
            echo "</div>";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    /**
     * Preparar dados para adição com validações e nome automático
     */
    function prepareInputForAdd($input) {
        // Debug dos dados recebidos
        error_log("prepareInputForAdd recebeu: " . print_r($input, true));
        
        // Validar campos obrigatórios
        if (empty($input['locations_id']) || $input['locations_id'] == 0) {
            Session::addMessageAfterRedirect(__('Location is required', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        if (empty($input['suppliers_id']) || $input['suppliers_id'] == 0) {
            Session::addMessageAfterRedirect(__('Supplier is required', 'linkdowntime'), false, ERROR);
            return false;
        }

        // Normalizar data/hora recebidas para 'Y-m-d H:i:s' quando possível
        $datetime_fields = ['start_datetime', 'end_datetime', 'communication_datetime'];
        foreach ($datetime_fields as $df) {
            if (isset($input[$df]) && !empty($input[$df])) {
                // Tentar interpretar com strtotime (suporta vários formatos, inclusive sem segundos)
                $ts = strtotime($input[$df]);
                if ($ts === false) {
                    // mantém a mensagem de erro original
                    Session::addMessageAfterRedirect(__('Invalid ' . ucfirst(str_replace('_',' ',$df)) . ' format', 'linkdowntime'), false, ERROR);
                    return false;
                } else {
                    // Normaliza para o formato exigido pelo banco/validacao interna
                    $input[$df] = date('Y-m-d H:i:s', $ts);
                }
            } else {
                // Se campo existir e for vazio, garantir que esteja vazio (para não quebrar comparações)
                $input[$df] = null;
            }
        }

        if (empty($input['start_datetime'])) {
            Session::addMessageAfterRedirect(__('Start Date/Time is required', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        // Validar formato da data
        if (!$this->isValidDateTime($input['start_datetime'])) {
            Session::addMessageAfterRedirect(__('Invalid Start Date/Time format', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        // Validar se data de fim é posterior à de início (se informada)
        if (!empty($input['end_datetime'])) {
            if (!$this->isValidDateTime($input['end_datetime'])) {
                Session::addMessageAfterRedirect(__('Invalid End Date/Time format', 'linkdowntime'), false, ERROR);
                return false;
            }
            
            if (strtotime($input['end_datetime']) <= strtotime($input['start_datetime'])) {
                Session::addMessageAfterRedirect(__('End Date/Time must be after Start Date/Time', 'linkdowntime'), false, ERROR);
                return false;
            }
        }
        
        // Validar data de comunicação (se informada)
        if (!empty($input['communication_datetime'])) {
            if (!$this->isValidDateTime($input['communication_datetime'])) {
                Session::addMessageAfterRedirect(__('Invalid Communication Date/Time format', 'linkdowntime'), false, ERROR);
                return false;
            }
        }
        
        // Processar tickets_id
        $input['tickets_id'] = isset($input['tickets_id']) ? (int)$input['tickets_id'] : 0;
        
        // Gerar nome automático: Localização + Data/Hora Down
        $input['name'] = $this->generateAutomaticName($input['locations_id'], $input['start_datetime']);
        
        // Definir campos padrão
        $input['date_creation'] = $_SESSION['glpi_currenttime'];
        $input['users_id'] = Session::getLoginUserID();
        
        // Garantir que a entidade está definida
        if (!isset($input['entities_id']) || empty($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        
        // Debug do resultado final
        error_log("prepareInputForAdd retornando: " . print_r($input, true));

        if (!isset($input['is_recursive']) || $input['is_recursive'] === '') {
        $input['is_recursive'] = 0;
        }

        return $input;
    }
    
    /**
     * Preparar dados para atualização com validações
     */
    function prepareInputForUpdate($input) {
        // Debug dos dados recebidos
        error_log("prepareInputForUpdate recebeu: " . print_r($input, true));
        
        // Aplicar mesmas validações da adição (se os campos foram alterados)
        if (isset($input['locations_id']) && (empty($input['locations_id']) || $input['locations_id'] == 0)) {
            Session::addMessageAfterRedirect(__('Location is required', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        if (isset($input['suppliers_id']) && (empty($input['suppliers_id']) || $input['suppliers_id'] == 0)) {
            Session::addMessageAfterRedirect(__('Supplier is required', 'linkdowntime'), false, ERROR);
            return false;
        }

        // Normalizar data/hora recebidas para 'Y-m-d H:i:s' quando possível
        $datetime_fields = ['start_datetime', 'end_datetime', 'communication_datetime'];
        foreach ($datetime_fields as $df) {
            if (isset($input[$df]) && !empty($input[$df])) {
                // Tentar interpretar com strtotime (suporta vários formatos, inclusive sem segundos)
                $ts = strtotime($input[$df]);
                if ($ts === false) {
                    // mantém a mensagem de erro original
                    Session::addMessageAfterRedirect(__('Invalid ' . ucfirst(str_replace('_',' ',$df)) . ' format', 'linkdowntime'), false, ERROR);
                    return false;
                } else {
                    // Normaliza para o formato exigido pelo banco/validacao interna
                    $input[$df] = date('Y-m-d H:i:s', $ts);
                }
            } else {
                // Se campo existir e for vazio, garantir que esteja vazio (para não quebrar comparações)
                $input[$df] = null;
            }
        }
        
        if (isset($input['start_datetime']) && empty($input['start_datetime'])) {
            Session::addMessageAfterRedirect(__('Start Date/Time is required', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        // Validar formatos de data se foram alterados
        if (isset($input['start_datetime']) && !$this->isValidDateTime($input['start_datetime'])) {
            Session::addMessageAfterRedirect(__('Invalid Start Date/Time format', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        if (isset($input['end_datetime']) && !empty($input['end_datetime']) && !$this->isValidDateTime($input['end_datetime'])) {
            Session::addMessageAfterRedirect(__('Invalid End Date/Time format', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        if (isset($input['communication_datetime']) && !empty($input['communication_datetime']) && !$this->isValidDateTime($input['communication_datetime'])) {
            Session::addMessageAfterRedirect(__('Invalid Communication Date/Time format', 'linkdowntime'), false, ERROR);
            return false;
        }
        
        // Validar datas se ambas estão sendo alteradas
        if (isset($input['start_datetime']) && isset($input['end_datetime']) && !empty($input['end_datetime'])) {
            if (strtotime($input['end_datetime']) <= strtotime($input['start_datetime'])) {
                Session::addMessageAfterRedirect(__('End Date/Time must be after Start Date/Time', 'linkdowntime'), false, ERROR);
                return false;
            }
        }
        
        // Regenerar nome se localização ou data de início foram alterados
        if (isset($input['locations_id']) || isset($input['start_datetime'])) {
            $current = $this->fields;
            $locations_id = isset($input['locations_id']) ? $input['locations_id'] : $current['locations_id'];
            $start_datetime = isset($input['start_datetime']) ? $input['start_datetime'] : $current['start_datetime'];
            
            $input['name'] = $this->generateAutomaticName($locations_id, $start_datetime);
        }
        
        // Processar tickets_id se fornecido
        if (isset($input['tickets_id'])) {
            $input['tickets_id'] = (int)$input['tickets_id'];
        }
        
        $input['date_mod'] = $_SESSION['glpi_currenttime'];
        
        // Debug do resultado final
        error_log("prepareInputForUpdate retornando: " . print_r($input, true));

        if (!isset($input['is_recursive']) || $input['is_recursive'] === '') {
        $input['is_recursive'] = 0;
        }
        
        return $input;
    }
    
    /**
     * Gerar nome automático: "Localização - DD/MM/YYYY HH:MM"
     */
    private function generateAutomaticName($locations_id, $start_datetime) {
        $location_name = "Localização não informada";
        
        if ($locations_id > 0) {
            $location = new Location();
            if ($location->getFromDB($locations_id)) {
                $location_name = $location->fields['name'];
            }
        }
        
        $formatted_date = "Data não informada";
        if (!empty($start_datetime)) {
            $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $start_datetime);
            if ($date_obj) {
                $formatted_date = $date_obj->format('d/m/Y H:i');
            }
        }
        
        return $location_name . " - " . $formatted_date;
    }
    
    /**
     * Validar formato de data/hora
     */
    function isValidDateTime($datetime) {
        // Aceitar timestamps do formato 'Y-m-d H:i:s' ou 'Y-m-d H:i'
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i'];
        foreach ($formats as $fmt) {
            $d = DateTime::createFromFormat($fmt, $datetime);
            if ($d && $d->format($fmt) === $datetime) {
                return true;
            }
        }
        // Tentar parse genérico
        return (strtotime($datetime) !== false);
    }

    
    // Métodos de permissão (mantidos iguais)
    static function canCreate() {
        return Session::haveRight(static::$rightname, CREATE);
    }
    
    static function canView() {
        return Session::haveRight(static::$rightname, READ);
    }
    
    static function canUpdate() {
        return Session::haveRight(static::$rightname, UPDATE);
    }
    
    static function canDelete() {
        return Session::haveRight(static::$rightname, DELETE);
    }
    
    static function canPurge() {
        return Session::haveRight(static::$rightname, PURGE);
    }
    
    function getSearchOptionsNew() {
        $tab = [];
        
        $tab[] = [
            'id'   => 'common',
            'name' => __('Characteristics')
        ];
        
        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];
        
        $tab[] = [
            'id'            => '2',
            'table'         => 'glpi_locations',
            'field'         => 'name',
            'name'          => __('Location'),
            'datatype'      => 'dropdown'
        ];
        
        $tab[] = [
            'id'            => '3',
            'table'         => 'glpi_suppliers',
            'field'         => 'name',
            'name'          => __('Supplier'),
            'datatype'      => 'dropdown'
        ];
        
        $tab[] = [
            'id'            => '8',
            'table'         => 'glpi_tickets',
            'field'         => 'name',
            'name'          => __('Ticket'),
            'datatype'      => 'dropdown'
        ];
        
        $tab[] = [
            'id'            => '4',
            'table'         => $this->getTable(),
            'field'         => 'start_datetime',
            'name'          => __('Start Date/Time', 'linkdowntime'),
            'datatype'      => 'datetime'
        ];
        
        $tab[] = [
            'id'            => '5',
            'table'         => $this->getTable(),
            'field'         => 'end_datetime',
            'name'          => __('End Date/Time', 'linkdowntime'),
            'datatype'      => 'datetime'
        ];
        
        $tab[] = [
            'id'            => '6',
            'table'         => $this->getTable(),
            'field'         => 'communication_datetime',
            'name'          => __('Communication Date/Time', 'linkdowntime'),
            'datatype'      => 'datetime'
        ];
        
        $tab[] = [
            'id'            => '7',
            'table'         => $this->getTable(),
            'field'         => 'observation',
            'name'          => __('Observation', 'linkdowntime'),
            'datatype'      => 'text'
        ];
        
        return $tab;
    }
    
    // Métodos de estatísticas mantidos (não alterados)
    public static function getDowntimeStatsByLocation($year = null) {
        global $DB;
        
        if ($year === null) {
            $year = date('Y');
        }
        
        $query = "SELECT 
                    l.id as location_id,
                    l.name as location_name,
                    COUNT(d.id) as total_incidents,
                    SUM(TIMESTAMPDIFF(MINUTE, d.start_datetime, d.end_datetime)) as total_downtime_minutes
                  FROM glpi_locations l
                  LEFT JOIN glpi_plugin_linkdowntime_downtimes d ON l.id = d.locations_id 
                    AND YEAR(d.start_datetime) = '$year'
                    AND d.is_deleted = 0
                    AND d.start_datetime IS NOT NULL 
                    AND d.end_datetime IS NOT NULL
                  WHERE l.is_deleted = 0
                  GROUP BY l.id, l.name
                  ORDER BY l.name";
        
        $result = $DB->query($query);
        $stats = [];
        
        while ($row = $DB->fetchAssoc($result)) {
            $downtime_minutes = (int)$row['total_downtime_minutes'];
            $downtime_hours = round($downtime_minutes / 60, 2);
            
            $total_hours_year = 8760;
            $downtime_percentage = $downtime_minutes > 0 ? round(($downtime_hours / $total_hours_year) * 100, 4) : 0;
            
            $stats[] = [
                'location_id' => $row['location_id'],
                'location_name' => $row['location_name'],
                'total_incidents' => (int)$row['total_incidents'],
                'downtime_minutes' => $downtime_minutes,
                'downtime_hours' => $downtime_hours,
                'downtime_percentage' => $downtime_percentage,
                'uptime_percentage' => round(100 - $downtime_percentage, 4)
            ];
        }
        
        return $stats;
    }
    
    public static function getGlobalDowntimeStats($year = null) {
        global $DB;
        
        if ($year === null) {
            $year = date('Y');
        }
        
        $query = "SELECT 
                    COUNT(d.id) as total_incidents,
                    SUM(TIMESTAMPDIFF(MINUTE, d.start_datetime, d.end_datetime)) as total_downtime_minutes,
                    COUNT(DISTINCT d.locations_id) as affected_locations
                  FROM glpi_plugin_linkdowntime_downtimes d
                  WHERE YEAR(d.start_datetime) = '$year'
                    AND d.is_deleted = 0
                    AND d.start_datetime IS NOT NULL 
                    AND d.end_datetime IS NOT NULL";
        
        $result = $DB->query($query);
        $row = $DB->fetchAssoc($result);
        
        $downtime_minutes = (int)$row['total_downtime_minutes'];
        $downtime_hours = round($downtime_minutes / 60, 2);
        
        $locations_query = "SELECT COUNT(*) as total_locations FROM glpi_locations WHERE is_deleted = 0";
        $locations_result = $DB->query($locations_query);
        $locations_row = $DB->fetchAssoc($locations_result);
        $total_locations = (int)$locations_row['total_locations'];
        
        $total_hours_year = 8760 * $total_locations;
        $downtime_percentage = $downtime_minutes > 0 ? round(($downtime_hours / ($total_hours_year > 0 ? $total_hours_year : 8760)) * 100, 4) : 0;
        
        return [
            'total_incidents' => (int)$row['total_incidents'],
            'affected_locations' => (int)$row['affected_locations'],
            'total_locations' => $total_locations,
            'downtime_minutes' => $downtime_minutes,
            'downtime_hours' => $downtime_hours,
            'downtime_percentage' => $downtime_percentage,
            'uptime_percentage' => round(100 - $downtime_percentage, 4)
        ];
    }
    
    public static function getSuppliersWithTag() {
        global $DB;
        
        $query = "SELECT DISTINCT s.id, s.name 
                  FROM glpi_suppliers s
                  INNER JOIN glpi_plugin_tag_tagitems t ON s.id = t.items_id 
                  WHERE t.itemtype = 'Supplier' 
                    AND t.plugin_tag_tags_id = 1
                    AND s.is_deleted = 0
                  ORDER BY s.name";
        
        $result = $DB->query($query);
        $suppliers = [];
        
        while ($row = $DB->fetchAssoc($result)) {
            $suppliers[$row['id']] = $row['name'];
        }
        
        return $suppliers;
    }
}