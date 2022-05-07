// **************
// Theme building
// **************

// Helper function for handling assetts in Webpack
function requireAll(r) {
  r.keys().forEach(r);
}

// Handling images for SVG sprite in Webpack
requireAll(require.context('../icons/', true, /\.svg$/));

// Handling images for optimization in Webpack
requireAll(require.context('../images/', true, /\.(png|jpg|jpeg|webp|svg)$/));

// **************
// Theme fuctions
// **************

// Theme fuctions with support of jQuery
(function ($) {
  $(document).ready(function () {

    // Place your code here

  });
}(jQuery));

// Theme fuctions with support of Drupal settings, ajax loading and jQuery
(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.material_sbs_Functions = {
    attach: function(context, settings) {

    // Handling overlay switcher visibility.
    $('.overlay-switcher-open__button').click(function(e) {
      e.preventDefault();
      $('body').addClass('overlay-switcher-open');
    });

    $('.overlay-switcher-close__button').click(function(e) {
      e.preventDefault();
      $('body').removeClass('overlay-switcher-open');
    });

    }
  };
}) (jQuery, Drupal, drupalSettings);
