<?php

namespace MiniOrange\OAuth\Controller\Actions;

use Magento\Authorization\Model\ResourceModel\Role\Collection;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\User;
use MiniOrange\OAuth\Helper\Curl;
use Magento\User\Model\UserFactory;
use MiniOrange\OAuth\Helper\Exception\MissingAttributesException;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthUtility;
use MiniOrange\OAuth\Helper\OAuthMessages;
use Magento\Framework\App\Config\ScopeConfigInterface;
/**
 * This action class processes the user attributes coming in
 * the SAML response to either log the customer or admin in
 * to their respective dashboard or create a customer or admin
 * based on the default role set by the admin and log them in
 * automatically.
 */
class ProcessUserAction extends BaseAction
{
    private $attrs;
    private $flattenedattrs;
    private $userEmail;
    private $checkIfMatchBy;
    private $defaultRole;
    private $emailAttribute;
    private $usernameAttribute;
    private $firstNameKey;
    private $lastNameKey;
    private $userGroupModel;
    private $adminRoleModel;
    private $adminUserModel;
    private $customerModel;
    private $customerLoginAction;
    private $responseFactory;
    private $customerFactory;
    private $userFactory;
    private $randomUtility;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        OAuthUtility $oauthUtility,
        \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel,
        Collection $adminRoleModel,
        User $adminUserModel,
        Customer $customerModel,
        StoreManagerInterface $storeManager,
        ResponseFactory $responseFactory,
        CustomerLoginAction $customerLoginAction,
        CustomerFactory $customerFactory,
        UserFactory $userFactory,
        Random $randomUtility
    ) {
        $this->emailAttribute = $oauthUtility->getStoreConfig(OAuthConstants::MAP_EMAIL);
        $this->emailAttribute = $oauthUtility->isBlank($this->emailAttribute) ? OAuthConstants::DEFAULT_MAP_EMAIL : $this->emailAttribute;
        $this->usernameAttribute = $oauthUtility->getStoreConfig(OAuthConstants::MAP_USERNAME);
        $this->usernameAttribute = $oauthUtility->isBlank($this->usernameAttribute) ? OAuthConstants::DEFAULT_MAP_USERN : $this->usernameAttribute;
        $this->firstNameKey = $oauthUtility->getStoreConfig(OAuthConstants::MAP_FIRSTNAME);
        $this->firstNameKey = $oauthUtility->isBlank($this->firstNameKey) ? OAuthConstants::DEFAULT_MAP_FN : $this->firstNameKey;
        $this->lastNameKey = $oauthUtility->getStoreConfig(OAuthConstants::MAP_LASTNAME);
        $this->lastNameKey = $oauthUtility->isBlank($this->lastNameKey) ? OAuthConstants::DEFAULT_MAP_LN : $this->lastNameKey;
        $this->defaultRole = $oauthUtility->getStoreConfig(OAuthConstants::MAP_DEFAULT_ROLE);
        $this->checkIfMatchBy = $oauthUtility->getStoreConfig(OAuthConstants::MAP_MAP_BY);
        $this->userGroupModel = $userGroupModel;
        $this->adminRoleModel = $adminRoleModel;
        $this->adminUserModel = $adminUserModel;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->checkIfMatchBy = $oauthUtility->getStoreConfig(OAuthConstants::MAP_MAP_BY);
        $this->responseFactory = $responseFactory;
        $this->customerLoginAction = $customerLoginAction;
        $this->customerFactory = $customerFactory;
        $this->userFactory = $userFactory;
        $this->randomUtility = $randomUtility;
            parent::__construct($context, $oauthUtility);
    }
    /**
     * Execute function to execute the classes function.
     *
     * @throws MissingAttributesException
     */
    public function execute()
    {    $this->oauthUtility->customlog("ProcessUserAction: execute")  ;
        // throw an exception if attributes are empty
        if (empty($this->attrs)) {
            $this->oauthUtility->customlog("No Attributes Received :")  ;
            throw new MissingAttributesException;
        }
        $firstName = $this->flattenedattrs[$this->firstNameKey] ?? null;
        $lastName = $this->flattenedattrs[$this->lastNameKey] ?? null;
        $userName = $this->flattenedattrs[$this->usernameAttribute] ?? null;
        $this->oauthUtility->customlog("ProcessUserAction: first name: ".$firstName)  ;
        $this->oauthUtility->customlog("ProcessUserAction: last name :".$lastName)  ;
        $this->oauthUtility->customlog("ProcessUserAction: username :".$userName)  ;
        if ($this->oauthUtility->isBlank($this->defaultRole)) {
            $this->defaultRole = OAuthConstants::DEFAULT_ROLE;
        }

        // process the user
        $this->processUserAction($this->userEmail, $firstName, $lastName, $userName, $this->defaultRole);
    }


    /**
     * This function processes the user values to either create
     * a new user on the site and log him/her in or log an existing
     * user to the site. Mapping is done based on $checkIfMatchBy
     * variable. Either email or username.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $checkIfMatchBy
     * @param $defaultRole
     */
    private function processUserAction($user_email, $firstName, $lastName, $userName, $defaultRole)
    {
        $admin = false;

        // check if the a customer or admin user exists based on the email in OAuth response
        $user = $this->getCustomerFromAttributes($user_email);

        if (!$user) {
            $this->oauthUtility->customlog("User Not found. Inside autocreate user tab")  ;
            $donotCreateUsers=$this->oauthUtility->getStoreConfig(OAuthConstants::MAGENTO_COUNTER);
            if (is_null($donotCreateUsers)) {
                 $this->oauthUtility->setStoreConfig(OAuthConstants::MAGENTO_COUNTER, 10);
                 $this->oauthUtility->reinitConfig();
                 $donotCreateUsers=$this->oauthUtility->getStoreConfig(OAuthConstants::MAGENTO_COUNTER);
            }
            if ($donotCreateUsers<1) {
                $email = $this->oauthUtility->getStoreConfig(OAuthConstants::CUSTOMER_EMAIL);
                $site = $this->oauthUtility->getBaseUrl();
                $magentoVersion = $this->oauthUtility->getProductVersion();
                Curl::submit_to_magento_team_autocreate_limit_exceeded($email,$site, $magentoVersion);
                $this->oauthUtility->customlog("Your Auto Create User Limit for the free Miniorange Magento OAuth/OpenID plugin is exceeded. Please Upgrade to any of the Premium Plan to continue the service.")  ;
                $this->messageManager->addErrorMessage(OAuthMessages::AUTO_CREATE_USER_LIMIT);
                 $url = $this->oauthUtility->getCustomerLoginUrl();
                return $this->getResponse()->setRedirect($url)->sendResponse();
            } else {
                $count=$this->oauthUtility->getStoreConfig(OAuthConstants::MAGENTO_COUNTER);
                $this->oauthUtility->setStoreConfig(OAuthConstants::MAGENTO_COUNTER, $count-1);
                $this->oauthUtility->reinitConfig();
                $this->oauthUtility->customlog("Creating new customer");
                 $user = $this->createNewUser($user_email, $firstName, $lastName, $userName, $user, $admin);
               $this->oauthUtility->customlog("processUserAction: user created")  ;
            }
        }else{
            $this->oauthUtility->customlog("processUserAction: User Found")  ;
        }

        // log the user in to it's respective dashboard
    $this->oauthUtility->customlog("processUserAction: redirecting customer")  ;
    if(is_array($this->attrs)){
            $this->customerLoginAction->setUser($user)->setRelayState($this->attrs['relayState'])->execute();}
    else{
        $this->customerLoginAction->setUser($user)->setRelayState($this->attrs->relayState)->execute();
    }
    }


    /**
     * Create a temporary email address based on the username
     * in the SAML response. Email Address is a required so we
     * need to generate a temp/fake email if no email comes from
     * the IDP in the SAML response.
     *
     * @param  $userName
     * @return string
     */
    private function generateEmail($userName)
    { $this->oauthUtility->customlog("processUserAction : generateEmail");

        $siteurl = $this->oauthUtility->getBaseUrl();
        $siteurl = substr($siteurl, strpos($siteurl, '//'), strlen($siteurl)-1);
        return $userName .'@'.$siteurl;
    }

    /**
     * Create a new user based on the SAML response and attributes. Log the user in
     * to it's appropriate dashboard. This class handles generating both admin and
     * customer users.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $defaultRole
     * @param $user
     */
    private function createNewUser($user_email, $firstName, $lastName, $userName, $user, &$admin)
    {

        // generate random string to be inserted as a password
   $this->oauthUtility->customlog("processUserAction: createNewUser") ;
  if(empty($firstName)){ $parts  = explode("@", $user_email);
    $firstName = $parts[0];
  }
  if(empty($lastName)){
    $parts  = explode("@",$user_email);
    $lastName = $parts[1];
  }
   $random_password = $this->randomUtility->getRandomString(8);
        $userName = !$this->oauthUtility->isBlank($userName)? $userName : $user_email;
        $firstName = !$this->oauthUtility->isBlank($firstName) ? $firstName : $userName;
        $lastName = !$this->oauthUtility->isBlank($lastName) ? $lastName : $userName;

        // create admin or customer user based on the role
        $user = $this->createCustomer($userName, $user_email, $firstName, $lastName, $random_password);

        return $user;
    }


    /**
     * Create a new customer.
     *
     * @param $email
     * @param $userName
     * @param $random_password
     * @param $role_assigned
     */
    private function createCustomer($userName, $email, $firstName, $lastName, $random_password)
    { $this->oauthUtility->customlog("processUserAction: createCustomer") ;

        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        $this->oauthUtility->customlog("processUserAction: websiteID :".$websiteId." : email: ".$email." :firstName: ".$firstName." lastname: ".$lastName) ;
        return $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->setEmail($email)
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setPassword($random_password)
            ->save();
    }

    /**
     * Get the Customer User from the Attributes in the SAML response
     * Return false if the customer doesn't exist. The customer is fetched
     * by email only. There are no usernames to set for a Magento Customer.
     *
     * @param $user_email
     * @param $userName
     */
    private function getCustomerFromAttributes($user_email)
    {  $this->oauthUtility->customlog("processUserAction: getCustomerFromAttributes") ;
        $this->customerModel->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $customer = $this->customerModel->loadByEmail($user_email);
        return !is_null($customer->getId()) ? $customer : false;
    }


    /**
     * The setter function for the Attributes Parameter
     */
    public function setAttrs($attrs)
    {
        $this->attrs = $attrs;
        return $this;
    }

    /**
     * The setter function for the Attributes Parameter
     */
    public function setFlattenedAttrs($flattenedattrs)
    {
        $this->flattenedattrs = $flattenedattrs;
        return $this;
    }

    /**
     * Setter for the User Email Parameter
     */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
        return $this;
    }
}
