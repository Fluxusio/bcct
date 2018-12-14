<?php

namespace Drupal\omise_payment\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Our example form class
 */
class CheckoutCollectCardForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'omise_checkout_collect_card_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $actionForm = Url::fromRoute('omise_payment.ajax_checkout_form_submit',[])->toString();
        $redirectRoute = \Drupal::routeMatch()->getRouteName();

        $form['email'] = [
          '#type' => 'hidden',
          '#value' => \Drupal::currentUser()->getEmail()
        ];
        $form['redirect'] = [
          '#type' => 'hidden',
          '#value' => $redirectRoute
        ];
        $form['entityId'] = [
          '#type' => 'hidden',
          '#value' => \Drupal::routeMatch()->getRawParameter('node')
        ];

        $form['#action'] =$actionForm;

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Display result.
        foreach ($form_state->getValues() as $key => $value) {
            //drupal_set_message($key . ': ' . $value);
        }

    }

}