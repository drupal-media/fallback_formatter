<?php

namespace Drupal\fallback_formatter\Tests;

use Drupal\Core\Render\Element;
use Drupal\node\NodeInterface;
use Drupal\simpletest\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;

/**
 * Test basic functionality of the fallback formatter.
 *
 * @group fallback_formatter
 */
class FallbackFormatterTestCase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'field',
    'node',
    'text',
    'fallback_formatter',
    'fallback_formatter_test',
  ];

  /**
   * The created node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $node_type_id = strtolower($this->randomMachineName(8));
    $node_type = NodeType::create([
      'type' => $node_type_id,
      'name' => $node_type_id,
    ]);
    $node_type->save();

    FieldStorageConfig::create([
      'field_name' => 'test_text',
      'entity_type' => 'node',
      'type' => 'text',
    ])->save();

    FieldConfig::create([
      'field_name' => 'test_text',
      'entity_type' => 'node',
      'label' => 'Test',
      'bundle' => $node_type_id,
    ])->save();

    $this->node = Node::create([
      'type' => $node_type_id,
      'test_text' => [
        [
          'value' => 'Apple',
          'format' => NULL,
        ],
        ['value' => 'Banana'],
        ['value' => 'Carrot'],
      ],
    ]);
  }

  /**
   * Tests basic functionality of fallback formatter.
   */
  public function testBasicFunctionality() {
    $formatters = [
      'fallback_test_a' => [
        'settings' => [],
        'status' => 1,
      ],
      'fallback_test_b' => [
        'settings' => [],
        'status' => 1,
      ],
      'fallback_test_default' => [
        'settings' => ['prefix' => 'DEFAULT: '],
        'status' => 1,
      ],
    ];
    $expected = [
      0 => ['#markup' => 'A: Apple'],
      1 => ['#markup' => 'B: Banana'],
      2 => ['#markup' => 'DEFAULT: Carrot'],
    ];
    $this->assertFallbackFormatter($this->node, $expected, $formatters);

    $formatters = [
      'fallback_test_a' => [
        'status' => 1,
      ],
      'fallback_test_b' => [
        'status' => 1,
      ],
      'fallback_test_default' => [
        'settings' => ['prefix' => 'DEFAULT: '],
        'status' => 1,
        'weight' => -1,
      ],
    ];
    $expected = [
      0 => ['#markup' => 'DEFAULT: Apple'],
      1 => ['#markup' => 'DEFAULT: Banana'],
      2 => ['#markup' => 'DEFAULT: Carrot'],
    ];
    $this->assertFallbackFormatter($this->node, $expected, $formatters);

    $formatters = [
      'fallback_test_a' => [
        'settings' => ['deny' => TRUE],
        'status' => 1,
      ],
      'fallback_test_b' => [
        'status' => 1,
      ],
      'fallback_test_default' => [
        'settings' => ['prefix' => 'DEFAULT: '],
        'status' => 1,
      ],
    ];
    $expected = [
      // Delta 0 skips the first formatter, but we test that it is still
      // returned in the proper order since the last formatter displayed it.
      0 => ['#markup' => 'DEFAULT: Apple'],
      1 => ['#markup' => 'B: Banana'],
      2 => ['#markup' => 'DEFAULT: Carrot'],
    ];
    $this->assertFallbackFormatter($this->node, $expected, $formatters);
  }

  /**
   * Asserts the fallback formatter output.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity object.
   * @param array $expected_output
   *   The expected output.
   * @param array $formatters
   *   The formatter settings.
   */
  protected function assertFallbackFormatter(NodeInterface $entity, array $expected_output, array $formatters = []) {
    $display = [
      'type' => 'fallback',
      'settings' => ['formatters' => $formatters],
    ];
    $output = $entity->test_text->view($display);
    $output = array_intersect_key($output, Element::children($output));
    $this->assertEqual($output, $expected_output);
  }

}
