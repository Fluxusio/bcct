<?php

namespace Drupal\omise_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures omise_payment settings.
 */
class SettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'omise_payment_admin_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
          'omise_payment.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
        $config = $this->config('omise_payment.settings');

        $form['credentials'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Omise Payment Credentials'),
          '#description' => $this->t(
            "To configure your Omise Payment credentials, enter the values in the appropriate fields below.
        You may instead set \$config['omise_payment.settings']['public_key'] and \$config['omise_payment.settings']['secret_key'] in your site's settings.php file.
        Values set in settings.php will override the values in these fields."
          ),
          '#collapsible' => FALSE
        ];

        $form['credentials']['is_live'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use Live environment'),
          '#default_value' => $config->get('is_live'),
          '#description' => $this->t(
            'Check this if you want to use Real environment. Leave unchecked if you want to use Test environment.'
          ),
        ];

        $form['credentials']['public_key'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Omise Payment Publick Key'),
          '#default_value' => $config->get('public_key')
        ];

        $form['credentials']['secret_key'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Omise Payment Secret Key'),
          '#default_value' => $config->get('secret_key'),
        ];

        $form['omise_options'] = [
            '#type' => 'fieldset'
        ];
        $form['omise_options']['use_js'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use JS for integration'),
          '#default_value' => $config->get('use_js'),
          '#description' => $this->t(
            'Checking this option the collecting card forms will be created using Omise JS Prebuilt options. 
            Otherwise, the collecting card forms will be created customly with PHP.
            <br/><strong>Not using JS</strong>: the cards will not be charged on validation.
            <br/><strong>Using JS</strong>: the cards will be validated with a 1THB charge. Better UX experience.'
          ),
        ];
        $form['omise_options']['auth_fee'] = [
            '#type' => 'number',
            '#title' => $this->t('Authorization fee'),
            '#field_suffix' => 'THB',
            '#min' => 0, // Minimum amount chargable in cards
            '#step' => 1,
            '#description' => $this->t('By default, the card collection process in Omise will automatically authenticate the card with a symbolic charge of 1THB. If you want to set a specific amount (greated than 20THB) then, a fee will be charged and refunded to authenticate the card (bear in mind Omise will take its fee on this transaction). <strong>Set 0 for using Omise default option</strong>'),
            '#default_value' => $config->get('auth_fee'),
            '#states' => [
                'visible' => [
                    ':input[name="use_js[value]"]' => [
                        'checked' => true,
                    ]
                ]
            ]
        ];


        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();
        $this->config('omise_payment.settings')
          ->set('public_key', $values['public_key'])
          ->set('secret_key', $values['secret_key'])
          ->set('is_live', $values['is_live'])
          ->set('use_js', $values['use_js'])
          ->set('auth_fee', $values['auth_fee'])
          ->save();

        parent::submitForm($form, $form_state);
    }

}
