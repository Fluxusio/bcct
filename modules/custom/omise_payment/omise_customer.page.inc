<?php

/**
 * @file
 * Contains omise_customer.page.inc.
 *
 * Page callback for Omise customer entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Omise customer templates.
 *
 * Default template: omise_customer.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_omise_customer(array &$variables) {
  // Fetch OmiseCustomer Entity Object.
  $omise_customer = $variables['elements']['#omise_customer'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
