<?php

/**
 * @file
 * Contains \Drupal\fallback_formatter\Plugin\Field\FieldFormatter\FallbackFormatter.
 */

namespace Drupal\fallback_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Fallback formatter.
 *
 * @FieldFormatter(
 *   id = "fallback",
 *   label = @Translation("Fallback"),
 *   weight = 100
 * )
 */
class FallbackFormatter extends FormatterBase {

  /**
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->formatterManager = \Drupal::service('plugin.manager.field.formatter');
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $settings = $this->getSettings();

    $items_array = array();
    foreach ($items as $item) {
      $items_array[] = $item;
    }

    // Merge defaults from the formatters and ensure proper ordering.
    $this->prepareFormatters($this->fieldDefinition->getType(), $settings['formatters']);

    // Loop through each formatter in order.
    foreach ($settings['formatters'] as $name => $options) {

      // Run any unrendered items through the formatter.
      $formatter_items = array_diff_key($items_array, $element);

      $formatter_instance = $this->getFormatter($options);
      $formatter_instance->prepareView(array($items->getEntity()->id() => $items));

      if ($result = $formatter_instance->viewElements($items)) {

        // Only add visible content from the formatter's render array result
        // that matches an unseen delta.
        $visible_deltas = Element::getVisibleChildren($result);
        $visible_deltas = array_intersect($visible_deltas, array_keys($formatter_items));
        $element += array_intersect_key($result, array_flip($visible_deltas));

        // If running this formatter completed the output for all items, then
        // there is no need to loop through the rest of the formatters.
        if (count($element) == count($items_array)) {
          break;
        }
      }
    }

    // Ensure the resulting elements are ordered properly by delta.
    ksort($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();
    $formatters = fallback_formatter_get_possible_formatters($this->fieldDefinition->getType());

    $elements['#attached']['js'][] = drupal_get_path('module', 'fallback_formatter') . '/fallback_formatter.admin.js';

    $weights = array();
    $current_weight = 0;
    foreach ($formatters as $name => $options) {
      if (isset($settings['formatters'][$name]['weight'])) {
        $weights[$name] = $settings['formatters'][$name]['weight'];
      }
      elseif (isset($options['weight'])) {
        $weights[$name] = $options['weight'];
      }
      elseif (empty($settings['formatters'])) {
        $weights[$name] = $current_weight++;
      }
    }

    $parents = array('fields', $this->fieldDefinition->getName(), 'settings_edit_form', 'settings', 'formatters');

    // Filter status.
    $elements['formatters']['status'] = array(
      '#type' => 'item',
      '#title' => t('Enabled formatters'),
      '#prefix' => '<div class="fallback-formatter-status-wrapper">',
      '#suffix' => '</div>',
    );
    foreach ($formatters as $name => $options) {
      $elements['formatters']['status'][$name] = array(
        '#type' => 'checkbox',
        '#title' => $options['label'],
        '#default_value' => !empty($settings['formatters'][$name]['status']),
        '#parents' => array_merge($parents, array($name, 'status')),
        '#weight' => $weights[$name],
      );
    }

    // Filter order (tabledrag).
    $elements['formatters']['order'] = array(
      '#type' => 'item',
      '#title' => t('Formatter processing order'),
      '#theme' => 'fallback_formatter_settings_order',
    );
    foreach ($formatters as $name => $options) {
      $elements['formatters']['order'][$name]['label'] = array(
        '#markup' => $options['label'],
      );
      $elements['formatters']['order'][$name]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $options['label'])),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $weights[$name],
        '#parents' => array_merge($parents, array($name, 'weight')),
      );
      $elements['formatters']['order'][$name]['#weight'] = $weights[$name];

      $elements['formatters']['settings'][$name]['formatter'] = array(
        '#type' => 'value',
        '#value' => $name,
        '#parents' => array_merge($parents, array($name, 'formatter')),
      );
    }

    // Filter settings.
    foreach ($formatters as $name => $options) {

      $formatter_instance = $this->getFormatter($options);
      $settings_form = $formatter_instance->settingsForm($form, $form_state);

      if (!empty($settings_form)) {
        $elements['formatters']['settings'][$name] = array(
          '#type' => 'fieldset',
          '#title' => $options['label'],
          '#parents' => array_merge($parents, array($name, 'settings')),
          '#weight' => $weights[$name],
          '#group' => 'formatter_settings',
        );
        $elements['formatters']['settings'][$name] += $settings_form;
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $formatters = $this->formatterManager->getDefinitions();

    $this->prepareFormatters($this->fieldDefinition->getType(), $settings['formatters']);

    $summary_items = array();
    foreach ($settings['formatters'] as $name => $options) {
      if (!isset($formatters[$name])) {
        $summary_items[] = t('Unknown formatter %name.', array('%name' => $name));
      }
      elseif (!in_array($this->fieldDefinition->getType(), $formatters[$name]['field_types'])) {
        $summary_items[] = t('Invalid formatter %name.', array('%name' => $formatters[$name]['label']));
      }
      else {

        $formatter_instance = $this->getFormatter($options);
        $result = $formatter_instance->settingsSummary();

        $summary_items[] = String::format('<strong>@label</strong>!settings_summary', array(
          '@label' => $formatter_instance->getPluginDefinition()['label'],
          '!settings_summary' => '<br>' . Xss::filter(!empty($result) ? implode(', ', $result) : ''),
        ));
      }
    }

    if (empty($summary_items)) {
      $summary = array(
        '#markup' => t('No formatters selected yet.'),
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      );
    }
    else {
      $summary = array(
        '#theme' => 'item_list',
        '#items' => $summary_items,
        '#type' => 'ol'
      );
    }

    return array(drupal_render($summary));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'formatters' => array(),
    );
  }

  /**
   * Gets an instance of a formatter.
   *
   * @param array $options
   *   Formatter options.
   *
   * @return \Drupal\Core\Field\FormatterInterface
   */
  protected function getFormatter($options) {
    if (!isset($options['settings'])) {
      $options['settings'] = array();
    }

    $options += array(
      'field_definition' => $this->fieldDefinition,
      'view_mode' => $this->viewMode,
      'configuration' => array('type' => $options['id'], 'settings' => $options['settings']),
    );
    return $this->formatterManager->getInstance($options);
  }

  /**
   * Decorates formatters definitions to be complete for plugin instantiation.
   *
   * @param string $field_type
   *   The field type for which to prepare the formatters.
   * @param array $formatters
   *   The formatter definitions we want to prepare.
   *
   * @todo - this might be merged with getFormatter()?
   */
  protected function prepareFormatters($field_type, array &$formatters) {
    $allowed_formatters = fallback_formatter_get_possible_formatters($field_type);

    $formatters = array_intersect_key($formatters, $allowed_formatters);

    foreach ($formatters as $formatter => $info) {
      // Remove disabled formatters.
      if (isset($info['status']) && !$info['status']) {
        unset($formatters[$formatter]);
        continue;
      }

      // Provide some default values.
      $formatters[$formatter] += array('weight' => 0);
      // Merge in defaults.
      $formatters[$formatter] += $allowed_formatters[$formatter];
      if (!empty($allowed_formatters[$formatter]['settings'])) {
        $formatters[$formatter]['settings'] += $allowed_formatters[$formatter]['settings'];
      }
    }

    // Sort by weight.
    uasort($formatters, 'fallback_formatter_sort_formatters');
  }
}
