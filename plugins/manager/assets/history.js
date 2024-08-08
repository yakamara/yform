
$(document).on('rex:ready', function (event, container) {
    container.find('#rex-yform-history-modal').on('shown.bs.modal', function () {
        // init tooltips
        container.find('[data-toggle="tooltip"]').tooltip({
            html: true
        });

        // history restore confirm dialog
        $("#yform-manager-history-restore").on("click", function() {
            return confirm($(this).attr('data-confirm-text'));
        });
    });
});