(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.material_sbs_MDCFunctions = {
    attach: function(context, settings) {

      // Using MDC auto init feature.
      // See https://material.io/develop/web/components/auto-init/
      window.mdc.autoInit();


      // Handling MDC Drawer.
      $(context).find('.drawer-open__button').click(function(e) {
        e.preventDefault();
        $('.mdc-drawer')[0].MDCDrawer.open = true;
      });

      $(context).find('.drawer-close__button').click(function(e) {
        e.preventDefault();
        $('.mdc-drawer')[0].MDCDrawer.open = false;
      });

      $(context).find('.drawer-toggle__button').click(function(e) {
        e.preventDefault();
        var drawer = $('.mdc-drawer')[0].MDCDrawer;
        drawer.open = !drawer.open;
      });

    }
  };
}) (jQuery, Drupal);
