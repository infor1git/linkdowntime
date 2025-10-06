<?php
/**
 * AJAX para busca de chamados
 * Arquivo: ajax/search_tickets.php
 */

include ('../../../../inc/includes.php');

// Verificar se usuário está logado
Session::checkLoginUser();

// Verificar se tem permissão para ver tickets
if (!Session::haveRight("ticket", READ)) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json');

$query = isset($_POST['query']) ? trim($_POST['query']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

global $DB;

$tickets = [];

try {
    // Se a busca começar com #, buscar por número
    if (strpos($query, '#') === 0) {
        $ticket_number = (int)substr($query, 1);
        
        $result = $DB->request([
            'SELECT' => ['id', 'name', 'content'],
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                'is_deleted' => 0,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'id' => $ticket_number
            ],
            'LIMIT' => 1
        ]);
        
        foreach ($result as $ticket) {
            $title = !empty($ticket['name']) ? $ticket['name'] : 'Sem título';
            if (strlen($title) > 60) {
                $title = substr($title, 0, 57) . '...';
            }
            
            $tickets[] = [
                'id' => (int)$ticket['id'],
                'name' => $title
            ];
        }
    } else {
        // Buscar por título ou número
        $search_conditions = [];
        
        // Se for número, buscar também por ID
        if (is_numeric($query)) {
            $search_conditions[] = ['id' => (int)$query];
        }
        
        // Buscar por título
        $search_conditions[] = ['name' => ['LIKE', '%' . $query . '%']];
        
        $result = $DB->request([
            'SELECT' => ['id', 'name', 'content'],
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                'is_deleted' => 0,
                'entities_id' => $_SESSION['glpiactive_entity'],
                'OR' => $search_conditions
            ],
            'ORDER' => 'id DESC',
            'LIMIT' => 10
        ]);
        
        foreach ($result as $ticket) {
            $title = !empty($ticket['name']) ? $ticket['name'] : 'Sem título';
            if (strlen($title) > 60) {
                $title = substr($title, 0, 57) . '...';
            }
            
            $tickets[] = [
                'id' => (int)$ticket['id'],
                'name' => $title
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Erro na busca de tickets: " . $e->getMessage());
    echo json_encode([]);
    exit;
}

echo json_encode($tickets);