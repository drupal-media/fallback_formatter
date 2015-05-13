<?php

/**
 * @file
 * Contains \Drupal\fallback_formatter_test\Plugin\Field\FieldFormatter\TestB.
 */

namespace Drupal\fallback_formatter_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'boolean' formatter.
 *
 * @FieldFormatter(
 *   id = "fallback_test_b",
 *   label = @Translation("Test B"),
 *   field_types = {
 *     "text",
 *   }
 * )
 */
class TestB extends FormatterBase {

  public function viewElements(FieldItemListInterface $items) {

    $elements = array();

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      $output = $item->value;
      if (strtolower(substr($output, 0, 1)) === 'b') {
        $elements[$delta] = array('#markup' => 'B: ' . $output);
        if (!empty($this->settings['deny'])) {
          $elements[$delta]['#access'] = FALSE;
        }
      }
    }

    return $elements;
  }

  public static function defaultSettings() {
    return array(
      'deny' => FALSE,
    );
  }

}
