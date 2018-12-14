<?php

namespace Drupal\omise_payment\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;


use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
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
     * Special handling to create form elements for multiple values.
     *
     * Handles generic features for multiple fields:
     * - number of widgets
     * - AHAH-'add more' button
     * - table display and drag-n-drop value reordering
     */
    protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state)
    {
        $field_name = $this->fieldDefinition->getName();
        $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
        $parents = $form['#parents'];

        // Determine the number of widgets to display.
        switch ($cardinality) {
            case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
                $field_state = static::getWidgetState($parents, $field_name, $form_state);
                $max = $field_state['items_count'];
                $is_multiple = true;
                break;

            default:
                $max = $cardinality - 1;
                $is_multiple = ($cardinality > 1);
                break;
        }

        $title = $this->fieldDefinition->getLabel();
        $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

        $elements = [];

        for ($delta = 0; $delta <= $max; $delta++) {
            // Add a new empty item if it doesn't exist yet at this delta.
            if (!isset($items[$delta])) {
                $items->appendItem();
            }

            // For multiple fields, title and description are handled by the wrapping
            // table.
            if ($is_multiple) {
                $element = [
                  '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
                  '#title_display' => 'invisible',
                  '#description' => '',
                ];
            } else {
                $element = [
                  '#title' => $title,
                  '#title_display' => 'before',
                  '#description' => $description,
                ];
            }

            $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

            if ($element) {
                // Input field for the delta (drag-n-drop reordering).
                if ($is_multiple) {
                    $elements['#prefix'] = '<div class="omise-payment-cards-wrapper">';
                    $elements['#suffix'] = '</div>';

                }

                $elements[$delta] = $element;
            }
        }

        return $elements;
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

        // Make hidden element
        $element['target_id'] = $element + [
            '#type' => 'hidden',
            '#size' => $this->getSetting('size'),
              // Entity reference field items are handling validation themselves via
              // the 'ValidReference' constraint.
            '#validate_reference' => false,
            '#default_value' => 0,
            '#placeholder' => $this->getSetting('placeholder'),
            '#maxlength' => $this->getFieldSetting('max_length'),
          ];

        $form['#attributes']['class'][] = "omise-customer";
        $omise = new OmiseTransactionController();
        $itemValue = $items->get($delta)->getValue();
        $fieldName=$this->fieldDefinition->get('field_name');
        $modulePath = file_create_url(drupal_get_path("module", "omise_payment") . '/images/cards/');
        $form['omise_customer'] = [
          '#type' => 'fieldgroup',
          '#title' => t('Payment card information')
        ];

        if (isset($_POST['omiseToken']) && $_POST['omiseToken'] != '') {
            $_POST['email'] = \Drupal::currentUser()->getEmail();
            $data = $omise->getTokenCheckout();
            $customerInfo = json_decode($data->getContent(), true);
            if ($customerInfo['result'] == 'success') {
                $itemValue['target_id'] = $customerInfo['data']['drupalOmiseCustomerId'];
                //$itemValue['omise_default_card'] = $customerInfo['data']['omiseTokenCard'];
                drupal_set_message(t("Please, don't forget to save your chages"), 'warning');
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
        // Still empty item value... get from form_state
        if ((empty($itemValue) || !$itemValue['target_id']) ) {
            $completionData = $form_state->getCompleteForm();
            if ($completionData) {
                $itemValue['target_id'] = $completionData[$fieldName]['widget'][0]['target_id']['#default_value'];
            }
        }


        if (!empty($itemValue) && $itemValue['target_id']) {
            // @TODO Cache omise callbacks?
            // We have a customer ID
            $cardInfo = "";
            $cardsData = [];
            $drupalOmiseCustomer = OmiseCustomer::load($itemValue['target_id']);
            if ($drupalOmiseCustomer) {
                try {
                    $omiseCustomerToken = $drupalOmiseCustomer->get('omise_id')->value;
                    $omiseCustomer = \OmiseCustomer::retrieve($omiseCustomerToken);
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

}