<?php

namespace MiniOrange\OAuth\Controller\Actions;

use MiniOrange\OAuth\Helper\OAuth\AuthorizationRequest;
use MiniOrange\OAuth\Helper\OAuthConstants;

/**
 * Handles generation and sending of AuthnRequest to the IDP
 * for authentication. AuthnRequest is generated and user is
 * redirected to the IDP for authentication.
 */
class sendAuthorizationRequest extends BaseAction
{

    /**
     * Execute function to execute the classes function.
     * @throws \Exception
     */
    public function execute()
    {   $Log_file_time= $this->oauthUtility->getStoreConfig(OAuthConstants::LOG_FILE_TIME);
        $current_time = time();
        $chk_enable_log = 1;
       $islogEnable= $this->oauthUtility->getStoreConfig(OAuthConstants::ENABLE_DEBUG_LOG);
       $log_file_exist = $this->oauthUtility->isCustomLogExist();

        if((($Log_file_time != NULL && ($current_time - $Log_file_time) >= 60*60*24*7) && $islogEnable) || ($islogEnable == 0 && $log_file_exist)) //7days
        {   
            $this->oauthUtility->setStoreConfig(OAuthConstants::ENABLE_DEBUG_LOG, 0);
            $chk_enable_log = 0;
            $this->oauthUtility->setStoreConfig(OAuthConstants::LOG_FILE_TIME, NULL);
            $this->oauthUtility->deleteCustomLogFile();
            $this->oauthUtility->flushCache();
        }
        $chk_enable_log ? $this->oauthUtility->customlog("SendAuthorizationRequest: execute") : NULL;

        $params = $this->getRequest()->getParams();  //get params
        
       $chk_enable_log ? $this->oauthUtility->customlog("SendAuthorizationRequest: Request prarms: ".implode(" ",$params)) : NULL;
        $relayState = isset($params['relayState']) ? $params['relayState'] : '/';

        if ($relayState == OAuthConstants::TEST_RELAYSTATE) {
            $this->oauthUtility->setStoreConfig(OAuthConstants::IS_TEST, true);
            $this->oauthUtility->flushCache();
        }

        if (!$this->oauthUtility->isOAuthConfigured()) {
            return;
        }

        //get required values from the database
        $clientID = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_ID);
        $scope = $this->oauthUtility->getStoreConfig(OAuthConstants::SCOPE);
        $authorizeURL =  $this->oauthUtility->getStoreConfig(OAuthConstants::AUTHORIZE_URL);
        $responseType = OAuthConstants::CODE;
            $redirectURL = $this->oauthUtility->getCallBackUrl();

        //generate the authorization request
        $authorizationRequest = (new AuthorizationRequest($clientID, $scope, $authorizeURL, $responseType, $redirectURL, $relayState,$params))->build();
       $chk_enable_log ? $this->oauthUtility->customlog("SendAuthorizationRequest:  Authorization Request: ".$authorizationRequest) : NULL;
        // send oauth request over
        $relayState = isset($params['relayState']) ? $params['relayState'] : '';
        return $this->sendHTTPRedirectRequest($authorizationRequest, $authorizeURL,$relayState,$params);
    }
}
