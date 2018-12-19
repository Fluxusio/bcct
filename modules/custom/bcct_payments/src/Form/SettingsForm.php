<?php

namespace Drupal\bcct_payments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'bcct_payments.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('bcct_payments.settings');
        $form['charge_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Charge type'),
            '#description' => $this->t('Select how you want to charge your customers'),
            '#options' => [
                'on_creation' => $this->t('Just once (on creation)'),
                'recurring' => $this->t('Recurring')
            ],
            '#default_value' => $config->get('charge_type'),
            '#required' => true
        ];
        $form['recurring_period'] = [
            '#type' => 'select',
            '#title' => $this->t('Recurring period'),
            '#description' => $this->t('Select frequency of charges'),
            '#options' => [
                'monthly' => $this->t('First of every month')
            ],
            '#default_value' => 'monthly',
            '#required' => true,
            '#states' => array(
                'visible' => array(
                    ':input[name="charge_type"]' =>
                        array('value' => 'recurring')
                ),
            ),
        ];
        $form['amount'] = [
            '#type' => 'number',
            '#title' => $this->t('Amount'),
            '#description' => $this->t('Amount to charge'),
            '#default_value' => $config->get('amount'),
            '#min' => 20,
            '#step' => 0.01,
            '#required' => true
        ];
        $form['signup_amount'] = [
            '#type' => 'number',
            '#title' => $this->t('Signup amount'),
            '#description' => $this->t('Amount to charge on signup. Set to 0 to apply general amount'),
            '#default_value' => $config->get('signup_amount'),
            '#min' => 0,
            '#step' => 0.01,
        ];
        $form['currency'] = [
            '#type' => 'select',
            '#title' => $this->t('Currency'),
            '#options' => [
                'THB' => $this->t('THB')
            ],
            '#default_value' => $config->get('currency'),
            '#required' => true
        ];

        return parent::buildForm($form, $form_state);
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
        parent::submitForm($form, $form_state);

        $this->config('bcct_payments.settings')
            ->set('charge_type', $form_state->getValue('charge_type'))
            ->set('recurring_period', $form_state->getValue('recurring_period'))
            ->set('amount', $form_state->getValue('amount'))
            ->set('signup_amount', $form_state->getValue('signup_amount'))
            ->set('currency', $form_state->getValue('currency'))
            ->save();
    }

}
