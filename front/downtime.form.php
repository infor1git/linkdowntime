<?php
/**
 * Formulário de Link Downtime - Corrigido (redirect robusto para ticket + fallback)
 * - Busca tickets_id no GET/POST e, se ausente, tenta pegar do registro no DB antes do redirect.
 * - Usa Toolbox::logInFile() para logging compatível com GLPI 10.
 */

include ('../../../inc/includes.php');

Session::checkRight(PluginLinkdowntimeDowntime::$rightname, READ);

$downtime = new PluginLinkdowntimeDowntime();

$redirect_to_ticket = false;
$ticket_id = 0;

// Detecta se veio de ticket via GET ou POST
if (!empty($_GET['tickets_id'])) {
    $redirect_to_ticket = true;
    $ticket_id = (int)$_GET['tickets_id'];
}
if (!empty($_POST['tickets_id'])) {
    $redirect_to_ticket = true;
    $ticket_id = (int)$_POST['tickets_id'];
}

/**
 * Helper para redirecionar corretamente ao ticket
 */
function redirectToTicketOrUrl($ticket_id, $urlIfNoTicket) {
    global $CFG_GLPI;
    if (!empty($ticket_id) && $ticket_id > 0) {
        $forcetab = 'PluginLinkdowntimeDowntime$1';
        $target = $CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . (int)$ticket_id . "&forcetab=" . $forcetab;
        Html::redirect($target);
    } else {
        Html::redirect($urlIfNoTicket);
    }
    exit;
}

/**
 * ADICIONAR
 */
if (isset($_POST["add"])) {
    $downtime->check(-1, CREATE, $_POST);
    $newID = $downtime->add($_POST);

    if (!$newID) {
        error_log('LinkDowntime add() falhou.');
        // Tenta usar ticket_id do POST/GET (já avaliado acima)
        if ($redirect_to_ticket) {
            redirectToTicketOrUrl($ticket_id, $downtime->getFormURL());
        } else {
            Html::redirect($downtime->getFormURL());
        }
    } else {
        Toolbox::logInFile('linkdowntime', sprintf('%s adicionou o item %s', $_SESSION["glpiname"], $_POST["name"] ?? $newID));

        // Tentativa adicional: se tickets_id não veio no POST, tenta buscar no registro
        if (!$redirect_to_ticket) {
            $downtime->getFromDB($newID);
            if (!empty($downtime->fields['tickets_id'])) {
                $redirect_to_ticket = true;
                $ticket_id = (int)$downtime->fields['tickets_id'];
            }
        }

        if ($redirect_to_ticket) {
            redirectToTicketOrUrl($ticket_id, $downtime->getFormURLWithID($newID));
        } else {
            Html::redirect($downtime->getFormURLWithID($newID));
        }
    }

/**
 * EXCLUIR
 */
} else if (isset($_POST["delete"])) {
    $id = (int)($_POST["id"] ?? 0);
    $downtime->check($id, DELETE);

    if ($id > 0 && $downtime->getFromDB($id)) {

        // Se tickets_id não veio no POST/GET, pega do registro
        if (!$redirect_to_ticket && !empty($downtime->fields['tickets_id'])) {
            $redirect_to_ticket = true;
            $ticket_id = (int)$downtime->fields['tickets_id'];
        }

        $result = $downtime->delete($_POST);

        if ($result) {
            Toolbox::logInFile('linkdowntime', sprintf('%s excluiu o item ID %d', $_SESSION["glpiname"], $id));
            if ($redirect_to_ticket) {
                redirectToTicketOrUrl($ticket_id, $downtime->getSearchURL());
            } else {
                Html::redirect($downtime->getSearchURL());
            }
        } else {
            Html::redirect($downtime->getFormURLWithID($id));
        }
    } else {
        // Mesmo que não exista, se veio de ticket, volta para a aba do ticket
        if ($redirect_to_ticket) {
            redirectToTicketOrUrl($ticket_id, $downtime->getSearchURL());
        } else {
            Html::redirect($downtime->getSearchURL());
        }
    }

/**
 * ATUALIZAR
 */
} else if (isset($_POST["update"])) {
    $id = (int)($_POST["id"] ?? 0);
    $downtime->check($id, UPDATE);

    // Se tickets_id não veio no POST/GET, tenta carregar antes de atualizar
    if ($id > 0 && !$redirect_to_ticket && $downtime->getFromDB($id)) {
        if (!empty($downtime->fields['tickets_id'])) {
            $redirect_to_ticket = true;
            $ticket_id = (int)$downtime->fields['tickets_id'];
        }
    }

    $downtime->update($_POST);

    // Recarrega para pegar eventual tickets_id atualizado
    if ($id > 0 && $downtime->getFromDB($id)) {
        if (!empty($downtime->fields['tickets_id'])) {
            $redirect_to_ticket = true;
            $ticket_id = (int)$downtime->fields['tickets_id'];
        }
    }

    Toolbox::logInFile('linkdowntime', sprintf('%s atualizou o item ID %d', $_SESSION["glpiname"], $id));

    if ($redirect_to_ticket) {
        redirectToTicketOrUrl($ticket_id, $downtime->getFormURLWithID($id));
    } else {
        Html::redirect($downtime->getFormURLWithID($id));
    }

/**
 * RESTAURAR
 */
} else if (isset($_POST["restore"])) {
    $id = (int)($_POST["id"] ?? 0);
    $downtime->check($id, DELETE);

    if ($id > 0 && $downtime->getFromDB($id)) {
        // busca tickets_id do registro, caso não venha no POST/GET
        if (!$redirect_to_ticket && !empty($downtime->fields['tickets_id'])) {
            $redirect_to_ticket = true;
            $ticket_id = (int)$downtime->fields['tickets_id'];
        }
    }

    if ($downtime->restore($_POST)) {
        Toolbox::logInFile('linkdowntime', sprintf('%s restaurou o item ID %d', $_SESSION["glpiname"], $id));
        if ($redirect_to_ticket) {
            redirectToTicketOrUrl($ticket_id, $downtime->getFormURLWithID($id));
        } else {
            Html::redirect($downtime->getFormURLWithID($id));
        }
    } else {
        if ($redirect_to_ticket) {
            redirectToTicketOrUrl($ticket_id, $downtime->getSearchURL());
        } else {
            Html::redirect($downtime->getSearchURL());
        }
    }

/**
 * PURGAR
 */
} else if (isset($_POST["purge"])) {
    $id = (int)($_POST["id"] ?? 0);
    $downtime->check($id, PURGE);

    if ($id > 0 && $downtime->getFromDB($id)) {
        // pega tickets_id se houver
        if (!$redirect_to_ticket && !empty($downtime->fields['tickets_id'])) {
            $redirect_to_ticket = true;
            $ticket_id = (int)$downtime->fields['tickets_id'];
        }

        if ($downtime->delete($_POST, 1)) {
            Toolbox::logInFile('linkdowntime', sprintf('%s purgou o item ID %d', $_SESSION["glpiname"], $id));
            if ($redirect_to_ticket) {
                redirectToTicketOrUrl($ticket_id, $downtime->getSearchURL());
            } else {
                Html::redirect($downtime->getSearchURL());
            }
        } else {
            Html::redirect($downtime->getFormURLWithID($id));
        }
    } else {
        if ($redirect_to_ticket) {
            redirectToTicketOrUrl($ticket_id, $downtime->getSearchURL());
        } else {
            Html::redirect($downtime->getSearchURL());
        }
    }

/**
 * EXIBIÇÃO
 */
} else {
    $ID = (int)($_GET["id"] ?? 0);

    if ($ID == 0) {
        $downtime->getEmpty();
        if (!empty($_GET['tickets_id']) && (int)$_GET['tickets_id'] > 0) {
            $ticket_id = (int)$_GET['tickets_id'];
            $ticket = new Ticket();
            if ($ticket->getFromDB($ticket_id)) {
                $downtime->fields['tickets_id']   = $ticket_id;
                $downtime->fields['locations_id'] = $ticket->fields['locations_id'] ?? null;
                $downtime->fields['entities_id']  = $ticket->fields['entities_id'] ?? null;
            }
        }
    } else {
        $downtime->getFromDB($ID);
    }

    Html::header(__('Link Downtime Manager', 'linkdowntime'), $_SERVER['PHP_SELF'], "tools", "pluginlinkdowntimemenu", "downtime");

    // Passa tickets_id tanto do GET quanto do registro (para o template)
    // Garante que fields exista
    $fields_for_template = $downtime->fields ?? [];
    $fields_for_template['tickets_id'] = $ticket_id ?? 0;

    $downtime->display([
        'id' => $ID,
        'tickets_id' => $ticket_id,
        'fields' => $fields_for_template,
    ]);

    Html::footer();
}
