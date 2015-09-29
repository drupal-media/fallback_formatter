<?php

/**
 * @file
 * Contains \Drupal\fallback_formatter_test\Plugin\Field\FieldFormatter\TestDefault.
 */

namespace Drupal\fallback_formatter_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'boolean' formatter.
 *
 * @FieldFormatter(
 *   id = "fallback_test_default",
 *   label = @Translation("Test Default"),
 *   field_types = {
 *     "text",
 *   }
 * )
 */
class TestDefault extends FormatterBase {

  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = array();

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      $output = $item->value;
      if (!empty($this->settings['prefix'])) {
        $elements[$delta] = array('#markup' => $this->settings['prefix'] . $output);
      }
      else {
        $elements[$delta] = array('#markup' => $output);
      }
    }

    return $elements;
  }

  public static function defaultSettings() {
    return array(
      'prefix' => '',
    );
  }

}
