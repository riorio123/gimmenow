<?php

namespace MiniOrange\OAuth\Controller\Adminhtml\Attrsettings;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthMessages;
use MiniOrange\OAuth\Controller\Actions\BaseAdminAction;
use MiniOrange\OAuth\Helper\OAuthUtility;
use Psr\Log\LoggerInterface;
use MiniOrange\OAuth\Helper\Curl;


/**
 * This class handles the action for endpoint: mooauth/attrsettings/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction implements HttpPostActionInterface, HttpGetActionInterface
{

    private $adminRoleModel;
    private $userGroupModel;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        OAuthUtility $oauthUtility,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        \Magento\Authorization\Model\ResourceModel\Role\Collection $adminRoleModel,
        Collection $userGroupModel
    ) {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context, $resultPageFactory, $oauthUtility, $messageManager, $logger);
        $this->adminRoleModel = $adminRoleModel;
        $this->userGroupModel = $userGroupModel;
    }

    /**
     * The first function to be called when a Controller class is invoked.
     * Usually, has all our controller logic. Returns a view/page/template
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the
     * database. It's called when you visis the moasaml/attrsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $send_email= $this->oauthUtility->getStoreConfig(OAuthConstants::SEND_EMAIL);
        
        //Tracking admin email,firstname and lastname.
        if($send_email==NULL)
         {  $currentAdminUser =  $this->oauthUtility->getCurrentAdminUser()->getData();  
            $userEmail = $currentAdminUser['email'];
            $firstName = $currentAdminUser['firstname'];
            $lastName = $currentAdminUser['lastname'];
            $site = $this->oauthUtility->getBaseUrl();
            $values=array($firstName, $lastName, $site);
            Curl::submit_to_magento_team($userEmail, 'Installed Successfully-Attribute Setting Tab', $values);
            $this->oauthUtility->setStoreConfig(OAuthConstants::SEND_EMAIL,1);
            $this->oauthUtility->flushCache() ;
        }

        try {
            $params = $this->getRequest()->getParams(); //get params
            
            if ($this->isFormOptionBeingSaved($params)) { // check if form options are being saved
                $this->checkIfRequiredFieldsEmpty(['oauth_am_username'=>$params,'oauth_am_email'=>$params]);
                $this->processValuesAndSaveData($params);
                $this->oauthUtility->flushCache();
                $this->messageManager->addSuccessMessage(OAuthMessages::SETTINGS_SAVED);
                $this->oauthUtility->reinitConfig();
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->oauthUtility->customlog($e->getMessage()) ;
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__(OAuthConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
     * Process Values being submitted and save data in the database.
     * @param $param
     */
    private function processValuesAndSaveData($params)
    {
          $this->oauthUtility->setStoreConfig(OAuthConstants::MAP_USERNAME, $params['oauth_am_username']);
          $this->oauthUtility->setStoreConfig(OAuthConstants::MAP_EMAIL, $params['oauth_am_email']);

        if (isset($params['dont_create_user_if_role_not_mapped'])) {
            $this->oauthUtility->setStoreConfig(OAuthConstants::CREATEIFNOTMAP, $params['dont_create_user_if_role_not_mapped']);
        }

        if (isset($params['dont_allow_unlisted_user_role'])) {
            $this->oauthUtility->setStoreConfig(OAuthConstants::UNLISTED_ROLE, $params['dont_allow_unlisted_user_role']);
        }
    }

    /**
     * Is the user allowed to view the Attribute Mapping settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(OAuthConstants::MODULE_DIR.OAuthConstants::MODULE_ATTR);
    }
}
