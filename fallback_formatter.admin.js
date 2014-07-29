(function ($, Drupal) {

  "use strict";

  /**
   * Attach behaviors for the display settings.
   */
  Drupal.behaviors.fileValidateAutoAttach = {
    attach: function (context, settings) {
    $('.fallback-formatter-status-wrapper input.form-checkbox', context).once('fallback-formatter-status', function () {
      var $checkbox = $(this);
      // Retrieve the tabledrag row belonging to this filter.
      var $row = $('#' + $checkbox.attr('id').replace(/-status$/, '-weight'), context).closest('tr');
      // Retrieve the vertical tab belonging to this filter.
      var tab = $('#' + $checkbox.attr('id').replace(/-status$/, '-settings'), context).data('verticalTab');
      // Because the vertical tabs aren't currently working, just work on the
      // fieldset itself for now.
      var $fieldset = $('#' + $checkbox.attr('id').replace(/-status$/, '-settings'), context);

      // Bind click handler to this checkbox to conditionally show and hide the
      // filter's tableDrag row and vertical tab pane.
      $checkbox.bind('click.filterUpdate', function () {
        if ($checkbox.is(':checked')) {
          $row.show();
          $fieldset.show();
          if (tab) {
            tab.tabShow().updateSummary();
          }
        }
        else {
          $row.hide();
          $fieldset.hide();
          if (tab) {
            tab.tabHide().updateSummary();
          }
        }
        // Restripe table after toggling visibility of table row.
        Drupal.tableDrag['fallback-formatter-order'].restripeTable();
      });

      // Attach summary for configurable filters (only for screen-readers).
      if (tab) {
        tab.fieldset.drupalSetSummary(function (tabContext) {
          return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
        });
      }

      // Trigger our bound click handler to update elements to initial state.
      $checkbox.triggerHandler('click.filterUpdate');
    });
  }
};

})(jQuery, Drupal);
