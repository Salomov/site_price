/**
 * @file
 * Misc JQuery scripts in this file
 */
(function ($, window, Drupal, drupalSettings) {

    'use strict';

    $(document).ready(function () {
        $('.price__group-title').click(function () {
            $('.price__group-items').hide('slow');
            var id = $(this).data('group');
            $('#price__group-items-' + id).show('slow');
        });
    });

    /**
     * Ajax команда плавной прокрутки страницы к заданному элементу.
     */
    Drupal.AjaxCommands.prototype.sitePriceScrollTo = function (ajax, response, status) {
        var destination = $(response.object).offset();
        $("html:not(:animated),body:not(:animated)").animate({ scrollTop: destination }, response.duration);
    };

})(jQuery, window, Drupal, drupalSettings);
