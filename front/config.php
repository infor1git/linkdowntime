<?php
/**
 * Página de configuração do plugin LinkDowntime
 * Necessária para os direitos aparecerem na seção Tools
 */

include ('../../../inc/includes.php');

Session::checkRight("config", UPDATE);

Html::header(__('Link Downtime Manager Configuration', 'linkdowntime'), $_SERVER['PHP_SELF'], "config", "plugins");

echo "<div class='center'>";
echo "<h2>" . __('Link Downtime Manager - Configuration', 'linkdowntime') . "</h2>";

echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ccc; background: #f9f9f9;'>";
echo "<h3>" . __('Plugin Information', 'linkdowntime') . "</h3>";
echo "<p><strong>" . __('Version') . ":</strong> 1.0.0</p>";
echo "<p><strong>" . __('Author') . ":</strong> INFOR1</p>";
echo "<p><strong>" . __('Description') . ":</strong> " . __('Plugin for managing network link downtime incidents', 'linkdowntime') . "</p>";
echo "</div>";

echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #d1ecf1; background: #d4edda;'>";
echo "<h3>" . __('How to Use', 'linkdowntime') . "</h3>";
echo "<ol>";
echo "<li>" . __('Go to Tools > Link Downtime Manager', 'linkdowntime') . "</li>";
echo "<li>" . __('Click Add to register a new downtime incident', 'linkdowntime') . "</li>";
echo "<li>" . __('Fill in the required information', 'linkdowntime') . "</li>";
echo "<li>" . __('Use the Dashboard to view statistics', 'linkdowntime') . "</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #f5c6cb; background: #f8d7da;'>";
echo "<h3>" . __('Requirements', 'linkdowntime') . "</h3>";
echo "<ul>";
echo "<li>" . __('Tag plugin must be installed for supplier filtering', 'linkdowntime') . "</li>";
echo "<li>" . __('Suppliers must have tag ID = 1 to appear in dropdowns', 'linkdowntime') . "</li>";
echo "<li>" . __('Locations must be configured in GLPI', 'linkdowntime') . "</li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='" . Plugin::getWebDir('linkdowntime') . "/front/downtime.php' class='vsubmit'>";
echo __('Go to Link Downtime Manager', 'linkdowntime');
echo "</a>";
echo "</p>";

echo "</div>";

Html::footer();