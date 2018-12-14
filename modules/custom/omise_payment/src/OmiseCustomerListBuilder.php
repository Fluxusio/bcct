<?php

namespace Drupal\omise_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Omise customer entities.
 *
 * @ingroup omise_payment
 */
class OmiseCustomerListBuilder extends EntityListBuilder
{


    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('Omise customer ID');
        $header['omise_id'] = $this->t('Omise ID');
        $header['uid'] = $this->t('Drupal UID');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /* @var $entity \Drupal\omise_payment\Entity\OmiseCustomer */
        $row['id'] = $entity->id();
        $row['omise_id'] = Link::createFromRoute(
            $entity->label(),
            'entity.omise_customer.edit_form',
            ['omise_customer' => $entity->id()]
        );
        $row['uid'] = $entity->getOwnerId();
        return $row + parent::buildRow($entity);
    }

}
