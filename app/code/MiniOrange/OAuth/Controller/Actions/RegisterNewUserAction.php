<?php 

namespace MiniOrange\OAuth\Controller\Actions;

use MiniOrange\OAuth\Helper\Curl;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthMessages;
use MiniOrange\OAuth\Helper\OAuthUtility;
use MiniOrange\OAuth\Helper\Exception\PasswordMismatchException;
use MiniOrange\OAuth\Helper\Exception\AccountAlreadyExistsException;
use MiniOrange\OAuth\Helper\Exception\TransactionLimitExceededException;

/**
 * Handles registration of new user account. This is called when the 
 * registration form is submitted. Process the credentials and 
 * information provided by the admin.
 * 
 * This action class first checks if a customer exists with the email
 * address provided. If no customer exists then start the validation process.
 */
class RegisterNewUserAction extends BaseAdminAction
{
    private $REQUEST;
    private $loginExistingUserAction;

	public function __construct(\Magento\Backend\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \MiniOrange\OAuth\Helper\OAuthUtility $oauthUtility,
                                \Magento\Framework\Message\ManagerInterface $messageManager,
                                \Psr\Log\LoggerInterface $logger,
                                \MiniOrange\OAuth\Controller\Actions\LoginExistingUserAction $loginExistingUserAction)
    {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context,$resultPageFactory,$oauthUtility,$messageManager,$logger);
        $this->loginExistingUserAction = $loginExistingUserAction;
    }

    
	/**
	 * Execute function to execute the classes function. 
     * 
	 * @throws \Exception
	 */
	public function execute()
	{
        $this->oauthUtility->customlog("RegisterNewUserAction: execute") ;
        if(isset($this->REQUEST['registered']))
        $this->checkIfRequiredFieldsEmpty(['email'=>$this->REQUEST,'password'=>$this->REQUEST]);
        else
        $this->checkIfRequiredFieldsEmpty(['email'=>$this->REQUEST,'password'=>$this->REQUEST,'confirmPassword'=>$this->REQUEST]);
        $email = $this->REQUEST['email'];
        $password = $this->REQUEST['password'];
        $confirmPassword = $this->REQUEST['confirmPassword'];
        $companyName = $this->REQUEST['companyName'];
        $firstName = $this->REQUEST['firstName'];
        $lastName = $this->REQUEST['lastName'];
        if(!isset($this->REQUEST['registered']))
        if (strcasecmp($confirmPassword, $password)!=0) {
            throw new PasswordMismatchException;
        }
        
        $result = $this->checkIfUserExists($email);
        if (strcasecmp($result['status'], 'CUSTOMER_NOT_FOUND') == 0) {
            $this->oauthUtility->setStoreConfig(OAuthConstants::CUSTOMER_EMAIL,$email);
            $this->oauthUtility->setStoreConfig(OAuthConstants::CUSTOMER_NAME,$companyName);
            $this->oauthUtility->setStoreConfig(OAuthConstants::CUSTOMER_FNAME,$firstName); 
            $this->oauthUtility->setStoreConfig(OAuthConstants::CUSTOMER_LNAME,$lastName); 
            $this->oauthUtility->setStoreConfig(OAuthConstants::REG_STATUS,OAuthConstants::STATUS_COMPLETE_LOGIN);
            
            $this->startVerificationProcess($result, $email, $companyName, $firstName, $lastName, $password);
        } else {
            $this->oauthUtility->setStoreConfig(OAuthConstants::CUSTOMER_EMAIL,$email);
            $this->loginExistingUserAction
                 ->setRequestParam($this->REQUEST)
                 ->execute();
        }
    }
    

    /**
     * Function is used to make a cURL call which will check
     * if a user exists with the given credentials. If a user
     * is found then his details are fetched automatically and
     * saved.
     * 
     * @param $email
     */
    private function checkIfUserExists($email)
    { $this->oauthUtility->customlog("RegisterNewUserAction: checkIfUserExists") ;

        $content = Curl::check_customer($email);
        return json_decode($content, true);
    }
    

   
    private function startVerificationProcess($result,$email,$companyName,$firstName,$lastName,$password)
    { $this->oauthUtility->customlog("RegisterNewUserAction: StartVerificationProcess") ;

        if (isset($this->REQUEST['registered'])) {
            throw new \Exception("Account does not exist");
        } else {
            $this->createUserInMiniorange($result, $email, $companyName, $firstName, $lastName, $password);
        }
    
    }


    private function createUserInMiniorange($result,$email,$companyName,$firstName,$lastName,$pass)
    {     $this->oauthUtility->customlog("In createUserInMiniorange()") ;

        $result = Curl::create_customer($email, $companyName,$pass, '', $firstName, $lastName);
        $result= json_decode($result, true);
   $this->oauthUtility->customlog(print_r($result,true))  ;

        if (strcasecmp($result['status'], 'SUCCESS') == 0) {
            $content = Curl::get_customer_key($email, $pass);
            $customerKey = json_decode($content, true);
            $this->configureUserInMagento($result,$customerKey);
        }
        elseif(strcasecmp($result['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0)
        {
            $this->oauthUtility->setStoreConfig(OAuthConstants::REG_STATUS, '');
            throw new AccountAlreadyExistsException;
        }
        elseif(strcasecmp($result['status'], 'TRANSACTION_LIMIT_EXCEEDED')==0)
        {
            $this->oauthUtility->setStoreConfig(OAuthConstants::REG_STATUS, '');
            throw new TransactionLimitExceededException;
        }
        
    }

    private function configureUserInMagento($result,$customerKey)
    {$this->oauthUtility->customlog("In configureUserInMagento()") ;

        $this->oauthUtility->setStoreConfig(OAuthConstants::SAMLSP_KEY, $result['id']);
        $this->oauthUtility->setStoreConfig(OAuthConstants::API_KEY, $result['apiKey']);
        $this->oauthUtility->setStoreConfig(OAuthConstants::TOKEN, $result['token']);
        $this->oauthUtility->setStoreConfig(OAuthConstants::REG_STATUS, OAuthConstants::STATUS_COMPLETE_LOGIN);
        $this->getMessageManager()->addSuccessMessage(OAuthMessages::REG_SUCCESS);
    }


    


	/** Setter for the request Parameter */
    public function setRequestParam($request)
    {
		$this->REQUEST = $request;
		return $this;
    }
}