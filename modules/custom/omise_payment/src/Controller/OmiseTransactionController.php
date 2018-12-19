<?php

namespace Drupal\omise_payment\Controller;


use Drupal\omise_payment\Entity\OmiseCustomer;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountProxy;

class OmiseTransactionController
{
    protected $omisePublicKey;
    protected $omiseSecretKey;
    protected $omiseIntegrationWithJS;
    protected $omiseAuthFee;

    public function __construct()
    {

        $this->omisePublicKey = \Drupal::config('omise_payment.settings')
            ->get('public_key');
        $this->omiseSecretKey = \Drupal::config('omise_payment.settings')
            ->get('secret_key');
        $this->omiseIntegrationWithJS = \Drupal::config('omise_payment.settings')
            ->get('use_js');
        $this->omiseAuthFee = \Drupal::config('omise_payment.settings')
            ->get('auth_fee');

        // Set keys for Omise library
        // Omise keys.
        if (!defined('OMISE_PUBLIC_KEY')) {
            define('OMISE_PUBLIC_KEY', $this->omisePublicKey);
        }
        if (!defined('OMISE_SECRET_KEY')) {
            define('OMISE_SECRET_KEY', $this->omiseSecretKey);
        }
    }

    public function getPublicKey()
    {
        return $this->omisePublicKey;
    }

    public function getAuthFee()
    {
        return $this->omiseAuthFee;
    }

    public function requiresJS()
    {
        return $this->omiseIntegrationWithJS;
    }

    public function addOmiseCardWidget(&$form, $fieldset = 'omise_widget')
    {

        $omisewithJS = $this->requiresJS();

        if (!isset($form[$fieldset])) {
            $form[$fieldset] = [
                '#type' => 'fieldgroup',
                '#title' => t('Payment cards')
            ];
        }

        // Always add omise_token hidden field to get new token
        $form[$fieldset]['omise_token'] = [
            '#type' => 'hidden',
            '#attributes' => [
                'class' => [
                    'omise-token',
                    'with-card'
                ]
            ],
        ];

        $form['#attached']['drupalSettings']['commerceOmise'] = [
            'publicKey' => $this->getPublicKey(),
            'useJS' => $omisewithJS,
            'frameLabel' => t('Validate card'),
            'buttonLabel' => t('Validate your card')
        ];
        // Check if admin has sett auth fee othen than default option (zero)
        $authFeeAmount = $this->getAuthFee() * 100;
        if ($authFeeAmount > 0) {
            // Setting amount the card auth process will charge and refund an amount
            $form['#attached']['drupalSettings']['commerceOmise']['amount'] = $authFeeAmount;
        }


        if ($omisewithJS) {
            $form[$fieldset]['add_more'] = [
                '#type' => 'button',
                '#value' => t('Add payment card'),
                '#attributes' => [
                    'id' => 'omise-card-button'
                ]
            ];
            $form['#attached']['library'][] = 'omise_payment/form';
        } else {

            $form[$fieldset]['add_more'] = [
                '#type' => 'link',
                '#weight' => 50,
                '#title' => t('Add Payment Card'),
                '#url' => Url::fromRoute('omise_payment.modal_collect_card_form'),
                '#attributes' => [
                    'class' => [
                        'omise-trigger',
                        'use-ajax',
                        'button',
                    ],
                ],
                // Attach library which will handle Omise response
                '#attached' => [
                    'library' => [
                        'omise_payment/form'
                    ]
                ]
            ];
            // Attach the library for pop-up dialogs/modals.
            $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
        }
    }

    /**
     * Create a customer from token attached to a card.
     *
     * @param $tokenCard
     * @param null $email
     * @param null $metadata
     * @return mixed|null
     */
    public function createCustomerWithCard($tokenCard, $email = null, $metadata = null)
    {
        $customer = null;

        try {
            $data = array(
                'card' => $tokenCard
            );
            if ($email) {
                $data['email'] = $email;
            }
            if ($metadata) {
                $data['metadata'] = $metadata;
            }

            // @TODO - check valid card with authCardwithCharge??

            // Create Customer
            $customer = \OmiseCustomer::create($data);

            // Check status response
            if (!isset($customer['id'])) {
                drupal_set_message("There was a problem creating new Omise customer. Please, try again later",
                    'error');
            }

        } catch (\OmiseException $e) {
            drupal_set_message(
                t("There was an error trying to create customer: @message", ['@message' => $e->getMessage()]),
                "error"
            );
            \Drupal::logger('omise_payment')->error(
                t("Error trying to create customer with token=@token :: @message",
                    [
                        '@token' => $tokenCard,
                        '@message' => $e->getMessage()
                    ]
                )
            );
        }
        return $customer;

    }

    /**
     * Create a recipient from bank account data.
     *
     * @param $data
     * @return mixed|null
     */
    public function createRecipient($data)
    {
        try {
            $recipientId = null;

            // Create Recipient
            $recipient = \OmiseRecipient::create($data);

            // Check status response
            if (!isset($recipient['id'])) {
                drupal_set_message("There was a problem creating new Omise bank account. Please, try again later",
                    'error');
            } else {
                $recipientId = $recipient['id'];
            }

        } catch (\OmiseException $e) {
            drupal_set_message(
                t("There was an error trying to create bank account: @message", ['@message' => $e->getMessage()]),
                "error"
            );
            \Drupal::logger('omise_payment')->error(
                t("Error trying to create recipient with data=@data :: @message",
                    [
                        '@data' => explode(";", $data),
                        '@message' => $e->getMessage()
                    ]
                )
            );
        }
        return $recipientId;

    }

    /**
     * This function will charge and refund the amount into the customer/card associated to the tokens passed as argument
     * Used for validating cards checking that they are chargable
     *
     * @param $tokenCard
     * @param null $amount
     * @param string $currency
     * @return mixed|null
     */
    public function authCardWithChargeRefund($tokenCustomer, $tokenCard = null, $amount = null, $currency = 'THB')
    {
        $authCard = null;

        if (!$amount) {
            $amount = $this->getAuthFee();
        }
        try {
            // Do charge
            $chargeData = [
                'amount' => $amount * 100,
                'currency' => $currency,
                'customer' => $tokenCustomer,
                'reversible' => true
            ];
            if ($tokenCard) {
                $chargeData['card'] = $tokenCard;
            }
            $charge = \OmiseCharge::create($chargeData);

            // Check status response
            if ($charge['status'] == 'successful') {
                // Charge was succesfully done
                // Do Refund
                $refunds = $charge->refunds();
                $createRefund = $refunds->create(array('amount' => $amount * 100));
                $refund = $refunds->retrieve($createRefund['id']);
                $authCard = $charge['card'];
                if (!$refund) {
                    drupal_set_message("There was a problem trying to refund the authorization fee. Please, contact the administrator",
                        'warning');
                    \Drupal::logger('omise_payment')->error(
                        t("Error trying to refund @tokenRefund of charge @tokenCharge with token card @tokenCard",
                            [
                                '@tokenRefund' => $createRefund['id'],
                                '@tokenCharge' => $charge['id'],
                                '@tokenCard' => $tokenCard,
                            ]
                        )
                    );
                }

            } else {
                drupal_set_message("There was a problem charging your card for validation. Please, try again later",
                    'error');
            }
        } catch (\OmiseException $e) {
            drupal_set_message(
                t("There was an error trying to validate the card: @message", [
                    '@message' => $e->getMessage()
                ]),
                "error"
            );
            \Drupal::logger('omise_payment')->error(
                t("Error trying to auth card with charge, token=@token: @message",
                    [
                        '@token' => $tokenCard,
                        '@message' => $e->getMessage()
                    ]
                )
            );
        }
        return $authCard;

    }

    /**
     * Execute a charge of amount on customer account
     *
     * @param $customer
     * @param $amount
     * @param string $currency
     * @return null|\OmiseCharge
     */
    public function doRefundableCharge($customer, $amount, $card = null, $currency = 'THB')
    {
        $charge = null;
        $chargeId = null;

        try {
            // Do charge
            $chargeData = array(
                // @todo Ensure amount matches Omise API spec. Amount must be integer.
                //   Currently the variable comes from the standalone-widget and it
                //   could be anything (int, float, string etc).
                'amount' => $amount * 100,
                'customer' => $customer,
                'currency' => $currency,
                'refundable' => true
            );
            if ($card) {
                $chargeData['card'] = $card;
            }
            $charge = \OmiseCharge::create($chargeData);

            // Check status response
            if ($charge['status'] == 'successful') {
                // Charge was succesfully done
                $chargeId = $charge['id'];
                \Drupal::logger('omise_payment')->info(t('Omise charged succesfully @chargeId',
                    ['@chargeId' => $chargeId]));
            } else {
                $failureMsg = isset($charge['failure_message']) ? $charge['failure_message'] : "Unknown failure";
                \Drupal::logger('omise_payment')->error(
                    t('Omise charge failed with customer=@customer, charge=@chargeId :: @msg',
                        [
                            '@customer' => $customer,
                            '@chargeId' => $charge['id'],
                            '@msg' => $failureMsg,
                        ]
                    )
                );
            }
        } catch (\OmiseException $e) {
            \Drupal::logger('omise_payment')->error(
                t('Error on doRefundableCharge with customer=@customer, amount=@amount, card=@card, charge=@chargeId :: @message',
                    [
                        '@customer' => $customer,
                        '@amount' => $amount,
                        '@card' => $card,
                        '@chargeId' => $chargeId,
                        '@message' => $e->getMessage(),
                    ]
                )
            );
            $charge['status'] = "error";
            $charge['failure_message'] = $e->getMessage();
        }
        return $charge;

    }

    /**
     * Execute a transfer of amount on customer account
     *
     * @param $recipient
     * @param $amount
     * @param string $currency
     * @return null|\OmiseCharge
     */
    public function doTransfer($recipient, $amount, $currency = 'THB')
    {
        $result = null;

        try {
            // Do transfer
            $transfer = \OmiseTransfer::create([
                'amount' => $amount * 100,
                'recipient' => $recipient
            ]);

            // Check status response
            if ($transfer['sent'] && $transfer['paid']) {
                // Transfer was succesfully done
                $transactionId = $transfer['id'];
                \Drupal::logger('omise_payment')->info(t('Omise transfer succesfully @transactionId',
                    ['@transactionId' => $transactionId]));
                $result = $transactionId;
            } else {
                // The transfer was not sent and paid. Probably because the bank account was not verified
                // Delete transfer
                $transfer->destroy();
                \Drupal::logger('omise_payment')->error(
                    t('Omise transfer failed with recipient=@customer, transaction=@transactionId, sent=@sent, paid=@paid. Proceed to delete transfer',
                        [
                            '@customer' => $recipient,
                            '@transactionId' => $transfer['id'],
                            '@sent' => $transfer['sent'],
                            '@paid' => $transfer['paid'],
                        ]
                    )
                );
            }
        } catch (\OmiseException $e) {
            \Drupal::logger('omise_payment')->error(
                t('Error on doTransfer with recipient=@customer :: @message',
                    [
                        '@customer' => $recipient,
                        '@message' => $e->getMessage(),
                    ]
                )
            );
        }
        return $result;

    }

    /**
     * Get omise customer from drupal user
     * @param $uid
     * @return mixed
     */
    public function getOmiseCustomerByUid($uid)
    {
        // Build generic query
        $connection = Database::getConnection();
        $query = $connection->select('omise_customer_field_data', 'oc');

        $query->condition('oc.user_id', $uid);

        $query->addField('oc', 'omise_id');
        $query->addField('oc', 'id');
        $customer = $query->execute()->fetchAssoc();

        return $customer;
    }


    /**
     * Omise Callback for checkout endpoint. Required for collecting cards or charging amount to a card
     * If is set parameter 'redirect' in $_POST the function will redirect with GET parameter 'ocid' (Omise Customer Id)
     * If is not set 'redirect' then will response with JSON data Omise Customer Id
     *
     * @param Request $request
     *
     * @return JsonResponse|null
     */
    function getTokenCheckout(Request $request = null)
    {
        try {
            $customerId=null;
            $tokenCard=null;
            if (isset($_POST['omiseToken'])) {
                $tokenCard = $_POST['omiseToken'];
                // Unset parameter to avoid multiple executions refreshing page
                unset($_POST['omiseToken']);
                $email = isset($_POST['email']) ? $_POST['email'] : null;
                $amount = isset($_POST['amount']) ? $_POST['amount'] : null;
                $customer = $this->createCustomerWithCard($tokenCard, $email);
                if ($customer && isset($customer['id'])) {
                    $customerId = $customer['id'];
                } else {

                    throw new \OmiseException(t('Error creating Omise customer. Please, contact administrator'));
                }
            }



            $response = [
                'result' => 'success',
                'data' => [
                    'omiseTokenCustomer' => $customerId,
                    'omiseTokenCard' => isset($customer['default_card']) ? $customer['default_card'] : $tokenCard
                ]
            ];


            /*

            // Load data from request
            $customerId = null;
            $amount = null;
            $data = null;
            if (isset($_POST['omiseToken'])) {
                $tokenCard = $_POST['omiseToken'];
                // Unset parameter to avoid multiple executions refreshing page
                unset($_POST['omiseToken']);
                $email = isset(isset($_POST['email']))?$_POST['email']:null;
                $amount = isset($_POST['amount']) ? $_POST['amount'] : null;
            }

            // Do checkout action
            \Drupal::logger('omise_payment')->info(
                t("Doing checkout with data: token=@token, email=@email, amount=@amount",
                    [
                        '@token' => $tokenCard,
                        '@email' => $email,
                        '@amount' => $amount
                    ]
                )
            );
            if ($tokenCard) {
                $omiseDrupalCustomer=null;
                if ($email) {
                    // Check if exists user in Drupal from email
                    $drupalUser = user_load_by_mail($email);


                    if (!$drupalUser) {
                        // Doesn't exist in Drupal
                        // Create user
                        $drupalUser = User::create([
                            'name' => $email,
                            'mail' => $email,
                            'pass' => '12345', // @TODO randomize?
                            'status' => 1
                        ]);
                        $drupalUser->save();
                    }

                    // Check if user has already a customer in Omise
                    $omiseDrupalCustomer = $this->getOmiseCustomerByUid($drupalUser->id());
                }
                if (!$omiseDrupalCustomer || empty($omiseDrupalCustomer)) {
                    // Drupal user has not customer in Omise
                    // Create customer in Omise and in Drupal
                    // @TODO Maybe it doesnt exist in Drupal but it does in Omise... implement webhook?
                    $customer = $this->createCustomerWithCard($tokenCard, $email);
                    if ($customer && isset($customer['id'])) {
                        $omiseDrupalCustomer = OmiseCustomer::create([
                            'user_id' => $drupalUser->id(),
                            'omise_id' => $customer['id']
                        ]);
                        $omiseDrupalCustomer->save();
                        $omiseDrupalCustomerId = $omiseDrupalCustomer->id();
                        $customerId=$customer['id'];
                    } else {

                        throw new \OmiseException(t('Error creating Omise customer. Please, contact administrator'));
                    }
                } else {
                    // Drupal user has a customer in Omise attached
                    $omiseDrupalCustomerId = $omiseDrupalCustomer['id'];
                    // Retrieve Omise customer
                    $customer = \OmiseCustomer::retrieve($omiseDrupalCustomer['omise_id']);
                    $customerId = $customer['id'];

                    // There is no way to check if card already exists
                    // We always add as new card
                    $customer->update(['card' => $tokenCard]);
                    // Reload customer (not sure if required)
                    $customer = \OmiseCustomer::retrieve($omiseDrupalCustomer['omise_id']);
                    // We need to get latest card id added
                    // So we retrieve list of cards and get latest
                    // Set default card
                    if ($customer) {
                        $cards = $customer->cards();
                        if ($cards && isset($cards['data']) && (!empty($cards['data']))) {
                            $customerCards=$cards['data'];
                            $latestCard = end($customerCards);
                            $customer = $customer->update(['default_card' => $latestCard['id']]);
                        }
                    }
                }

                $response = [
                    'result' => 'success',
                    'data' => [
                        'drupalOmiseCustomerId' => $omiseDrupalCustomerId,
                        'omiseTokenCustomer' => $customerId,
                        'omiseTokenCard' => isset($customer['default_card']) ? $customer['default_card'] : $tokenCard
                    ]
                ];

                // Do charge if amount is sent

                $charge = null;
                if ($amount) {
                    $charge = $this->doRefundableCharge($customerId, $amount);
                    if ($charge) {
                        // When there is charge we add info to response
                        $response['data']['chargeAmount'] = $amount;
                        $response['data']['chargeStatus'] = $charge['status'];
                        $response['data']['chargeFailure'] = $charge['failure_message'];

                        // Add Omise IDs as query parameters so that the data
                        // can be passed on to ReservationController.
                        $omise_ids['chargeId'] = $charge['id'];
                        $omise_ids['chargeTransactionId'] = $charge['transaction'];
                        $omise_ids['chargeCustomerId'] = $charge['customer'];
                        $omise_ids['chargeCardId'] = (!empty($charge['card']['id'])) ? $charge['card']['id'] : null;
                        $request->query->add($omise_ids);
                    }
                }



                // This aims to skip double callback checkout+reservation
                // @FIXME shouldn't be this way in order to keep Omise module independent
                // but due to widget issues we are patching it as follows
                if (isset($data['restaurantId']) && isset($data['startDate']) && isset($data['startTime'])) {
                    // We presume reservation data is coming
                    // Create reservation after checkout

                    // Add required parameters
                    $extraParameters = [
                        'customerOmiseId' => $omiseDrupalCustomerId,
                        'cardOmiseToken' => isset($customer['default_card']) ? $customer['default_card'] : $tokenCard
                    ];
                    // Add Omise parameters
                    $extraParameters+=$response['data'];

                    // When no charge is required ($amount==null|0) or there is charge==success
                    // Amount may be 0 when CCG=true and PREPAID=false
                    if (!$amount || ($charge && $charge['status'] == 'successful')) {
                        $request->query->add($extraParameters);

                        // Create reservation

                        $rc = new ReservationController(new AccountProxy(), Database::getConnection());
                        return $rc->post($data['restaurantId'], $request);


                    } else {
                        if ($charge) {
                            // Charge had error
                            $response = [
                                'result' => 'error',
                                'data' => [
                                    'message' => $charge['failure_message']
                                ]
                            ];
                        }
                    }

                }

            } else {
                $response = [
                    'result' => 'error',
                    'data' => [
                        'message' => t("The card couldn't be validated")
                    ]
                ];
            }


            if (isset($_POST['redirect'])) {
                $queryParameters = [
                    'ocid' => $omiseDrupalCustomerId
                ];
                if ($_POST['redirect'] == 'entity.node.edit_form') {
                    $queryParameters['node'] = $_POST['nodeId'];
                }
                $url = Url::fromRoute($_POST['redirect'], $queryParameters)->toString();
                $response = new RedirectResponse($url);
                $response->send();
                return null;
            }
*/
            return new JsonResponse($response);

        } catch
        (\OmiseException $e) {

            \Drupal::logger('omise_payment')->error(
                t("Error on AJAX callback trying to create customer with token=@token: @message",
                    [
                        '@token' => $tokenCard,
                        '@message' => $e->getMessage()
                    ]
                )
            );
            $response = [
                'result' => 'error',
                'data' => [
                    'message' => $e->getMessage()
                ]
            ];
            return new JsonResponse($response);
        }
    }

    /**
     * AJAX callback for deleting cards on customer
     * @return JsonResponse
     */
    public function removeCard()
    {
        try {
            if (isset($_POST['customer']) && isset($_POST['card'])) {

                // Load drupal omise customer
                $omiseDrupalCustomer = OmiseCustomer::load($_POST['customer']);


                if ($omiseDrupalCustomer) {
                    $customer = \OmiseCustomer::retrieve($omiseDrupalCustomer->getOmiseID());
                    $card = $customer->getCards()->retrieve($_POST['card']);
                    $card->destroy();

                    if ($card->isDestroyed()) {
                        $response = [
                            'result' => 'success',
                            'data' => [
                                'message' => t("Card succesfully removed")
                            ]
                        ];
                    } else {
                        $response = [
                            'result' => 'error',
                            'data' => [
                                'message' => t("Card not removed")
                            ]
                        ];
                    }
                } else {

                    $response = [
                        'result' => 'error',
                        'data' => [
                            'message' => t("Omise Customer not found")
                        ]
                    ];
                }


            } else {
                $response = [
                    'result' => 'error',
                    'data' => [
                        'message' => t("Data sent not valid")
                    ]
                ];
            }


            return new JsonResponse($response);
        } catch
        (\OmiseException $e) {

            \Drupal::logger('omise_payment')->error(
                t("Error on AJAX callback trying to remove card with token=@token: @message",
                    [
                        '@token' => $_POST['card'],
                        '@message' => $e->getMessage()
                    ]
                )
            );
            $response = [
                'result' => 'error',
                'data' => [
                    'message' => $e->getMessage()
                ]
            ];
            return new JsonResponse($response);
        }
    }

    /**
     * AJAX callback for deleting recipients (bank accounts)
     * @return JsonResponse
     */
    public function removeRecipient()
    {
        $response = null;
        try {
            if (isset($_POST['recipient'])) {

                // Load drupal omise customer
                $recipient = \OmiseRecipient::retrieve($_POST['recipient']);
                $recipient->destroy();
                // Remove field entity from Drupal
                $entity = Node::load($_POST['entityId']); //@TODO - check for entity type
                $entity->set($_POST['entityField'], '');
                $entity->save();

                $response = [
                    'result' => 'success',
                    'data' => [
                        'message' => t("Bank account succesfully removed")
                    ]
                ];

            } else {
                $response = [
                    'result' => 'error',
                    'data' => [
                        'message' => t("Data sent not valid")
                    ]
                ];
            }
            return new JsonResponse($response);
        } catch
        (\OmiseException $e) {

            \Drupal::logger('omise_payment')->error(
                t("Error on AJAX callback trying to remove recipient with id=@token: @message",
                    [
                        '@token' => $_POST['recipient'],
                        '@message' => $e->getMessage()
                    ]
                )
            );
            $response = [
                'result' => 'error',
                'data' => [
                    'message' => $e->getMessage()
                ]
            ];
            return new JsonResponse($response);
        }
    }

    /**
     * Get recipient
     * @param $id
     * @return bool|\OmiseRecipient
     */
    public function getRecipient($id)
    {
        try {
            return \OmiseRecipient::retrieve($id);
        } catch
        (\OmiseException $e) {

            \Drupal::logger('omise_payment')->error(
                t("Error trying to retrieve recipient id=@token: @message",
                    [
                        '@token' => $id,
                        '@message' => $e->getMessage()
                    ]
                )
            );
            drupal_set_message(t('Something went wrong retrieving your bank account info. Please, contact the administrator'),
                'error');
            return false;
        }
    }

    /**
     * Function to build Bank brand info from Omise brand code
     * @param $brand
     * @return array
     */
    public function getBrandInfo($brand)
    {
        $brandData = [];
        $dataFile = file_get_contents(drupal_get_path("module", "omise_payment") . "/banks.json");
        $dataObject = json_decode($dataFile, true);
        if (isset($dataObject['th'][$brand])) {
            $brandData['logo'] = file_create_url(drupal_get_path("module",
                    "omise_payment") . '/images/banks/' . $brand . '.svg');
            $brandData['color'] = $dataObject['th'][$brand]['color'];
            $brandData['officialName'] = $dataObject['th'][$brand]['official_name'];
            $brandData['name'] = $dataObject['th'][$brand]['nice_name'];
        }
        return $brandData;
    }

    /**
     * Function to build Card info from Omise data
     * @param $brand
     * @return array
     */
    public function getCardInfo($brand)
    {
        $brandData = [];
        $brandData['logo'] = file_create_url(drupal_get_path("module",
                "omise_payment") . '/images/cards/' . strtolower($brand) . '.png');

        return $brandData;
    }

    /**
     * Webhook listener for Omise events
     * @param Request $request
     * @return JsonResponse
     */
    public function webhookListener(Request $request)
    {
        $data = json_decode($request->getContent());
        switch ($data->key) {
            case 'customer.destroy':
                // Removed customer on Omise
                // Then remove customer in Drupal
                break;
            case 'recipient.destroy':
                // Removed recipient on Omise
                // Then remove bank account field in Drupal
                break;
            case 'transfer.pay':
                // Transfer pay
                // Notify in Drupal
                break;

        }
        return new JsonResponse(null);
    }
}
