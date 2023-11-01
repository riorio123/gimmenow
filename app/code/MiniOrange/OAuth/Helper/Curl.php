<?php

namespace MiniOrange\OAuth\Helper;

use MiniOrange\OAuth\Helper\OAuthConstants;


/**
 * This class denotes all the cURL related functions.
 */
class Curl
{

    public static function create_customer($email, $company, $password, $phone = '', $first_name = '', $last_name = '')
    {
        $url = OAuthConstants::HOSTNAME . '/moas/rest/customer/add';
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'companyName' => $company,
            'areaOfInterest' => OAuthConstants::AREA_OF_INTEREST,
            'firstname' => $first_name,
            'lastname' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function get_customer_key($email, $password)
    {
        $url = OAuthConstants::HOSTNAME . "/moas/rest/customer/key";
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'email' => $email,
            'password' => $password
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function check_customer($email)
    {
        $url = OAuthConstants::HOSTNAME . "/moas/rest/customer/check-if-exists";
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'email' => $email,
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function mo_send_access_token_request($postData, $url, $clientID, $clientSecret)
    {
        $authHeader = [
            "Content-Type: application/x-www-form-urlencoded",
            'Authorization: Basic '.base64_encode($clientID.":".$clientSecret)
        ];
        $response = self::callAPI($url, $postData, $authHeader);
        return $response;
    }

    public static function mo_send_user_info_request($url, $headers)
    {

        $response = self::callAPI($url, [], $headers);
        return $response;
    }

    public static function submit_contact_us(
        $q_email,
        $q_phone,
        $query
    ) {
        $url = OAuthConstants::HOSTNAME . "/moas/rest/customer/contact-us";
        $query = '[' . OAuthConstants::AREA_OF_INTEREST . ']: ' . $query;
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;

        $fields = [
            'email' => $q_email,
            'phone' => $q_phone,
            'query' => $query,
            'ccEmail' => 'magentosupport@xecurify.com'
                ];

        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);


        return true;
    }

//Tracking admin email,firstname and lastname.
    public static function submit_to_magento_team(
        $q_email,
        $sub,
        $values
    ) {
        $url = OAuthConstants::HOSTNAME . "/moas/api/notify/send";
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Magento 2.0 OAuth Client free Plugin $sub : $q_email",
                'content'       => "Admin Email = $q_email, First name= $values[0],last Name = $values[1], Site= $values[2]"
            ),
        );
        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Magento 2.0 OAuth Client free Plugin $sub : $q_email",
                'content'       => "Admin Email = $q_email, First name= $values[0],last Name = $values[1], Site= $values[2]"
            ),
        );

        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields1, $authHeader);
        $response = self::callAPI($url, $fields2, $authHeader);

        return true;
    }

    //Tracking Configuration data.
    public static function submit_to_magento_team_core_config_data(
        $q_email,
        $sub,
        $content,
        $values
    ) {
        $url = OAuthConstants::HOSTNAME . "/moas/api/notify/send";
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Magento 2.0 OAuth Client free Plugin $sub : $q_email",
                'content'       => "$content ,
                                     Admin Email = $q_email , Appname: $values[0] , Scope: $values[1] , Client_id:$values[2] ,Client_secret:$values[3] , Authorize_url: $values[4] , Accesstoken_url: $values[5] ,
                                     Getuserinfo_url: $values[6] , Header: $values[7] , Body: $values[8] , Well known config url: $values[9] ,
                                     Show_customer_link: $values[10] , Attribute_email: $values[11] , Attribute_username: $values[12] , Customer_email: $values[13]"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Magento 2.0 OAuth Client free Plugin $sub : $q_email",
                'content'       => "$content ,
                                     Admin Email = $q_email , Appname: $values[0] , Scope: $values[1] , Client_id:$values[2] ,Client_secret:$values[3] , Authorize_url: $values[4] , Accesstoken_url: $values[5] ,
                                     Getuserinfo_url: $values[6] , Header: $values[7] , Body: $values[8] , Well known config url: $values[9] ,
                                     Show_customer_link: $values[10] , Attribute_email: $values[11] , Attribute_username: $values[12] , Customer_email: $values[13]"
            ),
        );

       // $field_string = json_encode($fields);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);


        return true;
    }
    public static function submit_to_magento_team_autocreate_limit_exceeded($q_email, $site, $magentoVersion) {
        $url =  OAuthConstants::HOSTNAME . "/moas/api/notify/send";
        $customerKey =  OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey =  OAuthConstants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Magento2.0 OAuth Client free Plugin AUTOCREATE USER LIMIT EXEEDED",
                'content'       => "Admin User: $q_email, Site: $site, Magento Version = $magentoVersion"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Magento 2.0 OAuth Client free Plugin AUTOCREATE USER LIMIT EXEEDED",
                'content'       => "Admin User: $q_email, Site: $site, Magento Version = $magentoVersion"
            ),
        );

        $field_string1 = json_encode($fields1);
        $field_string2 = json_encode($fields2);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);
        return true;
    }
    public static function check_customer_ln($customerKey, $apiKey)
    {
        $url = OAuthConstants::HOSTNAME . '/moas/rest/customer/license';
        $fields = [
            'customerId' => $customerKey,
            'applicationName' => OAuthConstants::APPLICATION_NAME,
            'licenseType' => !MoUtility::micr() ? 'DEMO' : 'PREMIUM',
        ];

        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    private static function createAuthHeader($customerKey, $apiKey)
    {
        $currentTimestampInMillis = round(microtime(true) * 1000);
        $currentTimestampInMillis = number_format($currentTimestampInMillis, 0, '', '');

        $stringToHash = $customerKey . $currentTimestampInMillis . $apiKey;
        $authHeader = hash("sha512", $stringToHash);

        $header = [
            "Content-Type: application/json",
            "Customer-Key: $customerKey",
            "Timestamp: $currentTimestampInMillis",
            "Authorization: $authHeader"
        ];
        return $header;
    }

    private static function callAPI($url, $jsonData = [], $headers = ["Content-Type: application/json"])
    {
        // Custom functionality written to be in tune with Mangento2 coding standards.
        $curl = new MoCurl();
        $options = [
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_ENCODING' => "",
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_AUTOREFERER' => true,
            'CURLOPT_TIMEOUT' => 0,
            'CURLOPT_MAXREDIRS' => 10
        ];


        $data = in_array("Content-Type: application/x-www-form-urlencoded", $headers)
            ? (!empty($jsonData) ? http_build_query($jsonData) : "") : (!empty($jsonData) ? json_encode($jsonData) : "");

        $method = !empty($data) ? 'POST' : 'GET';
        $curl->setConfig($options);
        $curl->write($method, $url, '1.1', $headers, $data);
        $content = $curl->read();
        $curl->close();
        return $content;
    }
}
