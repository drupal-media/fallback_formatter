<?php

/**
 * @file
 * Contains \Drupal\fallback_formatter\Tests\FallbackFormatterTestCase.
 */

namespace Drupal\fallback_formatter\Tests;

use Drupal\Core\Render\Element;
use Drupal\simpletest\WebTestBase;

/**
 * Test basic functionality of the fallback formatter.
 *
 * @group Field types
 */
class FallbackFormatterTestCase extends WebTestBase {

  public static $modules = array('node', 'text', 'fallback_formatter', 'fallback_formatter_test');

  protected $contentType;

  public function setUp() {
    parent::setUp();

    $this->contentType = $this->drupalCreateContentType();

    $field = entity_create('field_storage_config', array(
      'name' => 'test_text',
      'entity_type' => 'node',
      'type' => 'text',
    ));
    $field->save();

    $instance = entity_create('field_instance_config', array(
      'field_storage' => $field,
      'bundle' => $this->contentType->id(),
    ));
    $instance->save();
  }

  public function test() {

    $node = entity_create('node', array(
      'type' => $this->contentType->id(),
      'test_text' => array(
        array(
          'value' => 'Apple',
          'format' => NULL,
        ),
        array(
          'value' => 'Banana'
        ),
        array(
          'value' => 'Carrot'
        )
      )
    ));

    $formatters = array(
      'fallback_test_a' => array(
        'settings' => array(),
      ),
      'fallback_test_b' => array(
        'settings' => array(),
      ),
      'fallback_test_default' => array(
        'settings' => array('prefix' => 'DEFAULT: '),
      ),
    );
    $expected = array(
      0 => array('#markup' => 'A: Apple'),
      1 => array('#markup' => 'B: Banana'),
      2 => array('#markup' => 'DEFAULT: Carrot'),
    );
    $this->assertFallbackFormatter($node, $formatters, $expected);

    $formatters = array(
      'fallback_test_a' => array(),
      'fallback_test_b' => array(),
      'fallback_test_default' => array(
        'settings' => array('prefix' => 'DEFAULT: '),
        'weight' => -1,
      ),
    );
    $expected = array(
      0 => array('#markup' => 'DEFAULT: Apple'),
      1 => array('#markup' => 'DEFAULT: Banana'),
      2 => array('#markup' => 'DEFAULT: Carrot'),
    );
    $this->assertFallbackFormatter($node, $formatters, $expected);

    $formatters = array(
      'fallback_test_a' => array(
        'settings' => array('deny' => TRUE),
      ),
      'fallback_test_b' => array(),
      'fallback_test_default' => array(
        'settings' => array('prefix' => 'DEFAULT: '),
      ),
    );
    $expected = array(
      // Delta 0 skips the first formatter, but we test that it is still
      // returned in the proper order since the last formatter displayed it.
      0 => array('#markup' => 'DEFAULT: Apple'),
      1 => array('#markup' => 'B: Banana'),
      2 => array('#markup' => 'DEFAULT: Carrot'),
    );
    $this->assertFallbackFormatter($node, $formatters, $expected);
  }

  protected function assertFallbackFormatter($entity, array $formatters = array(), array $expected_output) {
    $display = array(
      'type' => 'fallback',
      'settings' => array('formatters' => $formatters),
    );
    $output = $entity->test_text->view($display);
    $output = array_intersect_key($output, Element::children($output));
    $this->assertEqual($output, $expected_output);
  }
}
