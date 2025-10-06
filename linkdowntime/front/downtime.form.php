<?php
/**
 * Formulário de Link Downtime - CORRIGIDO
 * Usa Html::redirect() para evitar tela branca
 */

include ('../../../inc/includes.php');

Session::checkRight(PluginLinkdowntimeDowntime::$rightname, READ);

$downtime = new PluginLinkdowntimeDowntime();

$redirect_to_ticket = false;
$ticket_id = 0;

// Detecta se veio de ticket via GET
if (isset($_GET['tickets_id']) && (int)$_GET['tickets_id'] > 0) {
    $redirect_to_ticket = true;
    $ticket_id = (int)$_GET['tickets_id'];
}

// Detecta se veio de ticket via POST
if (isset($_POST['tickets_id']) && (int)$_POST['tickets_id'] > 0) {
    $redirect_to_ticket = true;
    $ticket_id = (int)$_POST['tickets_id'];
}

/**
 * Helper para redirecionar
 */
function redirectToTicketOrUrl($ticket_id, $urlIfNoTicket) {
    global $CFG_GLPI;
    if (!empty($ticket_id) && $ticket_id > 0) {
        $forcetab = "PluginLinkdowntimeDowntime";
        $target = $CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $ticket_id . "&forcetab=" . $forcetab;
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
        if ($redirect_to_ticket && $ticket_id > 0) {
            Html::redirect($downtime->getFormURL() . "?tickets_id=" . $ticket_id);
        } else {
            Html::redirect($downtime->getFormURL());
        }
    } else {
        Event::log($newID, "linkdowntime", 4, "tools",
                  sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"] ?? $newID));

        if ($redirect_to_ticket && $ticket_id > 0) {
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
        $result = $downtime->delete($_POST);

        if ($result) {
            Event::log($id, "linkdowntime", 4, "tools",
                      sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));

            if (!empty($_POST['_from_ticket']) || $ticket_id > 0) {
                redirectToTicketOrUrl($ticket_id, $downtime->getSearchURL());
            } else {
                Html::redirect($downtime->getSearchURL());
            }
        } else {
            Html::redirect($downtime->getFormURLWithID($id));
        }
    } else {
        Html::redirect($downtime->getSearchURL());
    }

/**
 * RESTAURAR
 */
} else if (isset($_POST["restore"])) {
    $id = (int)($_POST["id"] ?? 0);
    $downtime->check($id, DELETE);
    if ($downtime->restore($_POST)) {
        Html::redirect($downtime->getFormURLWithID($id));
    } else {
        Html::redirect($downtime->getSearchURL());
    }

/**
 * PURGAR
 */
} else if (isset($_POST["purge"])) {
    $id = (int)($_POST["id"] ?? 0);
    $downtime->check($id, PURGE);

    if ($id > 0 && $downtime->getFromDB($id)) {
        if ($downtime->delete($_POST, 1)) {
            if (!empty($_POST['_from_ticket']) || $ticket_id > 0) {
                redirectToTicketOrUrl($ticket_id, $downtime->getSearchURL());
            } else {
                Html::redirect($downtime->getSearchURL());
            }
        } else {
            Html::redirect($downtime->getFormURLWithID($id));
        }
    } else {
        Html::redirect($downtime->getSearchURL());
    }

/**
 * ATUALIZAR
 */
} else if (isset($_POST["update"])) {
    $id = (int)($_POST["id"] ?? 0);
    $downtime->check($id, UPDATE);

    $downtime->update($_POST);

    if (!empty($_POST['_from_ticket']) || $ticket_id > 0) {
        redirectToTicketOrUrl($ticket_id, $downtime->getFormURLWithID($id));
    } else {
        Html::redirect($downtime->getFormURLWithID($id));
    }

/**
 * EXIBIÇÃO
 */
} else {
    $ID = (int)($_GET["id"] ?? 0);

    if ($ID == 0) {
        $downtime->getEmpty();
        if (isset($_GET['tickets_id']) && (int)$_GET['tickets_id'] > 0) {
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

    $downtime->display([
        'id'         => $ID,
        'tickets_id' => (int)($_GET['tickets_id'] ?? 0)
    ]);

    Html::footer();
}
