<?php
/**
 * AJAX para obter informações de um ticket específico
 * Arquivo: ajax/get_ticket.php
 */

include ('../../../../inc/includes.php');

// Verificar se usuário está logado
Session::checkLoginUser();

// Verificar se tem permissão para ver tickets
if (!Session::haveRight("ticket", READ)) {
    http_response_code(403);
    echo json_encode(['error' => 'No permission']);
    exit;
}

header('Content-Type: application/json');

$ticket_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($ticket_id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

global $DB;

try {
    $result = $DB->request([
        'SELECT' => ['id', 'name', 'locations_id'],
        'FROM' => 'glpi_tickets',
        'WHERE' => [
            'id' => $ticket_id,
            'is_deleted' => 0
        ],
        'LIMIT' => 1
    ]);
    
    foreach ($result as $ticket) {
        $title = !empty($ticket['name']) ? $ticket['name'] : 'Sem título';
        if (strlen($title) > 80) {
            $title = substr($title, 0, 77) . '...';
        }
        
        echo json_encode([
            'id' => (int)$ticket['id'],
            'name' => $title,
            'locations_id' => (int)$ticket['locations_id']
        ]);
        exit;
    }
    
    echo json_encode(['error' => 'Ticket not found']);
    
} catch (Exception $e) {
    error_log("Erro ao buscar ticket: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}