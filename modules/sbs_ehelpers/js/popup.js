(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.ehelperRequestPopup = {
    attach: function(context, settings) {

      // Request the backend if the popup should be displayed.
      $.ajax(drupalSettings.sbsEhelpers.displayPopupPath).done(function (response){
        if (response.data) {
          document.querySelector('body').classList.add('overlay-open');
        }
      });

      // Popup dismissed.
      document.querySelector('.overlay__content .cancel').addEventListener('click', () => {
        // Notify the backend.
        $.ajax(drupalSettings.sbsEhelpers.cancelPath);

        // Hide the popup in the bottom before the overlay
        // disappears so it can be animated with a transition.
        $('.overlay__container').css('bottom', '-100vh');
        // Give time to the animation and hide the whole overlay.
        setTimeout(function() {
          $('body').removeClass('overlay-open');
        }, 300);

      });
    }
  };

}) (jQuery, Drupal, drupalSettings);
