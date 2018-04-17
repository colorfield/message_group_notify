<?php

/**
 * @file
 * Contains message_group.page.inc.
 *
 * Page callback for Message group entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Message group templates.
 *
 * Default template: message_group.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_message_group(array &$variables) {
  // Fetch MessageGroup Entity Object.
  $message_group = $variables['elements']['#message_group'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
