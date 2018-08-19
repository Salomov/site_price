/**
 * @file
 * Misc JQuery scripts in this file
 */
(function($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.sortableAjaxFunctions = {
        attach: function(context, settings) {
            $(".price__group-positions").sortable({
                placeholder: "ui-state-highlight",
                update: function(event, ui) {
                    var gid = parseInt($("#" + event.target.id).attr("group-id"));
                    var sorted = $("#" + event.target.id).sortable("serialize", { key: "pid" });

                    // Выполняет запрос ajax.
                    var ajaxObject = Drupal.ajax({
                        url: '/admin/config/kvantstudio/price/group-positions-set-weight/' + gid + '/&' + sorted + '/nojs',
                        base: false,
                        element: false,
                        progress: false
                    });
                    ajaxObject.execute();
                }
            });
            $(".price__group-positions").disableSelection();

            $(".price__category-positions").sortable({
                placeholder: "ui-state-highlight",
                update: function(event, ui) {
                    var cid = parseInt($("#" + event.target.id).attr("category-id"));
                    var sorted = $("#" + event.target.id).sortable("serialize", { key: "pid" });

                    // Выполняет запрос ajax.
                    var ajaxObject = Drupal.ajax({
                        url: '/admin/config/kvantstudio/price/category-positions-set-weight/' + cid + '/&' + sorted + '/nojs',
                        base: false,
                        element: false,
                        progress: false
                    });
                    ajaxObject.execute();
                }
            });
            $(".price__category-positions").disableSelection();
        }
    };

})(jQuery, Drupal, drupalSettings);