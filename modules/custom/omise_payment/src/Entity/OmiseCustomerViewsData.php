<?php

namespace Drupal\omise_payment\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Omise customer entities.
 */
class OmiseCustomerViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
