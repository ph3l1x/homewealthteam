(function ($) {
    'use strict';
    Drupal.behaviors.powrsocialfeed_dialog = {
      attach: function (context) {
        $( "#powrsocialfeed-dialog" ).dialog();
        $( "#powrsocialfeed-dialog" ).dialog("option", "width", 500);
      }
    };
  })(jQuery);
