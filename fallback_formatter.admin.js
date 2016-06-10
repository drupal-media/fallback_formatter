/**
 * @file
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Attach behaviors for the display settings.
   */
  Drupal.behaviors.fallbackFormatterStatus = {
    attach: function (context) {
      $('.fallback-formatter-status-wrapper input.form-checkbox', context).once('fallback-formatter-status').each(function () {
        var $checkbox = $(this);
        var formatterSelector = $checkbox.attr('data-drupal-selector').replace(/-status$/, '');

        // Retrieve the tabledrag row belonging to this filter.
        var $row = $('[data-drupal-selector="' + formatterSelector + '-weight"]', context).closest('tr');
        // Retrieve the vertical tab belonging to this filter.
        var tab = $('[data-drupal-selector="' + formatterSelector + '-settings"]', context).data('verticalTab');
        // Because the vertical tabs aren't currently working, just work on the
        // fieldset itself for now.
        var $fieldset = $('[data-drupal-selector="' + formatterSelector + '-settings"]', context);

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
