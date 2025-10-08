<?php
include ('../../../inc/includes.php');

Session::checkRight(PluginLinkdowntimeDowntime::$rightname, READ);

Html::header(__('Link Downtime Dashboard', 'linkdowntime'), $_SERVER['PHP_SELF'], "tools", "pluginlinkdowntimemenu", "dashboard");

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

echo "<div class='center'>";
echo "<h2>" . sprintf(__('Downtime Statistics for %d', 'linkdowntime'), $year) . "</h2>";

// Formulário para seleção de ano
echo "<form method='get' class='center'>";
echo "<label for='year'>" . __('Year:', 'linkdowntime') . " </label>";
echo "<select name='year' id='year'>";
for ($i = date('Y'); $i >= date('Y') - 5; $i--) {
    $selected = ($i == $year) ? 'selected' : '';
    echo "<option value='$i' $selected>$i</option>";
}
echo "</select>";
echo "<input type='submit' value='" . __('Filter', 'linkdowntime') . "' class='submit'>";
echo "</form>";

echo "<br>";

// Estatísticas globais
$global_stats = PluginLinkdowntimeDowntime::getGlobalDowntimeStats($year);
echo "<div class='card'>";
echo "<h3>" . __('Global Statistics', 'linkdowntime') . "</h3>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_1'>";
echo "<th class='center'>" . __('Total Incidents', 'linkdowntime') . "</th>";
echo "<th class='center'>" . __('Affected Locations', 'linkdowntime') . "</th>";
echo "<th class='center'>" . __('Total Downtime (hours)', 'linkdowntime') . "</th>";
echo "<th class='center'>" . __('Downtime %', 'linkdowntime') . "</th>";
echo "<th class='center'>" . __('Uptime %', 'linkdowntime') . "</th>";
echo "</tr>";
echo "<tr class='tab_bg_2'>";
echo "<td class='center'>" . $global_stats['total_incidents'] . "</td>";
echo "<td class='center'>" . $global_stats['affected_locations'] . "/" . $global_stats['total_locations'] . "</td>";
echo "<td class='center'>" . number_format($global_stats['downtime_hours'], 2) . "</td>";
echo "<td class='center' style='color: red;'>" . $global_stats['downtime_percentage'] . "%</td>";
echo "<td class='center' style='color: green;'>" . $global_stats['uptime_percentage'] . "%</td>";
echo "</tr>";
echo "</table>";
echo "</div>";

echo "<br>";

// Estatísticas por localização
$location_stats = PluginLinkdowntimeDowntime::getDowntimeStatsByLocation($year);

echo "<div class='card'>";
echo "<h3>" . __('Statistics by Location', 'linkdowntime') . "</h3>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_1'>";
echo "<th>" . __('Location') . "</th>";
echo "<th class='center'>" . __('Incidents', 'linkdowntime') . "</th>";
echo "<th class='center'>" . __('Downtime (hours)', 'linkdowntime') . "</th>";
echo "<th class='center'>" . __('Downtime %', 'linkdowntime') . "</th>";
echo "<th class='center'>" . __('Uptime %', 'linkdowntime') . "</th>";
echo "</tr>";

foreach ($location_stats as $stat) {
    echo "<tr class='tab_bg_2'>";
    echo "<td>" . $stat['location_name'] . "</td>";
    echo "<td class='center'>" . $stat['total_incidents'] . "</td>";
    echo "<td class='center'>" . number_format($stat['downtime_hours'], 2) . "</td>";
    echo "<td class='center' style='color: red;'>" . $stat['downtime_percentage'] . "%</td>";
    echo "<td class='center' style='color: green;'>" . $stat['uptime_percentage'] . "%</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "</div>";

Html::footer();
