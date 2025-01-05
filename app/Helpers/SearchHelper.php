<?php

namespace App\Helpers;

use App\Customer;
use Illuminate\Support\Facades\Validator;
use App\Client;
use App\WalkinCustomer;

Class SearchHelper {
    /*
      |--------------------------------------------------------------------------
      | SearchHelper that contains search related methods for APIs
      |--------------------------------------------------------------------------
      |
      | This Helper controls all the methods that use search processes
      |
     */

    /**
     * Description of SearchHelper
     *
     * @author ILSA Interactive
     */
    public static function processSearchQuery($inputs = []) {
        $validation = Validator::make($inputs, SearchValidationHelper::searchRules()['rules'], SearchValidationHelper::searchRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }
        if ($inputs['search_type'] == 'search_customer') {
            return self::customerSearchProcess($inputs);
        }
        return CommonHelper::jsonErrorResponse(MessageHelper::getMessageData('error', $inputs['lang'])['success_error']);
    }

    public static function customerSearchProcess($inputs) {
        $customers = Customer::searchCustomer($inputs['search_query']);
        $response = CustomerResponseHelper::customerListResponse($customers);
        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $response);
    }

    public static function searchFreelancerClient($inputs = []) {
        $validation = Validator::make($inputs, SearchValidationHelper::searchClientRules()['rules'], SearchValidationHelper::searchClientRules()['message_' . strtolower($inputs['lang'])]);
        if ($validation->fails()) {
            return CommonHelper::jsonErrorResponse($validation->errors()->first());
        }

        $inputs['freelancer_id'] = CommonHelper::getFreelancerIdByUuid($inputs['freelancer_uuid']);

        $clients_id_array = Client::getClientsColumn($inputs['freelancer_id'], 'customer_id');
        $getCustomerResponse = [];
        if(!empty($clients_id_array)){
            $get_clients = Client::getClients('freelancer_id', $inputs['freelancer_id']);

            $customers = Customer::searchClientCustomers($clients_id_array, $inputs['search_query']);
            $customer_response = CustomerResponseHelper::customerListResponse($customers, $inputs);
            $getCustomerResponse = ClientResponseHelper::addClientUuidinResponse($customer_response, $get_clients, $inputs['freelancer_id']);
        }
        else{
            $getCustomer = Customer::searchClientCustomers([], $inputs['search_query'],100,$inputs['freelancer_id']);
            $getCustomerResponse = CustomerResponseHelper::customerListResponse($getCustomer, $inputs);
        }


        //TODO: Commit the extra loginc and query in search client for freelancer


//        $get_clients = Client::searchClients('freelancer_uuid', $inputs['freelancer_uuid'], $inputs['search_query']);
//        $clients_response = ClientResponseHelper::clientListResponse($get_clients);

        $add_client_uuid = [];
       //$searchClients = array_merge($customer_response,$getCustomerResponse);


//        $walkin_customers = WalkinCustomer::searchClientWalkinCustomers($inputs['freelancer_uuid'], $inputs['search_query']);

        //$walkin_customers = WalkinCustomer::searchMultipleClientWalkinCustomers($clients_id_array, $inputs['search_query']);
        //$walkin_customers_response = WalkinCustomerResponseHelper::searchWalkinCustomersResponse($walkin_customers);

        //$response = array_merge($customer_response, $walkin_customers_response);
        //$response = $searchClients;

        return CommonHelper::jsonSuccessResponse(MessageHelper::getMessageData('success', $inputs['lang'])['successful_request'], $getCustomerResponse);
    }

}

?>
