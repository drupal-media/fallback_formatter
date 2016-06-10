<?php

namespace Drupal\fallback_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fallback formatter.
 *
 * @FieldFormatter(
 *   id = "fallback",
 *   label = @Translation("Fallback"),
 *   weight = 100
 * )
 */
class FallbackFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The manager for formatter plugins.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager.
   */
  protected $formatterManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a FallbackFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The manager for formatter plugins.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormatterPluginManager $formatter_manager, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->formatterManager = $formatter_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.field.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();

    $items_array = [];
    foreach ($items as $item) {
      $items_array[] = $item;
    }

    // Merge defaults from the formatters and ensure proper ordering.
    $this->prepareFormatters($this->fieldDefinition->getType(), $settings['formatters']);

    // Loop through each formatter in order.
    foreach ($settings['formatters'] as $options) {

      // Run any unrendered items through the formatter.
      $formatter_items = array_diff_key($items_array, $element);

      $formatter_instance = $this->getFormatter($options);
      $formatter_instance->prepareView([$items->getEntity()->id() => $items]);

      if ($result = $formatter_instance->viewElements($items, $langcode)) {

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

    $formatters = $settings['formatters'];
    $this->prepareFormatters($this->fieldDefinition->getType(), $formatters, FALSE);

    $elements['#attached']['library'][] = 'fallback_formatter/admin';

    $parents = [
      'fallback_formatter_settings',
      'formatters',
    ];

    // Filter status.
    $elements['formatters']['status'] = [
      '#type' => 'item',
      '#input' => FALSE,
      '#title' => t('Enabled formatters'),
      '#prefix' => '<div class="fallback-formatter-status-wrapper">',
      '#suffix' => '</div>',
      '#element_validate' => [[$this, 'fallbackFormatterValidate']],
    ];
    foreach ($formatters as $name => $options) {
      $elements['formatters']['status'][$name] = [
        '#type' => 'checkbox',
        '#title' => $options['label'],
        '#default_value' => !empty($options['status']),
        '#parents' => array_merge($parents, [$name, 'status']),
        '#weight' => $options['weight'],
      ];
    }

    // Filter weight (tabledrag).
    $elements['formatters']['weight'] = [
      '#type' => 'item',
      '#input' => FALSE,
      '#title' => t('Formatter processing weight'),
      '#theme' => 'fallback_formatter_settings_order',
    ];
    foreach ($formatters as $name => $options) {
      $elements['formatters']['weight'][$name]['label'] = [
        '#markup' => $options['label'],
      ];
      $elements['formatters']['weight'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $options['label']]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $options['weight'],
        '#parents' => array_merge($parents, [$name, 'weight']),
      ];
      $elements['formatters']['weight'][$name]['#weight'] = $options['weight'];
    }

    // Filter settings.
    foreach ($formatters as $name => $options) {
      $formatter_instance = $this->getFormatter($options);
      $settings_form = $formatter_instance->settingsForm($form, $form_state);

      if (!empty($settings_form)) {
        $elements['formatters']['settings'][$name] = [
          '#type' => 'fieldset',
          '#title' => $options['label'],
          '#parents' => array_merge($parents, [$name, 'settings']),
          '#weight' => $options['weight'],
          '#group' => 'formatter_settings',
        ];
        $elements['formatters']['settings'][$name] += $settings_form;
      }

      $elements['formatters']['settings'][$name]['formatter'] = [
        '#type' => 'value',
        '#value' => $name,
        '#parents' => array_merge($parents, [$name, 'formatter']),
      ];
    }

    return $elements;
  }

  /**
   * The #element_validate handler for the "status" element in settingsForm().
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function fallbackFormatterValidate(array $element, FormStateInterface $form_state) {
    $top_parents = array_diff($element['#parents'], array_slice($element['#parents'], -2));
    // Copy the settings values to the correct location.
    $form_state->setValue($top_parents, $form_state->getValue('fallback_formatter_settings'));
    // Clean up form state.
    $form_state->unsetValue('fallback_formatter_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $formatters = $this->formatterManager->getDefinitions();

    $this->prepareFormatters($this->fieldDefinition->getType(), $settings['formatters']);

    $summary_items = [];
    foreach ($settings['formatters'] as $name => $options) {
      if (!isset($formatters[$name])) {
        $summary_items[] = t('Unknown formatter %name.', ['%name' => $name]);
      }
      elseif (!in_array($this->fieldDefinition->getType(), $formatters[$name]['field_types'])) {
        $summary_items[] = t('Invalid formatter %name.', ['%name' => $formatters[$name]['label']]);
      }
      else {

        $formatter_instance = $this->getFormatter($options);
        $result = $formatter_instance->settingsSummary();

        $summary_items[] = [
          '#type' => 'inline_template',
          '#template' => '<strong>{{ label }}</strong>{{ settings_summary|raw }}',
          '#context' => [
            'label' => $formatter_instance->getPluginDefinition()['label'],
            'settings_summary' => '<br>' . Xss::filter(!empty($result) ? implode(', ', $result) : ''),
          ],
        ];
      }
    }

    if (empty($summary_items)) {
      $summary = [
        '#markup' => t('No formatters selected yet.'),
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      ];
    }
    else {
      $summary = [
        '#theme' => 'item_list',
        '#items' => $summary_items,
        '#type' => 'ol',
      ];
    }

    return [$this->renderer->render($summary)];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'formatters' => [],
    ];
  }

  /**
   * Gets an instance of a formatter.
   *
   * @param array $options
   *   Formatter options.
   *
   * @return \Drupal\Core\Field\FormatterInterface
   *   Returns the formatter instance.
   */
  protected function getFormatter($options) {
    if (!isset($options['settings'])) {
      $options['settings'] = [];
    }

    $options += [
      'field_definition' => $this->fieldDefinition,
      'view_mode' => $this->viewMode,
      'configuration' => ['type' => $options['id'], 'settings' => $options['settings']],
    ];

    return $this->formatterManager->getInstance($options);
  }

  /**
   * Decorates formatters definitions to be complete for plugin instantiation.
   *
   * @param string $field_type
   *   The field type for which to prepare the formatters.
   * @param array $formatters
   *   The formatter definitions we want to prepare.
   * @param bool $filter_enabled
   *   If TRUE (default) will filter out any disabled formatters. If FALSE will
   *   return all possible formatters.
   *
   * @todo - this might be merged with getFormatter()?
   */
  protected function prepareFormatters($field_type, array &$formatters, $filter_enabled = TRUE) {
    $default_weight = 0;

    $allowed_formatters = $this->getPossibleFormatters($field_type);
    $formatters += $allowed_formatters;

    $formatters = array_intersect_key($formatters, $allowed_formatters);

    foreach ($formatters as $formatter => $info) {
      // Remove disabled formatters.
      if ($filter_enabled && empty($info['status'])) {
        unset($formatters[$formatter]);
        continue;
      }

      // Provide some default values.
      $formatters[$formatter] += ['weight' => $default_weight++];
      // Merge in defaults.
      $formatters[$formatter] += $allowed_formatters[$formatter];
      if (!empty($allowed_formatters[$formatter]['settings'])) {
        $formatters[$formatter]['settings'] += $allowed_formatters[$formatter]['settings'];
      }
    }

    // Sort by weight.
    uasort($formatters, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
  }

  /**
   * Gets possible formatters for the given field type.
   *
   * @param string $field_type
   *   Field type for which we want to get the possible formatters.
   *
   * @return array
   *   Formatters info array.
   */
  protected function getPossibleFormatters($field_type) {
    $return = [];

    foreach ($this->formatterManager->getDefinitions() as $formatter => $info) {
      // The fallback formatter cannot be used as a fallback formatter.
      if ($formatter == 'fallback') {
        continue;
      }
      // Check that the field type is allowed for the formatter.
      elseif (!in_array($field_type, $info['field_types'])) {
        continue;
      }
      elseif (!$info['class']::isApplicable($this->fieldDefinition)) {
        continue;
      }
      else {
        $return[$formatter] = $info;
      }
    }

    return $return;
  }

}
