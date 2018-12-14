(function ($, Drupal) {

  Drupal.behaviors.toggleSidebar = {
    attach: function (context, settings) {
      $(document).ready(function () {
        // @FIXME this resizecrop library is outdated.
        // Use something else kind of https://foliotek.github.io/Croppie/
        // Is it required?? since views/tpl/design is changing
        //$('.img-rc-370-190-center img').resizecrop({width: 370, height: 190, vertical: "middle"});
        //$('.img-rc-520-300-center img').resizecrop({width: 520, height: 300, vertical: "middle"});
        //$('.img-rc-690-420-center img').resizecrop({width: 690, height: 420, vertical: "middle"});
      });
    }
  };

})(jQuery, Drupal);
