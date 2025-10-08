$(document).ready(function() {
    // Adicionar aba Link Downtime na página de perfil
    if ($('body').hasClass('itemtype-Profile') && $('#tabspanel').length > 0) {
        // Adicionar aba na lista de abas
        $('#tabspanel .nav-tabs').append('<li class="nav-item"><a class="nav-link" href="#linkdowntime-profile" data-toggle="tab">' + __('Link Downtime', 'linkdowntime') + '</a></li>');
        
        // Adicionar conteúdo da aba
        $('#tabspanel .tab-content').append('<div class="tab-pane" id="linkdowntime-profile"></div>');
        
        // Carregar conteúdo via AJAX quando a aba for clicada
        $('a[href="#linkdowntime-profile"]').on('shown.bs.tab', function(e) {
            if ($('#linkdowntime-profile').is(':empty')) {
                var profile_id = $('input[name="id"]').val();
                $('#linkdowntime-profile').load(CFG_GLPI.root_doc + '/plugins/linkdowntime/ajax/profile.php', {
                    profile_id: profile_id
                });
            }
        });
    }
});
