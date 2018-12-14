<?php

namespace Drupal\omise_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Class CollectCardForm.
 */
class CollectCardForm extends FormBase
{


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'collect_card_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        // First of all check get and check that Omise public key is set
        $omisePublicKey = $this->config('omise_payment.settings')
            ->get('public_key');

        if (!$omisePublicKey) {
            drupal_set_message($this->t('Looks like the Omise Public key is not properly set, 
                    please checkt it on @link or contact with the administrator', [
                '@link' =>
                    Link::createFromRoute(t('Omise Settings'),'omise_payment.admin_settings',
                        [], ['absolute' => true])->toString()
            ]), 'warning'
            );
            $form['error'] = [
                '#markup' => t('There was a problem with Omise Gateway. Please, contact with administrator.')
            ];
            return $form;
        }

        $form['name'] = [
            '#type' => 'textfield',
            '#placeholder' => $this->t('Name'),
            '#attributes' => [
                'data-omise' => 'holder_name',
                'class' => ['holder-name']
            ],
        ];
        $form['number'] = [
            '#type' => 'textfield',
            '#placeholder' => $this->t('Number'),
            '#maxlength' => 16,
            '#attributes' => [
                'data-omise' => 'number',
                'class' => ['card-number']
            ],
        ];
        $form['expiration'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Expiration')
        ];
        $form['expiration']['month'] = [
            '#type' => 'textfield',
            '#placeholder' => $this->t('Month'),
            '#attributes' => [
                'data-omise' => 'expiration_month',
                'class' => ['card-expiry-month']
            ],
            '#size' => 2,
            '#maxlength' => 2
        ];
        $form['expiration']['year'] = [
            '#type' => 'textfield',
            '#placeholder' => $this->t('Year'),
            '#attributes' => [
                'data-omise' => 'expiration_year',
                'class' => ['card-expiry-year']
            ],
            '#size' => 4,
            '#maxlength' => 4
        ];
        $form['security_code'] = [
            '#type' => 'textfield',
            '#placeholder' => $this->t('Security code'),
            '#attributes' => [
                'data-omise' => 'security_code',
                'class' => ['card-cvc']
            ],
        ];
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
            '#attributes' => [
                'id' => 'create_token',
                'class' => ['use-ajax']
            ]
        ];

        // This ensure the add payment method from user account works.
        $form['#attached']['library'][] = 'omise_payment/form';
        // Alter the form with Omise specific needs.
        $form['#attributes']['class'][] = 'omise-form';

        // Set our key to settings array.
        $form['#attached']['drupalSettings']['commerceOmise'] = [
            'publicKey' => $omisePublicKey
        ];




        // To display validation errors.
        $form['payment_errors'] = [
            '#type' => 'markup',
            '#markup' => '<div class="payment-errors"></div>',
            '#weight' => -200,
        ];


        // Populated by the JS library.
        $form['omise_token'] = [
            '#type' => 'hidden',
            '#attributes' => [
                'id' => 'omise_token',
            ],
        ];



        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';


        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // When sending form we've got the omise token
        $omiseToken=$form_state->getValue('omise_token');


    }


}
