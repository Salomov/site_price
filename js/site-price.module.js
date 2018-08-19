/**
 * @file
 * Misc JQuery scripts in this file
 */
(function($, window, Drupal, drupalSettings) {

    'use strict';

    /**
     * Ajax команда плавной прокрутки страницы к заданному элементу.
     */
    Drupal.AjaxCommands.prototype.sitePriceScrollTo = function(ajax, response, status) {
        var destination = $(response.object).offset();
        $("html:not(:animated),body:not(:animated)").animate({ scrollTop: destination }, response.duration);
    };

})(jQuery, window, Drupal, drupalSettings);