<?php

/**
 * @file
 * Contains \Drupal\fallback_formatter_test\Plugin\Field\FieldFormatter\TestA.
 */

namespace Drupal\fallback_formatter_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'boolean' formatter.
 *
 * @FieldFormatter(
 *   id = "fallback_test_a",
 *   label = @Translation("Test A"),
 *   field_types = {
 *     "text",
 *   }
 * )
 */
class TestA extends FormatterBase {

  public function viewElements(FieldItemListInterface $items) {

    $elements = array();

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      $output = $item->processed;
      if (strtolower(substr($output, 0, 1)) === 'a') {
        $elements[$delta] = array('#markup' => 'A: ' . $output);
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
