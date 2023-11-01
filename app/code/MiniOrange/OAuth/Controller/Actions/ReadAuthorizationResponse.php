<?php

namespace MiniOrange\OAuth\Controller\Actions;

use Exception;
use Magento\Framework\App\Action\Context;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuth\AccessTokenRequest;
use MiniOrange\OAuth\Helper\OAuth\AccessTokenRequestBody;
use MiniOrange\OAuth\Helper\Curl;
use MiniOrange\OAuth\Helper\OAuthUtility;

/**
 * Handles reading of Responses from the IDP. Read the SAML Response
 * from the IDP and process it to detect if it's a valid response from the IDP.
 * Generate a SAML Response Object and log the user in. Update existing user
 * attributes and groups if necessary.
 */
class ReadAuthorizationResponse extends BaseAction
{
    private $REQUEST;
    private $POST;
    private $processResponseAction;

    public function __construct(
        Context $context,
        OAuthUtility $oauthUtility,
        ProcessResponseAction $processResponseAction
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->processResponseAction = $processResponseAction;
        parent::__construct($context, $oauthUtility);
    }


/**
 * Execute function to execute the classes function.
 * @throws Exception
 */
    public function execute()
    {
         // read the response
         $params = $this->getRequest()->getParams();
        $Log_file_time= $this->oauthUtility->getStoreConfig(OAuthConstants::LOG_FILE_TIME);
        $current_time = time();
        $chk_enable_log =1;
        $islogEnable= $this->oauthUtility->getStoreConfig(OAuthConstants::ENABLE_DEBUG_LOG);
        if(($Log_file_time != NULL && ($current_time - $Log_file_time) >= 60*60*24*7) && $islogEnable) //7days
        {
            $this->oauthUtility->setStoreConfig(OAuthConstants::ENABLE_DEBUG_LOG, 0);
            $chk_enable_log = 0;
            $this->oauthUtility->setStoreConfig(OAuthConstants::LOG_FILE_TIME, NULL);
            $this->oauthUtility->deleteCustomLogFile();
            $this->oauthUtility->flushCache();
        }
        $chk_enable_log ? $this->oauthUtility->customlog("ReadAuthorizationResponse: execute"): NULL ;

        $chk_enable_log ? $this->oauthUtility->customlog("ReadAuthorizationResponse: params: ".implode(" ",$params)) : NULL;
        if (!isset($params['code'])) {
            $relayState = isset($params['relayState']) ? $params['relayState'] : '';
            $chk_enable_log ?  $this->oauthUtility->customlog("ReadAuthorizationResponse: params['code'] not set"): NULL;
            if (isset($params['error'])) {
                return $this->sendHTTPRedirectRequest('?error='.urlencode($params['error']), $this->oauthUtility->getBaseUrl());
            }
            return $this->sendHTTPRedirectRequest('?error=code+not+received', $this->oauthUtility->getBaseUrl(),$relayState,$params);
        }

        $authorizationCode = $params['code'];
        $relayState = $params['state']; // TODO: Security issue to be fixed
        $chk_enable_log ? $this->oauthUtility->customlog("ReadAuthorizationResponse: authorizationCode:  ".$authorizationCode) : NULL;
        $chk_enable_log ? $this->oauthUtility->customlog("ReadAuthorizationResponse: relayState:  ".$relayState) : NULL;

        //get required values from the database
        $clientID = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_ID);
        $clientSecret = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_SECRET);
        $grantType = OAuthConstants::GRANT_TYPE;
        $accessTokenURL =  $this->oauthUtility->getStoreConfig(OAuthConstants::ACCESSTOKEN_URL);
        $redirectURL = $this->oauthUtility->getCallBackUrl();


        $header = $this->oauthUtility->getStoreConfig(OAuthConstants::SEND_HEADER);
        $body = $this->oauthUtility->getStoreConfig(OAuthConstants::SEND_BODY);

        if($header == 1 && $body == 0)
        {
            $accessTokenRequest = (new AccessTokenRequestBody($grantType, $redirectURL, $authorizationCode))->build();
        }

        //generate the accessToken request
        else
        $accessTokenRequest = (new AccessTokenRequest($clientID, $clientSecret, $grantType, $redirectURL, $authorizationCode))->build();

        //send the accessToken request
        $accessTokenResponse = Curl::mo_send_access_token_request($accessTokenRequest, $accessTokenURL, $clientID, $clientSecret);

        // todo: if access token response has an error
        // if access token endpoint returned a success response
       $accessTokenResponseData = json_decode($accessTokenResponse, 'true');
        if (isset($accessTokenResponseData['access_token'])) {
            $accessToken = $accessTokenResponseData['access_token'];
            $userInfoURL = $this->oauthUtility->getStoreConfig(OAuthConstants::GETUSERINFO_URL);

            $header = "Bearer " . $accessToken;
            $authHeader =  [
                "Authorization: $header"
            ];

            $userInfoResponse = Curl::mo_send_user_info_request($userInfoURL, $authHeader);
            $userInfoResponseData = json_decode($userInfoResponse, 'true');
            $chk_enable_log ? $this->oauthUtility->customlog("ReadAuthorizationResponse : access_token: UserInfoResponseData-> ".json_encode($userInfoResponseData)): NULL;
        } elseif (isset($accessTokenResponseData['id_token'])) {
            $idToken = $accessTokenResponseData['id_token'];
            if (!empty($idToken)) {
                $x509_cert = $this->oauthUtility->getStoreConfig(OAuthConstants::X509CERT);
                $idTokenArray = explode(".", $idToken);
            }
        } else {
            $chk_enable_log ? $this->oauthUtility->customlog("ReadAuthorizationResponse :access_token ->NULL and id_token ->NULL" ): NULL;
            return $this->getResponse()->setBody("Invalid response. Please try again.");
        }

        if (empty($userInfoResponseData)) {
            $chk_enable_log ? $this->oauthUtility->customlog("ReadAuthorizationResponse : userinfoResponseData NULL") : NULL;
            return $this->getResponse()->setBody("Invalid response. Please try again.");
        }

        if(is_array($userInfoResponseData)){
            $userInfoResponseData['relayState']= $relayState;
        }
        else{
            $userInfoResponseData->relayState= $relayState;
        }
        $this->processResponseAction->setUserInfoResponse($userInfoResponseData)->execute();
    }

    /** Setter for the request Parameter */
    public function setRequestParam($request)
    {
        $this->REQUEST = $request;
        return $this;
    }


    /** Setter for the post Parameter */
    public function setPostParam($post)
    {
        $this->POST = $post;
        return $this;
    }

    public function get_base64_from_url($b64url)
    {
        return base64_decode(str_replace(['-','_'], ['+','/'], $b64url));
    }

}
