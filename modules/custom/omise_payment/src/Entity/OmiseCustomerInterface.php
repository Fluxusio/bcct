<?php

namespace Drupal\omise_payment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Omise customer entities.
 *
 * @ingroup omise_payment
 */
interface OmiseCustomerInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Omise customer ID.
   *
   * @return string
   *   ID of the Omise customer.
   */
  public function getOmiseID();

  /**
   * Sets the Omise customer ID.
   *
   * @param string $id
   *   The Omise customer ID.
   *
   * @return \Drupal\omise_payment\Entity\OmiseCustomerInterface
   *   The called Omise customer entity.
   */
  public function setOmiseID($id);

  /**
   * Gets the Omise customer creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Omise customer.
   */
  public function getCreatedTime();

  /**
   * Sets the Omise customer creation timestamp.
   *
   * @param int $timestamp
   *   The Omise customer creation timestamp.
   *
   * @return \Drupal\omise_payment\Entity\OmiseCustomerInterface
   *   The called Omise customer entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Omise customer published status indicator.
   *
   * Unpublished Omise customer are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Omise customer is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Omise customer.
   *
   * @param bool $published
   *   TRUE to set this Omise customer to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\omise_payment\Entity\OmiseCustomerInterface
   *   The called Omise customer entity.
   */
  public function setPublished($published);

}
