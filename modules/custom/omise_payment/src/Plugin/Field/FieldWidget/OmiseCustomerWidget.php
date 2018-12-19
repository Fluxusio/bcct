<?php

namespace Drupal\omise_payment\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;


use Drupal\Core\Url;
use Drupal\omise_payment\Controller\OmiseTransactionController;
use Drupal\omise_payment\Entity\OmiseCustomer;

/**
 * Plugin implementation of the 'omise_customer_widget' widget.
 *
 * @FieldWidget(
 *   id = "omise_customer_widget",
 *   label = @Translation("Omise customer widget"),
 *   field_types = {
 *     "omise_customer_type"
 *   }
 * )
 */
class OmiseCustomerWidget extends EntityReferenceAutocompleteWidget
{
    protected $omiseController;


    public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        array $third_party_settings
    ) {
        $this->omiseController = new OmiseTransactionController();
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    }


    /**
     * {@inheritdoc}
     */
    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state
    ) {

        $omise = new OmiseTransactionController();

        // Hide element storing value
        $element['target_id'] = $element + [
                '#type' => 'hidden',
                '#size' => $this->getSetting('size'),
                // Entity reference field items are handling validation themselves via
                // the 'ValidReference' constraint.
                '#validate_reference' => false,
                '#default_value' => null,
                '#placeholder' => $this->getSetting('placeholder'),
                '#maxlength' => $this->getFieldSetting('max_length'),
            ];

        // Set class to refer on JS
        $form['#attributes']['class'][] = "omise-customer";

        // Create widget fieldset
        $itemValue = $items->get($delta)->getValue();
        $fieldName = $this->fieldDefinition->get('field_name');
        $modulePath = file_create_url(drupal_get_path("module", "omise_payment") . '/images/cards/');
        $form['omise_customer'] = [
            '#type' => 'fieldgroup',
            '#title' => $element['#title']
        ];

        // Check if omiseToken is set in $_POST, so widget is reloading after adding card (from JS)
        if (isset($_POST['omiseToken']) && $_POST['omiseToken'] != '') {
            // Check if is logged so we add email to POST array
            if (\Drupal::currentUser()->id()) {
                $_POST['email'] = \Drupal::currentUser()->getEmail();
            } else {
                $_POST['email'] = null;
            }
            // Get token from checkout
            $data = $omise->getTokenCheckout();
            $customerInfo = json_decode($data->getContent(), true);
            if ($customerInfo['result'] == 'success' && isset($customerInfo['data']['drupalOmiseCustomerId'])) {
                // Drupal user exists
                // We save Drupal uid
                $itemValue['target_id'] = $customerInfo['data']['drupalOmiseCustomerId'];
            } elseif (isset($customerInfo['data']['omiseTokenCustomer'])) {
                // Drupal user doesnt exist. Widget is being used in register form
                // We save Omise customer id
                // We'll create customer when user is created. See hook_entity_presave
                $itemValue['target_id'] = $customerInfo['data']['omiseTokenCustomer'];
            } else {
                drupal_set_message(t('Sorry, something went wrong: ' . $customerInfo['data']['message']), 'error');
            }

        }

        // If there is no value set but we have ocid parameter (coming from Omise submission)
        if ((empty($itemValue) || !$itemValue['target_id']) && isset($_GET['ocid'])) {
            //$superEntity = $form_state->getFormObject()->getEntity();
            //$superEntity->set($fieldName,['target_id'=>$_GET['ocid']]);
            //$superEntity->save();
            $itemValue['target_id'] = $_GET['ocid'];
        }
        // Still empty item value... try to get it from form_state
        if ((empty($itemValue) || !$itemValue['target_id'])) {
            $completionData = $form_state->getCompleteForm();
            if ($completionData) {
                $itemValue['target_id'] = $completionData[$fieldName]['widget'][0]['target_id']['#default_value'];
            }
        }
        // Still empty item value... try to get it from user input
        if ((empty($itemValue) || !$itemValue['target_id'])) {
            $userInput = $form_state->getUserInput();
            if ($userInput && isset($userInput['field_credit_card'][0]['target_id'])) {
                $itemValue['target_id'] = $userInput['field_credit_card'][0]['target_id'];
            }
        }

        // Field value is set, it can be Drupal UID (user logged in) or Omise customer ID (user not logged in)
        if (!empty($itemValue) && $itemValue['target_id']) {
            $omiseCustomer = null;
            $omiseCustomerToken = null;
            $cardsData = [];
            // Check value type
            if (is_numeric($itemValue['target_id'])) {
                // The value is a Drupal customer ID
                // User is logged in
                $drupalOmiseCustomer = OmiseCustomer::load($itemValue['target_id']);


            } else {
                // The value is not a Drupal customer ID, but a Omise customer "cust_XXXXXXXXXXXXXXX"
                // User is not logged in
                // We create customer without uid
                $drupalOmiseCustomer=OmiseCustomer::create([
                    'omise_id' => $itemValue['target_id']
                ]);
                $drupalOmiseCustomer->save();
                $itemValue['target_id']=$drupalOmiseCustomer->id();
            }
            if ($drupalOmiseCustomer) {
                $omiseCustomerToken = $drupalOmiseCustomer->get('omise_id')->value;

            }
            try {

                $omiseCustomer = \OmiseCustomer::retrieve($omiseCustomerToken);

            } catch (\OmiseException $e) {
                drupal_set_message(
                    t("There was an error trying to get Omise customer: @message", ['@message' => $e->getMessage()]),
                    "error"
                );
                \Drupal::logger('omise_payment')->error(
                    t("Error trying to get customer with data=@data :: @message",
                        [
                            '@data' => $omiseCustomerToken,
                            '@message' => $e->getMessage()
                        ]
                    )
                );
            }
            if ($omiseCustomer) {
                $cards = $omiseCustomer->cards();
                $cardsData = $cards['data'];
                // Add logo
                foreach ($cardsData as $key => $card) {
                    $cardsData[$key]['extraInfo'] = $omise->getCardInfo($cardsData[$key]['brand']);
                }
            } else {
                \Drupal::logger('omise_payment')->error(
                    t("Error retrieving Omise customer with token=@token",
                        [
                            '@token' => $omiseCustomerToken
                        ]
                    )
                );
            }
            // Build cards list
            $renderOutput = array(
                '#theme' => 'omise_cards_list',
                '#cards' => $cardsData,
                '#modulePath' => $modulePath,
                '#drupalOmiseCustomer' => $itemValue['target_id']
            );
            // If has default credit card in field
            $form['omise_customer']['info']['#markup'] = drupal_render($renderOutput);
            $element['target_id']['#default_value'] = $itemValue['target_id'];
            $element['target_id']['#value'] = $itemValue['target_id'];

            if (empty($cardsData)) {
                // Customer exists but no cards found
                $this->omiseController->addOmiseCardWidget($form, 'omise_customer');
            } else {
                // Add remove cards behaviours
                $form['#attached']['drupalSettings']['commerceOmise']['removeCardCallback'] =
                    Url::fromRoute('omise_payment.ajax_remove_card')->toString();
                $form['#attached']['library'][] = 'omise_payment/form';
            }


        } else {
            // Empty value so we add widget
            // Check get and check that Omise public key is set
            $omisePublicKey = $this->omiseController->getPublicKey();
            $omisewithJS = $this->omiseController->requiresJS();
            if (!$omisePublicKey) {
                $form['omise_customer']['info'] = [
                    '#markup' => t('You can not add credit cards due to Omise gateway is not properly configured. Please, contact administrator')
                ];
            } else {
                // Build cards panel with info
                $renderOutput = array(
                    '#theme' => 'omise_cards_list',
                    '#cards' => [],
                    '#modulePath' => $modulePath,
                );
                // If has default credit card in field
                $form['omise_customer']['info']['#markup'] = drupal_render($renderOutput);
                // Add Omise card collection add card always (even if is singleElement)
                if (!isset($form['omise_widget']['add_more'])) {
                    $this->omiseController->addOmiseCardWidget($form, 'omise_customer');

                }
            }
            // Just add classes to make it work with Omise library
            $element['target_id']['#attributes']['class'][] = 'omise-token';
            $element['target_id']['#attributes']['class'][] = 'with-card';

        }


        return $element;
    }


    /**
     * {@inheritdoc}
     */
    public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
    {
        foreach ($values as $key => $value) {
            // The entity_autocomplete form element returns an array when an entity
            // was "autocreated", so we need to move it up a level.
            if (is_array($value['target_id'])) {
                unset($values[$key]['target_id']);
                $values[$key] += $value['target_id'];
            } elseif ($value['target_id'] == "") {
                // When default value is empty we remove it
                unset($values[$key]);
            }
        }

        return $values;
    }


}