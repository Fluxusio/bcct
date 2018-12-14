<?php

namespace Drupal\omise_payment;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Omise customer entity.
 *
 * @see \Drupal\omise_payment\Entity\OmiseCustomer.
 */
class OmiseCustomerAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\omise_payment\Entity\OmiseCustomerInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished omise customer entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published omise customer entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit omise customer entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete omise customer entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add omise customer entities');
  }

}
