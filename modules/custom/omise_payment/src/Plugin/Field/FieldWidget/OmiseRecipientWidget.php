<?php

namespace Drupal\omise_payment\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\omise_payment\Controller\OmiseTransactionController;


/**
 * Plugin implementation of the 'string_textfield' widget.
 *
 * @FieldWidget(
 *   id = "omise_recipient_textfield",
 *   label = @Translation("Omise Recipient Widget"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class OmiseRecipientWidget extends StringTextfieldWidget
{

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

        $brandBanks = [
            '-' => t('-Select-'),
            'bbl' => t('Bangkok Bank'),
            'kbank' => t('Kasikornbank'),
            'ktb' => t('Krungthai Bank'),
            'tmb' => t('TMB Bank'),
            'scb' => t('Siam Commercial Bank'),
            'citi' => t('Citibank'),
            'cimb' => t('CIMB Thai Bank'),
            'uob' => t('United Overseas Bank (Thai)'),
            'bay' => t('Bank of Ayudhya (Krungsri)'),
            'tbank' => t('Thanachart Bank'),
            'ibank' => t('Islamic Bank of Thailand'),
            'lhb' => t('Land and Houses Bank')
        ];

        $entity = $items->getEntity();
        $omise = new OmiseTransactionController();
        $value = isset($items[$delta]->value) ? $items[$delta]->value : null;

        // Do hide the rec1ipient ID
        $element['value'] = $element + [
                '#type' => 'hidden',
                '#default_value' => $value,
                '#size' => $this->getSetting('size'),
                '#placeholder' => $this->getSetting('placeholder'),
                '#maxlength' => $this->getFieldSetting('max_length'),
                '#attributes' => ['class' => ['js-text-full', 'text-full']],
            ];

        $form['#attributes']['class'][] = "omise-recipient";
        $form['#attributes']['omise-recipient-field'] = $this->fieldDefinition->get('field_name');

        // Add remove bank account behaviours
        $form['#attached']['drupalSettings']['commerceOmise']['removeBankAccountCallback'] =
            Url::fromRoute('omise_payment.ajax_remove_bank_account')->toString();
        $form['#attached']['library'][] = 'omise_payment/form';

        if ($value) {
            // There is already a bank account linked
            // Call to Omise to retrieve data and show
            $recipient = $omise->getRecipient($value);
            if ($recipient) {
                $bankAccountData = $recipient['bank_account'];
                // Add recipient Id
                $bankAccountData['id'] = $recipient['id'];
                $bankAccountData['entityType'] = $entity->getEntityTypeId();
                $bankAccountData['entityId'] = $entity->id();
                $bankAccountData['entityField'] = $this->fieldDefinition->get('field_name');
                $bankAccountData['verified'] = $recipient['verified'];
                $bankAccountData['active'] = $recipient['active'];
                $bankAccountData['brand'] =$omise->getBrandInfo($recipient['bank_account']['brand']);

                // Build cards list
                $renderOutput = array(
                    '#theme' => 'omise_bank_account',
                    '#bankAccount' => $bankAccountData
                );
                $element['bankAccountInfo'] = [
                    '#markup' => drupal_render($renderOutput)
                ];

            }
        } else {
            // No value
            // Show custom widget to add bank account info
            $element['bankAccount'] = [
                '#type' => 'fieldset',
                '#title' => t('Bank details for payments and transfers'),
                '#required' => $element['#required'],
                '#description' => t('These details are held securely in Tavolos in order to pay you any money due at the end of the month.')
            ];
            $element['bankAccount']['bankAccountBrand'] = [
                '#type' => 'select',
                '#title' => t('Bank name'),
                '#options' => $brandBanks
            ];
            $element['bankAccount']['bankAccountName'] = [
                '#type' => 'textfield',
                '#title' => t('Account name')

            ];
            $element['bankAccount']['bankAccountNumber'] = [
                '#type' => 'textfield',
                '#title' => t('Account number'),

            ];

        }


        // Get input and fetch values for a submitted form with bankAccount info
        $userInput = $form_state->getUserInput();
        // Bank account field is sent on the form
        foreach ($userInput as $field => $input) {
            if (isset($input[0]['bankAccount']) && (
                $input[0]['bankAccount']['bankAccountBrand'] != '-' ||
                $input[0]['bankAccount']['bankAccountNumber'] != '' ||
                $input[0]['bankAccount']['bankAccountName'] != ''
                )
            ) {
                // If there are some data
                // Try recipient creation
                $data = [
                    'name' => $entity->label(),
                    'description' => t('Restaurant on Tavolos'),
                    //'email' => 'tester@omise.co',
                    'type' => 'individual',
                    //'tax_id' => '',
                    'bank_account' => array(
                        'brand' => $input[0]['bankAccount']['bankAccountBrand'],
                        'number' => $input[0]['bankAccount']['bankAccountNumber'],
                        'name' => $input[0]['bankAccount']['bankAccountName']
                    )
                ];
                $recipient = $omise->createRecipient($data);
                if ($recipient) {
                    $element['value']['#value'] = $recipient;
                } else {
                    // Error message is shown from Omise Controller
                    $form_state->setErrorByName($field);
                }
            }
        }
        return $element;
    }

}
