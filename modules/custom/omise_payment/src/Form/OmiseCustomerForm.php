<?php

namespace Drupal\omise_payment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Omise customer edit forms.
 *
 * @ingroup omise_payment
 */
class OmiseCustomerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\omise_payment\Entity\OmiseCustomer */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Omise customer.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Omise customer.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.omise_customer.canonical', ['omise_customer' => $entity->id()]);
  }

}
