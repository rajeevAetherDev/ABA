<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
App::uses('UtilityController', 'Controller');
App::uses('AppController', 'Controller');
App::uses('CakeTime', 'Utility');
/**
 * CakePHP ReeloApisController
 * @author aetherss
 */
class ReeloApisController extends AppController {

    public function beforeFilter() {
        parent::beforeFilter();
        // Allow users to register and logout.
        $this->Auth->allow();
        $this->layout = false;
        $this->autoRender = false;
        error_reporting(0);
    }
    
    public function index()
    {
        echo "Welcome to PhyloPos";
    }
     

    /**
     * Integration of Reelo customer rewards API
     * POST / GET /reelo_apis/get_customer_loyalty_details
     */
    public function get_customer_loyalty_details() {
        // Read input data
        $user_id = null;
        $customer_mobile = null;
        $table_number = null;
        $number_of_people = null;

        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            $user_id = $jsonData['user_id'] ?? null;
            $customer_mobile = $jsonData['customer_mobile'] ?? null;
            $table_number = $jsonData['table_number'] ?? null;
            $number_of_people = $jsonData['number_of_people'] ?? null;
        }
        
        // Fallback to CakePHP request data (POST) or request query (GET)
        if (empty($user_id)) {
            $user_id = $this->request->data['user_id'] ?? $this->request->query['user_id'] ?? null;
        }
        if (empty($customer_mobile)) {
            $customer_mobile = $this->request->data['customer_mobile'] ?? $this->request->query['customer_mobile'] ?? null;
        }
        if (empty($table_number)) {
            $table_number = $this->request->data['table_number'] ?? $this->request->query['table_number'] ?? null;
        }
        if (empty($number_of_people)) {
            $number_of_people = $this->request->data['number_of_people'] ?? $this->request->query['number_of_people'] ?? null;
        }
        // Validate mandatory parameters
        if (empty($user_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'user_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // if (empty($customer_mobile)) {
        //     $response = array(
        //         'status_code' => 400,
        //         'message' => 'customer_mobile is required'
        //     );
        //     $this->response->type('json');
        //     return json_encode($response);
        // }

        // Clean customer_mobile to ensure it is numeric and exactly 10 digits
        $mobile = preg_replace('/[^0-9]/', '', $customer_mobile);
        
        // Strip country code if prefixed with 91 and length is greater than 10
        if (strlen($mobile) > 10 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, -10);
        }

        // if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        //     $response = array(
        //         'status_code' => 400,
        //         'message' => 'customer_mobile must be a valid 10-digit number'
        //     );
        //     $this->response->type('json');
        //     return json_encode($response);
        // }

        // Fetch customer name from order_customers table where phone matches customer_mobile input parameter
        $this->loadModel('Order_customers');
        $customerObj = $this->Order_customers->find('first', array(
            'conditions' => array(
                'Order_customers.phone' => $customer_mobile,
                'Order_customers.status !=' => 0
            ),
            'fields' => array('Order_customers.name'),
            'order' => array('Order_customers.id' => 'DESC'),
            'recursive' => -1
        ));
        
        // $customerName = !empty($customerObj) ? $customerObj['Order_customers']['name'] : '';
        
        $customerName = '';

        if (!empty($customerObj)) {
        
            $customerName = $customerObj['Order_customers']['name'];
        
        } else {
        
            $this->loadModel('Order_table');
        
            $orderObj = $this->Order_table->find('first', array(
                'conditions' => array(
                    'Order_table.customer_phone' => $customer_mobile
                ),
                'fields' => array('Order_table.customer_name'),
                'order' => array('Order_table.id' => 'DESC'),
                'recursive' => -1
            ));
        
            if (!empty($orderObj)) {
                $customerName = $orderObj['Order_table']['customer_name'];
            }
        }

        // Fetch user (cashier) to get location_id
        $this->loadModel('User');
        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.id' => $user_id,
                'User.status' => 1
            )
        ));

        if (empty($user)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Cashier not found or inactive'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        $location_id = $user['User']['location_id'] ?? null;
        if (empty($location_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'No location assigned to this cashier'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch location details to get reelo_merchant_id
        $this->loadModel('Location');
        $location = $this->Location->find('first', array(
            'conditions' => array(
                'Location.id' => $location_id,
                'Location.status' => 1
            )
        ));

        if (empty($location)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Location not found or inactive'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
        // $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
        // if (empty($reelo_merchant_id)) {
        //     $response = array(
        //         'status_code' => 400,
        //         'message' => 'Reelo Merchant ID not configured for this location'
        //     );
        //     $this->response->type('json');
        //     return json_encode($response);
        // }

        // Prepare payload for Reelo API
        // $url = REELO_BASE_URL . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/rewards/customer";

        // $postData = array(
        //     'customer_key' => (string)$reelo_customer_key,
        //     'merchant_id' => (string)$reelo_merchant_id,
        //     'customer_mobile' => (int)$mobile
        // );

        // if (!empty($table_number)) {
        //     $postData['table_number'] = (string)$table_number;
        // }

        // if (!empty($number_of_people)) {
        //     $postData['number_of_people'] = (string)$number_of_people;
        // }

        // $jsonData = json_encode($postData);
        // Execute API request to Reelo
        // $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     'Content-Type: application/json',
        //     'Accept: application/json'
        // ));
        // curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // $apiResponse = curl_exec($ch);
        // $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // $curl_error = curl_error($ch);
        // curl_close($ch);

        // if ($curl_error) {
        //     $response = array(
        //         'status_code' => 400,
        //         'message' => 'Failed to connect to Reelo API: ' . $curl_error
        //     );
        //     $this->response->type('json');
        //     return json_encode($response);
        // }

        // Decode response to verify it's valid JSON
        // $decodedResponse = json_decode($apiResponse, true);
        
        // if (json_last_error() !== JSON_ERROR_NONE) {
        //     $response = array(
        //         'status_code' => 400,
        //         'message' => 'Invalid JSON response from Reelo'
        //     );
        //     $this->response->type('json');
        //     return json_encode($response);
        // }
        // Handle Reelo API error status codes (e.g. 400, 404)
        // if ($http_code != 200 && $http_code != 201) {
        //     $reeloMessage = $decodedResponse['response']['message'] ?? $decodedResponse['message'] ?? 'Reelo Response Error';
        //     $response = array(
        //         'status_code' => 400,
        //         'message' => $reeloMessage
        //     );
        //     $this->response->type('json');
        //     return json_encode($response);
        // }
        
        
        // If allow_redemption_today parameter is missing in the response, default it to true
        // if (isset($decodedResponse['response']) && is_array($decodedResponse['response'])) {
        //     if (!isset($decodedResponse['response']['allow_redemption_today'])) {
        //         $decodedResponse['response']['allow_redemption_today'] = true;
        //     }
        // }
        
        $decodedResponse = array(
            "status_code" => 200,
            "response" => array(
                "loyalty_points" => 0,
                "allow_redemption_today" => false,
                "min_bill_for_redemption" => 0,
                "total_redeemable_points" => 0
            )
        );

        // Return Reelo response to cashier application
        $decodedResponse['customer_name'] = $customerName;
        $this->response->type('json');
        return json_encode($decodedResponse);
    }

    /**
     * Sync POS store products menu to Reelo
     * POST /reelo_apis/sync_reelo
     */
    public function sync_reelo() {
        $this->autoRender = false;

        // Verify session authorization
        if (!$this->Session->check('Auth.User.id')) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Session expired, please logout and login again');
            echo json_encode($responseArray);
            return;
        }

        // Verify request parameters
        if (!$this->request->is('post') || empty($this->request->data['store_id'])) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Invalid request or missing Store ID');
            echo json_encode($responseArray);
            return;
        }

        $store_id = $this->request->data['store_id'];

        // Load Location details to get reelo_merchant_id
        $this->loadModel('Location');
        $location = $this->Location->find('first', array(
            'conditions' => array(
                'Location.id' => $store_id,
                'Location.status' => 1
            )
        ));

        if (empty($location)) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Store/Location not found or inactive');
            echo json_encode($responseArray);
            return;
        }

        $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
        $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
        if (empty($reelo_merchant_id)) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Reelo Merchant ID not configured for this store');
            echo json_encode($responseArray);
            return;
        }

        
        // Load and fetch products assigned to this location/store
        $this->loadModel('Catalog_product');
        
        $fetchProducts = "SELECT 
            cp.id,
            cp.name as product_name,
            cp.cat_id,
            cp.cat_name,
            cpb.price
            FROM catalog_product cp
            INNER JOIN catalog_product_branches cpb ON cp.id = cpb.product_id
            WHERE cp.status = 1 
            AND cpb.status = 1
            AND cpb.location_id = " . intval($store_id) . "
            ORDER BY cp.id ASC";

        $productResult = $this->Catalog_product->query($fetchProducts);

        if (!$productResult) {
            $responseArray = array('response_code' => 0, 'response_message' => 'Database I/O error: Unable to fetch products');
            echo json_encode($responseArray);
            return;
        }

        $items = array();
        foreach ($productResult as $row) {
            $product_id = (string)$row['cp']['id'];
            $product_name = (string)$row['cp']['product_name'];
            $cat_id = isset($row['cp']['cat_id']) ? (string)$row['cp']['cat_id'] : "";
            $cat_name = isset($row['cp']['cat_name']) ? (string)$row['cp']['cat_name'] : "";
            $price = $row['cpb']['price'] ?? 0;
            
            // Format price to 4 decimal places string
            $formatted_price = number_format((float)$price, 4, '.', '');

            $items[] = array(
                'id' => $product_id,
                'name' => $product_name,
                'category_id' => $cat_id,
                'category' => $cat_name,
                'price' => $formatted_price
            );
        }

        if (empty($items)) {
            $responseArray = array('response_code' => 0, 'response_message' => 'No active products found to sync for this store');
            echo json_encode($responseArray);
            return;
        }

        // Prepare request payload for Reelo Menu Sync API
        $url = REELO_BASE_URL . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/menu";

        $postData = array(
            'customer_key' => (string)$reelo_customer_key,
            'merchant_id' => (string)$reelo_merchant_id,
            'items' => $items
        );

        $jsonData = json_encode($postData);
        // print_r($jsonData); exit;
        // Execute curl call
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $apiResponse = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $responseArray = array(
                'response_code' => 0,
                'response_message' => 'Failed to connect to Reelo API: ' . $curl_error
            );
            echo json_encode($responseArray);
            return;
        }

        $decodedResponse = json_decode($apiResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $responseArray = array(
                'response_code' => 0,
                'response_message' => 'Invalid response from Reelo API',
                'raw_response' => $apiResponse
            );
            echo json_encode($responseArray);
            return;
        }

        // Handle Reelo API response
        if ($http_code == 200) {
            $menuMessage = $decodedResponse['response']['message'] ?? $decodedResponse['message'] ?? 'Menu synced successfully to Reelo';
            
            // Execute payment types API call
            $paymentTypesUrl = REELO_BASE_URL . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/payment-types";
            $paymentTypesData = array(
                'merchant_id' => (string)$reelo_merchant_id,
                'customer_key' => (string)$reelo_customer_key,
                'payment_types' => array(
                    array(
                        'name' => 'Cash',
                        'types' => array()
                    ),
                    array(
                        'name' => 'Card',
                        'types' => array('Visa','MasterCard','RuPay','Maestro','American Express','Other')
                    ),
                    array(
                        'name' => 'UPI',
                        'types' => array()
                    ),
                    array(
                        'name' => 'Online',
                        'types' => array('Zomato Gold','EazyDiner','EazyDiner Offline','Swiggy Dineout','Sodexo','swiggy','zomato')
                    )
                )
            );
            $chPayment = curl_init($paymentTypesUrl);
            curl_setopt($chPayment, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chPayment, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($chPayment, CURLOPT_POSTFIELDS, json_encode($paymentTypesData));
            curl_setopt($chPayment, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'auth-key: ' . REELO_AUTH_KEY,
                'Accept: application/json'
            ));
            curl_setopt($chPayment, CURLOPT_TIMEOUT, 30);

            $paymentApiResponse = curl_exec($chPayment);
            $paymentHttpCode = curl_getinfo($chPayment, CURLINFO_HTTP_CODE);
            $paymentCurlError = curl_error($chPayment);
            curl_close($chPayment);

            if ($paymentCurlError) {
                $responseArray = array(
                    'response_code' => 0,
                    'response_message' => $menuMessage . ', but failed to connect to Reelo Payment Types API: ' . $paymentCurlError
                );
            } else {
                $decodedPaymentResponse = json_decode($paymentApiResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $responseArray = array(
                        'response_code' => 0,
                        'response_message' => $menuMessage . ', but invalid response from Reelo Payment Types API',
                        'raw_response' => $paymentApiResponse
                    );
                } else if ($paymentHttpCode == 200 || $paymentHttpCode == 201) {
                    $paymentMessage = $decodedPaymentResponse['response']['message'] ?? $decodedPaymentResponse['message'] ?? 'payment types synced successfully';
                    if (strtolower($paymentMessage) === 'success') {
                        $paymentMessage = 'payment types synced successfully';
                    }
                    $responseArray = array(
                        'response_code' => 1,
                        'response_message' => $menuMessage . ' and ' . $paymentMessage
                    );
                } else {
                    $paymentMessage = $decodedPaymentResponse['response']['message'] ?? $decodedPaymentResponse['message'] ?? 'failed to sync payment types';
                    $responseArray = array(
                        'response_code' => 0,
                        'response_message' => $menuMessage . ', but Reelo Payment Types API returned HTTP ' . $paymentHttpCode . ': ' . $paymentMessage
                    );
                }
            }
        } else {
            $message = $decodedResponse['response']['message'] ?? $decodedResponse['message'] ?? 'Failed to sync menu to Reelo';
            $responseArray = array(
                'response_code' => 0,
                'response_message' => 'Reelo API returned HTTP ' . $http_code . ': ' . $message
            );
        }

        echo json_encode($responseArray);
        return;
    }
    
    /**
     * Trigger redemption OTP authentication to customer mobile number
     * POST /reelo_apis/authenticate_redemption
     */
    public function authenticate_redemption() {
        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $user_id = null;
        $customer_mobile = null;
        $coupon_code = null;
        $coupon_name = null;
        $points = null;
        $wallet_credits = null;
        $reference_id = null;
        $split_reference_id = null;

        if (is_array($jsonData)) {
            $user_id = $jsonData['user_id'] ?? null;
            $customer_mobile = $jsonData['customer_mobile'] ?? null;
            $coupon_code = $jsonData['coupon_code'] ?? null;
            $points = $jsonData['points'] ?? null;
            $wallet_credits = $jsonData['wallet_credits'] ?? null;
            $reference_id = $jsonData['reference_id'] ?? null;
            $split_reference_id = $jsonData['split_reference_id'] ?? null;
            $coupon_name = $jsonData['coupon_name'] ?? $_REQUEST['coupon_name'] ?? null;
        }

        // Fallback to $_REQUEST (covers GET and POST parameters)
        if (empty($user_id)) {
            $user_id = $_REQUEST['user_id'] ?? null;
        }
        if (empty($customer_mobile)) {
            $customer_mobile = $_REQUEST['customer_mobile'] ?? null;
        }
        if (empty($coupon_code)) {
            $coupon_code = $_REQUEST['coupon_code'] ?? null;
        }
        if (empty($points)) {
            $points = $_REQUEST['points'] ?? null;
        }
        if (empty($wallet_credits)) {
            $wallet_credits = $_REQUEST['wallet_credits'] ?? null;
        }
        if (empty($reference_id)) {
            $reference_id = $_REQUEST['reference_id'] ?? null;
        }
        if (empty($split_reference_id)) {
            $split_reference_id = $_REQUEST['split_reference_id'] ?? null;
        }
        if (empty($coupon_name)) {
            $coupon_name = $_REQUEST['coupon_name'] ?? null;
        }
        
        // $this->response->type('json');
        //     return json_encode(array('status_code' => 400, 'message' => $split_reference_id));
        // Validate mandatory parameters
        if (empty($user_id)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'user_id is required'));
        }
        if (empty($customer_mobile)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'customer_mobile is required'));
        }
        if (empty($coupon_code) && empty($points) && empty($wallet_credits)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'Either coupon_code, points, or wallet_credits must be provided'));
        }

        // Resolve reference_id from split_reference_id if missing
        if (!empty($split_reference_id)) {
            if (empty($reference_id)) {
                $this->loadModel('Order_split_transaction');
                $splitTxnObj = $this->Order_split_transaction->find('first', array(
                    'conditions' => array('Order_split_transaction.split_reference_id' => $split_reference_id,
                    'Order_split_transaction.status !=' => 0),
                    'fields' => array('reference_id'),
                    'recursive' => -1
                ));
                if (!empty($splitTxnObj)) {
                    $reference_id = $splitTxnObj['Order_split_transaction']['reference_id'];
                }
            }
        }

        if (empty($reference_id)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'reference_id is required'));
        }
        
         // Check if discount entry already exists in ReeloDiscount table for this split_reference_id or reference_id
        $this->loadModel('ReeloDiscount');
        $existingDiscCond = array(
            'ReeloDiscount.status !=' => 0
        );
        if (!empty($split_reference_id)) {
            $existingDiscCond['ReeloDiscount.split_reference_id'] = $split_reference_id;
        } else {
            $existingDiscCond['ReeloDiscount.reference_id'] = $reference_id;
        }

        if (!empty($coupon_code)) {
            $existingDiscCond['ReeloDiscount.reelo_discount_type'] = 0;
            $existingDisc = $this->ReeloDiscount->find('first', array(
                'conditions' => $existingDiscCond,
                'recursive' => -1
            ));
            if (!empty($existingDisc)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Coupon discount already applied'));
            }
        } elseif (!empty($points)) {
            $existingDiscCond['ReeloDiscount.reelo_discount_type'] = 1;
            $existingDisc = $this->ReeloDiscount->find('first', array(
                'conditions' => $existingDiscCond,
                'recursive' => -1
            ));
            if (!empty($existingDisc)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Points discount already applied'));
            }
        } elseif (!empty($wallet_credits)) {
            $existingDiscCond['ReeloDiscount.reelo_discount_type'] = 2;
            $existingDisc = $this->ReeloDiscount->find('first', array(
                'conditions' => $existingDiscCond,
                'recursive' => -1
            ));
            if (!empty($existingDisc)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Wallet credits discount already applied'));
            }
        }

        // Clean customer_mobile to ensure it is numeric and exactly 10 digits
        $mobile = preg_replace('/[^0-9]/', '', $customer_mobile);
        
        // Strip country code if prefixed with 91 and length is greater than 10
        if (strlen($mobile) > 10 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, -10);
        }

        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'customer_mobile must be a valid 10-digit number'));
        }

        // Fetch user (cashier) to get location_id
        $this->loadModel('User');
        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.id' => $user_id,
                'User.status' => 1
            )
        ));

        if (empty($user)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'Cashier not found or inactive'));
        }

        $location_id = $user['User']['location_id'] ?? null;
        if (empty($location_id)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'No location assigned to this cashier'));
        }

        // Fetch location details to get reelo_merchant_id
        $this->loadModel('Location');
        $location = $this->Location->find('first', array(
            'conditions' => array(
                'Location.id' => $location_id,
                'Location.status' => 1
            )
        ));

        if (empty($location)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'Location not found or inactive'));
        }

        $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
        $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
        if (empty($reelo_merchant_id)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'Reelo Merchant ID not configured for this location'));
        }

        // Fetch customer loyalty details from Reelo internally
        $this->request->data = array(
            'user_id' => $user_id,
            'customer_mobile' => $customer_mobile
        );
        $loyaltyResponse = $this->get_customer_loyalty_details();
        $loyaltyDetails = json_decode($loyaltyResponse, true);
        
        if (empty($loyaltyDetails) || $loyaltyDetails['status_code'] != 200) {
            $this->response->type('json');
            $errMsg = $loyaltyDetails['message'] ?? 'Failed to get customer loyalty details';
            return json_encode(array('status_code' => 400, 'message' => $errMsg));
        }
        
        $loyaltyData = $loyaltyDetails['response'] ?? array();

        // 1. Validate allow_redemption_today
        $allowRedemptionToday = $loyaltyData['allow_redemption_today'] ?? true;
        if ($allowRedemptionToday === false || strtolower((string)$allowRedemptionToday) === 'false') {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'Redemption is not allowed today'));
        }

        // 2. Fetch order total
        if (!empty($split_reference_id)) {
            $this->loadModel('Order_split_orderhistory');
            $orderTotalResult = $this->Order_split_orderhistory->query("
                SELECT SUM(total_price) AS total_amount
                FROM order_split_orderhistories
                WHERE reference_id = '" . addslashes($reference_id) . "'
                  AND split_reference_id = '" . addslashes($split_reference_id) . "'
                  AND status != 0
            ");
        } else {
            $this->loadModel('Order_orderhistory');
            $orderTotalResult = $this->Order_orderhistory->query("
                SELECT SUM(total_price) AS total_amount
                FROM order_orderhistories
                WHERE reference_id = '" . addslashes($reference_id) . "' AND status != 0
            ");
        }
        $orderTotal = isset($orderTotalResult[0][0]['total_amount']) ? floatval($orderTotalResult[0][0]['total_amount']) : 0;

        // Auto-detect discount type (0 = coupon, 1 = points/cashback, 2 = wallet)
        $reelo_discount_type = null;
        if (!empty($coupon_code)) {
            $reelo_discount_type = 0;
        } elseif (!empty($points)) {
            $reelo_discount_type = 1;
        } elseif (!empty($wallet_credits)) {
            $reelo_discount_type = 2;
        }

        // 3. Validation Logic per Redemption Type
        if ($reelo_discount_type === 0) { // --- Coupon Validation ---
             if (empty($coupon_name)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'coupon_name is required']);
            }
            $coupons = $loyaltyData['coupons'] ?? array();
            $foundCoupon = null;
            foreach ($coupons as $coupon) {
                if (isset($coupon['coupon_code']) && $coupon['coupon_code'] == $coupon_code && isset($coupon['coupon_name']) && $coupon['coupon_name'] == $coupon_name) {
                    $foundCoupon = $coupon;
                    break;
                }
            }
            
            if (empty($foundCoupon)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Invalid coupon code or coupon name'));
            }

            $discount_on = $foundCoupon['discount_on'] ?? 'bill';
            $discount_type = $foundCoupon['discount_type'] ?? null;
            $discount_value = $foundCoupon['discount_value'] ?? null;

            if ($discount_type === null || $discount_value === null) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Coupon discount type/value could not be resolved'));
            }

            // Minimum Bill Limit Validation
            $couponMinBill = floatval($foundCoupon['min_bill_amount_for_redemption'] ?? $foundCoupon['min_bill_amount'] ?? $foundCoupon['min_bill_for_redemption'] ?? 0);
            if ($couponMinBill > 0 && $orderTotal < $couponMinBill) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Coupon requires minimum bill amount of $couponMinBill"));
            }

            if (strcasecmp($discount_on, 'bill') === 0 && strcasecmp($discount_type, 'fixed') === 0) {
                $requiredMinBill = max($couponMinBill, floatval($discount_value) + 1);
                if ($orderTotal < $requiredMinBill) {
                    $this->response->type('json');
                    return json_encode(array('status_code' => 400, 'message' => "Coupon requires minimum bill amount of $requiredMinBill"));
                }
            }

            // Maximum Bill Limit Validation
            $couponMaxBill = floatval($foundCoupon['max_bill_amount_for_redemption'] ?? $foundCoupon['max_bill_amount'] ?? $foundCoupon['max_bill_for_redemption'] ?? 0);
            if ($couponMaxBill > 0 && $orderTotal > $couponMaxBill) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Coupon requires maximum bill amount of $couponMaxBill"));
            }

            // Item Availability Validation
            $couponItems = $foundCoupon['products'] ?? $foundCoupon['items'] ?? $foundCoupon['product_ids'] ?? array();
            if (!empty($couponItems)) {
                $hasMatchedItem = false;
                $singleProductId = null;
                if (count($couponItems) === 1) {
                    $singleItem = reset($couponItems);
                    $singleProductId = is_array($singleItem) ? ($singleItem['_id'] ?? $singleItem['id'] ?? $singleItem['product_id']) : $singleItem;
                }

                foreach ($couponItems as $couponItem) {
                    $productId = is_array($couponItem) ? ($couponItem['_id'] ?? $couponItem['id'] ?? $couponItem['product_id']) : $couponItem;
                    if (empty($productId)) {
                        continue;
                    }

                    if (!empty($split_reference_id)) {
                        $this->loadModel('Order_split_orderhistory');
                        $ohItemCheck = $this->Order_split_orderhistory->find('first', array(
                            'conditions' => array(
                                'Order_split_orderhistory.reference_id' => $reference_id,
                                'Order_split_orderhistory.split_reference_id' => $split_reference_id,
                                'OR' => array(
                                    'Order_split_orderhistory.product_id' => $productId,
                                    'Order_split_orderhistory.product_id' => (int)$productId,
                                    'Order_split_orderhistory.product_id' => (string)$productId
                                ),
                                'Order_split_orderhistory.status !=' => 0
                            ),
                            'recursive' => -1
                        ));
                    } else {
                        $this->loadModel('Order_orderhistory');
                        $ohItemCheck = $this->Order_orderhistory->find('first', array(
                            'conditions' => array(
                                'Order_orderhistory.reference_id' => $reference_id,
                                'OR' => array(
                                    'Order_orderhistory.product_id' => $productId,
                                    'Order_orderhistory.product_id' => (int)$productId,
                                    'Order_orderhistory.product_id' => (string)$productId
                                ),
                                'Order_orderhistory.status !=' => 0
                            ),
                            'recursive' => -1
                        ));
                    }

                    if (!empty($ohItemCheck)) {
                        $hasMatchedItem = true;
                        break;
                    }
                }

                if (!$hasMatchedItem) {
                    $this->response->type('json');
                    if (count($couponItems) === 1 && !empty($singleProductId)) {
                        $this->loadModel('Catalog_product');
                        $prod = $this->Catalog_product->find('first', array(
                            'conditions' => array('Catalog_product.id' => $singleProductId),
                            'fields' => array('name'),
                            'recursive' => -1
                        ));
                        $prodName = $prod['Catalog_product']['name'] ?? "Product ID: $singleProductId";
                        return json_encode(array('status_code' => 400, 'message' => "Item '$prodName' is not available in the order"));
                    }
                    return json_encode(array('status_code' => 400, 'message' => 'None of the applicable coupon items are available in the order'));
                }
            }
        } 
        else if ($reelo_discount_type === 1) { // --- Points / Cashback Validation ---
            $discount_value = floatval($points);

            // Minimum Bill Limit
            $minBill = floatval($loyaltyData['min_bill_for_redemption'] ?? 0);
            if ($minBill > 0 && $orderTotal < $minBill) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Minimum bill amount of $minBill is required for points redemption"));
            }

            // Maximum Bill Limit
            $maxBill = floatval($loyaltyData['total_redeemable_points'] ?? 0);
            if ($maxBill > 0 && $discount_value > $maxBill) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Maximum points of $maxBill is allowed for points redemption"));
            }

            $loyaltyPoints = floatval($loyaltyData['loyalty_points'] ?? 0);
            $totalRedeemablePoints = floatval($loyaltyData['total_redeemable_points'] ?? 0);

            if ($loyaltyPoints > 0 && $discount_value > $loyaltyPoints) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "You don't have enough loyalty points (you have $loyaltyPoints, need $discount_value)"));
            }

            if ($totalRedeemablePoints > 0 && $discount_value > $totalRedeemablePoints) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Maximum redeemable amount is $totalRedeemablePoints"));
            }
            

            // Maximum Cap Percentage
            $maxCapPercent = floatval($loyaltyData['redemption_cap_on_max_bill_percentage'] ?? 0);
            if ($maxCapPercent > 0) {
                $maxAllowedDiscount = ($orderTotal * $maxCapPercent) / 100;
                if ($discount_value > $maxAllowedDiscount) {
                    $this->response->type('json');
                    return json_encode(array('status_code' => 400, 'message' => "Maximum points redemption is $maxCapPercent% of bill (amount: $maxAllowedDiscount)"));
                }
            }
            
            if($discount_value > $orderTotal){
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Max redeemable amount is $orderTotal"));
            }
             if ($orderTotal > 0) {
                $discountPercentage = ($discount_value / $orderTotal) * 100;
                $differencePercentage = 100 - $discountPercentage;
                if ($differencePercentage < 1) {
                    $this->response->type('json');
                    return json_encode(array('status_code' => 400, 'message' => "Points redemption difference is less than 1% (at least 1% of bill total must remain)"));
                }
            }
        } 
        else if ($reelo_discount_type === 2) { // --- Wallet Validation ---
            $foundMembership = $loyaltyData['membership'] ?? null;
            $isWalletActive = $foundMembership['active'] ?? false;
            
            if (empty($foundMembership) || !($isWalletActive === true || $isWalletActive === 1 || $isWalletActive === '1' || strcasecmp((string)$isWalletActive, 'true') === 0)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Wallet redemption is not active'));
            }

            // Get amount from transaction table if it exists
            if (!empty($split_reference_id)) {
                $this->loadModel('Order_split_transaction');
                $txnDataForBill = $this->Order_split_transaction->find('first', array(
                    'conditions' => array(
                        'Order_split_transaction.reference_id' => $reference_id,
                        'Order_split_transaction.split_reference_id' => $split_reference_id,
                        'Order_split_transaction.status !=' => 0
                    ),
                    'fields' => array('amount'),
                    'recursive' => -1
                ));
                $txnAmount = isset($txnDataForBill['Order_split_transaction']['amount']) ? floatval($txnDataForBill['Order_split_transaction']['amount']) : 0;
            } else {
                $this->loadModel('Order_transaction');
                $txnDataForBill = $this->Order_transaction->find('first', array(
                    'conditions' => array(
                        'Order_transaction.reference_id' => $reference_id,
                        'Order_transaction.status !=' => 0
                    ),
                    'fields' => array('amount'),
                    'recursive' => -1
                ));
                $txnAmount = isset($txnDataForBill['Order_transaction']['amount']) ? floatval($txnDataForBill['Order_transaction']['amount']) : 0;
            }
            if ($txnAmount > 0) {
                $orderTotal = $txnAmount;
            }

            $discount_value = floatval($wallet_credits);

            if ($discount_value > $orderTotal) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Wallet redemption amount ($discount_value) cannot exceed the total bill amount ($orderTotal)"));
            }

            // Wallet Balance Check
            $walletCredits = floatval($foundMembership['wallet_credits'] ?? 0);
            if ($walletCredits > 0 && $discount_value > $walletCredits) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Not enough wallet credits (you have $walletCredits, need $discount_value)"));
            }

            // Minimum Bill Limit
            $minWalletBill = floatval($foundMembership['min_bill_for_redemption_wallet'] ?? 0);
            if ($minWalletBill > 0 && $orderTotal < $minWalletBill) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Wallet redemption requires minimum bill amount of $minWalletBill"));
            }

            // Maximum Bill Limit
            $maxWalletBill = floatval($foundMembership['total_redeemable_points_wallet'] ?? 0);
            $walletCreditsToRedeem = floatval($discount_value ?? $_REQUEST['wallet_credits'] ?? 0);
            if ($maxWalletBill > 0 && $orderTotal > $maxWalletBill && $walletCreditsToRedeem > $maxWalletBill) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Wallet redemption requires maximum bill amount of $maxWalletBill"));
            }

            // Total Redeemable Wallet Limits
            $totalRedeemableWalletPoints = floatval($foundMembership['total_redeemable_points_wallet'] ?? $foundMembership['total_redeemable_points_membership'] ?? 0);
            if ($totalRedeemableWalletPoints > 0 && $discount_value > $totalRedeemableWalletPoints) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => "Maximum redeemable amount is $totalRedeemableWalletPoints"));
            }

            // Maximum Cap Percentage
            $maxWalletCapPercent = floatval($foundMembership['redemption_cap_on_max_bill_percentage_wallet'] ?? 0);
            if ($maxWalletCapPercent > 0) {
                $maxWalletAmount = ($orderTotal * $maxWalletCapPercent) / 100;
                if ($discount_value > $maxWalletAmount) {
                    $this->response->type('json');
                    return json_encode(array('status_code' => 400, 'message' => "Maximum wallet redemption is $maxWalletCapPercent% of bill (amount: $maxWalletAmount)"));
                }
            }
        }

        // --- All Validations Passed. Proceed with OTP API ---
        $url = REELO_BASE_URL . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/points/authenticate";

        $postData = array(
            'customer_key' => (string)$reelo_customer_key,
            'merchant_id' => (string)$reelo_merchant_id,
            'customer_mobile' => (string)$mobile
        );

        if (!empty($coupon_code)) {
            $postData['coupon_code'] = (string)$coupon_code;
        }

        if (!empty($points)) {
            $postData['points'] = (float)$points;
        }

        if (!empty($wallet_credits)) {
            $postData['wallet_credits'] = (string)$wallet_credits;
        }

        $jsonData = json_encode($postData);

        // Execute API request to Reelo
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->response->type('json');
            return json_encode(array(
                'status_code' => 400,
                'message' => 'Failed to connect to Reelo API: ' . $curl_error
            ));
        }

        // Decode response to verify it's valid JSON
        $decodedResponse = json_decode($apiResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->response->type('json');
            return json_encode(array(
                'status_code' => 400,
                'message' => 'Invalid JSON',
                'raw_response' => $apiResponse
            ));
        }

        // Handle Reelo API error status codes (e.g. 400, 404)
        if ($http_code != 200 && $http_code != 201) {
            $reeloMessage = $decodedResponse['response']['message'] ?? $decodedResponse['message'] ?? 'Reelo Response Error';
            $this->response->type('json');
            return json_encode(array(
                'status_code' => 400,
                'message' => $reeloMessage
            ));
        }
            
        // $response = array(
        //         'status_code' => 200,
        //         'message' => 'Ok',
        //     );
        // $this->response->type('json');
        // return json_encode($response);
            
        // Return Reelo response to cashier application
        $this->response->type('json');
        return json_encode($decodedResponse);
    }
    
    
     /**
     * Send Bill to Reelo (Capture Bill)
     * POST /reelo_apis/capture_bill
     */
    public function capture_bill() {
        // Read input data
        $user_id = null;
        $reference_id = null;
        $customer = array();

        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        if (is_array($jsonData)) {
            $user_id = $jsonData['user_id'] ?? null;
            $reference_id = $jsonData['reference_id'] ?? null;
            $customer = $jsonData['customer'] ?? array();
        }

        // Fallback to $_REQUEST (covers GET and POST parameters)
        if (empty($user_id)) {
            $user_id = $_REQUEST['user_id'] ?? null;
        }
        if (empty($reference_id)) {
            $reference_id = $_REQUEST['reference_id'] ?? null;
        }
        if (empty($customer) && isset($_REQUEST['customer'])) {
            $customer = is_string($_REQUEST['customer']) ? json_decode($_REQUEST['customer'], true) : $_REQUEST['customer'];
        }

        // Validate mandatory parameters
        if (empty($user_id)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'user_id is required'));
        }

        if (empty($reference_id)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 400, 'message' => 'reference_id is required'));
        }

        // Fetch transaction details from database using reference_id
        $this->loadModel('Order_transaction');
        $txn = $this->Order_transaction->find('first', array(
            'conditions' => array(
                    'Order_transaction.reference_id' => $reference_id,
                    'Order_transaction.status' => 2
                )
            ));

        $isSplitBill = false;
        $splitTxns = array();
        if (!empty($txn) && $txn['Order_transaction']['is_split'] == 1) {
            $this->loadModel('Order_split_transaction');
            $splitTxns = $this->Order_split_transaction->find('all', array(
                'conditions' => array(
                    'Order_split_transaction.reference_id' => $reference_id,
                    'Order_split_transaction.status' => 2
                )
            ));
            if (!empty($splitTxns)) {
                $isSplitBill = true;
                $txn = null;
            }
        } 

        if (empty($txn) && empty($splitTxns)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 404, 'message' => 'Transaction not found'));
        }

        if ($isSplitBill) {
            $lastRes = null;
            $successCount = 0;
            $errors = array();
            $lastHttpCode = 200;

            foreach ($splitTxns as $st) {
                $txnData = $st['Order_split_transaction'];
                $res = $this->_send_bill_to_reelo($txnData, true, $customer);
                $lastRes = $res;

                if (empty($res['error']) && ($res['http_code'] == 200 || $res['http_code'] == 201)) {
                    $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 1 WHERE id = " . intval($txnData['id']));
                    $successCount++;
                } else {
                    $msg = $res['response']['response']['message'] ?? $res['response']['message'] ?? $res['error'] ?? '';
                    if ($res['http_code'] == false && stripos($msg, 'exists') !== false) {
                        $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 1 WHERE id = " . intval($txnData['id']));
                        $successCount++;
                    } else {
                        $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 2 WHERE id = " . intval($txnData['id']));
                        $errors[] = !empty($res['error']) ? $res['error'] : $msg;
                        $lastHttpCode = $res['http_code'];
                    }
                }
            }

            $failCount = count($splitTxns) - $successCount;

            if ($successCount === 0 && !empty($errors)) {
                $this->response->type('json');
                return json_encode(array(
                    'status_code' => $lastHttpCode,
                    'message' => "Reelo Sync Failed: $successCount succeeded, $failCount failed. Errors: " . implode('; ', array_unique($errors))
                ));
            }

            $this->response->type('json');
            return json_encode(array(
                'status_code' => 200,
                'message' => "Reelo Sync Complete: $successCount succeeded, $failCount failed."
            ));
        } else {
            $txnData = $txn['Order_transaction'];
            $res = $this->_send_bill_to_reelo($txnData, false, $customer);
            

            if (!empty($res['error'])) {
                $this->response->type('json');
                return json_encode(array('status_code' => $res['http_code'], 'message' => $res['error']));
            }

            if ($res['http_code'] != 200 && $res['http_code'] != 201) {
                $reeloMessage = $res['response']['response']['message'] ?? $res['response']['message'] ?? 'Reelo Response Error';
                $this->response->type('json');
                return json_encode(array('status_code' => $res['http_code'], 'message' => $reeloMessage));
            }

            $this->Order_transaction->query("UPDATE order_transactions SET reelo_sync = 1 WHERE id = " . intval($txnData['id']));

            $this->response->type('json');
            return json_encode($res['response']);
        }
    }

    /**
     * Action to Sync Billed Transactions to Reelo (Used by hourly Cron job)
     * GET /reelo_apis/cron_sync_reelo_bills
     */
    public function cron_sync_reelo_bills() {
        $this->autoRender = false;
        $this->layout = false;

        $this->loadModel('Order_transaction');
        $this->loadModel('Order_split_transaction');


        // Fetch unprocessed successful regular transactions (status = 2, reelo_sync = 0, is_split = 0)
        $txns = $this->Order_transaction->find('all', array(
            'conditions' => array(
                'Order_transaction.status' => 2,
                'Order_transaction.reelo_sync' => 0,
                'Order_transaction.is_split' => 0
            ),
            'limit' => 15,
            'order' => array('Order_transaction.id' => 'ASC')
        ));

        // Fetch unprocessed successful split transactions (status = 2, reelo_sync = 0)
        $splitTxns = $this->Order_split_transaction->find('all', array(
            'conditions' => array(
                'Order_split_transaction.status' => 2,
                'Order_split_transaction.reelo_sync' => 0
            ),
            'limit' => 15,
            'order' => array('Order_split_transaction.id' => 'ASC')
        ));

        if (empty($txns) && empty($splitTxns)) {
            $response = array('status_code' => 200, 'message' => 'No transactions to sync to Reelo.');
            $this->response->type('json');
            return json_encode($response);
        }

        $successCount = 0;
        $failCount = 0;

        // Process regular transactions
        foreach ($txns as $t) {
            $txn = $t['Order_transaction'];
            $res = $this->_send_bill_to_reelo($txn, false);

            if (empty($res['error']) && ($res['http_code'] == 200 || $res['http_code'] == 201)) {
                $this->Order_transaction->query("UPDATE order_transactions SET reelo_sync = 1 WHERE id = " . intval($txn['id']));
                $successCount++;
            } else {
                $msg = $res['response']['response']['message'] ?? $res['response']['message'] ?? '';
                if ($res['http_code'] == 400 && stripos($msg, 'exists') !== false) {
                    $this->Order_transaction->query("UPDATE order_transactions SET reelo_sync = 1 WHERE id = " . intval($txn['id']));
                    $successCount++;
                } else {
                    $this->Order_transaction->query("UPDATE order_transactions SET reelo_sync = 2 WHERE id = " . intval($txn['id']));
                    $failCount++;
                }
            }
        }

        // Process split transactions
        foreach ($splitTxns as $st) {
            $splitTxnData = $st['Order_split_transaction'];
            $res = $this->_send_bill_to_reelo($splitTxnData, true);

            if (empty($res['error']) && ($res['http_code'] == 200 || $res['http_code'] == 201)) {
                $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 1 WHERE id = " . intval($splitTxnData['id']));
                $successCount++;
            } else {
                $msg = $res['response']['response']['message'] ?? $res['response']['message'] ?? '';
                if ($res['http_code'] == 400 && stripos($msg, 'exists') !== false) {
                    $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 1 WHERE id = " . intval($splitTxnData['id']));
                    $successCount++;
                } else {
                    $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 2 WHERE id = " . intval($splitTxnData['id']));
                    $failCount++;
                }
            }
        }

        $response = array(
            'status_code' => 200,
            'message' => "Reelo Sync Complete: $successCount succeeded, $failCount failed."
        );
        $this->response->type('json');
        return json_encode($response);
    }

    /**
     * Helper to format and send a transaction to Reelo Capture API
     *
     * @param array $txnData The transaction record data (Order_transaction or Order_split_transaction)
     * @param bool $isSplit True if this is a split transaction
     * @param array $customerOverride Customer details passed by client (if any)
     * @param array $transactionOverride Transaction details passed by client (if any)
     * @return array Array containing 'http_code', 'response', and 'error'
     */
    protected function _send_bill_to_reelo($txnData, $isSplit, $customerOverride = array(), $transactionOverride = array()) {
        $reference_id = $txnData['reference_id'];
        if (empty($reference_id)) {
           return array('http_code' => 400, 'error' => 'Reference ID is missing in transaction');
        }

        $cashier_id = $txnData['cashier_id'];

        // Fetch user (cashier) to get location_id
        $this->loadModel('User');
        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.id' => $cashier_id,
                'User.status' => 1
            )
        ));

        if (empty($user)) {
            return array('http_code' => 400, 'error' => 'Cashier not found or inactive');
        }

        $location_id = $user['User']['location_id'] ?? null;
        if (empty($location_id)) {
            return array('http_code' => 400, 'error' => 'No location assigned to this cashier');
        }

        // Fetch location details to get reelo_merchant_id
        $this->loadModel('Location');
        $location = $this->Location->find('first', array(
            'conditions' => array(
                'Location.id' => $location_id,
                'Location.status' => 1
            )
        ));

        if (empty($location)) {
            return array('http_code' => 400, 'error' => 'Location not found or inactive');
        }

        $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
        $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
        if (empty($reelo_merchant_id)) {
            return array('http_code' => 400, 'error' => 'Reelo Merchant ID not configured for this location');
        }

        // Fetch items
        $itemsPayload = array();
        $order_subtotal = 0.0;
        if ($isSplit) {
            $this->loadModel('Order_split_orderhistory');
            $dbItems = $this->Order_split_orderhistory->find('all', array(
                'conditions' => array(
                    'Order_split_orderhistory.reference_id' => $reference_id,
                    'Order_split_orderhistory.status' => 2
                )
            ));
            foreach ($dbItems as $item) {
                $subtotal = floatval($item['Order_split_orderhistory']['total_price']);
                $order_subtotal += $subtotal;
                $itemsPayload[] = array(
                    'name' => (string)$item['Order_split_orderhistory']['product_name'],
                    'id' => (string)$item['Order_split_orderhistory']['product_id'],
                    'rate' => floatval($item['Order_split_orderhistory']['price']),
                    'quantity' => floatval($item['Order_split_orderhistory']['quantity']),
                    'subtotal' => $subtotal,
                    'category' => (string)($item['Order_split_orderhistory']['category_name'] ?? ''),
                    'addon' => array()
                );
            }
        } else {
            $this->loadModel('Order_orderhistory');
            $dbItems = $this->Order_orderhistory->find('all', array(
                'conditions' => array(
                    'Order_orderhistory.reference_id' => $reference_id,
                    'Order_orderhistory.status' => 2
                )
            ));
            foreach ($dbItems as $item) {
                $subtotal = floatval($item['Order_orderhistory']['total_price']);
                $order_subtotal += $subtotal;
                $itemsPayload[] = array(
                    'name' => (string)$item['Order_orderhistory']['product_name'],
                    'id' => (string)$item['Order_orderhistory']['product_id'],
                    'rate' => floatval($item['Order_orderhistory']['price']),
                    'quantity' => floatval($item['Order_orderhistory']['quantity']),
                    'subtotal' => $subtotal,
                    'category' => (string)($item['Order_orderhistory']['category_name'] ?? ''),
                    'addon' => array()
                );
            }
        }

        if (empty($itemsPayload)) {
            return array('http_code' => 400, 'error' => 'No items found for this transaction');
        }

        // Fetch payments
        $paymentsGrouped = array();
        $custom_payment_type = '';
        $is_no_charge = false;
        if ($isSplit) {
            $this->loadModel('Order_split_payment');
            $dbPayments = $this->Order_split_payment->find('all', array(
                'conditions' => array(
                    'Order_split_payment.reference_id' => $reference_id,
                    'Order_split_payment.status' => 2
                )
            ));
            foreach ($dbPayments as $p) {
                $type = intval($p['Order_split_payment']['type']);
                if ($type === 7) {
                    $is_no_charge = true;
                }
                $type_value = $p['Order_split_payment']['type_value'] ?: '';
                $pAmount = floatval($p['Order_split_payment']['amount']);
                
                $payment_type = 'Others';
                if ($type === 1) {
                    $payment_type = 'Cash';
                } elseif ($type === 2) {
                    $payment_type = 'Card';
                } elseif ($type === 6) {
                    $payment_type = 'UPI';
                } elseif ($type === 11) {
                    $payment_type = 'Online';
                }
                
                if (!isset($paymentsGrouped[$payment_type])) {
                    $paymentsGrouped[$payment_type] = array(
                        'payment_type' => $payment_type,
                        'total_amount' => 0.0,
                        'types' => array()
                    );
                }
                $paymentsGrouped[$payment_type]['total_amount'] += $pAmount;
                if ($payment_type !== 'Cash' && !empty($type_value)) {
                    $paymentsGrouped[$payment_type]['types'][] = array(
                        'name' => $type_value,
                        'amount' => $pAmount
                    );
                    $custom_payment_type = $type_value;
                }
            }
        } else {
            $this->loadModel('Order_payment');
            $dbPayments = $this->Order_payment->find('all', array(
                'conditions' => array(
                    'Order_payment.reference_id' => $reference_id,
                    'Order_payment.status' => 2
                )
            ));
            foreach ($dbPayments as $p) {
                $type = intval($p['Order_payment']['type']);
                if ($type === 7) {
                    $is_no_charge = true;
                }
                $type_value = $p['Order_payment']['type_value'] ?: '';
                $pAmount = floatval($p['Order_payment']['amount']);
                
                $payment_type = 'Others';
                if ($type === 1) {
                    $payment_type = 'Cash';
                } elseif ($type === 2) {
                    $payment_type = 'Card';
                } elseif ($type === 6) {
                    $payment_type = 'UPI';
                } elseif ($type === 11) {
                    $payment_type = 'Online';
                }
                
                if (!isset($paymentsGrouped[$payment_type])) {
                    $paymentsGrouped[$payment_type] = array(
                        'payment_type' => $payment_type,
                        'total_amount' => 0.0,
                        'types' => array()
                    );
                }
                $paymentsGrouped[$payment_type]['total_amount'] += $pAmount;
                if ($payment_type !== 'cash' && !empty($type_value)) {
                    $paymentsGrouped[$payment_type]['types'][] = array(
                        'name' => $type_value,
                        'amount' => $pAmount
                    );
                    $custom_payment_type = $type_value;
                }
            }
        }

        $paymentsPayload = array();
        foreach ($paymentsGrouped as $data) {
            $payItem = array(
                'payment_type' => $data['payment_type'],
                'total_amount' => $data['total_amount']
            );
            if (!empty($data['types'])) {
                $payItem['types'] = $data['types'];
            }
            $paymentsPayload[] = $payItem;
        }

        // Fetch taxes
        $taxesPayload = array();
        if ($isSplit) {
            $splitTaxAmount = floatval($txnData['total_tax'] ?? 0);
            if ($splitTaxAmount > 0) {
                $taxesPayload[] = array(
                    'name' => 'Tax',
                    'percentage' => 0.0,
                    'amount' => $splitTaxAmount
                );
            }
        } else {
            $this->loadModel('Order_tax');
            $dbTaxes = $this->Order_tax->find('all', array(
                'conditions' => array(
                    'Order_tax.reference_id' => $reference_id,
                    'Order_tax.status' => 2
                )
            ));
            $taxesGrouped = array();
            foreach ($dbTaxes as $t) {
                $tax_name = $t['Order_tax']['tax_name'] ?? '';
                if (empty($tax_name)) {
                    $tax_name = 'Tax';
                }
                $tax_percentage = floatval($t['Order_tax']['tax_percentage'] ?? 0);
                $tax_value = floatval($t['Order_tax']['tax_value'] ?? 0);
                
                $key = $tax_name . '_' . $tax_percentage;
                if (!isset($taxesGrouped[$key])) {
                    $taxesGrouped[$key] = array(
                        'name' => $tax_name,
                        'percentage' => $tax_percentage,
                        'amount' => 0.0
                    );
                }
                $taxesGrouped[$key]['amount'] += $tax_value;
            }
            $taxesPayload = array_values($taxesGrouped);
        }

        // Fetch charges & discounts
        $discount = floatval($txnData['discount'] ?? 0);
        $service_charge = floatval($txnData['servicecharge'] ?? 0);
        $delivery_charge = floatval($txnData['delivery_charge'] ?? 0);
        $packing_charge = floatval($txnData['packing_charge'] ?? 0);
        $amount = floatval($txnData['amount'] ?? 0);
        
        $chargesPayload = array();
        if ($service_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Service charge',
                'amount' => $service_charge
            );
        }
        if ($delivery_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Delivery charge',
                'amount' => $delivery_charge
            );
        }
        if ($packing_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Packing charge',
                'amount' => $packing_charge
            );
        }

        // Calculate gross and net amount
        $gross_amount = $order_subtotal - $discount;
        $total_charges = $service_charge + $delivery_charge + $packing_charge;
        $total_tax = 0.0;
        foreach ($taxesPayload as $t) {
            $total_tax += $t['amount'];
        }
        $net_amount = $gross_amount + $total_charges + $total_tax;

        // Map primary payment type
        $primary_payment = 'Cash';
        if (!empty($paymentsPayload)) {
            $primary_payment = $paymentsPayload[0]['payment_type'];
        }

        $is_takeaway = intval($txnData['is_takeaway'] ?? 0);
        $order_type = ($is_takeaway === 1) ? 'take_away' : 'dine_in';

        // Format dates
        $order_time = date('Y-m-d H:i:s', strtotime($txnData['created']));

        // Map order source
        $source = 'POS';
        $pm = strtolower($txnData['payment_mode'] ?? '');
        if (strpos($pm, 'zomato') !== false) {
            $source = 'zomato';
        } elseif (strpos($pm, 'swiggy') !== false) {
            $source = 'swiggy';
        }

        // Format customer details
        $custName = $customerOverride['name'] ?? $txnData['name'] ?? '';
        $custMobile = $customerOverride['mobile'] ?? $txnData['phone'] ?? '';
        $custEmail = $customerOverride['email'] ?? '';
        $custDob = $customerOverride['dob'] ?? '';
        $custAnniv = $customerOverride['anniversary_date'] ?? '';
        $mobile = preg_replace('/[^0-9]/', '', $custMobile);
        if (strlen($mobile) > 10 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, -10);
        }

        // if (!preg_match('/^[6789][0-9]{9}$/', $mobile)) {
        //     return array('http_code' => 400, 'error' => 'Invalid Mobile Number');
        // }
        

        // Date formatting helpers
        $dobVal = '';
        if (!empty($custDob)) {
            $dobTime = strtotime($custDob);
            if ($dobTime !== false) {
                $dobVal = date('d/m/Y', $dobTime);
            }
        }

        $anniversaryVal = '';
        if (!empty($custAnniv)) {
            $annivTime = strtotime($custAnniv);
            if ($annivTime !== false) {
                $anniversaryVal = date('d/m/Y', $annivTime);
            }
        }

        // Build fetched transaction object
        $status = $is_no_charge ? 0 : 1;
        $fetchedTransaction = array(
            'id' => (string)$reference_id,
            'number' => (string)($txnData['orderno'] ?: $txnData['tokenno']),
            'type' => $order_type,
            'source' => $source,
            'status' => $status,
            'loyalty_flag' => intval($txnData['loyalty_flag'] ?? 0),
            'payment_type' => $primary_payment,
            'order_subtotal' => $order_subtotal,
            'gross_amount' => $gross_amount,
            'net_amount' => $net_amount,
            'amount' => $amount ?: $net_amount,
            'items' => $itemsPayload,
            'order_time' => $order_time,
            'order_from' => 'POS'
        );
        
        $this->loadModel('ReeloDiscount');
        $conditions = array(
            'ReeloDiscount.reference_id' => $reference_id,
            'ReeloDiscount.status' => 1
        );
        if ($isSplit && !empty($txnData['split_reference_id'])) {
            $conditions['ReeloDiscount.split_reference_id'] = $txnData['split_reference_id'];
        }
        $reeloDiscs = $this->ReeloDiscount->find('all', array(
            'conditions' => $conditions,
            'recursive' => -1
        ));

        // Merge client-sent transaction fields to allow overrides
        $mergedTransaction = array_merge($fetchedTransaction, $transactionOverride);
        if (!isset($transactionOverride['items'])) {
            $mergedTransaction['items'] = $itemsPayload;
        }
        if (!isset($transactionOverride['taxes']) && !empty($taxesPayload)) {
            $mergedTransaction['taxes'] = $taxesPayload;
        }
        if (!isset($transactionOverride['charges']) && !empty($chargesPayload)) {
            $mergedTransaction['charges'] = $chargesPayload;
        }
        if (!isset($transactionOverride['payments']) && !empty($paymentsPayload)) {
            $mergedTransaction['payments'] = $paymentsPayload;
        }
        $mergedTransaction['discount'] = '';
        if (!$reeloDiscs && $discount > 0 && !isset($transactionOverride['discount'])) {
            $mergedTransaction['discount'] = $discount;
        }
        // if ($discount > 0 && !isset($transactionOverride['discount'])) {
        //     $mergedTransaction['discount'] = $discount;
        // }
        if (!empty($txnData['table_number']) && !isset($transactionOverride['table_number'])) {
            $mergedTransaction['table_number'] = (string)$txnData['table_number'];
        }
        if (!empty($txnData['pax']) && !isset($transactionOverride['number_of_people'])) {
            $mergedTransaction['number_of_people'] = (string)$txnData['pax'];
        }
        if (!empty($custom_payment_type) && !isset($transactionOverride['custom_payment_type'])) {
            $mergedTransaction['custom_payment_type'] = $custom_payment_type;
        }

        // Build redemption object if present in DB discount_type or overrides
        $redemptionPayload = null;

       

        if (!empty($reeloDiscs)) {
            $redemptionPayload = array();
            $redemptionPayload['reward_id'] = '';
            $redemptionPayload['redeemed_amount'] = '';
            $redemptionPayload['redeemed_wallet_credits'] = '';
            foreach ($reeloDiscs as $disc) {
                $discType = intval($disc['ReeloDiscount']['reelo_discount_type']);
                $discVal = $disc['ReeloDiscount']['discount_value'];
                $couponCode = $disc['ReeloDiscount']['coupon_code'];

                if ($discType === 0) {
                    if (!empty($couponCode)) {
                        $redemptionPayload['reward_id'] = (string)$couponCode;
                    }
                } elseif ($discType === 1) {
                    if ($discVal !== null && $discVal !== '') {
                        $redemptionPayload['redeemed_amount'] = (string)$discVal;
                    }
                } elseif ($discType === 2) {
                    if ($discVal !== null && $discVal !== '') {
                        $redemptionPayload['redeemed_wallet_credits'] = (string)$discVal;
                    }
                }
            }
            if (empty($redemptionPayload)) {
                $redemptionPayload = null;
            }
        } else {
            if ($txnData['discount_type'] == '0' && !empty($txnData['discount_type_value'])) {
                $redemptionPayload = array(
                    'reward_id' => (string)$txnData['discount_type_value']
                );
            }
        }

        if (isset($transactionOverride['redemption'])) {
            $redemptionPayload = $transactionOverride['redemption'];
        }

        // Clean up empty optional fields to satisfy "leave empty if not mandatory"
        if (empty($mergedTransaction['charges'])) {
            unset($mergedTransaction['charges']);
        }
        if (empty($mergedTransaction['taxes'])) {
            unset($mergedTransaction['taxes']);
        }
        if (empty($mergedTransaction['payments'])) {
            unset($mergedTransaction['payments']);
        }
        if ($redemptionPayload !== null) {
            $cleanedRedemption = array(
                'reward_id' => '',
                'redeemed_amount' => '',
                'redeemed_wallet_credits' => ''
            );
            if (!empty($redemptionPayload['reward_id'])) {
                $cleanedRedemption['reward_id'] = (string)$redemptionPayload['reward_id'];
            }
            if (!empty($redemptionPayload['redeemed_amount'])) {
                $cleanedRedemption['redeemed_amount'] = (string)$redemptionPayload['redeemed_amount'];
            }
            if (!empty($redemptionPayload['redeemed_wallet_credits'])) {
                $cleanedRedemption['redeemed_wallet_credits'] = (string)$redemptionPayload['redeemed_wallet_credits'];
            }
            $mergedTransaction['redemption'] = $cleanedRedemption;
        }

        // Ensure coupon/cashback redemption constraints (one type of redemption only)

        // Prepare request payload for Reelo API
        $postData = array(
            'customer_key' => (string)$reelo_customer_key,
            'merchant_id' => (string)$reelo_merchant_id,
            'customer' => array(
                'name' => (string)$custName,
                'mobile' => (string)$mobile,
                'dob' => (string)$dobVal,
                'anniversary_date' => (string)$anniversaryVal,
                'email' => (string)$custEmail
            ),
            'transaction' => $mergedTransaction
        );

        $jsonPayload = json_encode($postData);
        // print_r($jsonPayload);exit;
        // Use defined constant or default to development capture URL
        $baseUrl = defined('REELO_BASE_URL') ? REELO_BASE_URL : 'https://capture-dev.reelo.io';
        $url = $baseUrl . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/bill/customer";

        // Execute API request to Reelo Capture API
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return array(
                'http_code' => 400,
                'error' => 'Failed to connect to Reelo Capture API: ' . $curl_error
            );
        }
        // Decode response to verify it's valid JSON
        $decodedResponse = json_decode($apiResponse, true);
        
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'http_code' => 400,
                'error' => 'Invalid JSON response from Reelo'
            );
        }

        return array(
            'http_code' => $http_code,
            'response' => $decodedResponse,
            'error' => null
        );
    }
    
    
    /**
     * Helper to format and send a transaction update to Reelo Edit Bill API
     *
     * @param array $txnData The transaction record data (Order_transaction or Order_split_transaction)
     * @param bool $isSplit True if this is a split transaction
     * @param array $transactionOverride Transaction details passed by client (if any)
     * @return array Array containing 'http_code', 'response', and 'error'
     */
    protected function _send_bill_update_to_reelo($txnData, $isSplit, $transactionOverride = array()) {
        $reference_id = $txnData['reference_id'];
        if (empty($reference_id)) {
           return array('http_code' => 400, 'error' => 'Reference ID is missing in transaction');
        }

        $cashier_id = $txnData['cashier_id'];

        // Fetch user (cashier) to get location_id
        $this->loadModel('User');
        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.id' => $cashier_id,
                'User.status' => 1
            )
        ));

        if (empty($user)) {
            return array('http_code' => 400, 'error' => 'Cashier not found or inactive');
        }

        $location_id = $user['User']['location_id'] ?? null;
        if (empty($location_id)) {
            return array('http_code' => 400, 'error' => 'No location assigned to this cashier');
        }

        // Fetch location details to get reelo_merchant_id
        $this->loadModel('Location');
        $location = $this->Location->find('first', array(
            'conditions' => array(
                'Location.id' => $location_id,
                'Location.status' => 1
            )
        ));

        if (empty($location)) {
            return array('http_code' => 400, 'error' => 'Location not found or inactive');
        }

        $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
        $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
        if (empty($reelo_merchant_id)) {
            return array('http_code' => 400, 'error' => 'Reelo Merchant ID not configured for this location');
        }

        // Fetch items
        $itemsPayload = array();
        $order_subtotal = 0.0;
        if ($isSplit) {
            $this->loadModel('Order_split_orderhistory');
            $dbItems = $this->Order_split_orderhistory->find('all', array(
                'conditions' => array(
                    'Order_split_orderhistory.reference_id' => $reference_id,
                    'Order_split_orderhistory.status' => 2
                )
            ));
            foreach ($dbItems as $item) {
                $subtotal = floatval($item['Order_split_orderhistory']['total_price']);
                $order_subtotal += $subtotal;
                $itemsPayload[] = array(
                    'name' => (string)$item['Order_split_orderhistory']['product_name'],
                    'id' => (string)$item['Order_split_orderhistory']['product_id'],
                    'rate' => floatval($item['Order_split_orderhistory']['price']),
                    'quantity' => floatval($item['Order_split_orderhistory']['quantity']),
                    'subtotal' => $subtotal,
                    'category' => (string)($item['Order_split_orderhistory']['category_name'] ?? ''),
                    'addon' => array()
                );
            }
        } else {
            $this->loadModel('Order_orderhistory');
            $dbItems = $this->Order_orderhistory->find('all', array(
                'conditions' => array(
                    'Order_orderhistory.reference_id' => $reference_id,
                    'Order_orderhistory.status' => 2
                )
            ));
            foreach ($dbItems as $item) {
                $subtotal = floatval($item['Order_orderhistory']['total_price']);
                $order_subtotal += $subtotal;
                $itemsPayload[] = array(
                    'name' => (string)$item['Order_orderhistory']['product_name'],
                    'id' => (string)$item['Order_orderhistory']['product_id'],
                    'rate' => floatval($item['Order_orderhistory']['price']),
                    'quantity' => floatval($item['Order_orderhistory']['quantity']),
                    'subtotal' => $subtotal,
                    'category' => (string)($item['Order_orderhistory']['category_name'] ?? ''),
                    'addon' => array()
                );
            }
        }

        if (empty($itemsPayload)) {
            return array('http_code' => 400, 'error' => 'No items found for this transaction');
        }

        // Fetch payments
        $paymentsGrouped = array();
        $custom_payment_type = '';
        if ($isSplit) {
            $this->loadModel('Order_split_payment');
            $dbPayments = $this->Order_split_payment->find('all', array(
                'conditions' => array(
                    'Order_split_payment.reference_id' => $reference_id,
                    'Order_split_payment.status' => 2
                )
            ));
            foreach ($dbPayments as $p) {
                $type = intval($p['Order_split_payment']['type']);
                $type_value = $p['Order_split_payment']['type_value'] ?: '';
                $pAmount = floatval($p['Order_split_payment']['amount']);
                
                $payment_type = 'Others';
                if ($type === 1) {
                    $payment_type = 'Cash';
                } elseif ($type === 2) {
                    $payment_type = 'Card';
                } elseif ($type === 6) {
                    $payment_type = 'UPI';
                } elseif ($type === 11) {
                    $payment_type = 'Online';
                }
                
                if (!isset($paymentsGrouped[$payment_type])) {
                    $paymentsGrouped[$payment_type] = array(
                        'payment_type' => $payment_type,
                        'total_amount' => 0.0,
                        'types' => array()
                    );
                }
                $paymentsGrouped[$payment_type]['total_amount'] += $pAmount;
                if ($payment_type !== 'Cash' && !empty($type_value)) {
                    $paymentsGrouped[$payment_type]['types'][] = array(
                        'name' => $type_value,
                        'amount' => $pAmount
                    );
                    $custom_payment_type = $type_value;
                }
            }
        } else {
            $this->loadModel('Order_payment');
            $dbPayments = $this->Order_payment->find('all', array(
                'conditions' => array(
                    'Order_payment.reference_id' => $reference_id,
                    'Order_payment.status' => 2
                )
            ));
            foreach ($dbPayments as $p) {
                $type = intval($p['Order_payment']['type']);
                $type_value = $p['Order_payment']['type_value'] ?: '';
                $pAmount = floatval($p['Order_payment']['amount']);
                
                $payment_type = 'Others';
                if ($type === 1) {
                    $payment_type = 'Cash';
                } elseif ($type === 2) {
                    $payment_type = 'Card';
                } elseif ($type === 6) {
                    $payment_type = 'UPI';
                } elseif ($type === 11) {
                    $payment_type = 'Online';
                }
                
                if (!isset($paymentsGrouped[$payment_type])) {
                    $paymentsGrouped[$payment_type] = array(
                        'payment_type' => $payment_type,
                        'total_amount' => 0.0,
                        'types' => array()
                    );
                }
                $paymentsGrouped[$payment_type]['total_amount'] += $pAmount;
                if ($payment_type !== 'cash' && !empty($type_value)) {
                    $paymentsGrouped[$payment_type]['types'][] = array(
                        'name' => $type_value,
                        'amount' => $pAmount
                    );
                    $custom_payment_type = $type_value;
                }
            }
        }

        $paymentsPayload = array();
        foreach ($paymentsGrouped as $data) {
            $payItem = array(
                'payment_type' => $data['payment_type'],
                'total_amount' => $data['total_amount']
            );
            if (!empty($data['types'])) {
                $payItem['types'] = $data['types'];
            }
            $paymentsPayload[] = $payItem;
        }

        // Fetch taxes
        $taxesPayload = array();
        if ($isSplit) {
            $splitTaxAmount = floatval($txnData['total_tax'] ?? 0);
            if ($splitTaxAmount > 0) {
                $taxesPayload[] = array(
                    'name' => 'Tax',
                    'percentage' => 0.0,
                    'amount' => $splitTaxAmount
                );
            }
        } else {
            $this->loadModel('Order_tax');
            $dbTaxes = $this->Order_tax->find('all', array(
                'conditions' => array(
                    'Order_tax.reference_id' => $reference_id,
                    'Order_tax.status' => 2
                )
            ));
            $taxesGrouped = array();
            foreach ($dbTaxes as $t) {
                $tax_name = $t['Order_tax']['tax_name'] ?? '';
                if (empty($tax_name)) {
                    $tax_name = 'Tax';
                }
                $tax_percentage = floatval($t['Order_tax']['tax_percentage'] ?? 0);
                $tax_value = floatval($t['Order_tax']['tax_value'] ?? 0);
                
                $key = $tax_name . '_' . $tax_percentage;
                if (!isset($taxesGrouped[$key])) {
                    $taxesGrouped[$key] = array(
                        'name' => $tax_name,
                        'percentage' => $tax_percentage,
                        'amount' => 0.0
                    );
                }
                $taxesGrouped[$key]['amount'] += $tax_value;
            }
            $taxesPayload = array_values($taxesGrouped);
        }

        // Fetch charges & discounts
        $discount = floatval($txnData['discount'] ?? 0);
        $service_charge = floatval($txnData['servicecharge'] ?? 0);
        $delivery_charge = floatval($txnData['delivery_charge'] ?? 0);
        $packing_charge = floatval($txnData['packing_charge'] ?? 0);
        $amount = floatval($txnData['amount'] ?? 0);
        
        $chargesPayload = array();
        if ($service_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Service charge',
                'amount' => $service_charge
            );
        }
        if ($delivery_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Delivery charge',
                'amount' => $delivery_charge
            );
        }
        if ($packing_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Packing charge',
                'amount' => $packing_charge
            );
        }

        // Calculate gross and net amount
        $gross_amount = $order_subtotal - $discount;
        $total_charges = $service_charge + $delivery_charge + $packing_charge;
        $total_tax = 0.0;
        foreach ($taxesPayload as $t) {
            $total_tax += $t['amount'];
        }
        $net_amount = $gross_amount + $total_charges + $total_tax;

        // Map primary payment type
        $primary_payment = 'Cash';
        if (!empty($paymentsPayload)) {
            $primary_payment = $paymentsPayload[0]['payment_type'];
        }

        $is_takeaway = intval($txnData['is_takeaway'] ?? 0);
        $order_type = ($is_takeaway === 1) ? 'take_away' : 'dine_in';

        // Format dates
        $order_time = date('Y-m-d H:i:s', strtotime($txnData['created']));

        // Map order source
        $source = 'POS';
        $pm = strtolower($txnData['payment_mode'] ?? '');
        if (strpos($pm, 'zomato') !== false) {
            $source = 'zomato';
        } elseif (strpos($pm, 'swiggy') !== false) {
            $source = 'swiggy';
        }

        // Build fetched transaction object
        $fetchedTransaction = array(
            'id' => (string)$reference_id,
            'number' => (string)($txnData['orderno'] ?: $txnData['tokenno']),
            'type' => $order_type,
            'source' => $source,
            'payment_type' => $primary_payment,
            'order_subtotal' => $order_subtotal,
            'gross_amount' => $gross_amount,
            'net_amount' => $net_amount,
            'amount' => $amount ?: $net_amount,
            'items' => $itemsPayload,
            'order_time' => $order_time,
            'order_from' => 'POS'
        );

        // Merge client-sent transaction fields to allow overrides
        $mergedTransaction = array_merge($fetchedTransaction, $transactionOverride);
        if (!isset($transactionOverride['items'])) {
            $mergedTransaction['items'] = $itemsPayload;
        }
        if (!isset($transactionOverride['taxes']) && !empty($taxesPayload)) {
            $mergedTransaction['taxes'] = $taxesPayload;
        }
        if (!isset($transactionOverride['charges']) && !empty($chargesPayload)) {
            $mergedTransaction['charges'] = $chargesPayload;
        }
        if (!isset($transactionOverride['payments']) && !empty($paymentsPayload)) {
            $mergedTransaction['payments'] = $paymentsPayload;
        }
        if ($discount > 0 && !isset($transactionOverride['discount'])) {
            $mergedTransaction['discount'] = $discount;
        }
        if (!empty($txnData['table_number']) && !isset($transactionOverride['table_number'])) {
            $mergedTransaction['table_number'] = (string)$txnData['table_number'];
        }
        if (!empty($txnData['pax']) && !isset($transactionOverride['number_of_people'])) {
            $mergedTransaction['number_of_people'] = (string)$txnData['pax'];
        }
        if (!empty($custom_payment_type) && !isset($transactionOverride['custom_payment_type'])) {
            $mergedTransaction['custom_payment_type'] = $custom_payment_type;
        }

        // Build redemption object if present in DB discount_type or overrides
        $redemptionPayload = null;
        if ($txnData['discount_type'] == '0' && !empty($txnData['discount_type_value'])) {
            $redemptionPayload = array(
                'reward_id' => (string)$txnData['discount_type_value']
            );
        }
        if (isset($transactionOverride['redemption'])) {
            $redemptionPayload = $transactionOverride['redemption'];
        }

        // Clean up empty optional fields to satisfy "leave empty if not mandatory"
        if (empty($mergedTransaction['charges'])) {
            unset($mergedTransaction['charges']);
        }
        if (empty($mergedTransaction['taxes'])) {
            unset($mergedTransaction['taxes']);
        }
        if (empty($mergedTransaction['payments'])) {
            unset($mergedTransaction['payments']);
        }
        if ($redemptionPayload !== null) {
            $cleanedRedemption = array();
            if (!empty($redemptionPayload['reward_id'])) {
                $cleanedRedemption['reward_id'] = (string)$redemptionPayload['reward_id'];
            }
            if (!empty($redemptionPayload['redeemed_amount'])) {
                $cleanedRedemption['redeemed_amount'] = (string)$redemptionPayload['redeemed_amount'];
            }
            if (!empty($redemptionPayload['redeemed_wallet_credits'])) {
                $cleanedRedemption['redeemed_wallet_credits'] = (string)$redemptionPayload['redeemed_wallet_credits'];
            }
            if (empty($cleanedRedemption)) {
                unset($mergedTransaction['redemption']);
            } else {
                $mergedTransaction['redemption'] = $cleanedRedemption;
            }
        }

        // Ensure coupon/cashback redemption constraints (one type of redemption only)
        if (isset($mergedTransaction['redemption'])) {
            $red = $mergedTransaction['redemption'];
            if (!empty($red['reward_id']) && !empty($red['redeemed_amount'])) {
                return array(
                    'http_code' => 400,
                    'error' => 'A bill can only have one type of redemption — either reward_id or redeemed_amount, never both.'
                );
            }
        }

        // Prepare request payload for Reelo API
        $postData = array(
            'customer_key' => (string)$reelo_customer_key,
            'merchant_id' => (string)$reelo_merchant_id,
            'transaction' => $mergedTransaction
        );

        $jsonPayload = json_encode($postData);

        // Use defined constant or default to development capture URL
        $baseUrl = defined('REELO_BASE_URL') ? REELO_BASE_URL : 'https://capture-dev.reelo.io';
        $url = $baseUrl . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/update-bill";

        // Execute API request to Reelo Capture API via PATCH
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return array(
                'http_code' => 400,
                'error' => 'Failed to connect to Reelo Update Bill API: ' . $curl_error
            );
        }

        // Decode response to verify it's valid JSON
        $decodedResponse = json_decode($apiResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'http_code' => 400,
                'error' => 'Invalid JSON response from Reelo'
            );
        }

        return array(
            'http_code' => $http_code,
            'response' => $decodedResponse,
            'error' => null
        );
    }
    
    /**
     * Redeem Coupon — OTP Verification
     * POST /reelo_apis/redeem_coupon
     */
    public function redeem_coupon() {
        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $user_id = null;
        $customer_mobile = null;
        $coupon_code = null;
        $otp = null;

        if (is_array($jsonData)) {
            $user_id = $jsonData['user_id'] ?? null;
            $customer_mobile = $jsonData['customer_mobile'] ?? null;
            $coupon_code = $jsonData['coupon_code'] ?? null;
            $otp = $jsonData['otp'] ?? null;
        }

        if (empty($user_id) && !empty($this->request->data) && is_array($this->request->data)) {
            $user_id = $this->request->data['user_id'] ?? null;
        }
        if (empty($customer_mobile) && !empty($this->request->data) && is_array($this->request->data)) {
            $customer_mobile = $this->request->data['customer_mobile'] ?? null;
        }
        if (empty($coupon_code) && !empty($this->request->data) && is_array($this->request->data)) {
            $coupon_code = $this->request->data['coupon_code'] ?? null;
        }
        if (empty($otp) && !empty($this->request->data) && is_array($this->request->data)) {
            $otp = $this->request->data['otp'] ?? null;
        }

        // Validate mandatory parameters
        if (empty($user_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'user_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (empty($customer_mobile)) {
            $response = array(
                'status_code' => 400,
                'message' => 'customer_mobile is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (empty($coupon_code)) {
            $response = array(
                'status_code' => 400,
                'message' => 'coupon_code is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (empty($otp)) {
            $response = array(
                'status_code' => 400,
                'message' => 'otp is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Clean customer_mobile to ensure it is numeric and exactly 10 digits
        $mobile = preg_replace('/[^0-9]/', '', $customer_mobile);
        
        // Strip country code if prefixed with 91 and length is greater than 10
        if (strlen($mobile) > 10 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, -10);
        }

        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $response = array(
                'status_code' => 400,
                'message' => 'customer_mobile must be a valid 10-digit number'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch user (cashier) to get location_id
        $this->loadModel('User');
        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.id' => $user_id,
                'User.status' => 1
            )
        ));

        if (empty($user)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Cashier not found or inactive'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        $location_id = $user['User']['location_id'] ?? null;
        if (empty($location_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'No location assigned to this cashier'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch location details to get reelo_merchant_id
        $this->loadModel('Location');
        $location = $this->Location->find('first', array(
            'conditions' => array(
                'Location.id' => $location_id,
                'Location.status' => 1
            )
        ));

        if (empty($location)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Location not found or inactive'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
        $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
        if (empty($reelo_merchant_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Reelo Merchant ID not configured for this location'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Prepare payload for Reelo API
        $vendor_id = defined('REELO_VENDOR_ID') ? REELO_VENDOR_ID : '';
        $baseUrl = defined('REELO_BASE_URL') ? REELO_BASE_URL : 'https://dev.api.reelo.io';
        $url = $baseUrl . "/v1/pos/integrations/" . urlencode($vendor_id) . "/redeem/coupon";

        $postData = array(
            'customer_key' => (string)$reelo_customer_key,
            'merchant_id' => (string)$reelo_merchant_id,
            'customer_mobile' => (string)$mobile,
            'coupon_code' => (string)$coupon_code,
            'otp' => (string)$otp
        );

        $jsonData = json_encode($postData);

        // Execute API request to Reelo
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $response = array(
                'status_code' => 400,
                'message' => 'Failed to connect to Reelo API: ' . $curl_error
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Decode response to verify it's valid JSON
        $decodedResponse = json_decode($apiResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = array(
                'status_code' => 400,
                'message' => 'Invalid JSON',
                'raw_response' => $apiResponse
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Handle Reelo API error status codes (e.g. 400, 404)
        if ($http_code != 200 && $http_code != 201) {
            $reeloMessage = $decodedResponse['response']['message'] ?? $decodedResponse['message'] ?? 'Reelo Response Error';
            $response = array(
                'status_code' => 400,
                'message' => $reeloMessage
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Return Reelo response to cashier application
        $this->response->type('json');
        return json_encode($decodedResponse);
    }
    
    
    /**
     * Redeem Points — Cashback OTP Verification
     * POST /reelo_apis/redeem_points
     */
    public function redeem_points() {
        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $user_id = null;
        $customer_mobile = null;
        $points = null;
        $otp = null;
        $wallet_credits = null;

        if (is_array($jsonData)) {
            $user_id = $jsonData['user_id'] ?? null;
            $customer_mobile = $jsonData['customer_mobile'] ?? null;
            $points = $jsonData['points'] ?? null;
            $otp = $jsonData['otp'] ?? null;
            $wallet_credits = $jsonData['wallet_credits'] ?? null;
        }

        // Fallback to CakePHP request data
        if (empty($user_id) && !empty($this->request->data) && is_array($this->request->data)) {
            $user_id = $this->request->data['user_id'] ?? null;
        }
        if (empty($customer_mobile) && !empty($this->request->data) && is_array($this->request->data)) {
            $customer_mobile = $this->request->data['customer_mobile'] ?? null;
        }
        if (($points === null || $points === '') && !empty($this->request->data) && is_array($this->request->data) && isset($this->request->data['points'])) {
            $points = $this->request->data['points'];
        }
        if (empty($otp) && !empty($this->request->data) && is_array($this->request->data)) {
            $otp = $this->request->data['otp'] ?? null;
        }
        if (($wallet_credits === null || $wallet_credits === '') && !empty($this->request->data) && is_array($this->request->data) && isset($this->request->data['wallet_credits'])) {
            $wallet_credits = $this->request->data['wallet_credits'];
        }

        // Validate mandatory parameters
        if (empty($user_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'user_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (empty($customer_mobile)) {
            $response = array(
                'status_code' => 400,
                'message' => 'customer_mobile is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (($points === null || $points === '') && ($wallet_credits === null || $wallet_credits === '')) {
            $response = array(
                'status_code' => 400,
                'message' => 'Either points or wallet_credits is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (empty($otp)) {
            $response = array(
                'status_code' => 400,
                'message' => 'otp is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Clean customer_mobile to ensure it is numeric and exactly 10 digits
        $mobile = preg_replace('/[^0-9]/', '', $customer_mobile);
        
        // Strip country code if prefixed with 91 and length is greater than 10
        if (strlen($mobile) > 10 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, -10);
        }

        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $response = array(
                'status_code' => 400,
                'message' => 'customer_mobile must be a valid 10-digit number'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch user (cashier) to get location_id
        $this->loadModel('User');
        $user = $this->User->find('first', array(
            'conditions' => array(
                'User.id' => $user_id,
                'User.status' => 1
            )
        ));

        if (empty($user)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Cashier not found or inactive'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        $location_id = $user['User']['location_id'] ?? null;
        if (empty($location_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'No location assigned to this cashier'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch location details to get reelo_merchant_id
        $this->loadModel('Location');
        $location = $this->Location->find('first', array(
            'conditions' => array(
                'Location.id' => $location_id,
                'Location.status' => 1
            )
        ));

        if (empty($location)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Location not found or inactive'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
        $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
        if (empty($reelo_merchant_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'Reelo Merchant ID not configured for this location'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Prepare payload for Reelo API
        $vendor_id = defined('REELO_VENDOR_ID') ? REELO_VENDOR_ID : '';
        $baseUrl = defined('REELO_BASE_URL') ? REELO_BASE_URL : 'https://dev.api.reelo.io';
        $url = $baseUrl . "/v1/pos/integrations/" . urlencode($vendor_id) . "/redeem/points";

        $postData = array(
            'customer_key' => (string)$reelo_customer_key,
            'merchant_id' => (string)$reelo_merchant_id,
            'customer_mobile' => (string)$mobile,
            'points' => (float)$points,
            'otp' => (string)$otp
        );

        if ($wallet_credits !== null && $wallet_credits !== '') {
            $postData['wallet_credits'] = (string)$wallet_credits;
        }

        $jsonData = json_encode($postData);

        // Execute API request to Reelo
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $response = array(
                'status_code' => 400,
                'message' => 'Failed to connect to Reelo API: ' . $curl_error
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Decode response to verify it's valid JSON
        $decodedResponse = json_decode($apiResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = array(
                'status_code' => 400,
                'message' => 'Invalid JSON',
                'raw_response' => $apiResponse
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Handle Reelo API error status codes (e.g. 400, 404)
        if ($http_code != 200 && $http_code != 201) {
            $reeloMessage = $decodedResponse['response']['message'] ?? $decodedResponse['message'] ?? 'Reelo Response Error';
            $response = array(
                'status_code' => 400,
                'message' => $reeloMessage
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Return Reelo response to cashier application
        $this->response->type('json');
        return json_encode($decodedResponse);
    }
    
    
    /**
     * Edit Bill (Update Points)
     * POST /reelo_apis/update_bill
     */
    public function update_bill() {
        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $user_id = null;
        $reference_id = null;

        if (is_array($jsonData)) {
            $user_id = $jsonData['user_id'] ?? null;
            $reference_id = $jsonData['reference_id'] ?? null;
        }

        // Fallback to $_REQUEST (covers GET and POST parameters)
        if (empty($user_id)) {
            $user_id = $_REQUEST['user_id'] ?? null;
        }
        if (empty($reference_id)) {
            $reference_id = $_REQUEST['reference_id'] ?? null;
        }

        // Validate mandatory parameters
        if (empty($user_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'user_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (empty($reference_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'reference_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch transaction details from database using reference_id
        $this->loadModel('Order_transaction');
        $txn = $this->Order_transaction->find('first', array(
            'conditions' => array(
                'Order_transaction.reference_id' => $reference_id,
                'Order_transaction.status' => 2
            )
        ));

        $isSplitBill = false;
        $splitTxns = array();
        if (!empty($txn) && $txn['Order_transaction']['is_split'] == 1) {
            $this->loadModel('Order_split_transaction');
            $splitTxns = $this->Order_split_transaction->find('all', array(
                'conditions' => array(
                    'Order_split_transaction.reference_id' => $reference_id,
                    'Order_split_transaction.status' => 2
                )
            ));
            if (!empty($splitTxns)) {
                $isSplitBill = true;
                $txn = null;
            }
        }

        if (empty($txn) && empty($splitTxns)) {
            $this->response->type('json');
            return json_encode(array('status_code' => 404, 'message' => 'Transaction not found'));
        }

        if ($isSplitBill) {
            $lastRes = null;
            $successCount = 0;
            $errors = array();
            $lastHttpCode = 200;

            foreach ($splitTxns as $st) {
                $txnData = $st['Order_split_transaction'];
                $res = $this->_send_bill_update_to_reelo($txnData, true);
                $lastRes = $res;

                if (empty($res['error']) && ($res['http_code'] == 200 || $res['http_code'] == 201)) {
                    $successCount++;
                } else {
                    $msg = $res['response']['response']['message'] ?? $res['response']['message'] ?? $res['error'] ?? '';
                    $errors[] = !empty($res['error']) ? $res['error'] : $msg;
                    $lastHttpCode = $res['http_code'];
                }
            }

            $failCount = count($splitTxns) - $successCount;

            if ($successCount === 0 && !empty($errors)) {
                $this->response->type('json');
                return json_encode(array(
                    'status_code' => $lastHttpCode,
                    'message' => "Reelo Sync Failed: $successCount succeeded, $failCount failed. Errors: " . implode('; ', array_unique($errors))
                ));
            }

            $this->response->type('json');
            return json_encode(array(
                'status_code' => 200,
                'message' => "Reelo Sync Complete: $successCount succeeded, $failCount failed."
            ));
        } else {
            $txnData = $txn['Order_transaction'];
            $res = $this->_send_bill_update_to_reelo($txnData, false);

            if (!empty($res['error'])) {
                $this->response->type('json');
                return json_encode(array('status_code' => $res['http_code'], 'message' => $res['error']));
            }

            if ($res['http_code'] != 200 && $res['http_code'] != 201) {
                $reeloMessage = $res['response']['response']['message'] ?? $res['response']['message'] ?? 'Reelo Response Error';
                $this->response->type('json');
                return json_encode(array('status_code' => $res['http_code'], 'message' => $reeloMessage));
            }

            $this->response->type('json');
        }
    }

    /**
     * Revert Points (Delete Bill)
     * POST /reelo_apis/revert_points
     */
    public function revert_points() {
        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $user_id = null;
        $reference_id = null;

        if (is_array($jsonData)) {
            $user_id = $jsonData['user_id'] ?? null;
            $reference_id = $jsonData['reference_id'] ?? $jsonData['transaction_id'] ?? null;
        }

        // Fallback to $_REQUEST (covers GET and POST parameters)
        if (empty($user_id)) {
            $user_id = $_REQUEST['user_id'] ?? null;
        }
        if (empty($reference_id)) {
            $reference_id = $_REQUEST['reference_id'] ?? $_REQUEST['transaction_id'] ?? null;
        }

        // Validate mandatory parameters
        if (empty($user_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'user_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        if (empty($reference_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'reference_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch transaction details from database using reference_id
        $this->loadModel('Order_transaction');
        $txn = $this->Order_transaction->find('first', array(
            'conditions' => array(
                'Order_transaction.reference_id' => $reference_id,
                'Order_transaction.status' => 0
            )
        ));

        $isSplitBill = false;
        $splitTxns = array();
        if (!empty($txn) && $txn['Order_transaction']['is_split'] == 1) {
            $this->loadModel('Order_split_transaction');
            $splitTxns = $this->Order_split_transaction->find('all', array(
                'conditions' => array(
                    'Order_split_transaction.reference_id' => $reference_id,
                    'Order_split_transaction.status' => 0
                )
            ));
            if (!empty($splitTxns)) {
                $isSplitBill = true;
                $txn = null;
            }
        }

        // Fallback: If not found in DB, resolve store/cashier from user_id and call Reelo directly.
        if (empty($txn) && empty($splitTxns)) {
            $this->loadModel('User');
            $user = $this->User->find('first', array(
                'conditions' => array(
                    'User.id' => $user_id,
                    'User.status' => 1
                )
            ));

            if (empty($user)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Cashier not found or inactive'));
            }

            $location_id = $user['User']['location_id'] ?? null;
            if (empty($location_id)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'No location assigned to this cashier'));
            }

            $this->loadModel('Location');
            $location = $this->Location->find('first', array(
                'conditions' => array(
                    'Location.id' => $location_id,
                    'Location.status' => 1
                )
            ));

            if (empty($location)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Location not found or inactive'));
            }

            $reelo_merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
            $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
            if (empty($reelo_merchant_id)) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Reelo Merchant ID not configured for this location'));
            }

            $baseUrl = defined('REELO_BASE_URL') ? REELO_BASE_URL : 'https://capture-dev.reelo.io';
            $url = $baseUrl . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/revert-points";

            $postData = array(
                'customer_key' => (string)$reelo_customer_key,
                'merchant_id' => (string)$reelo_merchant_id,
                'transaction_id' => (string)$reference_id
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json'
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $apiResponse = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Failed to connect to Reelo API: ' . $curl_error));
            }

            $decodedResponse = json_decode($apiResponse, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->response->type('json');
                return json_encode(array('status_code' => 400, 'message' => 'Invalid JSON response from Reelo'));
            }

            if ($http_code != 200 && $http_code != 201) {
                $reeloMessage = $decodedResponse['response']['message'] ?? $decodedResponse['message'] ?? 'Reelo Response Error';
                $this->response->type('json');
                return json_encode(array('status_code' => $http_code, 'message' => $reeloMessage));
            }

            $this->response->type('json');
            return json_encode($decodedResponse);
        }

        if ($isSplitBill) {
            $lastRes = null;
            $successCount = 0;
            $errors = array();
            $lastHttpCode = 200;

            foreach ($splitTxns as $st) {
                $txnData = $st['Order_split_transaction'];
                $res = $this->_send_bill_revert_to_reelo($txnData, true);
                $lastRes = $res;

                if (empty($res['error']) && ($res['http_code'] == 200 || $res['http_code'] == 201)) {
                    $successCount++;
                } else {
                    $msg = $res['response']['response']['message'] ?? $res['response']['message'] ?? $res['error'] ?? '';
                    $errors[] = !empty($res['error']) ? $res['error'] : $msg;
                    $lastHttpCode = $res['http_code'];
                }
            }

            $failCount = count($splitTxns) - $successCount;

            if ($successCount === 0 && !empty($errors)) {
                $this->response->type('json');
                return json_encode(array(
                    'status_code' => $lastHttpCode,
                    'message' => "Reelo Sync Failed: $successCount succeeded, $failCount failed. Errors: " . implode('; ', array_unique($errors))
                ));
            }

            $this->response->type('json');
            return json_encode(array(
                'status_code' => 200,
                'message' => "Reelo Reversion Complete: $successCount succeeded, $failCount failed."
            ));
        } else {
            $txnData = $txn['Order_transaction'];
            $res = $this->_send_bill_revert_to_reelo($txnData, false);

            if (!empty($res['error'])) {
                $this->response->type('json');
                return json_encode(array('status_code' => $res['http_code'], 'message' => $res['error']));
            }

            if ($res['http_code'] != 200 && $res['http_code'] != 201) {
                $reeloMessage = $res['response']['response']['message'] ?? $res['response']['message'] ?? 'Reelo Response Error';
                $this->response->type('json');
                return json_encode(array('status_code' => $res['http_code'], 'message' => $reeloMessage));
            }

            $this->response->type('json');
            return json_encode($res['response']);
        }
    }

    /**
     * Sync last 15 transactions marked for update (reelo_sync = 3)
     * POST / GET /reelo_apis/cron_backend_update
     */
    public function cron_backend_update() {
        $this->autoRender = false;
        $this->layout = false;

        $this->loadModel('Order_transaction');
        $this->loadModel('Order_split_transaction');

        // Fetch up to 15 regular transactions (status = 2, reelo_sync = 3, is_split = 0)
        $txns = $this->Order_transaction->find('all', array(
            'conditions' => array(
                'Order_transaction.status' => 2,
                'Order_transaction.reelo_sync' => 3,
                'Order_transaction.is_split' => 0
            ),
            'limit' => 15,
            'order' => array('Order_transaction.id' => 'ASC')
        ));

        // Fetch up to 15 split transactions (status = 2, reelo_sync = 3)
        $splitTxns = $this->Order_split_transaction->find('all', array(
            'conditions' => array(
                'Order_split_transaction.status' => 2,
                'Order_split_transaction.reelo_sync' => 3
            ),
            'limit' => 15,
            'order' => array('Order_split_transaction.id' => 'ASC')
        ));

        if (empty($txns) && empty($splitTxns)) {
            $response = array('status_code' => 200, 'message' => 'No transactions pending update to sync to Reelo.');
            $this->response->type('json');
            return json_encode($response);
        }

        $successCount = 0;
        $failCount = 0;

        // Process regular transactions
        foreach ($txns as $t) {
            $txn = $t['Order_transaction'];
            $res = $this->_send_bill_update_to_reelo($txn, false);

            if (empty($res['error']) && ($res['http_code'] == 200 || $res['http_code'] == 201)) {
                $this->Order_transaction->query("UPDATE order_transactions SET reelo_sync = 1 WHERE id = " . intval($txn['id']));
                $successCount++;
            } else {
                $this->Order_transaction->query("UPDATE order_transactions SET reelo_sync = 2 WHERE id = " . intval($txn['id']));
                $failCount++;
            }
        }

        // Process split transactions
        foreach ($splitTxns as $st) {
            $splitTxnData = $st['Order_split_transaction'];
            $res = $this->_send_bill_update_to_reelo($splitTxnData, true);

            if (empty($res['error']) && ($res['http_code'] == 200 || $res['http_code'] == 201)) {
                $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 1 WHERE id = " . intval($splitTxnData['id']));
                $successCount++;
            } else {
                $this->Order_split_transaction->query("UPDATE order_split_transactions SET reelo_sync = 2 WHERE id = " . intval($splitTxnData['id']));
                $failCount++;
            }
        }

        $response = array(
            'status_code' => 200,
            'message' => "Reelo Backend Update Complete: $successCount succeeded, $failCount failed."
        );
        $this->response->type('json');
        return json_encode($response);
    }
    
    
    
    public function sale_return() {
        // Parse JSON raw payload if present
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $customer_mobile = null;
        $merchant_id = null;
        $transactionInput = null;

        if (is_array($jsonData)) {
            $customer_mobile = $jsonData['customer_mobile'] ?? null;
            $merchant_id = $jsonData['merchant_id'] ?? null;
            $transactionInput = $jsonData['transaction'] ?? null;
        }

        // Fallback to $_REQUEST (covers GET and POST parameters)
        if (empty($customer_mobile)) {
            $customer_mobile = $_REQUEST['customer_mobile'] ?? null;
        }
        if (empty($merchant_id)) {
            $merchant_id = $_REQUEST['merchant_id'] ?? null;
        }
        if (empty($transactionInput) && isset($_REQUEST['transaction'])) {
            $transactionInput = is_string($_REQUEST['transaction']) ? json_decode($_REQUEST['transaction'], true) : $_REQUEST['transaction'];
        }

        if (!is_array($transactionInput)) {
            $transactionInput = array();
        }

        // Find reference_transaction_id
        $reference_transaction_id = $_REQUEST['reference_id'] ?? null;

        if (empty($reference_transaction_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'reference_transaction_id is required'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch original transaction details from database using reference_transaction_id
        $this->loadModel('Order_transaction');
        $txn = $this->Order_transaction->find('first', array(
            'conditions' => array(
                'Order_transaction.reference_id' => $reference_transaction_id,
                'Order_transaction.status' => 2
            )
        ));

        $isSplit = false;
        $splitTxn = null;
        if (!empty($txn) && $txn['Order_transaction']['is_split'] == 1) {
            $this->loadModel('Order_split_transaction');
            $splitTxn = $this->Order_split_transaction->find('first', array(
                'conditions' => array(
                    'Order_split_transaction.reference_id' => $reference_transaction_id,
                    'Order_split_transaction.status' => 2
                ),
                'order' => array('Order_split_transaction.id' => 'DESC')
            ));
            if (!empty($splitTxn)) {
                $isSplit = true;
            }
        }

        if (empty($txn) && empty($splitTxn)) {
            $response = array(
                'status_code' => 404,
                'message' => 'Original transaction not found'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        $txnData = $isSplit ? $splitTxn['Order_split_transaction'] : $txn['Order_transaction'];

        // Resolve customer_mobile if empty
        if (empty($customer_mobile)) {
            $customer_mobile = $txnData['phone'] ?? null;
        }

        if (empty($customer_mobile)) {
            $response = array(
                'status_code' => 400,
                'message' => 'customer_mobile is required and could not be resolved from transaction'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Clean customer_mobile to ensure it is numeric and exactly 10 digits
        $mobile = preg_replace('/[^0-9]/', '', $customer_mobile);
        
        // Strip country code if prefixed with 91 and length is greater than 10
        if (strlen($mobile) > 10 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, -10);
        }

        if (!preg_match('/^[0-9]{10}$/', $mobile)) {
            $response = array(
                'status_code' => 400,
                'message' => 'customer_mobile must be a valid 10-digit number'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Resolve merchant_id if empty
        if (empty($merchant_id)) {
            $cashier_id = $txnData['cashier_id'];

            // Fetch user (cashier) to get location_id
            $this->loadModel('User');
            $user = $this->User->find('first', array(
                'conditions' => array(
                    'User.id' => $cashier_id,
                    'User.status' => 1
                )
            ));

            if (!empty($user)) {
                $location_id = $user['User']['location_id'] ?? null;
                if (!empty($location_id)) {
                    // Fetch location details to get reelo_merchant_id
                    $this->loadModel('Location');
                    $location = $this->Location->find('first', array(
                        'conditions' => array(
                            'Location.id' => $location_id,
                            'Location.status' => 1
                        )
                    ));
                    if (!empty($location)) {
                        $merchant_id = $location['Location']['reelo_merchant_id'] ?? null;
                        $reelo_customer_key = $location['Location']['reelo_customer_key'] ?? null;
                    }
                }
            }
        }

        if (empty($merchant_id)) {
            $response = array(
                'status_code' => 400,
                'message' => 'merchant_id is required and could not be resolved'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Fetch original items
        $itemsPayload = array();
        $order_subtotal = 0.0;
        if ($isSplit) {
            $this->loadModel('Order_split_orderhistory');
            $dbItems = $this->Order_split_orderhistory->find('all', array(
                'conditions' => array(
                    'Order_split_orderhistory.reference_id' => $reference_transaction_id,
                    'Order_split_orderhistory.status' => 2
                )
            ));
            foreach ($dbItems as $item) {
                $subtotal = floatval($item['Order_split_orderhistory']['total_price']);
                $order_subtotal += $subtotal;
                $itemsPayload[] = array(
                    'name' => (string)$item['Order_split_orderhistory']['product_name'],
                    'id' => (string)$item['Order_split_orderhistory']['product_id'],
                    'rate' => floatval($item['Order_split_orderhistory']['price']),
                    'quantity' => floatval($item['Order_split_orderhistory']['quantity']),
                    'subtotal' => $subtotal,
                    'category' => (string)($item['Order_split_orderhistory']['category_name'] ?? ''),
                    'addon' => array()
                );
            }
        } else {
            $this->loadModel('Order_orderhistory');
            $dbItems = $this->Order_orderhistory->find('all', array(
                'conditions' => array(
                    'Order_orderhistory.reference_id' => $reference_transaction_id,
                    'Order_orderhistory.status' => 2
                )
            ));
            foreach ($dbItems as $item) {
                $subtotal = floatval($item['Order_orderhistory']['total_price']);
                $order_subtotal += $subtotal;
                $itemsPayload[] = array(
                    'name' => (string)$item['Order_orderhistory']['product_name'],
                    'id' => (string)$item['Order_orderhistory']['product_id'],
                    'rate' => floatval($item['Order_orderhistory']['price']),
                    'quantity' => floatval($item['Order_orderhistory']['quantity']),
                    'subtotal' => $subtotal,
                    'category' => (string)($item['Order_orderhistory']['category_name'] ?? ''),
                    'addon' => array()
                );
            }
        }

        // Fetch taxes
        $taxesPayload = array();
        if ($isSplit) {
            $splitTaxAmount = floatval($txnData['total_tax'] ?? 0);
            if ($splitTaxAmount > 0) {
                $taxesPayload[] = array(
                    'name' => 'Tax',
                    'percentage' => 0.0,
                    'amount' => $splitTaxAmount
                );
            }
        } else {
            $this->loadModel('Order_tax');
            $dbTaxes = $this->Order_tax->find('all', array(
                'conditions' => array(
                    'Order_tax.reference_id' => $reference_transaction_id,
                    'Order_tax.status' => 2
                )
            ));
            $taxesGrouped = array();
            foreach ($dbTaxes as $t) {
                $tax_name = $t['Order_tax']['tax_name'] ?? '';
                if (empty($tax_name)) {
                    $tax_name = 'Tax';
                }
                $tax_percentage = floatval($t['Order_tax']['tax_percentage'] ?? 0);
                $tax_value = floatval($t['Order_tax']['tax_value'] ?? 0);
                
                $key = $tax_name . '_' . $tax_percentage;
                if (!isset($taxesGrouped[$key])) {
                    $taxesGrouped[$key] = array(
                        'name' => $tax_name,
                        'percentage' => $tax_percentage,
                        'amount' => 0.0
                    );
                }
                $taxesGrouped[$key]['amount'] += $tax_value;
            }
            $taxesPayload = array_values($taxesGrouped);
        }

        // Fetch charges & discounts
        $discount = floatval($txnData['discount'] ?? 0);
        $service_charge = floatval($txnData['servicecharge'] ?? 0);
        $delivery_charge = floatval($txnData['delivery_charge'] ?? 0);
        $packing_charge = floatval($txnData['packing_charge'] ?? 0);
        $amount = floatval($txnData['amount'] ?? 0);

        $chargesPayload = array();
        if ($service_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Service charge',
                'amount' => $service_charge
            );
        }
        if ($delivery_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Delivery charge',
                'amount' => $delivery_charge
            );
        }
        if ($packing_charge > 0) {
            $chargesPayload[] = array(
                'name' => 'Packing charge',
                'amount' => $packing_charge
            );
        }

        // Calculate gross and net amount
        $gross_amount = $order_subtotal - $discount;
        $total_charges = $service_charge + $delivery_charge + $packing_charge;
        $total_tax = 0.0;
        foreach ($taxesPayload as $t) {
            $total_tax += $t['amount'];
        }
        $net_amount = $gross_amount + $total_charges + $total_tax;

        // Build base transaction object
        $baseTransaction = array(
            'id' => $txnData['id'] ?? '',
            'number' => $txnData['orderno'] ?? '',
            'refund_type' => $transactionInput['refund_type'] ?? 'cash',
            'reference_transaction_id' => (string)$reference_transaction_id,
            'gross_amount' => $gross_amount,
            'net_amount' => $net_amount,
            'amount' => $amount ?: $net_amount,
            'items' => $itemsPayload,
            'charges' => $chargesPayload,
            'taxes' => $taxesPayload,
            'order_from' => $transactionInput['order_from'] ?? 'pos',
            'order_type' => 'sale_return',
            'order_time' => $txnData['created'] ?? ''
        );

        // Merge overrides
        $mergedTransaction = array_merge($baseTransaction, $transactionInput);

        // Explicitly format items, charges, taxes to array if overridden
        if (isset($transactionInput['items'])) {
            $mergedTransaction['items'] = $transactionInput['items'];
        }
        if (isset($transactionInput['charges'])) {
            $mergedTransaction['charges'] = $transactionInput['charges'];
        }
        if (isset($transactionInput['taxes'])) {
            $mergedTransaction['taxes'] = $transactionInput['taxes'];
        }

        // Build URL
        $paymentTypesUrl = REELO_BASE_URL . "/v1/pos/integrations/" . urlencode(REELO_VENDOR_ID) . "/sale-return";

        $postData = array(
            'customer_mobile' => (string)$mobile,
            'merchant_id' => (string)$merchant_id,
            'transaction' => $mergedTransaction
        );

        $jsonData = json_encode($postData);
        // Execute API request to Reelo
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $response = array(
                'status_code' => 400,
                'message' => 'Failed to connect to Reelo API: ' . $curl_error
            );
            $this->response->type('json');
            return json_encode($response);
        }

        // Decode response to verify it's valid JSON
        $decodedResponse = json_decode($apiResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = array(
                'status_code' => 400,
                'message' => 'Invalid JSON response from Reelo'
            );
            $this->response->type('json');
            return json_encode($response);
        }

        $this->response->type('json');
        return json_encode($decodedResponse);
    }


    /**
     * API to track Reelo discount
     * POST / GET /reelo_apis/track_reelo_discount
    */
     
    public function track_reelo_discount() {
        // Read input data
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $store_id = $jsonData['store_id'] ?? $_REQUEST['store_id'] ?? null;
        $user_id = $jsonData['user_id'] ?? $_REQUEST['user_id'] ?? null; // required
        $reference_id = $jsonData['reference_id'] ?? $_REQUEST['reference_id'] ?? null; // required
        $item_json = $jsonData['item_json'] ?? $_REQUEST['item_json'] ?? null;
        $reelo_discount_type = $jsonData['reelo_discount_type'] ?? $_REQUEST['reelo_discount_type'] ?? null; // required
        $coupon_code = $jsonData['coupon_code'] ?? $_REQUEST['coupon_code'] ?? null; // required if reelo_discount_type = 0
        $discount_on = $jsonData['discount_on'] ?? $_REQUEST['discount_on'] ?? null;
        $discount_type = $jsonData['discount_type'] ?? $_REQUEST['discount_type'] ?? null;
        $discount_value = $jsonData['discount_value'] ?? $_REQUEST['discount_value'] ?? null;
        $otp = $jsonData['otp'] ?? $_REQUEST['otp'] ?? null; // required
        $status = $jsonData['status'] ?? $_REQUEST['status'] ?? 1;
        $service_charge = $jsonData['service_charge'] ?? $_REQUEST['service_charge'] ?? 1;
        $wallet_amount = $jsonData['wallet_credits'] ?? $_REQUEST['wallet_credits'] ?? null;
        $coupon_name = $jsonData['coupon_name'] ?? $_REQUEST['coupon_name'] ?? null;
        
        
        // Validation of mandatory parameters
        if (empty($user_id)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'user_id is required']);
        }
        if (empty($reference_id)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'reference_id is required']);
        }
        if (empty($otp)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'otp is required']);
        }

        // Fetch customer mobile from order table
        $customer_mobile = null;
        $this->loadModel('Order_table');
        $tableData = $this->Order_table->find('first', [
            'conditions' => [
                'Order_table.reference_id' => $reference_id
            ],
            'fields' => ['customer_phone'],
            'recursive' => -1
        ]);
        if (!empty($tableData)) {
            $customer_mobile = $tableData['Order_table']['customer_phone'];
        }
        if (empty($customer_mobile)) {
            $this->loadModel('Order_transaction');
            $txnData = $this->Order_transaction->find('first', [
                'conditions' => [
                    'Order_transaction.reference_id' => $reference_id,
                    'Order_transaction.status !=' => 0
                ],
                'fields' => ['phone'],
                'recursive' => -1
            ]);
            if (!empty($txnData)) {
                $customer_mobile = $txnData['Order_transaction']['phone'];
            }
        }
        if (empty($customer_mobile)) {
            $customer_mobile = $jsonData['customer_mobile'] ?? $_REQUEST['customer_mobile'] ?? null;
        }
        if ($reelo_discount_type === null) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'reelo_discount_type is required']);
        }
        if ($reelo_discount_type != 0) {
            $coupon_code = null;
        }
        if ($reelo_discount_type == 1) {
            $discount_type = $jsonData['discount_type'] ?? $_REQUEST['discount_type'] ?? "fixed";
            $discount_value = $jsonData['points'] ?? $_REQUEST['points'] ?? null;
            if ($discount_value === null) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'points is required for points redemption']);
            }
        }
        if ($reelo_discount_type == 2) {
            if ($wallet_amount === null || $wallet_amount === '') {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'wallet_amount is required']);
            }
            $discount_value = $wallet_amount;
            if (empty($discount_type)) {
                $discount_type = 'fixed';
            }
        }
        if (empty($customer_mobile)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'customer_mobile is required']);
        }

        // Auto-fetch store_id if empty
        if (empty($store_id)) {
            $this->loadModel('User');
            $userData = $this->User->find('first', [
                'conditions' => ['User.id' => $user_id, 'User.status' => 1],
                'fields' => ['location_id'],
                'recursive' => -1
            ]);
            if (!empty($userData)) {
                $store_id = $userData['User']['location_id'];
            }
        }

        // Auto-fetch table_number if empty
        $table_number = $jsonData['table_number'] ?? $_REQUEST['table_number'] ?? null;
        if (empty($table_number)) {
            $this->loadModel('Order_table');
            $tableData = $this->Order_table->find('first', [
                'conditions' => [
                    'Order_table.reference_id' => $reference_id,
                    'Order_table.status' => 2
                ],
                'fields' => ['number'],
                'recursive' => -1
            ]);
            if (!empty($tableData)) {
                $table_number = $tableData['Order_table']['number'] ?? null;
            }
        }
        if (empty($table_number)) {
            $this->loadModel('Order_orderhistory');
            $ohData = $this->Order_orderhistory->find('first', [
                'conditions' => [
                    'Order_orderhistory.reference_id' => $reference_id,
                    'Order_orderhistory.status !=' => 0
                ],
                'fields' => ['table_number'],
                'recursive' => -1
            ]);
            if (!empty($ohData)) {
                $table_number = $ohData['Order_orderhistory']['table_number'];
            }
        }

        // --- Step 2: Get customer loyalty details from Reelo ---
        $loyaltyDetails = null;
        $foundCoupon = null;
        $foundCashback = null;
        $foundMembership = null;

        // Call get_customer_loyalty_details internally
        $this->request->data = [
            'user_id' => $user_id,
            'customer_mobile' => $customer_mobile
        ];
        $loyaltyResponse = $this->get_customer_loyalty_details();
        $loyaltyDetails = json_decode($loyaltyResponse, true);
        
        if (empty($loyaltyDetails) || $loyaltyDetails['status_code'] != 200) {
            $this->response->type('json');
            $errMsg = $loyaltyDetails['message'] ?? 'Failed to get customer loyalty details';
            return json_encode(['status_code' => 400, 'message' => $errMsg]);
        }
        
        $loyaltyData = $loyaltyDetails['response'] ?? [];
        $loyaltyPoints = floatval($loyaltyData['loyalty_points'] ?? 0);
        $totalRedeemablePoints = floatval($loyaltyData['total_redeemable_points'] ?? 0);

        // --- Step 2: Validate allow_redemption_today ---
        $allowRedemptionToday = $loyaltyData['allow_redemption_today'] ?? true;
        if ($allowRedemptionToday === false || strtolower((string)$allowRedemptionToday) === 'false') {
            $this->response->type('json');
            return json_encode([
                'status_code' => 400,
                'message' => 'Redemption is not allowed today'
            ]);
        }

        // If reelo_discount_type == 0, fetch coupon fields early
        if ($reelo_discount_type == 0) {
            if (empty($coupon_code)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'coupon_code is required for coupon discount']);
            }

            if (empty($coupon_name)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'coupon_name is required']);
            }
            $coupons = $loyaltyData['coupons'] ?? [];
            foreach ($coupons as $coupon) {
                if (isset($coupon['coupon_code']) && $coupon['coupon_code'] == $coupon_code && isset($coupon['coupon_name']) && $coupon['coupon_name'] == $coupon_name) {
                    $foundCoupon = $coupon;
                    break;
                }
            }
            
            if (empty($foundCoupon)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'Invalid coupon code']);
            }

            // Fetch coupon configuration fields from get loyalty details response
            $discount_on = $foundCoupon['discount_on'] ?? $discount_on;
            $discount_type = $foundCoupon['discount_type'] ?? $discount_type;
            $discount_value = $foundCoupon['discount_value'] ?? $discount_value;

            if ($discount_type === null) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'discount_type could not be resolved from coupon']);
            }
            if ($discount_value === null) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'discount_value could not be resolved from coupon']);
            }

            // If it is item-level discount, populate item_json from products array
            if (strcasecmp($discount_on, 'item') === 0) {
                $item_json = $foundCoupon['products'] ?? $foundCoupon['items'] ?? $foundCoupon['product_ids'] ?? [];
            }
        }

        // --- Step 1: Check item availability in order history ---
        $this->loadModel('Order_orderhistory');
        
        if (!empty($item_json) && strcasecmp($discount_on, 'item') === 0) {
            $items = is_string($item_json) ? json_decode($item_json, true) : $item_json;
            if (is_array($items)) {
                $hasMatchedItem = false;
                $singleProductId = null;
                if (count($items) === 1) {
                    $singleItem = reset($items);
                    $singleProductId = is_array($singleItem) ? ($singleItem['id'] ?? $singleItem['_id'] ?? $singleItem['product_id'] ?? null) : $singleItem;
                }

                foreach ($items as $item) {
                    $productId = is_array($item) ? ($item['id'] ?? $item['_id'] ?? $item['product_id'] ?? null) : $item;
                    if ($productId === null) {
                        continue;
                    }
                    // Try checking both string and integer versions of product_id in case of type mismatch
                    $ohItems = $this->Order_orderhistory->find('all', [
                        'conditions' => [
                            'Order_orderhistory.reference_id' => $reference_id,
                            'OR' => [
                                'Order_orderhistory.product_id' => $productId,
                                'Order_orderhistory.product_id' => (int)$productId,
                                'Order_orderhistory.product_id' => (string)$productId
                            ],
                            'Order_orderhistory.status !=' => 0
                        ],
                        'recursive' => -1
                    ]);

                    if (!empty($ohItems)) {
                        $hasMatchedItem = true;
                        if ($reelo_discount_type == 0) {
                            $allowedQty = floatval(is_array($item) ? ($item['quantity'] ?? 1) : 1);
                            $totalExistingQty = 0;
                            foreach ($ohItems as $ohItem) {
                                $totalExistingQty += floatval($ohItem['Order_orderhistory']['quantity']);
                            }
                            if ($totalExistingQty < $allowedQty) {
                                $this->response->type('json');
                                return json_encode(['status_code' => 400, 'message' => 'discount is applicable only if you have this much qty']);
                            }
                        }
                        break;
                    }
                }

                if (!$hasMatchedItem) {
                    $this->response->type('json');
                    if (count($items) === 1 && !empty($singleProductId)) {
                        $this->loadModel('Catalog_product');
                        $prod = $this->Catalog_product->find('first', [
                            'conditions' => ['Catalog_product.id' => $singleProductId],
                            'fields' => ['name'],
                            'recursive' => -1
                        ]);
                        $prodName = $prod['Catalog_product']['name'] ?? "ID: " . $singleProductId;
                        return json_encode(['status_code' => 400, 'message' => "Item '{$prodName}' does not exist in this order"]);
                    }
                    return json_encode(['status_code' => 400, 'message' => 'None of the applicable coupon items are available in the order']);
                }
            }
        }

        // --- Step 1a: Get order total amount from order_orderhistory ---
        $this->loadModel('Order_orderhistory');
        $orderTotalResult = $this->Order_orderhistory->query("
            SELECT SUM(total_price) AS total_amount
            FROM order_orderhistories
            WHERE reference_id = '" . addslashes($reference_id) . "' AND status != 0
        ");
        $orderTotal = isset($orderTotalResult[0][0]['total_amount']) ? floatval($orderTotalResult[0][0]['total_amount']) : 0;

        // Validate based on reelo_discount_type (0 = coupon, 1 = points/cashback, 2 = wallet)
        if ($reelo_discount_type == 0) { // Coupon
            // $foundCoupon is already fetched and validated above

            // --- Coupon-specific validation ---
            $couponMinBill = 0;
            if (isset($foundCoupon['min_bill_amount_for_redemption'])) {
                $couponMinBill = floatval($foundCoupon['min_bill_amount_for_redemption']);
            } elseif (isset($foundCoupon['min_bill_amount'])) {
                $couponMinBill = floatval($foundCoupon['min_bill_amount']);
            } elseif (isset($foundCoupon['min_bill_for_redemption'])) {
                $couponMinBill = floatval($foundCoupon['min_bill_for_redemption']);
            }

            if ($couponMinBill > 0 && $orderTotal < $couponMinBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Coupon requires minimum bill amount of $couponMinBill"
                ]);
            }

            if (strcasecmp($discount_on, 'bill') === 0 && strcasecmp($discount_type, 'fixed') === 0) {
                $requiredMinBill = max($couponMinBill, floatval($discount_value) + 1);
                if ($orderTotal < $requiredMinBill) {
                    $this->response->type('json');
                    return json_encode([
                        'status_code' => 400,
                        'message' => "Coupon requires minimum bill amount of $requiredMinBill"
                    ]);
                }
            }

            $couponMaxBill = 0;
            if (isset($foundCoupon['max_bill_amount_for_redemption'])) {
                $couponMaxBill = floatval($foundCoupon['max_bill_amount_for_redemption']);
            } elseif (isset($foundCoupon['max_bill_amount'])) {
                $couponMaxBill = floatval($foundCoupon['max_bill_amount']);
            } elseif (isset($foundCoupon['max_bill_for_redemption'])) {
                $couponMaxBill = floatval($foundCoupon['max_bill_for_redemption']);
            }

            if ($couponMaxBill > 0 && $orderTotal > $couponMaxBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Coupon requires maximum bill amount of $couponMaxBill"
                ]);
            }

            // --- Handle max_discount cap ---
            $maxDiscount = 0;
            if (isset($foundCoupon['max_discount'])) {
                $maxDiscount = floatval($foundCoupon['max_discount']);
            } elseif (isset($foundCoupon['max_discount_value'])) {
                $maxDiscount = floatval($foundCoupon['max_discount_value']);
            }

            if ($maxDiscount > 0 && strcasecmp($discount_on, 'bill') === 0) {
                $proposedDiscount = 0;
                if (strcasecmp($discount_type, 'percentage') === 0) {
                    $proposedDiscount = ($orderTotal * floatval($discount_value)) / 100;
                } else {
                    $proposedDiscount = floatval($discount_value);
                }
                if ($proposedDiscount > $maxDiscount) {
                    $discount_value = $maxDiscount;
                    $discount_type = 'fixed';
                }
            }

            // Check if coupon has products/items and verify they are present in order history
            $couponItems = $foundCoupon['products'] ?? $foundCoupon['items'] ?? $foundCoupon['product_ids'] ?? [];
            if (!empty($couponItems)) {
                $hasMatchedItem = false;
                $singleProductId = null;
                if (count($couponItems) === 1) {
                    $singleItem = reset($couponItems);
                    $singleProductId = is_array($singleItem) ? ($singleItem['_id'] ?? $singleItem['id'] ?? $singleItem['product_id']) : $singleItem;
                }

                foreach ($couponItems as $couponItem) {
                    // Get product_id from coupon item (could be string or array with _id/id/product_id)
                    $productId = is_array($couponItem) ? ($couponItem['_id'] ?? $couponItem['id'] ?? $couponItem['product_id']) : $couponItem;
                    if (empty($productId)) {
                        continue;
                    }

                    // Check if product exists in order history with status != 0
                    // Try checking both string and integer versions of product_id in case of type mismatch
                    $ohItemCheck = $this->Order_orderhistory->find('first', [
                        'conditions' => [
                            'Order_orderhistory.reference_id' => $reference_id,
                            'OR' => [
                                'Order_orderhistory.product_id' => $productId,
                                'Order_orderhistory.product_id' => (int)$productId,
                                'Order_orderhistory.product_id' => (string)$productId
                            ],
                            'Order_orderhistory.status !=' => 0
                        ],
                        'recursive' => -1
                    ]);

                    if (!empty($ohItemCheck)) {
                        $hasMatchedItem = true;
                        break;
                    }
                }

                if (!$hasMatchedItem) {
                    $this->response->type('json');
                    if (count($couponItems) === 1 && !empty($singleProductId)) {
                        $this->loadModel('Catalog_product');
                        $prod = $this->Catalog_product->find('first', [
                            'conditions' => ['Catalog_product.id' => $singleProductId],
                            'fields' => ['name'],
                            'recursive' => -1
                        ]);
                        $prodName = $prod['Catalog_product']['name'] ?? "Product ID: $singleProductId";
                        return json_encode([
                            'status_code' => 400,
                            'message' => "Item '$prodName' is not available in the order"
                        ]);
                    }
                    return json_encode([
                        'status_code' => 400,
                        'message' => 'None of the applicable coupon items are available in the order'
                    ]);
                }
            }
        } 
        else if ($reelo_discount_type == 1) { // Cashback/Points
            $discount_on = 'bill';

            // --- Cashback/Points specific validation ---
            $minBill = 0;
            if (isset($loyaltyData['min_bill_for_redemption'])) {
                $minBill = floatval($loyaltyData['min_bill_for_redemption']);
            } 

            if ($minBill > 0 && $orderTotal < $minBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Minimum bill amount of $minBill is required for points redemption"
                ]);
            }

            $maxBill = 0;
            if (isset($loyaltyData['total_redeemable_points'])) {
                $maxBill = floatval($loyaltyData['total_redeemable_points']);
            }

            if ($maxBill > 0 && $discount_value > $maxBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Maximum bill amount of $maxBill is allowed for points redemption"
                ]);
            }

            // --- Validate point cap (Maximum redemption cap) ---
            $maxCapPercent = 0;
            if (isset($loyaltyData['redemption_cap_on_max_bill_percentage'])) {
                $maxCapPercent = floatval($loyaltyData['redemption_cap_on_max_bill_percentage']);
            }

            // --- Calculate actual discount amount ---
            $actualDiscountAmount = 0;
            if (strcasecmp($discount_type, 'percentage') === 0) {
                $actualDiscountAmount = ($orderTotal * floatval($discount_value)) / 100;
            } else { // Fixed amount
                $actualDiscountAmount = floatval($discount_value);
            }

            // Validate against loyalty points
            if ($loyaltyPoints > 0 && $actualDiscountAmount > $loyaltyPoints) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "You don't have enough loyalty points (you have $loyaltyPoints, need $actualDiscountAmount)"
                ]);
            }

            // Validate against total redeemable points
            if ($totalRedeemablePoints > 0 && $actualDiscountAmount > $totalRedeemablePoints) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Maximum redeemable amount is $totalRedeemablePoints"
                ]);
            }

            if ($maxCapPercent > 0) {
                $maxAllowedDiscount = ($orderTotal * $maxCapPercent) / 100;
                if ($actualDiscountAmount > $maxAllowedDiscount) {
                    $this->response->type('json');
                    return json_encode([
                        'status_code' => 400,
                        'message' => "Maximum points redemption is $maxCapPercent% of bill (amount: $maxAllowedDiscount)"
                    ]);
                }
            }
        } 
        else if ($reelo_discount_type == 2) { // Wallet
            $foundMembership = $loyaltyData['membership'] ?? null;
            $isWalletActive = $foundMembership['active'] ?? false;
            if (empty($foundMembership) || !($isWalletActive === true || $isWalletActive === 1 || $isWalletActive === '1' || strcasecmp((string)$isWalletActive, 'true') === 0)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'Wallet redemption is not active']);
            }

            // Get amount from transaction table
            $this->loadModel('Order_transaction');
            $txnDataForBill = $this->Order_transaction->find('first', [
                'conditions' => [
                    'Order_transaction.reference_id' => $reference_id,
                    'Order_transaction.status !=' => 0
                ],
                'fields' => ['amount'],
                'recursive' => -1
            ]);
            $txnAmount = isset($txnDataForBill['Order_transaction']['amount']) ? floatval($txnDataForBill['Order_transaction']['amount']) : 0;

            if ($txnAmount > 0) {
                $orderTotal = $txnAmount;
            }

            // --- Wallet specific validation ---
            $walletCredits = floatval($foundMembership['wallet_credits'] ?? 0);
            
            $minWalletBill = 0;
            if (isset($foundMembership['min_bill_for_redemption_wallet'])) {
                $minWalletBill = floatval($foundMembership['min_bill_for_redemption_wallet']);
            }   

            if ($minWalletBill > 0 && $orderTotal < $minWalletBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Wallet redemption requires minimum bill amount of $minWalletBill"
                ]);
            }

            $maxWalletBill = 0;
            if (isset($foundMembership['total_redeemable_points_wallet'])) {
                $maxWalletBill = floatval($foundMembership['total_redeemable_points_wallet']);
            } 

            $walletCreditsToRedeem = floatval($discount_value ?? $_REQUEST['wallet_credits'] ?? 0);
            if ($maxWalletBill > 0 && $orderTotal > $maxWalletBill && $walletCreditsToRedeem > $maxWalletBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Wallet redemption requires maximum bill amount of $maxWalletBill"
                ]);
            }

            $maxWalletCapPercent = 0;
            if (isset($foundMembership['redemption_cap_on_max_bill_percentage_wallet'])) {
                $maxWalletCapPercent = floatval($foundMembership['redemption_cap_on_max_bill_percentage_wallet']);
            } 

            if (empty($discount_on)) {
                $discount_on = 'bill';
            }
            if (empty($discount_type)) {
                $discount_type = 'fixed';
            }

            // Calculate actual discount amount for wallet
            $actualWalletDiscount = 0;
            if (strcasecmp($discount_type, 'percentage') === 0) {
                $actualWalletDiscount = ($orderTotal * floatval($discount_value)) / 100;
            } else {
                $actualWalletDiscount = floatval($discount_value);
            }

            if ($actualWalletDiscount > $orderTotal) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Wallet redemption amount ($actualWalletDiscount) cannot exceed the total bill amount ($orderTotal)"
                ]);
            }

            if ($walletCredits > 0 && $actualWalletDiscount > $walletCredits) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Not enough wallet credits (you have $walletCredits, need $actualWalletDiscount)"
                ]);
            }

            // Validate against total redeemable wallet points / credits
            $totalRedeemableWalletPoints = 0;
            if (isset($foundMembership['total_redeemable_points_wallet'])) {
                $totalRedeemableWalletPoints = floatval($foundMembership['total_redeemable_points_wallet']);
            } elseif (isset($foundMembership['total_redeemable_points_membership'])) {
                $totalRedeemableWalletPoints = floatval($foundMembership['total_redeemable_points_membership']);
            }

            if ($totalRedeemableWalletPoints > 0 && $actualWalletDiscount > $totalRedeemableWalletPoints) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Maximum redeemable amount is $totalRedeemableWalletPoints"
                ]);
            }

            if ($maxWalletCapPercent > 0) {
                $maxWalletAmount = ($orderTotal * $maxWalletCapPercent) / 100;
                if ($actualWalletDiscount > $maxWalletAmount) {
                    $this->response->type('json');
                    return json_encode([
                        'status_code' => 400,
                        'message' => "Maximum wallet redemption is $maxWalletCapPercent% of bill (amount: $maxWalletAmount)"
                    ]);
                }
            }
        }

        // Cap fixed bill-level discount to order total to avoid POS validation errors
        // if (strcasecmp($discount_on, 'bill') === 0 && strcasecmp($discount_type, 'fixed') === 0) {
        //     if ($discount_value >= $orderTotal) {
        //         $discount_value = max(0, $orderTotal - 0.01);
        //     }
        // }

        // --- Step 3: Validate OTP and Redeem ---
        $redeemResult = null;
        if ($reelo_discount_type == 0) { // Coupon
            if (empty($otp)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'otp is required for coupon redemption']);
            }
            // Call redeem_coupon internally
            $this->request->data = [
                'user_id' => $user_id,
                'customer_mobile' => $customer_mobile,
                'coupon_code' => $coupon_code,
                'otp' => $otp
            ];
            $redeemResponse = $this->redeem_coupon();
            $redeemResult = json_decode($redeemResponse, true);
            

        } else if ($reelo_discount_type == 1 || $reelo_discount_type == 2) { // Points or Wallet
            if (empty($otp)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'otp is required for points/wallet redemption']);
            }
            // Call redeem_points internally
            $this->request->data = [
                'user_id' => $user_id,
                'customer_mobile' => $customer_mobile,
                ($reelo_discount_type == 2 ? 'wallet_credits' : 'points') => $discount_value,
                'otp' => $otp
            ];
            $redeemResponse = $this->redeem_points();
            $redeemResult = json_decode($redeemResponse, true);
        }

        if (empty($redeemResult) || !in_array($redeemResult['status_code'] ?? null, [200, 201])) {
            $this->response->type('json');
            return json_encode([
                'status' => false,
                'message' => $redeemResult['message'] ?? 'Failed to redeem discount'
            ]);
        }

        // --- Step 4: Save reelo discount tracking record ---
        $this->loadModel('ReeloDiscount');
        $this->ReeloDiscount->create();
        $saveData = [
            'ReeloDiscount' => [
                'store_id' => $store_id,
                'user_id' => $user_id,
                'reference_id' => $reference_id,
                'item_json' => is_array($item_json) ? json_encode($item_json) : $item_json,
                'reelo_discount_type' => $reelo_discount_type,
                'coupon_code' => $coupon_code,
                'discount_on' => $discount_on,
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'otp' => $otp
            ]
        ];
        if (!$this->ReeloDiscount->save($saveData)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'Failed to save reelo discount tracking record']);
        }

        if ($reelo_discount_type == 2) {
            $this->response->type('json');
            return json_encode([
                'status_code' => 200,
                'message' => 'Wallet redemption tracked successfully'
            ]);
        }

        // --- Step 5: Apply discount to order history ---
        // Reset orderhistory discount column for this order (must be 0 for all Reelo discounts)
        $this->Order_orderhistory->updateAll(
            [
                'Order_orderhistory.discount' => 0,
                'Order_orderhistory.sync' => 0,
                'Order_orderhistory.local_sync' => 0
            ],
            ['Order_orderhistory.reference_id' => $reference_id]
        );

        $calculatedItemDiscountSum = 0;
        if (!empty($item_json) && strcasecmp($discount_on, 'item') === 0) {
            $items = is_string($item_json) ? json_decode($item_json, true) : $item_json;
            if (is_array($items)) {
                foreach ($items as $item) {
                    $productId = is_array($item) ? ($item['id'] ?? $item['_id'] ?? $item['product_id'] ?? null) : $item;
                    if ($productId === null || $productId === '') {
                        continue;
                    }
                    
                    $itemDiscountVal = is_array($item) ? ($item['discount_value'] ?? $discount_value) : $discount_value;
                    $itemDiscountType = is_array($item) ? ($item['discount_type'] ?? $discount_type) : $discount_type;

                    $ohItems = $this->Order_orderhistory->find('all', [
                        'conditions' => [
                            'Order_orderhistory.reference_id' => $reference_id,
                            'OR' => [
                                'Order_orderhistory.product_id' => $productId,
                                'Order_orderhistory.product_id' => (int)$productId,
                                'Order_orderhistory.product_id' => (string)$productId
                            ],
                            'Order_orderhistory.status !=' => 0
                        ],
                        'recursive' => -1
                    ]);

                    $allowedQty = floatval(is_array($item) ? ($item['quantity'] ?? 1) : 1);
                    foreach ($ohItems as $ohItem) {
                        $originalTotalPrice = floatval($ohItem['Order_orderhistory']['total_price']);
                        $price = floatval($ohItem['Order_orderhistory']['price']);
                        $quantity = floatval($ohItem['Order_orderhistory']['quantity']);
                        
                        $computedDiscount = 0;
                        if ($reelo_discount_type == 0) {
                            if ($quantity >= $allowedQty) {
                                $discountableQty = $allowedQty;
                            } else {
                                $this->response->type('json');
                                return json_encode(['status_code' => 400, 'message' => 'discount is applicable only if you have this much qty']);
                            }
                            if ($discountableQty > 0) {
                                if (strcasecmp($itemDiscountType, 'percentage') === 0) {
                                    $computedDiscount = round((($price * $discountableQty) * floatval($itemDiscountVal)) / 100, 2);
                                } else {
                                    $computedDiscount = round(min(floatval($itemDiscountVal), $price * $discountableQty), 2);
                                }
                                $allowedQty -= $discountableQty;
                            } else {
                                $computedDiscount = 0;
                            }
                        } else {
                            if (strcasecmp($itemDiscountType, 'percentage') === 0) {
                                $computedDiscount = round(($originalTotalPrice * floatval($itemDiscountVal)) / 100, 2);
                            } else {
                                $computedDiscount = round(floatval($itemDiscountVal), 2);
                            }
                        }
                        $calculatedItemDiscountSum += $computedDiscount;
                    }
                }
            }
        }

        // --- Step 6: Populate $_REQUEST and call bill_table_package_discount ---
        $_REQUEST['id'] = $user_id;
        $_REQUEST['table_number'] = $table_number;
        $_REQUEST['reference_id'] = $reference_id;
        $_REQUEST['kds'] = 'all';
        $_REQUEST['service_charge'] = $service_charge;
        $_REQUEST['loyalty_flag'] = $jsonData['loyalty_flag'] ?? $_REQUEST['loyalty_flag'] ?? 1;
        
        // Check if bill is for No Charge in payments table (type = 7)
        $this->loadModel('Order_payment');
        $ncPayment = $this->Order_payment->find('first', [
            'conditions' => [
                'Order_payment.reference_id' => $reference_id,
                'Order_payment.type' => 7,
                'Order_payment.status !=' => 0
            ],
            'recursive' => -1
        ]);
        if (!empty($ncPayment)) {
            $_REQUEST['loyalty_flag'] = 0;
        }

        $_REQUEST['discount_on'] = $discount_on;
        $_REQUEST['reelo_discount_type'] = $reelo_discount_type;
        $_REQUEST['reelo_discount_value'] = $discount_value;
        $_REQUEST['reelo_discount_type_name'] = $discount_type;
        $_REQUEST['item_json'] = is_array($item_json) ? json_encode($item_json) : $item_json;

        if (strcasecmp($discount_on, 'item') === 0) {
            $_REQUEST['discount'] = (string)$calculatedItemDiscountSum;
            $_REQUEST['discount_value'] = (string)$calculatedItemDiscountSum;
            $_REQUEST['type'] = 2; // Flat fixed discount in transaction
        } else {
            $_REQUEST['discount'] = $discount_value;
            $_REQUEST['discount_value'] = $discount_value;
            $_REQUEST['type'] = (strcasecmp($discount_type, 'percentage') === 0) ? 1 : 2;
        }

        $result = $this->bill_table_package_discount();
        $this->response->type('json');
        return $result;
    }

    /**
     * API to track Reelo discount for split bills
     * POST / GET /reelo_apis/track_split_reelo_discount
    */
    public function track_split_reelo_discount() {
        // Read input data
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $store_id = $jsonData['store_id'] ?? $_REQUEST['store_id'] ?? null;
        $user_id = $jsonData['user_id'] ?? $_REQUEST['user_id'] ?? null; // required
        $split_reference_id = $jsonData['split_reference_id'] ?? $_REQUEST['split_reference_id'] ?? null; // required for split bill
        $reference_id = $jsonData['reference_id'] ?? $_REQUEST['reference_id'] ?? null; // optional (resolved dynamically)
        $item_json = $jsonData['item_json'] ?? $_REQUEST['item_json'] ?? null;
        $reelo_discount_type = $jsonData['reelo_discount_type'] ?? $_REQUEST['reelo_discount_type'] ?? null; // required
        $coupon_code = $jsonData['coupon_code'] ?? $_REQUEST['coupon_code'] ?? null; // required if reelo_discount_type = 0
        $discount_on = $jsonData['discount_on'] ?? $_REQUEST['discount_on'] ?? null;
        $discount_type = $jsonData['discount_type'] ?? $_REQUEST['discount_type'] ?? null;
        $discount_value = $jsonData['discount_value'] ?? $_REQUEST['discount_value'] ?? null;
        $otp = $jsonData['otp'] ?? $_REQUEST['otp'] ?? null; // required
        $status = $jsonData['status'] ?? $_REQUEST['status'] ?? 1;
        $service_charge = $jsonData['service_charge'] ?? $_REQUEST['service_charge'] ?? 1;
        $wallet_amount = $jsonData['wallet_credits'] ?? $_REQUEST['wallet_credits'] ?? null;
        $coupon_name = $jsonData['coupon_name'] ?? $_REQUEST['coupon_name'] ?? null;
        $service_charge = ($service_charge === 'true' || $service_charge === true) ? 1 : 0;
            
        // Validation of mandatory parameters
        if (empty($user_id)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'user_id is required']);
        }
        if (empty($split_reference_id) && empty($reference_id)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'reference_id or split_reference_id is required']);
        }
        if (empty($otp)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'otp is required']);
        }

        $isSplit = !empty($split_reference_id);
        $customer_mobile = null;

        if ($isSplit) {
            // Load split transaction
            $this->loadModel('Order_split_transaction');
            $splitTxn = $this->Order_split_transaction->find('first', [
                'conditions' => [
                    'Order_split_transaction.split_reference_id' => $split_reference_id,
                    'Order_split_transaction.status !=' => 0
                ],
                'recursive' => -1
            ]);
            if (empty($splitTxn)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'Split transaction not found']);
            }
            if ((int)$splitTxn['Order_split_transaction']['status'] === 2) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'This split has already been settled and cannot be modified']);
            }

            $reference_id = $splitTxn['Order_split_transaction']['reference_id'];
            $customer_mobile = $splitTxn['Order_split_transaction']['phone'];
        } else {
            // Load normal parent transaction
            $this->loadModel('Order_transaction');
            $parentTxn = $this->Order_transaction->find('first', [
                'conditions' => [
                    'Order_transaction.reference_id' => $reference_id,
                    'Order_transaction.status !='    => 0
                ],
                'recursive' => -1
            ]);
            if (empty($parentTxn)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'Transaction not found']);
            }
            if ((int)$parentTxn['Order_transaction']['status'] === 2) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'This bill has already been settled and cannot be modified']);
            }

            $customer_mobile = $parentTxn['Order_transaction']['phone'];
        }

        // Set dynamic history model and table based on bill type
        $ohModel = $isSplit ? 'Order_split_orderhistory' : 'Order_orderhistory';
        $ohTable = $isSplit ? 'order_split_orderhistories' : 'order_orderhistories';
        $this->loadModel($ohModel);

        $ohBaseConditions = [
            "{$ohModel}.reference_id" => $reference_id,
            "{$ohModel}.status !="    => 0
        ];
        if ($isSplit) {
            $ohBaseConditions["{$ohModel}.split_reference_id"] = $split_reference_id;
        }

        // Fetch customer mobile from table, fallback to main tables if empty
        if (empty($customer_mobile)) {
            $this->loadModel('Order_table');
            $tableData = $this->Order_table->find('first', [
                'conditions' => ['Order_table.reference_id' => $reference_id],
                'fields' => ['customer_phone'],
                'recursive' => -1
            ]);
            if (!empty($tableData)) {
                $customer_mobile = $tableData['Order_table']['customer_phone'];
            }
        }
        if (empty($customer_mobile)) {
            $this->loadModel('Order_transaction');
            $txnData = $this->Order_transaction->find('first', [
                'conditions' => [
                    'Order_transaction.reference_id' => $reference_id, 
                    'Order_transaction.status !=' => 0
                    ],
                'fields' => ['phone'],
                'recursive' => -1
            ]);
            if (!empty($txnData)) {
                $customer_mobile = $txnData['Order_transaction']['phone'];
            }
        }
        if (empty($customer_mobile)) {
            $customer_mobile = $jsonData['customer_mobile'] ?? $_REQUEST['customer_mobile'] ?? null;
        }

        if ($reelo_discount_type === null) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'reelo_discount_type is required']);
        }
        if ($reelo_discount_type == 1) {
            $discount_type = $jsonData['discount_type'] ?? $_REQUEST['discount_type'] ?? "fixed";
            $discount_value = $jsonData['points'] ?? $_REQUEST['points'] ?? null;
            if ($discount_value === null) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'points is required for points redemption']);
            }
        }
        if ($reelo_discount_type == 2) {
            if ($wallet_amount === null || $wallet_amount === '') {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'wallet_amount is required']);
            }
            $discount_value = $wallet_amount;
            if (empty($discount_type)) {
                $discount_type = 'fixed';
            }
        }
        if (empty($customer_mobile)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'customer_mobile is required']);
        }

        // Auto-fetch store_id if empty
        if (empty($store_id)) {
            $this->loadModel('User');
            $userData = $this->User->find('first', [
                'conditions' => ['User.id' => $user_id, 'User.status' => 1],
                'fields' => ['location_id'],
                'recursive' => -1
            ]);
            if (!empty($userData)) {
                $store_id = $userData['User']['location_id'];
            }
        }

        // Auto-fetch table_number if empty
        $table_number = $jsonData['table_number'] ?? $_REQUEST['table_number'] ?? null;
        if (empty($table_number)) {
            $this->loadModel('Order_table');
            $tableData = $this->Order_table->find('first', [
                'conditions' => [
                    'Order_table.reference_id' => $reference_id,
                    'Order_table.status' => 2
                ],
                'fields' => ['number'],
                'recursive' => -1
            ]);
            if (!empty($tableData)) {
                $table_number = $tableData['Order_table']['number'] ?? null;
            }
        }
        if (empty($table_number)) {
            $ohData = $this->{$ohModel}->find('first', [
                'conditions' => $ohBaseConditions,
                'fields' => ['table_number'],
                'recursive' => -1
            ]);
            if (!empty($ohData)) {
                $table_number = $ohData[$ohModel]['table_number'];
            }
        }

        // --- Step 2: Get customer loyalty details from Reelo ---
        $loyaltyDetails = null;
        $foundCoupon = null;

        $this->request->data = [
            'user_id' => $user_id,
            'customer_mobile' => $customer_mobile
        ];
        $loyaltyResponse = $this->get_customer_loyalty_details();
        $loyaltyDetails = json_decode($loyaltyResponse, true);
        
        if (empty($loyaltyDetails) || $loyaltyDetails['status_code'] != 200) {
            $this->response->type('json');
            $errMsg = $loyaltyDetails['message'] ?? 'Failed to get customer loyalty details';
            return json_encode(['status_code' => 400, 'message' => $errMsg]);
        }
        
        $loyaltyData = $loyaltyDetails['response'] ?? [];
        $loyaltyPoints = floatval($loyaltyData['loyalty_points'] ?? 0);
        $totalRedeemablePoints = floatval($loyaltyData['total_redeemable_points'] ?? 0);

        // Validate allow_redemption_today
        $allowRedemptionToday = $loyaltyData['allow_redemption_today'] ?? true;
        if ($allowRedemptionToday === false || strtolower((string)$allowRedemptionToday) === 'false') {
            $this->response->type('json');
            return json_encode([
                'status_code' => 400,
                'message' => 'Redemption is not allowed today'
            ]);
        }

        // If coupon, validate coupon fields
        if ($reelo_discount_type == 0) {
            if (empty($coupon_code)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'coupon_code is required for coupon discount']);
            }
            if (empty($coupon_name)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'coupon_name is required']);
            }
            $coupons = $loyaltyData['coupons'] ?? [];
            foreach ($coupons as $coupon) {
                if (isset($coupon['coupon_code']) && $coupon['coupon_code'] == $coupon_code && isset($coupon['coupon_name']) && $coupon['coupon_name'] == $coupon_name) {
                    $foundCoupon = $coupon;
                    break;
                }
            }
            
            if (empty($foundCoupon)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'Invalid coupon code']);
            }

            $discount_on = $foundCoupon['discount_on'] ?? $discount_on;
            $discount_type = $foundCoupon['discount_type'] ?? $discount_type;
            $discount_value = $foundCoupon['discount_value'] ?? $discount_value;

            if ($discount_type === null) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'discount_type could not be resolved from coupon']);
            }
            if ($discount_value === null) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'discount_value could not be resolved from coupon']);
            }

            if (strcasecmp($discount_on, 'item') === 0) {
                $item_json = $foundCoupon['products'] ?? $foundCoupon['items'] ?? $foundCoupon['product_ids'] ?? [];
            }
        }

        // Check item availability in order history
        if (!empty($item_json) && strcasecmp($discount_on, 'item') === 0) {
            $items = is_string($item_json) ? json_decode($item_json, true) : $item_json;
            if (is_array($items)) {
                $hasMatchedItem = false;
                $singleProductId = null;
                if (count($items) === 1) {
                    $singleItem = reset($items);
                    $singleProductId = is_array($singleItem) ? ($singleItem['id'] ?? $singleItem['_id'] ?? $singleItem['product_id'] ?? null) : $singleItem;
                }

                foreach ($items as $item) {
                    $productId = is_array($item) ? ($item['id'] ?? $item['_id'] ?? $item['product_id'] ?? null) : $item;
                    if ($productId === null) {
                        continue;
                    }
                    $ohItems = $this->{$ohModel}->find('all', [
                        'conditions' => array_merge($ohBaseConditions, [
                            'OR' => [
                                "{$ohModel}.product_id" => $productId,
                                "{$ohModel}.product_id" => (int)$productId,
                                "{$ohModel}.product_id" => (string)$productId
                            ]
                        ]),
                        'recursive' => -1
                    ]);

                    if (!empty($ohItems)) {
                        $hasMatchedItem = true;
                        if ($reelo_discount_type == 0) {
                            $allowedQty = floatval(is_array($item) ? ($item['quantity'] ?? 1) : 1);
                            $totalExistingQty = 0;
                            foreach ($ohItems as $ohItem) {
                                $totalExistingQty += floatval($ohItem[$ohModel]['quantity']);
                            }
                            if ($totalExistingQty < $allowedQty) {
                                $this->response->type('json');
                                return json_encode(['status_code' => 400, 'message' => 'discount is applicable only if you have this much qty']);
                            }
                        }
                        break;
                    }
                }

                if (!$hasMatchedItem) {
                    $this->response->type('json');
                    if (count($items) === 1 && !empty($singleProductId)) {
                        $this->loadModel('Catalog_product');
                        $prod = $this->Catalog_product->find('first', [
                            'conditions' => ['Catalog_product.id' => $singleProductId],
                            'fields' => ['name'],
                            'recursive' => -1
                        ]);
                        $prodName = $prod['Catalog_product']['name'] ?? "ID: " . $singleProductId;
                        $orderText = $isSplit ? 'this split order' : 'this order';
                        return json_encode(['status_code' => 400, 'message' => "Item '{$prodName}' does not exist in {$orderText}"]);
                    }
                    $orderText = $isSplit ? 'the split order' : 'the order';
                    return json_encode(['status_code' => 400, 'message' => "None of the applicable coupon items are available in {$orderText}"]);
                }
            }
        }

        // Get order total amount
        $orderTotalResult = $this->{$ohModel}->query("
            SELECT SUM(total_price) AS total_amount
            FROM {$ohTable}
            WHERE reference_id = '" . addslashes($reference_id) . "'
              " . ($isSplit ? "AND split_reference_id = '" . addslashes($split_reference_id) . "'" : "") . "
              AND status != 0
        ");
        $orderTotal = isset($orderTotalResult[0][0]['total_amount']) ? floatval($orderTotalResult[0][0]['total_amount']) : 0;

        // Validate coupon constraints
        if ($reelo_discount_type == 0) { // Coupon
            $couponMinBill = 0;
            if (isset($foundCoupon['min_bill_amount_for_redemption'])) {
                $couponMinBill = floatval($foundCoupon['min_bill_amount_for_redemption']);
            } elseif (isset($foundCoupon['min_bill_amount'])) {
                $couponMinBill = floatval($foundCoupon['min_bill_amount']);
            } elseif (isset($foundCoupon['min_bill_for_redemption'])) {
                $couponMinBill = floatval($foundCoupon['min_bill_for_redemption']);
            }

            if ($couponMinBill > 0 && $orderTotal < $couponMinBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Coupon requires minimum bill amount of $couponMinBill"
                ]);
            }

            if (strcasecmp($discount_on, 'bill') === 0 && strcasecmp($discount_type, 'fixed') === 0) {
                $requiredMinBill = max($couponMinBill, floatval($discount_value) + 1);
                if ($orderTotal < $requiredMinBill) {
                    $this->response->type('json');
                    return json_encode([
                        'status_code' => 400,
                        'message' => "Coupon requires minimum bill amount of $requiredMinBill"
                    ]);
                }
            }

            $couponMaxBill = 0;
            if (isset($foundCoupon['max_bill_amount_for_redemption'])) {
                $couponMaxBill = floatval($foundCoupon['max_bill_amount_for_redemption']);
            } elseif (isset($foundCoupon['max_bill_amount'])) {
                $couponMaxBill = floatval($foundCoupon['max_bill_amount']);
            } elseif (isset($foundCoupon['max_bill_for_redemption'])) {
                $couponMaxBill = floatval($foundCoupon['max_bill_for_redemption']);
            }

            if ($couponMaxBill > 0 && $orderTotal > $couponMaxBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Coupon requires maximum bill amount of $couponMaxBill"
                ]);
            }

            $maxDiscount = 0;
            if (isset($foundCoupon['max_discount'])) {
                $maxDiscount = floatval($foundCoupon['max_discount']);
            } elseif (isset($foundCoupon['max_discount_value'])) {
                $maxDiscount = floatval($foundCoupon['max_discount_value']);
            }

            if ($maxDiscount > 0 && strcasecmp($discount_on, 'bill') === 0) {
                $proposedDiscount = 0;
                if (strcasecmp($discount_type, 'percentage') === 0) {
                    $proposedDiscount = ($orderTotal * floatval($discount_value)) / 100;
                } else {
                    $proposedDiscount = floatval($discount_value);
                }
                if ($proposedDiscount > $maxDiscount) {
                    $discount_value = $maxDiscount;
                    $discount_type = 'fixed';
                }
            }

            $couponItems = $foundCoupon['products'] ?? $foundCoupon['items'] ?? $foundCoupon['product_ids'] ?? [];
            if (!empty($couponItems)) {
                $hasMatchedItem = false;
                $singleProductId = null;
                if (count($couponItems) === 1) {
                    $singleItem = reset($couponItems);
                    $singleProductId = is_array($singleItem) ? ($singleItem['_id'] ?? $singleItem['id'] ?? $singleItem['product_id']) : $singleItem;
                }

                foreach ($couponItems as $couponItem) {
                    $productId = is_array($couponItem) ? ($couponItem['_id'] ?? $couponItem['id'] ?? $couponItem['product_id']) : $couponItem;
                    if (empty($productId)) {
                        continue;
                    }

                    $ohItemCheck = $this->{$ohModel}->find('first', [
                        'conditions' => array_merge($ohBaseConditions, [
                            'OR' => [
                                "{$ohModel}.product_id" => $productId,
                                "{$ohModel}.product_id" => (int)$productId,
                                "{$ohModel}.product_id" => (string)$productId
                            ]
                        ]),
                        'recursive' => -1
                    ]);

                    if (!empty($ohItemCheck)) {
                        $hasMatchedItem = true;
                        break;
                    }
                }

                if (!$hasMatchedItem) {
                    $this->response->type('json');
                    if (count($couponItems) === 1 && !empty($singleProductId)) {
                        $this->loadModel('Catalog_product');
                        $prod = $this->Catalog_product->find('first', [
                            'conditions' => ['Catalog_product.id' => $singleProductId],
                            'fields' => ['name'],
                            'recursive' => -1
                        ]);
                        $prodName = $prod['Catalog_product']['name'] ?? "Product ID: $singleProductId";
                        $orderText = $isSplit ? 'the split order' : 'the order';
                        return json_encode([
                            'status_code' => 400,
                            'message' => "Item '$prodName' is not available in {$orderText}"
                        ]);
                    }
                    $orderText = $isSplit ? 'the split order' : 'the order';
                    return json_encode([
                        'status_code' => 400,
                        'message' => "None of the applicable coupon items are available in {$orderText}"
                    ]);
                }
            }
        } 
        else if ($reelo_discount_type == 1) { // Cashback/Points
        $discount_on = 'bill';

            $minBill = 0;
            if (isset($loyaltyData['min_bill_for_redemption'])) {
                $minBill = floatval($loyaltyData['min_bill_for_redemption']);
            } 

            if ($minBill > 0 && $orderTotal < $minBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Minimum bill amount of $minBill is required for points redemption"
                ]);
            }

            $maxBill = 0;
            if (isset($loyaltyData['total_redeemable_points'])) {
                $maxBill = floatval($loyaltyData['total_redeemable_points']);
            }

            if ($maxBill > 0 && $discount_value > $maxBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Maximum bill amount of $maxBill is allowed for points redemption"
                ]);
            }

            $maxCapPercent = 0;
            if (isset($loyaltyData['redemption_cap_on_max_bill_percentage'])) {
                $maxCapPercent = floatval($loyaltyData['redemption_cap_on_max_bill_percentage']);
            }

            $actualDiscountAmount = 0;
            if (strcasecmp($discount_type, 'percentage') === 0) {
                $actualDiscountAmount = ($orderTotal * floatval($discount_value)) / 100;
            } else {
                $actualDiscountAmount = floatval($discount_value);
            }

            if ($loyaltyPoints > 0 && $actualDiscountAmount > $loyaltyPoints) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "You don't have enough loyalty points (you have $loyaltyPoints, need $actualDiscountAmount)"
                ]);
            }

            if ($totalRedeemablePoints > 0 && $actualDiscountAmount > $totalRedeemablePoints) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Maximum redeemable amount is $totalRedeemablePoints"
                ]);
            }

            if ($maxCapPercent > 0) {
                $maxAllowedDiscount = ($orderTotal * $maxCapPercent) / 100;
                if ($actualDiscountAmount > $maxAllowedDiscount) {
                    $this->response->type('json');
                    return json_encode([
                        'status_code' => 400,
                        'message' => "Maximum points redemption is $maxCapPercent% of bill (amount: $maxAllowedDiscount)"
                    ]);
                }
            }
        } else if ($reelo_discount_type == 2) { // Wallet
            $foundMembership = $loyaltyData['membership'] ?? null;
            $isWalletActive = $foundMembership['active'] ?? false;
            if (empty($foundMembership) || !($isWalletActive === true || $isWalletActive === 1 || $isWalletActive === '1' || strcasecmp((string)$isWalletActive, 'true') === 0)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'Wallet redemption is not active']);
            }

            $txnAmount = $isSplit ? (float)($splitTxn['Order_split_transaction']['amount'] ?? 0) : (float)($parentTxn['Order_transaction']['amount'] ?? 0);
            if ($txnAmount > 0) {
                $orderTotal = $txnAmount;
            }

            $walletCredits = floatval($foundMembership['wallet_credits'] ?? 0);
            
            $minWalletBill = 0;
            if (isset($foundMembership['min_bill_for_redemption_wallet'])) {
                $minWalletBill = floatval($foundMembership['min_bill_for_redemption_wallet']);
            }   

            if ($minWalletBill > 0 && $orderTotal < $minWalletBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Wallet redemption requires minimum bill amount of $minWalletBill"
                ]);
            }

            $maxWalletBill = 0;
            if (isset($foundMembership['total_redeemable_points_wallet'])) {
                $maxWalletBill = floatval($foundMembership['total_redeemable_points_wallet']);
            } 

            $walletCreditsToRedeem = floatval($discount_value ?? $_REQUEST['wallet_credits'] ?? 0);
            if ($maxWalletBill > 0 && $orderTotal > $maxWalletBill && $walletCreditsToRedeem > $maxWalletBill) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Wallet redemption requires maximum bill amount of $maxWalletBill"
                ]);
            }

            $maxWalletCapPercent = 0;
            if (isset($foundMembership['redemption_cap_on_max_bill_percentage_wallet'])) {
                $maxWalletCapPercent = floatval($foundMembership['redemption_cap_on_max_bill_percentage_wallet']);
            } 

            if (empty($discount_on)) {
                $discount_on = 'bill';
            }
            if (empty($discount_type)) {
                $discount_type = 'fixed';
            }

            $actualWalletDiscount = 0;
            if (strcasecmp($discount_type, 'percentage') === 0) {
                $actualWalletDiscount = ($orderTotal * floatval($discount_value)) / 100;
            } else {
                $actualWalletDiscount = floatval($discount_value);
            }

            if ($actualWalletDiscount > $orderTotal) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Wallet redemption amount ($actualWalletDiscount) cannot exceed the total bill amount ($orderTotal)"
                ]);
            }

            if ($walletCredits > 0 && $actualWalletDiscount > $walletCredits) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Not enough wallet credits (you have $walletCredits, need $actualWalletDiscount)"
                ]);
            }

            $totalRedeemableWalletPoints = 0;
            if (isset($foundMembership['total_redeemable_points_wallet'])) {
                $totalRedeemableWalletPoints = floatval($foundMembership['total_redeemable_points_wallet']);
            } elseif (isset($foundMembership['total_redeemable_points_membership'])) {
                $totalRedeemableWalletPoints = floatval($foundMembership['total_redeemable_points_membership']);
            }

            if ($totalRedeemableWalletPoints > 0 && $actualWalletDiscount > $totalRedeemableWalletPoints) {
                $this->response->type('json');
                return json_encode([
                    'status_code' => 400,
                    'message' => "Maximum redeemable amount is $totalRedeemableWalletPoints"
                ]);
            }

            if ($maxWalletCapPercent > 0) {
                $maxWalletAmount = ($orderTotal * $maxWalletCapPercent) / 100;
                if ($actualWalletDiscount > $maxWalletAmount) {
                    $this->response->type('json');
                    return json_encode([
                        'status_code' => 400,
                        'message' => "Maximum wallet redemption is $maxWalletCapPercent% of bill (amount: $maxWalletAmount)"
                    ]);
                }
            }
        }

        // Cap fixed bill-level discount to split order total to avoid POS validation errors
        if (strcasecmp($discount_on, 'bill') === 0 && strcasecmp($discount_type, 'fixed') === 0) {
            if ($discount_value >= $orderTotal) {
                $discount_value = max(0, $orderTotal - 0.01);
            }
        }

        // --- Step 3: Validate OTP and Redeem ---
        $redeemResult = null;
        if ($reelo_discount_type == 0) { // Coupon
            if (empty($otp)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'otp is required for coupon redemption']);
            }
            $this->request->data = [
                'user_id' => $user_id,
                'customer_mobile' => $customer_mobile,
                'coupon_code' => $coupon_code,
                'otp' => $otp
            ];
            $redeemResponse = $this->redeem_coupon();
            $redeemResult = json_decode($redeemResponse, true);

        } else if ($reelo_discount_type == 1 || $reelo_discount_type == 2) { // Points or Wallet
            if (empty($otp)) {
                $this->response->type('json');
                return json_encode(['status_code' => 400, 'message' => 'otp is required for points/wallet redemption']);
            }
            $this->request->data = [
                'user_id' => $user_id,
                'customer_mobile' => $customer_mobile,
                'points' => $discount_value,
                'otp' => $otp
            ];
            if ($reelo_discount_type == 2 && isset($foundMembership['wallet_credits'])) {
                $this->request->data['wallet_credits'] = $foundMembership['wallet_credits'];
            }
            $redeemResponse = $this->redeem_points();
            $redeemResult = json_decode($redeemResponse, true);

        }
        
        if (empty($redeemResult) || !in_array($redeemResult['status_code'] ?? null, [200, 201])) {
            $this->response->type('json');
            return json_encode([
                'status' => false,
                'message' => $redeemResult['message'] ?? 'Failed to redeem discount'
            ]);
        }

        // --- Step 4: Save reelo discount tracking record ---
        $this->loadModel('ReeloDiscount');
        $this->ReeloDiscount->create();
        $saveData = [
            'ReeloDiscount' => [
                'store_id' => $store_id,
                'user_id' => $user_id,
                'reference_id' => $reference_id,
                'split_reference_id' => $split_reference_id,
                'item_json' => is_array($item_json) ? json_encode($item_json) : $item_json,
                'reelo_discount_type' => $reelo_discount_type,
                'coupon_code' => $coupon_code,
                'discount_on' => $discount_on,
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'otp' => $otp
            ]
        ];
        if (!$this->ReeloDiscount->save($saveData)) {
            $this->response->type('json');
            return json_encode(['status_code' => 400, 'message' => 'Failed to save reelo discount tracking record']);
        }

        if ($reelo_discount_type == 2) {
            $this->response->type('json');
            return json_encode([
                'status_code' => 200,
                'message' => 'Wallet redemption tracked successfully'
            ]);
        }

        // Reset order history discount column for this order (must be 0 for all Reelo discounts)
        $resetConds = ['reference_id' => $reference_id];
        if ($isSplit) {
            $resetConds['split_reference_id'] = $split_reference_id;
        }
        $this->{$ohModel}->updateAll(
            [
                "{$ohModel}.discount" => 0,
                "{$ohModel}.sync" => 0,
                "{$ohModel}.local_sync" => 0
            ],
            $resetConds
        );

        $calculatedSplitItemDiscountSum = 0;
        if (!empty($item_json) && strcasecmp($discount_on, 'item') === 0) {
            $items = is_string($item_json) ? json_decode($item_json, true) : $item_json;
            if (is_array($items)) {
                foreach ($items as $item) {
                    $productId = is_array($item) ? ($item['id'] ?? $item['_id'] ?? $item['product_id'] ?? null) : $item;
                    if ($productId === null || $productId === '') {
                        continue;
                    }
                    
                    $itemDiscountVal = is_array($item) ? ($item['discount_value'] ?? $discount_value) : $discount_value;
                    $itemDiscountType = is_array($item) ? ($item['discount_type'] ?? $discount_type) : $discount_type;

                    $ohItems = $this->{$ohModel}->find('all', [
                        'conditions' => array_merge($ohBaseConditions, [
                            'OR' => [
                                "{$ohModel}.product_id" => $productId,
                                "{$ohModel}.product_id" => (int)$productId,
                                "{$ohModel}.product_id" => (string)$productId
                            ]
                        ]),
                        'recursive' => -1
                    ]);

                    $allowedQty = floatval(is_array($item) ? ($item['quantity'] ?? 1) : 1);
                    foreach ($ohItems as $ohItem) {
                        $originalTotalPrice = floatval($ohItem[$ohModel]['total_price']);
                        $quantity = floatval($ohItem[$ohModel]['quantity']);
                        
                        $computedDiscount = 0;
                        if ($reelo_discount_type == 0) {
                            $price = isset($ohItem[$ohModel]['price']) ? floatval($ohItem[$ohModel]['price']) : ($quantity > 0 ? floatval($originalTotalPrice / $quantity) : 0);
                            if ($quantity >= $allowedQty) {
                                $discountableQty = $allowedQty;
                            } else {
                                $this->response->type('json');
                                return json_encode(['status_code' => 400, 'message' => 'discount is applicable only if you have this much qty']);
                            }
                            if ($discountableQty > 0) {
                                if (strcasecmp($itemDiscountType, 'percentage') === 0) {
                                    $computedDiscount = round((($price * $discountableQty) * floatval($itemDiscountVal)) / 100, 2);
                                } else {
                                    $computedDiscount = round(min(floatval($itemDiscountVal), $price * $discountableQty), 2);
                                }
                                $allowedQty -= $discountableQty;
                            } else {
                                $computedDiscount = 0;
                            }
                        } else {
                            if (strcasecmp($itemDiscountType, 'percentage') === 0) {
                                $computedDiscount = round(($originalTotalPrice * floatval($itemDiscountVal)) / 100, 2);
                            } else {
                                $computedDiscount = round(floatval($itemDiscountVal), 2);
                            }
                        }
                        $calculatedSplitItemDiscountSum += $computedDiscount;
                    }
                }
            }
        }

        // Populate $_REQUEST for update_split_adjustments
        $_REQUEST['id'] = $user_id;
        $_REQUEST['reference_id'] = $reference_id;
        $_REQUEST['split_reference_id'] = $isSplit ? $split_reference_id : '';
        $_REQUEST['kds'] = 'all';
        $_REQUEST['service_charge'] = $service_charge;
        $_REQUEST['discount_on'] = $discount_on;
        // $_REQUEST['reelo_discount_type'] = $reelo_discount_type;
        // $_REQUEST['reelo_discount_value'] = $discount_value;
        // $_REQUEST['reelo_discount_type_name'] = $discount_type;
        // $_REQUEST['item_json'] = is_array($item_json) ? json_encode($item_json) : $item_json;

        $_REQUEST['loyalty_flag'] = $jsonData['loyalty_flag'] ?? $_REQUEST['loyalty_flag'] ?? 1;

        // Check if bill is for No Charge in payments table (type = 7)
        if ($isSplit) {
            $this->loadModel('Order_split_payment');
            $ncPayment = $this->Order_split_payment->find('first', [
                'conditions' => [
                    'Order_split_payment.reference_id' => $reference_id,
                    'Order_split_payment.split_reference_id' => $split_reference_id,
                    'Order_split_payment.type' => 7,
                    'Order_split_payment.status !=' => 0
                ],
                'recursive' => -1
            ]);
            if (!empty($ncPayment)) {
                $_REQUEST['loyalty_flag'] = 0;
            }
        } else {
            $this->loadModel('Order_payment');
            $ncPayment = $this->Order_payment->find('first', [
                'conditions' => [
                    'Order_payment.reference_id' => $reference_id,
                    'Order_payment.type' => 7,
                    'Order_payment.status !=' => 0
                ],
                'recursive' => -1
            ]);
            if (!empty($ncPayment)) {
                $_REQUEST['loyalty_flag'] = 0;
            }
        }

        if ($_REQUEST['loyalty_flag'] == 0) {
            if ($isSplit) {
                $this->loadModel('Order_split_transaction');
                $this->Order_split_transaction->updateAll(
                    [
                        'Order_split_transaction.loyalty_flag' => 0,
                        'Order_split_transaction.sync' => 0,
                        'Order_split_transaction.local_sync' => 0
                    ],
                    [
                        'Order_split_transaction.reference_id' => $reference_id,
                        'Order_split_transaction.split_reference_id' => $split_reference_id,
                        'Order_split_transaction.status !=' => 0
                    ]
                );
            }
            $this->loadModel('Order_transaction');
            $this->Order_transaction->updateAll(
                [
                    'Order_transaction.loyalty_flag' => 0,
                    'Order_transaction.sync' => 0,
                    'Order_transaction.local_sync' => 0
                ],
                [
                    'Order_transaction.reference_id' => $reference_id,
                    'Order_transaction.status !=' => 0
                ]
            );
        }

        if (strcasecmp($discount_on, 'item') === 0) {
            $_REQUEST['discount'] = (string)$calculatedSplitItemDiscountSum;
            $_REQUEST['discount_value'] = (string)$calculatedSplitItemDiscountSum;
            $_REQUEST['type'] = 2; // Flat fixed discount in split transaction
        } else {
            $_REQUEST['discount'] = $discount_value;
            $_REQUEST['discount_value'] = $discount_value;
            $_REQUEST['type'] = (strcasecmp($discount_type, 'percentage') === 0) ? 1 : 2;
        }

        // Call update_split_adjustments
        $result = $this->update_split_adjustments();
        $resArray = json_decode($result, true);
        if (is_array($resArray) && isset($resArray['status'])) {
            $resArray['status_code'] = $resArray['status'] ? 200 : 400;
            if (is_array($resArray)) {
                if (!isset($resArray['status_code'])) {
                    $resArray['status_code'] = (!empty($resArray['status']) && $resArray['status'] !== false) ? 200 : 400;
                }
            } else {
                $resArray = ['status_code' => 400, 'message' => 'Something went wrong'];
            }
        }
        
        $this->response->type('json');
        return json_encode($resArray);
    }
    // --- COPIES OF FUNCTIONS FROM ApisControllerLocal.php ---

    public function bill_table_package_discount()
    {
        $this->loadModel('User');
        $this->loadModel('Order_table');
        $this->loadModel('Order_customers');
        $this->loadModel('Order_transaction');
        $this->loadModel('Order_table_tracking');
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_tax');
        $this->loadModel('Catalog_package');

        $response['status_code']= 400;
        $response['message']= "fail";
        
        $cashierid = $_REQUEST['id'];
        $loyalty_flag = $_REQUEST['loyalty_flag'] ?? 1;
        if($cashierid==''){
            $response =array(
                'status_code'=> 400,
                'message'=> "Cashier Id is Missing",
            );
            return json_encode($response);
        }
        $table_number = $_REQUEST['table_number'];
        if($table_number==''){
            $response =array(
                'status_code'=> 400,
                'message'=> "Table Number is Missing",
            );
            return json_encode($response);
        }
        $table_referenceid = $_REQUEST['reference_id'];
        if($table_referenceid==''){
            $response =array(
                'status_code'=> 400,
                'message'=> "Table refernce Id is Missing",
            );
            return json_encode($response);
        }
        
        $disc=$_REQUEST['discount'];
        $type = $_REQUEST['type'];
        $dish_type = $_REQUEST['kds'];
        $sc = $_REQUEST['service_charge'] ?? 0;
        
        if($disc==''){
            $response =array(
                'status_code'=> 400,
                'message'=> "Discount Missing",
            );
            return json_encode($response);
        }
        
        if($type === '' || $type === null){
            $response =array(
                'status_code'=> 400,
                'message'=> "Discount Type Missing",
            );
            return json_encode($response);
        }
        
        if($dish_type==''){
            $response =array(
                'status_code'=> 400,
                'message'=> "Dish Type Missing",
            );
            return json_encode($response);
        }
        
        $device_id='';
        if(isset($_REQUEST['device_id'])){
            $device_id=$_REQUEST['device_id'];
        }
        $name='';
        if(isset($_REQUEST['name'])){
            $name=$_REQUEST['name'];
        }
        $phone='';
        if(isset($_REQUEST['phone'])){
            $phone=$_REQUEST['phone'];
        }
        $address='';
        if(isset($_REQUEST['address'])){
            $address=$_REQUEST['address'];
        }
        $pax=1;
        if(isset($_REQUEST['pax'])){
            $pax=$_REQUEST['pax'];
        }
        $users = $this->User->find('first', array('conditions'=>array('User.id'=>$cashierid,'User.status'=>1)));
        
        if(empty($users))
        {
            $response =array(
                'status_code'=> 400,
                'message'=> "User not found",
            );
            return json_encode($response);
        }
        else{
            $location_id = $users['User']['location_id'];
        }
            
        $tablechk = $this->Order_table->find('first', array('conditions'=>array('Order_table.reference_id'=>$table_referenceid,'Order_table.number'=>$table_number,'Order_table.status'=>2)));
        if(empty($tablechk))
        {
            $response =array(
                'status_code'=> 400,
                'message'=> "Table not found",
            );
            return json_encode($response);
        }
        $waiter_id=$tablechk['Order_table']['waiter_id'];
        $waiter_name = $tablechk['Order_table']['waiter_name'];
        $pax = $tablechk['Order_table']['customer_pax'];
        $name    = trim($tablechk['Order_table']['customer_name']);
        $phone   = trim($tablechk['Order_table']['customer_phone']);
        $address = trim($tablechk['Order_table']['customer_address']);
        $is_takeaway = $tablechk['Order_table']['is_takeaway'];
        
        $customerchk = $this->Order_customers->find('first', [
            'conditions' => ['Order_customers.phone' => $phone,'Order_customers.status !=' => 0],
            'fields' => ['Order_customers.id'],
            'recursive' => -1
        ]);
    
        if (empty($customerchk)) {
            // Insert new customer
            $data = [
                'Order_customers' => [
                    'phone'   => $phone,
                    'name'    => $name,
                    'address' => $address
                ]
            ];
    
            if ($this->Order_customers->saveAll($data)) {
                
            } else {
                return json_encode([
                    'status_code' => 400,
                    'message' => "Failed to save customer details."
                ]);
            }
        } else {
            // Customer already exists — optional: update their name/address if needed
            $this->Order_customers->id = $customerchk['Order_customers']['id'];
            $this->Order_customers->save([
                'name' => $name,
                'address' => $address
            ]);
    
        }
        
        $this->Order_table->updateAll(
            [
                // 'Order_table.waiter_id' => "'0'",
                // 'Order_table.waiter_name' => "''",
                // 'Order_table.customer_address' => "''",
                // 'Order_table.customer_phone' => "''",
                'Order_table.sync'              => 0,
                'Order_table.local_sync'        => 0
            ],
            ['Order_table.reference_id' => $table_referenceid]
        );
        
        $trxchck = $this->Order_transaction->find('first', array(
            'conditions' => array(
                'Order_transaction.reference_id' => $table_referenceid,
                'NOT' => array('Order_transaction.status' => array(3, 4, 0))
            )
        ));
        if(!empty($trxchck))
        {
            // Transaction already exists - update table status and proceed directly to kds discount recalculation & update
            $this->Order_table->query("UPDATE order_tables SET status = 3,sync=0,local_sync=0 WHERE reference_id = '$table_referenceid' and status!=0");
            $kdsResult = $this->bill_table_package_kds_discount($cashierid,$table_referenceid,$disc,$sc,$type,$dish_type);
            if (!$kdsResult) {
                return json_encode([
                    'status_code' => 400,
                    'message' => "Discount not saved",
                ]);
            }
            return json_encode([
                'status_code' => 200,
                'message' => "Bill Generated Successfully..!"
            ]);
        }        
        
        $todayDate = date("Y-m-d");
        
        $refid = []; // T9-fix: no merged tables guard

        $mergetablecheck = $this->Order_table_tracking->find('all', [
            'conditions' => [
                'Order_table_tracking.main_table_ref_id' => $table_referenceid,
                'Order_table_tracking.status' => 1,
                "DATE(Order_table_tracking.created) =" => $todayDate
            ]
        ]);
        if (!empty($mergetablecheck)) {
            foreach ($mergetablecheck as $merge) {
                $refid[] = $merge['Order_table_tracking']['merged_table_ref_id']; 
            }
        }
        
        $refid = "'" . implode("','", $refid) . "'";
        
        $this->Order_orderhistory->query("
            UPDATE order_orderhistories o
            SET o.reference_id = '$table_referenceid',o.sync=0,o.local_sync=0
            WHERE o.reference_id IN ($refid)
        ");
        
        $this->Order_tax->query("
            UPDATE order_tax t
            SET t.reference_id = '$table_referenceid',t.sync=0,t.local_sync=0
            WHERE t.reference_id IN ($refid)
        ");
        
        //chk package value in table - item in ordergistory link quanti*package
        // Fetch all product_ids for the given reference_id
        // 1. Set package=1 for packaged items using JOIN (efficient)
        $this->Order_orderhistory->query("
            UPDATE order_orderhistories o
            JOIN catalog_package p ON o.product_id = p.main_product_id AND p.status = 1
            SET o.package = 1,o.sync=0,o.local_sync=0
            WHERE o.reference_id = '$table_referenceid' AND o.status != 0
        ");
        
        $this->Order_tax->query("
            UPDATE order_tax t
            JOIN catalog_package p ON t.product_id = p.main_product_id AND p.status = 1
            SET t.package = 1,t.sync=0,t.local_sync=0
            WHERE t.reference_id = '$table_referenceid' AND t.status = 1
        ");
        // 2. Get total packaged quantity
        $total_package = 0;
        $totalpackagechkorder = $this->Order_orderhistory->query("
            SELECT SUM(package) AS total_package
            FROM order_orderhistories 
            WHERE reference_id = '$table_referenceid' AND status != 0 AND package = 1
        ");
        $packagechngchk=false;
        if (!empty($totalpackagechkorder[0][0]['total_package'])) {
            
            $total_package = isset($totalpackagechkorder[0][0]['total_package']) 
                ? $totalpackagechkorder[0][0]['total_package'] 
                : 0;
                // 3. Non-package items and discount percentage logic
                $discount_percentage = 0;
                $nonpackagechkorder = $this->Order_orderhistory->query("
                    SELECT product_id, SUM(quantity) as order_quantity
                    FROM order_orderhistories 
                    WHERE reference_id = '$table_referenceid' AND status != 0 AND package = 0
                    GROUP BY product_id
                ");
                $discount_pro_array = []; // initialize
                foreach ($nonpackagechkorder as $row) {
                    $orderproduct_id = $row['order_orderhistories']['product_id'];
                    $orderproduct_quantity = $row[0]['order_quantity'];
                    
                    // 4. Find linked package quantity for this non-package item
                    $linkpackagechk = $this->Catalog_package->query("
                        SELECT SUM(link_quantity) AS link_qty,link_product_id
                        FROM catalog_package
                        WHERE link_product_id = '$orderproduct_id' AND status = 1
                        AND main_product_id IN (
                            SELECT product_id 
                            FROM order_orderhistories 
                            WHERE reference_id = '$table_referenceid' AND package = 1 AND status != 0
                        )
                    ");
                    
                    $link_package_item_quantity = 0;
                    if (!empty($linkpackagechk[0][0]['link_qty'])) {
                        $link_package_item_quantity = $linkpackagechk[0][0]['link_qty'];
                    }
                    
                    // 5. Calculate discount
                    if ($orderproduct_quantity <= $link_package_item_quantity) {
                        $discount_percentage = 100;
                    } elseif ($link_package_item_quantity > 0) {
                        $discount_percentage = round(($link_package_item_quantity * 100) / $orderproduct_quantity, 2);
                    } else {
                        $discount_percentage = 0;
                    }
                    // print_r($discount_percentage);exit;
                    
                    $orderdata = $this->ordercomputewithpackagediscount($table_referenceid, $cashierid,$orderproduct_id, $discount_percentage);
                    if($orderdata['status_code'] == 200)
                    {
                        $packagechngchk=true;
                    }
                }
            }

            //discount update in ordertax
            $orderss = $this->Order_orderhistory->query("SELECT sum(price) as price,sum(total_tax) as total_tax,sum(total_price) as total_price,sum(discount) as discount FROM `order_orderhistories` WHERE reference_id='$table_referenceid' and status!=0");
            if(!empty($orderss))
            {
                if($orderss[0][0]['total_price']<1){
                    $response =array(
                        'status_code'=> 400,
                        'message'=> "There is no item to bill. Please Check..!",
                    );
                    return json_encode($response);
                }
                
                $todayDate = date("Y-m-d");
                $tdaytrx=$this->Order_transaction->query("SELECT COUNT(id) as tkn FROM `order_transactions` WHERE date(created)='$todayDate' and status=2");
                $token=$tdaytrx['0']['0']['tkn']+1;
                $currenttime=date("H:i:s");
                $updated_discount=0;
                $updated_tax=0;
                $updated_final_amount=0;
                if($packagechngchk){
                    $orderstaxdatas = $this->Order_tax->query("SELECT ot.product_id, SUM((ot.price * ot.quantity) * (ot.discount_percentage / 100)) AS discounted_total, SUM(((ot.price * ot.quantity) * (ot.discount_percentage / 100)) * (IFNULL(ooh.tax_percent, 0) / 100)) AS total_tax, SUM(((ot.price * ot.quantity) * (ot.discount_percentage / 100)) * (1 + (IFNULL(ooh.tax_percent, 0) / 100))) AS final_amount FROM order_tax ot LEFT JOIN ( SELECT product_id, SUM(total_tax_percentage) AS tax_percent FROM order_orderhistories WHERE reference_id = '$table_referenceid' AND status = 1 GROUP BY product_id ) ooh ON ooh.product_id = ot.product_id WHERE ot.reference_id = '$table_referenceid' AND ot.package = 0 AND ot.status = 1");
                    if(!empty($orderstaxdatas))
                    {
                        $updated_discount=$orderstaxdatas[0][0]['discounted_total'];
                        $updated_tax=$orderstaxdatas[0][0]['total_tax'];
                        $updated_final_amount=$orderstaxdatas[0][0]['final_amount'];
                    }
                }
                
                $flatDiscountVal = floatval($_REQUEST['discount'] ?? 0);
                if ($type == 1) {
                    $flatDiscountVal = round((floatval($orderss[0][0]['total_price']) * $flatDiscountVal) / 100, 2);
                }
                $discount_to_subtract = $flatDiscountVal;

                $grand_total = $orderss[0][0]['total_price'] + $orderss[0][0]['total_tax'];
                $net_total = $grand_total - $discount_to_subtract - $updated_final_amount;
                
                $finalamount = round(($orderss[0][0]['total_price']+$orderss[0][0]['total_tax'])-$discount_to_subtract-$updated_final_amount);        // Rounded to nearest integer
                $roundfinalamount = round(($orderss[0][0]['total_price']+$orderss[0][0]['total_tax'])-$discount_to_subtract-$updated_final_amount,2); // Rounded to 2 decimal places
               
                $round_amount = $finalamount - $roundfinalamount;
                
                $txnDiscountVal = $flatDiscountVal;
                $txnDiscountType = $type;
                $txnDiscountTypeValue = $_REQUEST['discount_value'] ?? $_REQUEST['discount'] ?? $flatDiscountVal;
                
                $datas['Order_transaction']=array(
                    'reference_id'=>$table_referenceid,
                    'device_id'=>$device_id,
                    'cashier_id'=>$cashierid,
                    'store_id'=>$location_id,
                    'price'=>round($orderss[0][0]['total_price']-$updated_discount,2),
                    'total_tax'=>round($orderss[0][0]['total_tax']-$updated_tax,2),
                    'discount'=>$txnDiscountVal,
                    'discount_type'=>$txnDiscountType,
                    'discount_type_value'=>$txnDiscountTypeValue,
                    'discount_items'=>$dish_type,
                    'amount'=>$finalamount,
                    'round'=>round($round_amount,0),
                    'name'=>$name,
                    'phone'=>$phone,
                    'address'=>$address,
                    'tokenno'=>$token,
                    'table_number'=>$table_number,
                    'pax'=>$pax,
                    'waiter_id'=>$waiter_id,
                    'waiter_name'=>$waiter_name,
                    'is_takeaway' => $is_takeaway,
                    'createtime'=>$currenttime,
                    'loyalty_flag'=>$loyalty_flag
                );
                if($this->Order_transaction->saveAll($datas['Order_transaction']))
                {
                    $this->Order_table->query("UPDATE order_tables SET status = 3,sync=0,local_sync=0 WHERE reference_id = '$table_referenceid' and status!=0");
                    $this->Order_table->query("UPDATE order_tables SET status = 1,sync=0,local_sync=0 WHERE reference_id IN ($refid)  and status!=0");
                    $this->Order_table_tracking->query("UPDATE order_table_tracking SET status = 0,sync=0,local_sync=0 WHERE merged_table_ref_id IN ($refid)");

                    if (!$this->bill_table_package_kds_discount($cashierid,$table_referenceid,$disc,$sc,$type,$dish_type)) {
                    
                        return json_encode([
                            'status_code'  => 400,
                            'message' => "Discount not saved",
                        ]);
                    }
                    
                    $response['status_code']= 200;
                    $response['message']= "Bill Generated Successfully..!";
                }else{
                    $response['status_code']= 400;
                    $response['message']= "Transaction Data not saved Error:103";
                }
            }
        
        return json_encode($response);
    }

    public function bill_table_package_kds_discount($cashierid,$ref_id,$disc,$sc,$type,$dish_type)
    {
        $this->loadModel('Coupon');
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_tax');
        $this->loadModel('Order_transaction');

        $todayDate = date("Y-m-d");
        
        $discount_type_value = $_REQUEST['discount_value'] ?? $_REQUEST['discount'] ?? $disc;
        
        $retry = false;   // 🔒 prevents second retry in same execution

        if($type==0){
            if(!empty($disc)){
                $coup = $this->Coupon->find('first', [
                    'conditions'=>[
                        'Coupon.code'=>$disc,
                        'Coupon.expiry_date >='=>$todayDate,
                        'Coupon.status'=>1
                    ]
                ]);
    
                if(empty($coup)){
                    $disc = $_REQUEST['discount_value'] ?? 0;
                } else {
                    $disc = $coup['Coupon']['value'];
                }
            }
            else{
                $disc=0;
            }
        }
    
        if($type==1){
            $disc=$disc;
        }
    
        if($type==2){
    
            if($dish_type==='all'){
                $subtotals = $this->Order_orderhistory->query("
                    SELECT SUM(total_price) as subtotal 
                    FROM order_orderhistories 
                    WHERE status!=0 AND reference_id='$ref_id'
                ");
            }
            else{
                $subtotals = $this->Order_orderhistory->query("
                    SELECT SUM(total_price) as subtotal 
                    FROM order_orderhistories 
                    WHERE status!=0 AND reference_id='$ref_id' 
                    AND dish_type=$dish_type
                ");
            }
    
            if($subtotals){
                $subtotal = $subtotals[0][0]['subtotal'];
    
                if($subtotal){
                    $disc = round((($disc * 100) / $subtotal),2);
                }
                else{
                    return json_encode([
                        'status_code'=> 400,
                        'message'=> "Bill Not Found",
                    ]);
                }
            }
            else{
                return json_encode([
                    'status_code'=> 400,
                    'message'=> "Bill Not Found",
                ]);
            }
        }
    
        if($disc > 100){
            return json_encode([
                'status_code'=> 400,
                'message'=> "Discount cannot be more than 100%",
            ]);
        }
    
        if($dish_type!='all'){
            $kdschk = $this->Order_orderhistory->find('first', [
                'conditions' => [
                    'Order_orderhistory.reference_id' => $ref_id,
                    'Order_orderhistory.status !=' => 0,
                    'Order_orderhistory.dish_type'=>$dish_type
                ]
            ]);
        }
        else{
            $kdschk = $this->Order_orderhistory->find('first', [
                'conditions'=>[
                    'Order_orderhistory.reference_id'=>$ref_id,
                    'Order_orderhistory.status !='=>0
                ]
            ]);
        }
    
        if(empty($kdschk)){
            return json_encode([
                'status_code'=> 400,
                'message'=> "No Item found for this KDS while applying discount.",
            ]);
        }
        
        $packagechk = $this->Order_orderhistory->find('first', [
            'conditions'=>[
                'Order_orderhistory.reference_id'=>$ref_id,
                'Order_orderhistory.package'=>1,
                'Order_orderhistory.status !='=>0
            ]
        ]);
    
        if(!empty($packagechk)){
            return json_encode([
                'status_code'=> 400,
                'message'=> "You cannot merge Discount with package.",
            ]);
        }
    
        if($disc>=0){
            $checkdiscount=$this->Order_orderhistory->find('first',[
                'conditions'=>[
                    'Order_orderhistory.discount > '=>0,
                    'Order_orderhistory.cashier_id'=>$cashierid,
                    'Order_orderhistory.status'=>2,
                    'Order_orderhistory.reference_id'=>$ref_id
                ]
            ]);
    
            if(!empty($checkdiscount)){
                return json_encode([
                    'status_code'=> 400,
                    'message'=> "Discount cannot merge with item level discount"
                ]);
            }
        }
        else{
            return json_encode([
                'status_code'=> 400,
                'message'=> "Please check discount value"
            ]);
        }
        
        if($dish_type==='all'){
            $orderdata=$this->ordercompute($ref_id,$cashierid,$sc,$disc);
        }else{
            $this->Order_tax->query("UPDATE order_tax SET discount_percentage='0',sync=0,local_sync=0 WHERE reference_id = '$ref_id'");
            $orderdata=$this->ordercomputewithkds($ref_id,$cashierid,$sc,$disc,$dish_type);
        }
    
        if($orderdata['status_code'] == 200)
        {
            $totalservicechg=$orderdata['totalservicechg'];
            $totalprice=$orderdata['totalprice'];
            $totaltax=$orderdata['totaltax'];
            $totaldiscount=$orderdata['totaldiscount'];
            if ($type == 2) {
                $totaldiscount = round($totaldiscount);
            }

            $exactTotalAmount = (($totalprice + $totaltax + $totalservicechg) - $totaldiscount);
            
            $round2TotalAmount = round($exactTotalAmount, 2);   // 2 decimal value //
            $finalTotalAmount  = round($exactTotalAmount);      // nearest integer
            $roundOffAmount    = round($finalTotalAmount - $round2TotalAmount, 2);
    
            $createtime = date('H:i:s');
            
            $txnDiscountVal = $totaldiscount;
            $txnDiscountType = $type;
            $txnDiscountTypeValue = $discount_type_value;
            
            $this->Order_transaction->query("
                UPDATE order_transactions 
                SET 
                    price = '$totalprice',
                    total_tax='$totaltax',
                    servicecharge='$totalservicechg',
                    discount='$txnDiscountVal',
                    discount_type='$txnDiscountType',
                    discount_type_value='$txnDiscountTypeValue',
                    amount='$finalTotalAmount',
                    round='$roundOffAmount',
                    createtime='$createtime',sync=0,local_sync=0
                WHERE reference_id = '$ref_id' and status!=0
            ");
            
            if ($totaldiscount > 0 && $retry === false) {
                
                // Check if we already attempted safety net for this reference (prevents repeated attempts)
                $transactionCheck = $this->Order_transaction->find('first', array(
                    'conditions' => array(
                        'Order_transaction.reference_id' => $ref_id,
                        'Order_transaction.status' => 1,
                        'Order_transaction.discount >'   => 0
                    ),
                    'fields' => array('id')
                ));
                
                // Only run safety net if transaction has discount but we haven't marked it as processed
                // The transaction discount being set means the main flow completed
                if (!empty($transactionCheck)) {
                    
                    $ordertaxCheck = $this->Order_tax->find('count', array(
                        'conditions' => array(
                            'Order_tax.reference_id' => $ref_id,
                            'Order_tax.discount_percentage >' => 0,
                            'Order_tax.status' => 1
                        ),
                        'fields' => array('id')
                    ));
                    
                    if(!empty($ordertaxCheck==0)){
 
                        $retry = true;
 
                        if($dish_type==='all'){
                            $orderdata=$this->ordercompute($ref_id,$cashierid,$sc,$disc);
                        }else{
                            $this->Order_tax->query("UPDATE order_tax SET discount_percentage='0',sync=0,local_sync=0 WHERE reference_id = '$ref_id'");
                            $orderdata=$this->ordercomputewithkds($ref_id,$cashierid,$sc,$disc,$dish_type);
                        }
                    }
                }
            }
            
            return json_encode([
                'status_code'=> 200,
                'message'=> "Saved",
            ]);
        }
        else{
            return json_encode([
                'status_code'=> 400,
                'message'=> "Not Saved",
            ]);
        }
    }

    public function ordercomputewithpackagediscount($txnid=null,$user_id=null,$product_id=0,$discount=0)
    {
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_tax');
        $this->loadModel('User');
        $users = null;
        if (!empty($user_id)) {
            $users = $this->User->find('first', array('conditions'=>array('User.id'=>$user_id,'User.status'=>1)));
        }

        $response['status_code']=400;
        $totalservicechg=0;
        $totalprice=0;
        $totaltax1=0;
        $totaltax2=0;
        $totaldiscount=0;
        $sc=0;
        $is_sc=0;
        if($is_sc==1){
            $sc=servicecharge;
        }
        $discq="(((Order_orderhistory.total_price)-(Order_orderhistory.discount))*$discount/100)";
        $priceq="((Order_orderhistory.total_price)-(Order_orderhistory.discount))";
        
        $this->Order_orderhistory->virtualFields = array('tdisc'=>"SUM($discq)",'tservicechg'=>"SUM(($priceq-$discq)*($sc/100))",'tprice'=>"SUM($priceq)",'total_tax'=>"SUM(($priceq+(($priceq-$discq)*($sc/100))-$discq)*((Order_orderhistory.total_tax_percentage)/100))");  
        $orderhistories=$this->Order_orderhistory->find('first',array('conditions'=>array('Order_orderhistory.reference_id'=>$txnid,'Order_orderhistory.product_id'=>$product_id,'Order_orderhistory.status !=' =>0),'fields'=>array('product_id','tdisc','Order_orderhistory.reference_id','tprice','total_tax','tservicechg')));
        if(!empty($orderhistories['Order_orderhistory']['reference_id'])){
            $response['status_code']=200;
            $totalservicechg=round($orderhistories['Order_orderhistory']['tservicechg'],2);
            $totalprice=round($orderhistories['Order_orderhistory']['tprice'],2);
            $totaltax=round($orderhistories['Order_orderhistory']['total_tax'],2);
            $totaldiscount=round($orderhistories['Order_orderhistory']['tdisc'],2);
            
            //tax update
            $discq="(((Order_orderhistory.price)-(Order_orderhistory.discount))*$discount/100)";
            $priceq="((Order_orderhistory.price)-(Order_orderhistory.discount))";
            
            $taxes=[];
            
            $this->Order_orderhistory->virtualFields = array('tamount'=>"($priceq+(($priceq-$discq)*($sc/100))-$discq)");  
            $neworderhistories=$this->Order_orderhistory->find('all',array('conditions'=>array('Order_orderhistory.reference_id'=>$txnid,'Order_orderhistory.product_id'=>$product_id,'Order_orderhistory.cashier_id'=>$user_id,'Order_orderhistory.status !=' =>0),'fields'=>array('Order_orderhistory.reference_id','tamount','product_id','price','id')));
            foreach($neworderhistories as $data){
                $orderhistoryid=$data['Order_orderhistory']['id'];
                $reference_id=$data['Order_orderhistory']['reference_id'];
                $product_id=$data['Order_orderhistory']['product_id'];
                $tamount=$data['Order_orderhistory']['tamount'];
                $price=$data['Order_orderhistory']['price'];
                
                $taxitem=$this->Order_tax->find('all',array('conditions'=>array('Order_tax.reference_id'=>$txnid,'Order_tax.product_id'=>$product_id,'Order_tax.status !='=>0,'Order_tax.orderhistory_id'=>$orderhistoryid)));
                foreach($taxitem as $item){
                    $id=$item['Order_tax']['id'];
                    $tax_id=$item['Order_tax']['tax_id'];
                    $tax_percentage=$item['Order_tax']['tax_percentage'];
                    $quantity=$item['Order_tax']['quantity'];
                    $taxamount=round($tamount*$quantity,2);
                    $taxvalue=round((($tamount*$quantity*$tax_percentage)/100),2);
                    
                    $db = $this->Order_tax->getDataSource();
                    
                    try {
                        $this->Order_tax->updateAll(
                            [
                                'Order_tax.taxable_price'       => $taxamount,
                                'Order_tax.tax_value'           => $taxvalue,
                                'Order_tax.discount_percentage' => $discount,
                                'Order_tax.sync'                => 0,
                                'Order_tax.local_sync'          => 0
                            ],
                            [
                                'Order_tax.id' => $id
                            ]
                        );
                    
                        if ($db->lastError()) {
                            $this->trackUser($txnid,$users, 'ordercomputewithpackagediscount (silent)', [
                                'ref_id'    => $txnid,
                                'taxamount' => $taxamount,
                                'taxvalue'  => $taxvalue,
                                'discount'  => $discount,
                                'id'        => $id,
                                'db_error'  => $db->lastError()
                            ]);
                        }
                    } catch (Exception $e) {
                        $this->trackUser($txnid,$users, 'ordercomputewithpackagediscount (exception)', [
                            'ref_id'    => $txnid,
                            'taxamount' => $taxamount,
                            'taxvalue'  => $taxvalue,
                            'discount'  => $discount,
                            'id'        => $id,
                            'error'     => $e->getMessage()
                        ]);
                    }
                }
            }
            
            $response['totalservicechg']=$totalservicechg;
            $response['totalprice']=$totalprice;
            $response['totaltax']=$totaltax;
            $response['totaldiscount']=$totaldiscount;
        }
        return $response;
    }

    public function ordercompute($txnid = null, $user_id = null, $is_sc = 1, $discount = 0)
    {
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_tax');

        $response = ['status_code' => 400];
    
        if (empty($txnid)) {
            return $response;
        }
    
        $sc = ($is_sc == 1 || $is_sc === true || strcasecmp((string)$is_sc, 'true') === 0) ? servicecharge : 0;
        
        CakeLog::write('debug', "Reelo ordercompute Debug: txnid={$txnid}, is_sc={$is_sc}, resolved_sc={$sc}, discount={$discount}");
        /* -------------------------------------------------
         * STEP 1: ORDER TOTALS
         * ------------------------------------------------- */
    
        $this->loadModel('ReeloDiscount');
        $reeloDiscount = $this->ReeloDiscount->find('first', [
            'conditions' => [
                'ReeloDiscount.reference_id' => $txnid,
                'ReeloDiscount.status' => 1
            ],
            'order' => ['ReeloDiscount.id' => 'DESC'],
            'recursive' => -1
        ]);

        $is_item_discount = false;
        $item_json_arr = [];
        $reelo_disc_type = null;
        $reelo_disc_val = 0;
        $reelo_disc_type_name = 'fixed';

        if (!empty($reeloDiscount)) {
            $discOn = $reeloDiscount['ReeloDiscount']['discount_on'] ?? '';
            if (strcasecmp($discOn, 'item') === 0) {
                $is_item_discount = true;
                $itemJsonRaw = $reeloDiscount['ReeloDiscount']['item_json'] ?? '';
                $item_json_arr = is_string($itemJsonRaw) ? json_decode($itemJsonRaw, true) : (is_array($itemJsonRaw) ? $itemJsonRaw : []);
                $reelo_disc_type = $reeloDiscount['ReeloDiscount']['reelo_discount_type'] ?? 0;
                $reelo_disc_val = floatval($reeloDiscount['ReeloDiscount']['discount_value'] ?? 0);
                $reelo_disc_type_name = $reeloDiscount['ReeloDiscount']['discount_type'] ?? 'fixed';
            }
        } elseif (isset($_REQUEST['discount_on']) && strcasecmp($_REQUEST['discount_on'], 'item') === 0) {
            $is_item_discount = true;
            $itemJsonRaw = $_REQUEST['item_json'] ?? '';
            $item_json_arr = is_string($itemJsonRaw) ? json_decode($itemJsonRaw, true) : (is_array($itemJsonRaw) ? $itemJsonRaw : []);
            $reelo_disc_type = $_REQUEST['reelo_discount_type'] ?? 0;
            $reelo_disc_val = floatval($_REQUEST['reelo_discount_value'] ?? 0);
            $reelo_disc_type_name = $_REQUEST['reelo_discount_type_name'] ?? 'fixed';
        }

        $priceq = "Order_orderhistory.total_price";
        $discq  = "($priceq * $discount / 100)";
        $scq    = "(($priceq - $discq) * $sc / 100)";
        $taxq   = "(($priceq - $discq + $scq) * (Order_orderhistory.total_tax_percentage / 100))";
    
        $this->Order_orderhistory->virtualFields = [
            'tprice'      => "SUM($priceq)",
            'tdisc'       => "SUM($discq)",
            'tservicechg' => "SUM($scq)",
            'total_tax'   => "SUM($taxq)"
        ];
    
        $summary = $this->Order_orderhistory->find('first', [
            'conditions' => [
                'Order_orderhistory.reference_id' => $txnid,
                'Order_orderhistory.status !='    => 0
            ],
            'fields' => ['reference_id', 'tprice', 'tdisc', 'tservicechg', 'total_tax'],
            'recursive' => -1
        ]);
    
        if (empty($summary['Order_orderhistory']['reference_id'])) {
            return $response;
        }
    
        $response['status_code']         = 200;
        $response['totalprice']     = round($summary['Order_orderhistory']['tprice'], 2);
        $response['totaldiscount']  = round($summary['Order_orderhistory']['tdisc'], 2);
        $response['totalservicechg']= round($summary['Order_orderhistory']['tservicechg'], 2);
        $response['totaltax']       = round($summary['Order_orderhistory']['total_tax'], 2);
    
        /* -------------------------------------------------
         * STEP 2: ITEM LEVEL TAX UPDATE
         * ------------------------------------------------- */
        $items = $this->Order_orderhistory->find('all', [
            'conditions' => [
                'Order_orderhistory.reference_id' => $txnid,
                'Order_orderhistory.status !='    => 0
            ],
            'fields' => ['id', 'product_id', 'price', 'total_price', 'quantity'],
            'recursive' => -1
        ]);
    
        foreach ($items as $row) {
    
            $orderhistory_id = $row['Order_orderhistory']['id'];
            $product_id      = $row['Order_orderhistory']['product_id'];
            $itemPrice       = floatval($row['Order_orderhistory']['price']);
            $itemQty         = floatval($row['Order_orderhistory']['quantity']);
            $itemTotalPrice  = floatval($row['Order_orderhistory']['total_price']);
            
            $itemDiscountPercent = $discount;
            if ($is_item_discount && !empty($item_json_arr)) {
                $isMatched = false;
                foreach ($item_json_arr as $ij) {
                    $ijId = is_array($ij) ? ($ij['id'] ?? $ij['_id'] ?? $ij['product_id'] ?? null) : $ij;
                    if ($ijId !== null && (string)$ijId === (string)$product_id) {
                        $isMatched = true;
                        break;
                    }
                }
                if ($isMatched) {
                    if (strcasecmp($reelo_disc_type_name, 'percentage') === 0) {
                        $itemDiscountPercent = $reelo_disc_val;
                    } else {
                        $itemDiscountPercent = ($itemTotalPrice > 0) ? min(100, round(($reelo_disc_val * 100) / $itemTotalPrice, 4)) : 0;
                    }
                } else {
                    $itemDiscountPercent = 0;
                }
            }

            $unitPrice = $itemPrice;
            $unitDisc = ($unitPrice * $itemDiscountPercent) / 100;
            $unitSC = (($unitPrice - $unitDisc) * $sc) / 100;
            $unitTamount = ($unitPrice - $unitDisc + $unitSC);
    
            $taxItems = $this->Order_tax->find('all', [
                'conditions' => [
                    'Order_tax.reference_id'     => $txnid,
                    'Order_tax.orderhistory_id' => $orderhistory_id,
                    'Order_tax.product_id'      => $product_id,
                    'Order_tax.status !='       => 0
                ],
                'recursive' => -1
            ]);
    
            foreach ($taxItems as $taxRow) {
    
                $tax = $taxRow['Order_tax'];
    
                $taxable_price = round($unitTamount * $tax['quantity'], 2);
                $tax_value     = round(($taxable_price * $tax['tax_percentage']) / 100, 2);
    
                try {
                    $this->Order_tax->updateAll(
                        [
                            'Order_tax.taxable_price'       => $taxable_price,
                            'Order_tax.tax_value'           => $tax_value,
                            'Order_tax.discount_percentage' => $itemDiscountPercent,
                            'Order_tax.sync'                => 0,
                            'Order_tax.local_sync'          => 0
                        ],
                        [
                            'Order_tax.id' => $tax['id']
                        ]
                    );
                    
                    $db = $this->Order_tax->getDataSource();
                    if ($db->lastError()) {
                        $this->trackUser($txnid, $user_id, 'order_tax_update_sql_error', [
                            'tax_id' => $tax['id'],
                            'error'  => $db->lastError()
                        ]);
                    }
                } catch (Exception $e) {
                    $this->trackUser($txnid, $user_id, 'order_tax_update_exception', [
                        'tax_id' => $tax['id'],
                        'error'  => $e->getMessage()
                    ]);
                }
            }
        }
    
        return $response;
    }

    public function ordercomputewithkds($txnid = null, $user_id = null, $is_sc = 1, $discount = 0, $dish = 0)
    {
        $this->loadModel('Order_orderhistory');
        $this->loadModel('Order_tax');

        $response = ['status_code' => 400];
    
        if (empty($txnid)) {
            return $response;
        }
    
        $sc = ($is_sc == 1 || $is_sc === true || strcasecmp((string)$is_sc, 'true') === 0) ? servicecharge : 0;
        $kds = (int)$dish; // dish type to filter discount
    
        // STEP 1: FETCH ALL ORDER ITEMS
        $items = $this->Order_orderhistory->find('all', [
            'conditions' => [
                'Order_orderhistory.reference_id' => $txnid,
                'Order_orderhistory.status !=' => 0
            ],
            'fields' => [
                'Order_orderhistory.id',
                'Order_orderhistory.product_id',
                'Order_orderhistory.dish_type',
                'Order_orderhistory.price',
                'Order_orderhistory.quantity',
                'Order_orderhistory.discount',
                'Order_orderhistory.total_tax_percentage'
            ],
            'recursive' => -1
        ]);
    
        if (empty($items)) return $response;
    
        // STEP 2: CALCULATE TOTALS
        $totalprice = $totaldiscount = $totalservicechg = $totaltax = 0;
    
        foreach ($items as $row) {
            $item = $row['Order_orderhistory'];
    
            $basePrice = ($item['price'] * $item['quantity']) - $item['discount'];
            $applyDiscount = ($item['dish_type'] == $kds);
            $itemDiscount = $applyDiscount ? ($basePrice * $discount) / 100 : 0;
    
            $priceAfterDiscount = $basePrice - $itemDiscount;
            $serviceCharge = ($priceAfterDiscount * $sc) / 100;
            $taxableAmount = $priceAfterDiscount + $serviceCharge;
            $taxValue = ($taxableAmount * $item['total_tax_percentage']) / 100;
    
            $totalprice += $basePrice;
            $totaldiscount += $itemDiscount;
            $totalservicechg += $serviceCharge;
            $totaltax += $taxValue;
        }
 
        // STEP 3: UPDATE ORDER TAX (RESET + APPLY)
        $taxRows = $this->Order_tax->find('all', [
            'conditions' => [
                'Order_tax.reference_id' => $txnid,
                'Order_tax.status !=' => 0
            ],
            'fields' => [
                'Order_tax.id',
                'Order_tax.orderhistory_id',
                'Order_tax.tax_percentage',
                'Order_tax.quantity'
            ],
            'recursive' => -1
        ]);
    
        // Build a lookup map
        $itemMap = [];
        foreach ($items as $itemRow) {
            $itemMap[$itemRow['Order_orderhistory']['id']] = $itemRow['Order_orderhistory'];
        }
    
        foreach ($taxRows as $row) {
            $tax = $row['Order_tax'];
    
            if (!isset($itemMap[$tax['orderhistory_id']])) continue;
    
            $item = $itemMap[$tax['orderhistory_id']];
    
            $basePrice = ($item['price'] * $tax['quantity']) - $item['discount'];
    
            // Use the item’s dish_type to determine discount
            $applyDiscount = ($item['dish_type'] == $kds);
            $itemDiscount = $applyDiscount ? ($basePrice * $discount) / 100 : 0;
    
            $priceAfterDiscount = $basePrice - $itemDiscount;
            $serviceCharge = ($priceAfterDiscount * $sc) / 100;
            $taxablePrice = round($priceAfterDiscount + $serviceCharge, 2);
            $taxValue = round(($taxablePrice * $tax['tax_percentage']) / 100, 2);
            $discountPercent = $applyDiscount ? (float)$discount : 0.0;
            if (strcasecmp($_REQUEST['discount_on'] ?? '', 'item') === 0) {
                $itemDiscountVal = floatval($item['discount']);
                $itemPrice = floatval($item['price']);
                $itemQty = floatval($item['quantity']);
                $itemBasePrice = $itemPrice * $itemQty;
                $discountPercent = 0;
                if ($itemBasePrice > 0) {
                    $discountPercent = ($itemDiscountVal * 100) / $itemBasePrice;
                }
            }
    
            // Update tax row
            try {
                $this->Order_tax->updateAll(
                    [
                        'taxable_price' => (float)$taxablePrice,
                        'tax_value' => (float)$taxValue,
                        'discount_percentage' => $discountPercent,
                        'dish_type' => (int)$item['dish_type'], // crucial: copy dish_type from item
                        'sync' => 0,
                        'local_sync' => 0
                    ],
                    ['id' => (int)$tax['id']]
                );
                
                $db = $this->Order_tax->getDataSource();
                if ($db->lastError()) {
                    $this->trackUser($txnid,$user_id,'order_tax_sql_error1',[
                        'tax_id'=>$tax['id'],
                        'error'=>$db->lastError()
                    ]);
                }
            } catch (Exception $e) {
                $this->trackUser($txnid, $user_id, 'order_tax_update_error', [
                    'tax_id' => $tax['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    
        // STEP 4: RETURN TOTALS
        return [
            'status_code' => 200,
            'totalprice' => round($totalprice, 2),
            'totaldiscount' => round($totaldiscount, 2),
            'totalservicechg' => round($totalservicechg, 2),
            'totaltax' => round($totaltax, 2)
        ];
    }
    
    
    
    public function update_split_adjustments()
    {
        $this->autoRender = false;

        $this->loadModel('User');
        $this->loadModel('Order_transaction');
        $this->loadModel('Order_split_transaction');
        $this->loadModel('Order_split_payment');
        $this->loadModel('Order_tax');

        /* ---------------------------------------------------------------
           1. Read parameters
        --------------------------------------------------------------- */
        $cashierid  = trim($_REQUEST['id']                  ?? '');
        $ref_id     = trim($_REQUEST['reference_id']        ?? '');
        $split_ref  = trim($_REQUEST['split_reference_id']  ?? '');
        $disc       = trim($_REQUEST['discount']            ?? '0');
        $type       = (int)($_REQUEST['type']               ?? 1);
        $sc         = (int)($_REQUEST['service_charge']     ?? 1);
        $dish_type  = trim($_REQUEST['kds']                 ?? 'all');

        

        if (!empty($split_ref) && $split_ref != '') {
            $todayDate = date("Y-m-d");

            if($type==0){
                if(!empty($disc)){
                    $coup = $this->Coupon->find('first', [
                        'conditions'=>[
                            'Coupon.code'=>$disc,
                            'Coupon.expiry_date >='=>$todayDate,
                            'Coupon.status'=>1
                        ]
                    ]);
                    $discper=$coup['Coupon']['value'] ?? 0;
                }
                else{
                    $discper=0;
                }
            }

            if($type==1){
                $discper=$disc;
            }

            if($type==2){

                // Use this split's own subtotal so the full flat amount is applied
                $splitsubtotals = $this->Order_split_orderhistory->query("
                    SELECT SUM(total_price) as subtotal
                    FROM order_split_orderhistories
                    WHERE status!=0
                      AND reference_id='$ref_id'
                      AND split_reference_id='$split_ref'
                ");

                if(!empty($splitsubtotals[0][0]['subtotal'])){

                    $splitSubtotal = $splitsubtotals[0][0]['subtotal'];

                    if($splitSubtotal > 0){
                        if($disc > $splitSubtotal){
                            return json_encode([
                                'status_code' => 400,
                                'status'      => false,
                                'message'     => "Flat discount cannot exceed or equal the split bill total",
                            ]);
                        }
                        // convert flat amount to percentage against this split only
                        $discper = ($disc * 100) / $splitSubtotal;
                    }else{
                        return json_encode([
                            'status_code' => 400,
                            'status'      => false,
                            'message'     => "Bill Not Found",
                        ]);
                    }

                }else{
                    return json_encode([
                        'status_code' => 400,
                        'status'      => false,
                        'message'     => "Bill Not Found",
                    ]);
                }
            }

            if($discper>100){
                $response = array(
                    'status_code' => 400,
                    'status'      => false,
                    'message'     => "Discount is not more than 100%",
                );
                return json_encode($response);
            }

            /* ---------------------------------------------------------------
               2. Required-field validation
            --------------------------------------------------------------- */
            if (empty($cashierid)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'User ID missing']);
            }
            if (empty($ref_id)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'reference_id missing']);
            }
            if (empty($split_ref)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'split_reference_id missing']);
            }

            /* ---------------------------------------------------------------
               3. Validate user
            --------------------------------------------------------------- */
            $user = $this->User->find('first', [
                'conditions' => ['User.id' => $cashierid, 'User.status' => 1],
                'recursive'  => -1
            ]);
            if (empty($user)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'User not found']);
            }

            /* ---------------------------------------------------------------
               4. Verify parent order_transactions exists and is a split bill
            --------------------------------------------------------------- */
            $parentTxn = $this->Order_transaction->find('first', [
                'conditions' => [
                    'Order_transaction.reference_id' => $ref_id,
                    'Order_transaction.status !='    => 0
                ],
                'recursive' => -1
            ]);
            if (empty($parentTxn)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'Transaction not found']);
            }
            if (empty($parentTxn['Order_transaction']['is_split'])) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'This is not a split bill']);
            }

            /* ---------------------------------------------------------------
               5. Load the specific split transaction
            --------------------------------------------------------------- */
            $splitTxn = $this->Order_split_transaction->find('first', [
                'conditions' => [
                    'Order_split_transaction.reference_id'       => $ref_id,
                    'Order_split_transaction.split_reference_id' => $split_ref,
                    'Order_split_transaction.status !='          => 0
                ],
                'recursive' => -1
            ]);
            if (empty($splitTxn)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'Split transaction not found']);
            }


            /* ---------------------------------------------------------------
               6. Settlement guard — refuse edits on an already-settled split
            --------------------------------------------------------------- */
            if ((int)$splitTxn['Order_split_transaction']['status'] === 2) {
                return json_encode([
                    'status_code' => 400,
                    'status'     => false,
                    'error_code' => 'SPLIT_ALREADY_SETTLED',
                    'message'    => 'This split has already been settled and cannot be modified'
                ]);
            }

            /* ---------------------------------------------------------------
               7. Snapshot existing payments for the undercut check after compute
            --------------------------------------------------------------- */
            $paidRows = $this->Order_split_payment->query("
                SELECT COALESCE(SUM(amount), 0) AS paid
                FROM order_split_payments
                WHERE reference_id      = '$ref_id'
                  AND split_reference_id = '$split_ref'
                  AND status            != 0
            ");
            $alreadyPaid = isset($paidRows[0][0]['paid']) ? (float)$paidRows[0][0]['paid'] : 0.0;

            /* ---------------------------------------------------------------
               8. Begin database transaction
            --------------------------------------------------------------- */
            $ds = $this->Order_split_transaction->getDataSource();
            $ds->begin();

            try {

                /* -----------------------------------------------------------
                   9. Call existing private helper
                      split_bill_table_package_kds_discount() (lines 17886-18177)
                ----------------------------------------------------------- */
                $helperResult = $this->split_bill_table_package_kds_discount(
                    $cashierid,
                    $ref_id,
                    $split_ref,
                    $disc,
                    $sc,
                    $type,
                    $dish_type
                );

                /* -----------------------------------------------------------
                   10. Handle explicit error returns from helper (type=2 path)
                ----------------------------------------------------------- */
                if ($helperResult !== null) {
                    $ds->rollback();
                    return $helperResult;
                }

                /* -----------------------------------------------------------
                   11. Verify the UPDATE actually applied
                ----------------------------------------------------------- */
                $updated = $this->Order_split_transaction->find('first', [
                    'conditions' => [
                        'Order_split_transaction.reference_id'       => $ref_id,
                        'Order_split_transaction.split_reference_id' => $split_ref,
                        'Order_split_transaction.status !='          => 0
                    ],
                    'recursive' => -1
                ]);
                if (empty($updated)) {
                    $ds->rollback();
                    return json_encode([
                        'status_code' => 400,
                        'status'     => false,
                        'error_code' => 'COMPUTE_FAILED',
                        'message'    => 'Adjustment could not be applied. Please try again.'
                    ]);
                }


                $newAmount = (float)$updated['Order_split_transaction']['amount'];

                /* -----------------------------------------------------------
                   12. Payment undercut guard
                ----------------------------------------------------------- */
                if ($alreadyPaid > 0 && $newAmount < $alreadyPaid) {
                    $ds->rollback();
                    return json_encode([
                        'status_code' => 400,
                        'status'     => false,
                        'error_code' => 'AMOUNT_BELOW_PAID',
                        'message'    => 'New total (' . number_format($newAmount, 2) . ') cannot be less than amount already paid (' . number_format($alreadyPaid, 2) . ')'
                    ]);
                }

                /* -----------------------------------------------------------
                   13. Re-aggregate parent order_transactions from all splits
                ----------------------------------------------------------- */
                $this->Order_transaction->query("
                    UPDATE order_transactions
                    SET
                        price         = (SELECT COALESCE(SUM(price), 0)
                                         FROM order_split_transactions
                                         WHERE reference_id = '$ref_id' AND status != 0),
                        total_tax     = (SELECT COALESCE(SUM(total_tax), 0)
                                         FROM order_split_transactions
                                         WHERE reference_id = '$ref_id' AND status != 0),
                        servicecharge = (SELECT COALESCE(SUM(servicecharge), 0)
                                         FROM order_split_transactions
                                         WHERE reference_id = '$ref_id' AND status != 0),
                        discount      = (SELECT COALESCE(SUM(discount), 0)
                                         FROM order_split_transactions
                                         WHERE reference_id = '$ref_id' AND status != 0),
                        amount        = ROUND( (SELECT COALESCE(SUM(amount), 0)
                                         FROM order_split_transactions
                                         WHERE reference_id = '$ref_id' AND status != 0),
                                         0),
                        sync          = 0,
                        local_sync    = 0
                    WHERE reference_id = '$ref_id'
                      AND status != 0
                ");

                $ds->commit();

            } catch (Exception $e) {

                $ds->rollback();
                return json_encode([
                    'status_code' => 400,
                    'status'     => false,
                    'error_code' => 'SPLIT_LOCKED',
                    'message'    => 'Update conflict detected. Please try again.'
                ]);
            }

            /* ---------------------------------------------------------------
               14. Re-read updated split row and build response
            --------------------------------------------------------------- */
            $freshTxn = $this->Order_split_transaction->query("
                SELECT ost.*, SUM(ost.amount) AS total_amount
                FROM order_split_transactions ost
                WHERE ost.reference_id       = '$ref_id'
                  AND ost.split_reference_id = '$split_ref'
                  AND ost.status            != 0
            ");

            $transactionitems = [];

            if (!empty($freshTxn)) {
                foreach ($freshTxn as $row) {
                    $sqlData   = $row[0];
                    $transData = $row['ost'];

                    $discperRows = $this->Order_tax->query("
                        SELECT discount_percentage
                        FROM order_tax
                        WHERE reference_id       = '$ref_id'
                          AND status            != 0
                          AND discount_percentage != 0
                        LIMIT 1
                    ");
                    $discount_percentage = (!empty($discperRows))
                        ? number_format($discperRows[0]['order_tax']['discount_percentage'], 2)
                        : '0';

                    $transactionitems[] = [
                        'id'                  => $transData['id'],
                        'reference_id'        => $transData['reference_id'],
                        'split_reference_id'  => $transData['split_reference_id'],
                        'orderno'             => $transData['orderno'],
                        'device_id'           => $transData['device_id'],
                        'cashier_id'          => $transData['cashier_id'],
                        'table_number'        => $transData['table_number'],
                        'price'               => $transData['price'],
                        'total_tax'           => $transData['total_tax'],
                        'servicecharge'       => $transData['servicecharge'],
                        'discount'            => $transData['discount'],
                        'discount_percentage' => $discount_percentage,
                        'discount_type'       => $transData['discount_type'],
                        'discount_type_value' => $transData['discount_type_value'],
                        'taxable_amount'      => number_format(
                            ($transData['price'] + $transData['servicecharge']) - $transData['discount'],
                            2
                        ),
                        'delivery_charge'     => $transData['delivery_charge'] ?? 0,
                        'packing_charge'      => $transData['packing_charge'] ?? 0,
                        'amount'              => $sqlData['total_amount'],
                        'round'               => $transData['round'],
                        'name'                => $transData['name'],
                        'phone'               => $transData['phone'],
                        'address'             => $transData['address'],
                        'tokenno'             => $transData['tokenno'],
                        'createtime'          => $transData['createtime'],
                        'created'             => $transData['created'],
                        'pax'                 => $transData['pax'],
                        'status'              => $transData['status'],
                        'waiter_id'           => $transData['waiter_id'],
                        'waiter_name'         => $transData['waiter_name'],
                        'is_takeaway'         => $transData['is_takeaway'],
                        'remarks'             => $transData['remarks'] ?? '',
                    ];
                }
            }

            /* ---------------------------------------------------------------
               15. Compute remaining_amount for this split
            --------------------------------------------------------------- */
            $paidAfter = $this->Order_split_payment->query("
                SELECT COALESCE(SUM(amount), 0) AS paid
                FROM order_split_payments
                WHERE reference_id       = '$ref_id'
                  AND split_reference_id = '$split_ref'
                  AND status            != 0
            ");
            $paidAmount  = isset($paidAfter[0][0]['paid']) ? (float)$paidAfter[0][0]['paid'] : 0.0;
            $freshAmount = !empty($transactionitems) ? (float)$transactionitems[0]['amount'] : 0.0;
            $remaining   = max(0, $freshAmount - $paidAmount);

            return json_encode([
                'status_code'      => 200,
                'status'           => true,
                'message'          => 'Saved',
                'transactions'     => $transactionitems,
                'remaining_amount' => number_format($remaining, 2, '.', '')
            ]);
        }
        else {
            /* ---------------------------------------------------------------
               NORMAL BILL ADJUSTMENTS FOR REELO (when split_reference_id is empty)
            --------------------------------------------------------------- */
            $todayDate = date("Y-m-d");
            $disc_original_active = $disc;

            if ($type == 0) {
                if (!empty($disc)) {
                    $coup = $this->Coupon->find('first', [
                        'conditions' => [
                            'Coupon.code'        => $disc,
                            'Coupon.expiry_date >=' => $todayDate,
                            'Coupon.status'      => 1
                        ]
                    ]);
                    $discper = $coup['Coupon']['value'] ?? 0;
                } else {
                    $discper = 0;
                }
            }

            if ($type == 1) {
                $discper = $disc;
            }

            if ($type == 2) {
                $totalsubtotals = $this->Order_orderhistory->query("
                    SELECT SUM(total_price) as subtotal
                    FROM order_orderhistories
                    WHERE status != 0
                      AND reference_id = '$ref_id'
                ");

                if (!empty($totalsubtotals[0][0]['subtotal'])) {
                    $billSubtotal = $totalsubtotals[0][0]['subtotal'];
                    if ($billSubtotal > 0) {
                        if ($disc > $billSubtotal) {
                            return json_encode([
                                'status_code' => 400,
                                'status'      => false,
                                'message'     => "Flat discount cannot exceed or equal the bill total",
                            ]);
                        }
                        $discper = ($disc * 100) / $billSubtotal;
                    } else {
                        return json_encode([
                            'status_code' => 400,
                            'status'      => false,
                            'message'     => "Bill Not Found",
                        ]);
                    }
                } else {
                    return json_encode([
                        'status_code' => 400,
                        'status'      => false,
                        'message'     => "Bill Not Found",
                    ]);
                }
            }

            if ($discper > 100) {
                return json_encode([
                    'status_code' => 400,
                    'status'      => false,
                    'message'     => "Discount is not more than 100%",
                ]);
            }

            /* Required-field validation */
            if (empty($cashierid)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'User ID missing']);
            }
            if (empty($ref_id)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'reference_id missing']);
            }

            /* Validate user */
            $user = $this->User->find('first', [
                'conditions' => ['User.id' => $cashierid, 'User.status' => 1],
                'recursive'  => -1
            ]);
            if (empty($user)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'User not found']);
            }

            /* Verify parent order_transactions exists */
            $parentTxn = $this->Order_transaction->find('first', [
                'conditions' => [
                    'Order_transaction.reference_id' => $ref_id,
                    'Order_transaction.status !='    => 0
                ],
                'recursive' => -1
            ]);
            if (empty($parentTxn)) {
                return json_encode(['status_code' => 400, 'status' => false, 'message' => 'Transaction not found']);
            }

            /* Settlement guard */
            if ((int)$parentTxn['Order_transaction']['status'] === 2) {
                return json_encode([
                    'status_code' => 400,
                    'status'     => false,
                    'error_code' => 'SPLIT_ALREADY_SETTLED',
                    'message'    => 'This bill has already been settled and cannot be modified'
                ]);
            }

            /* Snapshot existing payments for normal bill */
            $paidRows = $this->Order_payment->query("
                SELECT COALESCE(SUM(amount), 0) AS paid
                FROM order_payments
                WHERE reference_id = '$ref_id'
                  AND status      != 0
            ");
            $alreadyPaid = isset($paidRows[0][0]['paid']) ? (float)$paidRows[0][0]['paid'] : 0.0;

            /* Begin database transaction */
            $ds = $this->Order_transaction->getDataSource();
            $ds->begin();

            try {
                if ($dish_type == 'all') {
                    $orderdata = $this->ordercompute($ref_id, $cashierid, $sc, $discper);
                } else {
                    $orderdata = $this->ordercomputewithkds($ref_id, $cashierid, $sc, $discper, $dish_type);
                }

                if (empty($orderdata) || (isset($orderdata['status']) && $orderdata['status'] === false)) {
                    $ds->rollback();
                    return json_encode([
                        'status_code' => 400,
                        'status'     => false,
                        'error_code' => 'COMPUTE_FAILED',
                        'message'    => 'Adjustment could not be applied. Please try again.'
                    ]);
                }

                $totalservicechg = $orderdata['totalservicechg'];
                $totalprice      = $orderdata['totalprice'];
                $totaltax        = $orderdata['totaltax'];
                $totaldiscount   = $orderdata['totaldiscount'];
                $totalamount     = round((($totalprice + $totaltax + $totalservicechg) - $totaldiscount), 0);

                /* Payment undercut guard */
                if ($alreadyPaid > 0 && $totalamount < $alreadyPaid) {
                    $ds->rollback();
                    return json_encode([
                        'status_code' => 400,
                        'status'     => false,
                        'error_code' => 'AMOUNT_BELOW_PAID',
                        'message'    => 'New total (' . number_format($totalamount, 2) . ') cannot be less than amount already paid (' . number_format($alreadyPaid, 2) . ')'
                    ]);
                }

                /* Update parent order_transactions */
                $this->Order_transaction->query("
                    UPDATE order_transactions
                    SET
                        price               = '$totalprice',
                        total_tax           = '$totaltax',
                        servicecharge       = '$totalservicechg',
                        discount            = '$totaldiscount',
                        discount_type       = '$type',
                        discount_type_value = '$disc_original_active',
                        amount              = '$totalamount',
                        sync                = 0,
                        local_sync          = 0
                    WHERE reference_id = '$ref_id'
                      AND status      != 0
                ");

                $ds->commit();

            } catch (Exception $e) {
                $ds->rollback();
                return json_encode([
                    'status_code' => 400,
                    'status'     => false,
                    'error_code' => 'SPLIT_LOCKED',
                    'message'    => 'Update conflict detected. Please try again.'
                ]);
            }

            /* Re-read updated parent transaction and build response */
            $freshTxn = $this->Order_transaction->find('first', [
                'conditions' => [
                    'Order_transaction.reference_id' => $ref_id,
                    'Order_transaction.status !='    => 0
                ],
                'recursive' => -1
            ]);

            $transactionitems = [];

            if (!empty($freshTxn)) {
                $transData = $freshTxn['Order_transaction'];

                $discperRows = $this->Order_tax->query("
                    SELECT discount_percentage
                    FROM order_tax
                    WHERE reference_id       = '$ref_id'
                      AND status            != 0
                      AND discount_percentage != 0
                    LIMIT 1
                ");
                $discount_percentage = (!empty($discperRows))
                    ? number_format($discperRows[0]['order_tax']['discount_percentage'], 2)
                    : '0';

                $transactionitems[] = [
                    'id'                  => $transData['id'],
                    'reference_id'        => $transData['reference_id'],
                    'split_reference_id'  => 0,
                    'orderno'             => $transData['orderno'],
                    'device_id'           => $transData['device_id'],
                    'cashier_id'          => $transData['cashier_id'],
                    'table_number'        => $transData['table_number'],
                    'price'               => $transData['price'],
                    'total_tax'           => $transData['total_tax'],
                    'servicecharge'       => $transData['servicecharge'],
                    'discount'            => $transData['discount'],
                    'discount_percentage' => $discount_percentage,
                    'discount_type'       => $transData['discount_type'],
                    'discount_type_value' => $transData['discount_type_value'],
                    'taxable_amount'      => number_format(
                        ($transData['price'] + $transData['servicecharge']) - $transData['discount'],
                        2
                    ),
                    'delivery_charge'     => $transData['delivery_charge'] ?? 0,
                    'packing_charge'      => $transData['packing_charge'] ?? 0,
                    'amount'              => $transData['amount'],
                    'round'               => $transData['round'],
                    'name'                => $transData['name'] ?? '',
                    'phone'               => $transData['phone'] ?? '',
                    'address'             => $transData['address'] ?? '',
                    'tokenno'             => $transData['tokenno'] ?? '',
                    'createtime'          => $transData['createtime'] ?? '',
                    'created'             => $transData['created'] ?? '',
                    'pax'                 => $transData['pax'] ?? '',
                    'status'              => $transData['status'],
                    'waiter_id'           => $transData['waiter_id'] ?? '',
                    'waiter_name'         => $transData['waiter_name'] ?? '',
                    'is_takeaway'         => $transData['is_takeaway'] ?? 0,
                    'remarks'             => $transData['remarks'] ?? '',
                ];
            }

            /* Compute remaining_amount for normal bill */
            $paidAfter = $this->Order_payment->query("
                SELECT COALESCE(SUM(amount), 0) AS paid
                FROM order_payments
                WHERE reference_id = '$ref_id'
                  AND status      != 0
            ");
            $paidAmount  = isset($paidAfter[0][0]['paid']) ? (float)$paidAfter[0][0]['paid'] : 0.0;
            $freshAmount = !empty($transactionitems) ? (float)$transactionitems[0]['amount'] : 0.0;
            $remaining   = max(0, $freshAmount - $paidAmount);

            return json_encode([
                'status_code'      => 200,
                'status'           => true,
                'message'          => 'Saved',
                'transactions'     => $transactionitems,
                'remaining_amount' => number_format($remaining, 2, '.', '')
            ]);
        }
    }
    
    public function split_bill_table_package_kds_discount($cashierid,$ref_id,$split_reference_id,$disc,$sc,$type,$dish_type)
    {
        $this->loadModel('Coupon');
        $this->loadModel('Order_split_orderhistory');
        $this->loadModel('Order_split_transaction');
        $this->loadModel('Order_tax');

        $todayDate = date("Y-m-d");
        $disc_original_active = $disc; // capture before any type conversion
        
        if($type==0){
            if(!empty($disc)){
                $coup = $this->Coupon->find('first', [
                    'conditions'=>[
                        'Coupon.code'=>$disc,
                        'Coupon.expiry_date >='=>$todayDate,
                        'Coupon.status'=>1
                    ]
                ]);
    
                if(empty($coup)){
                    // return json_encode([
                    //     'status'=> false,
                    //     'message'=> "Coupon not found Or Coupon Expired",
                    // ]);
                    // print_r("1");
                }
    
                $disc=$coup['Coupon']['value'];
            }
            else{
                $disc=0;
            }
        }
    
        if($type==1){
            $disc=$disc;
        }
        
        /*
        if($type==2){
    
            if($dish_type==='all'){
                $subtotals = $this->Order_split_orderhistory->query("
                    SELECT SUM(total_price) as subtotal 
                    FROM order_split_orderhistories 
                    WHERE status!=0 AND reference_id='$ref_id' AND split_reference_id='$split_reference_id' 
                ");
            }
            else{
                $subtotals = $this->Order_split_orderhistory->query("
                    SELECT SUM(total_price) as subtotal 
                    FROM order_split_orderhistories 
                    WHERE status!=0 AND reference_id='$ref_id' AND split_reference_id='$split_reference_id' 
                    AND dish_type=$dish_type
                ");
            }
    
            if($subtotals){
                $subtotal = $subtotals[0][0]['subtotal'];
    
                if($subtotal){
                    $disc = round((($disc * 100) / $subtotal),2);
                }
                else{
                    return json_encode([
                        'status'=> false,
                        'message'=> "Bill Not Found",
                    ]);
                }
            }
            else{
                return json_encode([
                    'status'=> false,
                    'message'=> "Bill Not Found",
                ]);
            }
        }
                */
        
        if($type==2){

            // Use this split's own subtotal so the full flat amount is applied
            $splitsubtotals = $this->Order_split_orderhistory->query("
                SELECT SUM(total_price) as subtotal
                FROM order_split_orderhistories
                WHERE status!=0
                  AND reference_id='$ref_id'
                  AND split_reference_id='$split_reference_id'
            ");

            if(!empty($splitsubtotals[0][0]['subtotal'])){

                $splitSubtotal = $splitsubtotals[0][0]['subtotal'];

                if($splitSubtotal > 0){
                    if($disc > $splitSubtotal){
                        return json_encode([
                            'status_code' => 400,
                            'status'      => false,
                            'message'     => "Flat discount cannot exceed or equal the split bill total",
                        ]);
                    }
                    // convert flat amount to percentage against this split only
                    $disc = ($disc * 100) / $splitSubtotal;
                }else{
                    return json_encode([
                        'status_code' => 400,
                        'status'      => false,
                        'message'     => "Bill Not Found",
                    ]);
                }

            }else{
                return json_encode([
                    'status_code' => 400,
                    'status'      => false,
                    'message'     => "Bill Not Found",
                ]);
            }
        }

        if($disc > 99){
            // return json_encode([
            //     'status'=> false,
            //     'message'=> "Discount is not more than 99%",
            // ]);
            // print_r("2");
        }

        if($dish_type!='all'){
            $kdschk = $this->Order_split_orderhistory->find('first', [
                'conditions'=>[
                    'Order_split_orderhistory.reference_id'=>$ref_id,
                    'Order_split_orderhistory.split_reference_id'=>$split_reference_id,
                    'Order_split_orderhistory.dish_type'=>$dish_type,
                    'Order_split_orderhistory.status !='=>0
                ],
                'recursive' => -1
            ]);
        }
        else{
            $kdschk = $this->Order_split_orderhistory->find('first', [
                'conditions'=>[
                    'Order_split_orderhistory.reference_id'=>$ref_id,
                    'Order_split_orderhistory.split_reference_id'=>$split_reference_id,
                    'Order_split_orderhistory.status !='=>0
                ],
                'recursive' => -1
            ]);
        }
    
        if(empty($kdschk)){
            // return json_encode([
            //     'status'=> false,
            //     'message'=> "No Item found for this KDS while applying discount.",
            // ]);
        }
    
    
        $packagechk = $this->Order_split_orderhistory->find('first', [
            'conditions' => [
                'Order_split_orderhistory.reference_id'       => $ref_id,
                'Order_split_orderhistory.split_reference_id' => $split_reference_id,
                'Order_split_orderhistory.package'            => 1,
                'Order_split_orderhistory.status >'           => 0
            ],
            'recursive' => -1
        ]);

        if(!empty($packagechk)){
            // return json_encode([
            //     'status'=> false,
            //     'message'=> "You cannot merge Discount with package.",
            // ]);
            // print_r("5");
        }
     
        if($disc>=0){
            $checkdiscount=$this->Order_split_orderhistory->find('first',[
                'conditions'=>[
                    'Order_split_orderhistory.discount > '=>0,
                    'Order_split_orderhistory.cashier_id'=>$cashierid,
                    'Order_split_orderhistory.status'=>2,
                    'Order_split_orderhistory.reference_id'=>$ref_id,
                    'Order_split_orderhistory.split_reference_id'=>$split_reference_id
                ],
                'recursive' => -1
            ]);
    
            if(!empty($checkdiscount)){
                // return json_encode([
                //     'status'=> false,
                //     'message'=> "Discount cannot merge with item level discount"
                // ]);
                // print_r("6");
            }
        }
        else{
            // return json_encode([
            //     'status'=> false,
            //     'message'=> "Please check discount value"
            // ]);
            // print_r("7");
        }
        
        if($dish_type=='all'){
            $orderdata=$this->split_ordercompute($ref_id,$split_reference_id,$cashierid,$sc,$disc);
        }else{
            $orderdata=$this->split_ordercomputewithkds($ref_id,$split_reference_id,$cashierid,$sc,$disc,$dish_type);
        }
        
        if($orderdata['status'])
        {
            $totalservicechg=$orderdata['totalservicechg'];
            $totalprice=$orderdata['totalprice'];
            $totaltax=$orderdata['totaltax'];
            $totaldiscount=$orderdata['totaldiscount'];
            // $totalamount=round((($totalprice+$totaltax+$totalservicechg)-$totaldiscount),2);
            $exactTotalAmount = (($totalprice + $totaltax + $totalservicechg) - $totaldiscount);

            $round2TotalAmount = round($exactTotalAmount, 2);   // 2 decimal value
            $finalTotalAmount  = round($exactTotalAmount);      // nearest integer
            $roundOffAmount    = round($finalTotalAmount - $round2TotalAmount, 2);
            
            $createtime = date('H:i:s');
    
            $this->Order_split_transaction->query("
                UPDATE order_split_transactions 
                SET 
                    price = '$totalprice',
                    total_tax='$totaltax',
                    servicecharge='$totalservicechg',
                    discount='$totaldiscount',
                    discount_type='$type',
                    discount_type_value='$disc_original_active',
                    discount_items='$dish_type',
                    amount='$finalTotalAmount',
                    round='$roundOffAmount',
                    createtime='$createtime',sync=0,local_sync=0
                WHERE reference_id = '$ref_id' and split_reference_id='$split_reference_id' and status!=0
            ");
            
            // return json_encode([
            //     'status'=> true,
            //     'message'=> "Saved",
            // ]);
        }
        else{
            // return json_encode([
            //     'status'=> false,
            //     'message'=> "Not Saved",
            // ]);
        }
    }
    
    public function split_ordercompute($txnid=null,$split_reference_id=null,$user_id=null,$is_sc=1,$discount=0)
    {
        $response['status']=false;
        $totalservicechg=0;
        $totalprice=0;
        $totaltax1=0;
        $totaltax2=0;
        $totaldiscount=0;
        $sc=0;
        if($is_sc==1){
            $sc=servicecharge;
        }
        $this->Order_split_orderhistory->virtualFields = [];

        $discq="(((Order_split_orderhistory.total_price)-(Order_split_orderhistory.discount))*$discount/100)";
        $priceq="((Order_split_orderhistory.total_price)-(Order_split_orderhistory.discount))";
    
        $this->Order_split_orderhistory->virtualFields = array('tdisc'=>"SUM($discq)",'tservicechg'=>"SUM(($priceq-$discq)*($sc/100))",'tprice'=>"SUM($priceq)",'total_tax'=>"SUM(($priceq+(($priceq-$discq)*($sc/100))-$discq)*((Order_split_orderhistory.total_tax_percentage)/100))");  
        
        $orderhistories = $this->Order_split_orderhistory->find('first', [
            'conditions' => [
                'Order_split_orderhistory.reference_id'       => $txnid,
                'Order_split_orderhistory.split_reference_id' => $split_reference_id,
                'Order_split_orderhistory.status !='          => 0
            ],
            'fields' => [
                'tdisc',
                'Order_split_orderhistory.reference_id',
                'tprice',
                'total_tax',
                'tservicechg'
            ],
            'recursive' => -1
        ]);

        if(!empty($orderhistories['Order_split_orderhistory']['reference_id'])){
            $response['status']=true;
            $totalservicechg=round($orderhistories['Order_split_orderhistory']['tservicechg'],2);
            $totalprice=round($orderhistories['Order_split_orderhistory']['tprice'],2);
            $totaltax=round($orderhistories['Order_split_orderhistory']['total_tax'],2);
            $totaldiscount=round($orderhistories['Order_split_orderhistory']['tdisc'],2);
            
            $response['totalservicechg']=$totalservicechg;
            $response['totalprice']=$totalprice;
            $response['totaltax']=$totaltax;
            $response['totaldiscount']=$totaldiscount;
            
            
            // Now update order_tax for each item, just like ordercompute() does
            $itemDiscq = "((Order_split_orderhistory.price - Order_split_orderhistory.discount) * $discount / 100)";
            $itemPriceq = "(Order_split_orderhistory.price - Order_split_orderhistory.discount)";
            $itemScq = "(($itemPriceq - $itemDiscq) * $sc / 100)";
            $itemTamountq = "($itemPriceq - $itemDiscq + $itemScq)";
            
            $this->Order_split_orderhistory->virtualFields = [
                'tamount' => $itemTamountq
            ];
            
            $items = $this->Order_split_orderhistory->find('all', [
                'conditions' => [
                    'Order_split_orderhistory.reference_id'       => $txnid,
                    'Order_split_orderhistory.split_reference_id' => $split_reference_id,
                    'Order_split_orderhistory.status !='          => 0
                ],
                'fields' => ['id', 'order_history_id', 'product_id', 'quantity', 'price', 'tamount'],
                'recursive' => -1
            ]);
            
            foreach ($items as $row) {
                $orderhistory_id = $row['Order_split_orderhistory']['order_history_id'];
                $product_id = $row['Order_split_orderhistory']['product_id'];
                $tamount = round($row['Order_split_orderhistory']['tamount'], 2);
                
                $taxItems = $this->Order_tax->find('all', [
                    'conditions' => [
                        'Order_tax.reference_id'     => $txnid,
                        'Order_tax.orderhistory_id' => $orderhistory_id,
                        'Order_tax.product_id'      => $product_id,
                        'Order_tax.status !='       => 0
                    ],
                    'recursive' => -1
                ]);
                
                foreach ($taxItems as $taxRow) {
                    $tax = $taxRow['Order_tax'];
                    $taxable_price = round($tamount * $tax['quantity'], 2);
                    $tax_value = round(($taxable_price * $tax['tax_percentage']) / 100, 2);
                    
                    try {
                        $this->Order_tax->updateAll(
                            [
                                'Order_tax.taxable_price'       => $taxable_price,
                                'Order_tax.tax_value'           => $tax_value,
                                'Order_tax.discount_percentage' => $discount,
                                'Order_tax.sync'                => 0,
                                'Order_tax.local_sync'          => 0
                            ],
                            [
                                'Order_tax.id' => $tax['id']
                            ]
                        );
                        
                        $db = $this->Order_tax->getDataSource();
                        if ($db->lastError()) {
                            $this->trackUser($txnid, $user_id, 'order_tax_update_sql_error', [
                                'tax_id' => $tax['id'],
                                'error'  => $db->lastError()
                            ]);
                        }
                    } catch (Exception $e) {
                        $this->trackUser($txnid, $user_id, 'order_tax_update_exception', [
                            'tax_id' => $tax['id'],
                            'error'  => $e->getMessage()
                        ]);
                    }
                }
            }

        }
        // print_r($response);exit;
        return $response;
    }
    
    public function split_ordercomputewithkds(
        $txnid = null,
        $split_reference_id = null,
        $user_id = null,
        $is_sc = 1,
        $discount = 0,
        $dish_type = 0
    )
    {
        $response = ['status' => false];
    
        if (empty($txnid) || empty($split_reference_id)) {
            return $response;
        }
    
        $sc  = ($is_sc == 1) ? servicecharge : 0;
        $kds = (int)$dish_type;
    
        // --------------------------------------------------
        // STEP 1 : FETCH ALL SPLIT ITEMS
        // --------------------------------------------------
        $items = $this->Order_split_orderhistory->find('all', [
            'conditions' => [
                'Order_split_orderhistory.reference_id'       => $txnid,
                'Order_split_orderhistory.split_reference_id' => $split_reference_id,
                'Order_split_orderhistory.status !='          => 0
            ],
            'fields' => [
                'id',
                'order_history_id',
                'product_id',
                'dish_type',
                'price',
                'quantity',
                'discount',
                'total_tax_percentage'
            ],
            'recursive' => -1
        ]);
    
        if (empty($items)) {
            return $response;
        }
    
        // --------------------------------------------------
        // STEP 2 : CALCULATE TOTALS (same as main function)
        // --------------------------------------------------
        $totalprice = 0;
        $totaldiscount = 0;
        $totalservicechg = 0;
        $totaltax = 0;
    
        foreach ($items as $row) {
    
            $item = $row['Order_split_orderhistory'];
    
            $basePrice = ($item['price'] * $item['quantity']) - $item['discount'];
    
            // discount only for matching dish_type
            $applyDiscount = ($item['dish_type'] == $kds);
    
            $itemDiscount = $applyDiscount
                ? ($basePrice * $discount) / 100
                : 0;
    
            $priceAfterDiscount = $basePrice - $itemDiscount;
    
            $serviceCharge = ($priceAfterDiscount * $sc) / 100;
    
            $taxableAmount = $priceAfterDiscount + $serviceCharge;
    
            $taxValue = ($taxableAmount * $item['total_tax_percentage']) / 100;
    
            $totalprice      += $basePrice;
            $totaldiscount   += $itemDiscount;
            $totalservicechg += $serviceCharge;
            $totaltax        += $taxValue;
            
                        
            // Now update order_tax for this item, just like ordercompute() does
            $orderhistory_id = $item['order_history_id'];
            $product_id = $item['product_id'];
            $perItemPrice = $item['price'] - $item['discount'];
            $perItemDiscount = $applyDiscount ? ($perItemPrice * $discount / 100) : 0;
            $perItemAfterDiscount = $perItemPrice - $perItemDiscount;
            $perItemServiceCharge = ($perItemAfterDiscount * $sc) / 100;
            $perItemTamount = $perItemAfterDiscount + $perItemServiceCharge;
            $tamount = round($perItemTamount, 2);
            
            $taxItems = $this->Order_tax->find('all', [
                'conditions' => [
                    'Order_tax.reference_id'     => $txnid,
                    'Order_tax.orderhistory_id' => $orderhistory_id,
                    'Order_tax.product_id'      => $product_id,
                    'Order_tax.status !='       => 0
                ],
                'recursive' => -1
            ]);
            
            foreach ($taxItems as $taxRow) {
                $tax = $taxRow['Order_tax'];
                $taxable_price = round($tamount * $tax['quantity'], 2);
                $new_tax_value = round(($taxable_price * $tax['tax_percentage']) / 100, 2);
                
                try {
                    $this->Order_tax->updateAll(
                        [
                            'Order_tax.taxable_price'       => $taxable_price,
                            'Order_tax.tax_value'           => $new_tax_value,
                            'Order_tax.discount_percentage' => $applyDiscount ? $discount : 0,
                            'Order_tax.sync'                => 0,
                            'Order_tax.local_sync'          => 0
                        ],
                        [
                            'Order_tax.id' => $tax['id']
                        ]
                    );
                    
                    $db = $this->Order_tax->getDataSource();
                    if ($db->lastError()) {
                        $this->trackUser($txnid, $user_id, 'order_tax_update_sql_error', [
                            'tax_id' => $tax['id'],
                            'error'  => $db->lastError()
                        ]);
                    }
                } catch (Exception $e) {
                    $this->trackUser($txnid, $user_id, 'order_tax_update_exception', [
                        'tax_id' => $tax['id'],
                        'error'  => $e->getMessage()
                    ]);
                }
            }

        }
    
        // --------------------------------------------------
        // STEP 3 : RETURN TOTALS
        // --------------------------------------------------
        return [
            'status'          => true,
            'totalprice'      => round($totalprice, 2),
            'totaldiscount'   => round($totaldiscount, 2),
            'totalservicechg' => round($totalservicechg, 2),
            'totaltax'        => round($totaltax, 2)
        ];
    }

    


}


