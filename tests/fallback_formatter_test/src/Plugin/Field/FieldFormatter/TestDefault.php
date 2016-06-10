<?php

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

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      $output = $item->value;
      if (!empty($this->settings['prefix'])) {
        $elements[$delta] = ['#markup' => $this->settings['prefix'] . $output];
      }
      else {
        $elements[$delta] = ['#markup' => $output];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['prefix' => ''];
  }

}
