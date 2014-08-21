<?php
class Mailrelay_Mrsync_Adminhtml_MrsyncController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        // check if we have API key
        $model = Mage::getModel("mrsync/mrsync");
        $api = Mage::getStoreConfig("mrsync/mrsync/sync_api_key");

                if (!$api)
                {
                        $this->_getSession()->addError(Mage::helper("mrsync")->__("There has been an error with your Mailrelay sync settings. Please check that they are correct"));
                        $this->_redirect("*/system_config/edit/section/mrsync");
                }

        $this->loadLayout();
        $this->_setActiveMenu("customer/mrsync");

        $this->_addBreadcrumb(Mage::helper('mrsync')->__("Mailrelay sync users"), Mage::helper('mrsync')->__("Mailrelay sync users"));

        // first retrieve all customer groups
        $customer_group = new Mage_Customer_Model_Group();
        $all_groups = $customer_group->getCollection()->toOptionHash();

        $groups = array();
        foreach($all_groups as $key=>$group)
        {
            $groups[$key] = array("value"=>$key, "label"=>$group);
        }
        $stores = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $all_stores = $group->getStores();
                foreach ($all_stores as $store) {
                    $stores[$store->getId()] = array("value"=>$store->getId(), "label"=>$store->getName());
                }
            }
        }
        $block = $this->getLayout()->getBlock("mrsync");
        $block->setData("customer_groups", $groups);
        $block->setData("customer_stores", $stores);

        $model = Mage::getModel("mrsync/mrsync");
        $groups = $model->getGroups();
        $final = array();
        foreach($groups as $group)
        {
            $final[] = array("label"=>$group["label"], "value"=>$group["value"]);
        }
        $block->setData("mrsync_groups", $final);

        // check if we have error
        $error = Mage::app()->getRequest()->getParam("error");
        if ($error)
        {
            $block->setData("error", $error);
        }

        $this->renderLayout();
    }

    // do the sync
    protected function syncCustomers($customer_groups, $mrsync_groups)
    {
        // get the customers in that groups
                $customers = Mage::getModel("customer/customer")
                        ->getCollection()
                        ->addAttributeToFilter("group_id", array("in"=>$customer_groups))
            ->addAttributeToSelect("*");

                $mrsync_model = Mage::getModel("mrsync/mrsync");

        foreach($customers as $customer)
        {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);

            if ($subscriber->isSubscribed()) {
                $userinfo["email"] = $customer->getEmail();
                $userinfo["name"] = $customer->getName();

                // check if customer exists in mailrelay
                $mruser = $mrsync_model->getUser($userinfo["email"]);
                if ($mruser->email == $userinfo["email"])
                {
                    // update user
                    $this->_syncedUpdatedUsers += $mrsync_model->updateMailrelayUser( $mruser->id, $userinfo['email'], $userinfo['name'], $mrsync_groups );
                }
                else
                {
                    // add user
                    $this->_syncedNewUsers += $mrsync_model->addMailrelayUser($userinfo["email"], $userinfo["name"], $mrsync_groups);
                }
            }
        }

    }

    // do the sync by Store
    protected function syncCustomersByStore($customer_stores, $mrsync_groups)
    {
        // get the customers in that groups
                $customers = Mage::getModel("customer/customer")
                        ->getCollection()
                        ->addAttributeToFilter("entity_id", array("in"=>$customer_stores))
            ->addAttributeToSelect("*");

                $mrsync_model = Mage::getModel("mrsync/mrsync");

        foreach($customers as $customer)
        {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);

            if ($subscriber->isSubscribed()) {
                $userinfo["email"] = $customer->getEmail();
                $userinfo["name"] = $customer->getName();

                // check if customer exists in mailrelay
                $mruser = $mrsync_model->getUser($userinfo["email"]);
                if ($mruser->email == $userinfo["email"])
                {
                    // update user
                    $this->_syncedUpdatedUsers += $mrsync_model->updateMailrelayUser( $mruser->id, $userinfo['email'], $userinfo['name'], $mrsync_groups );
                }
                else
                {
                    // add user
                    $this->_syncedNewUsers += $mrsync_model->addMailrelayUser($userinfo["email"], $userinfo["name"], $mrsync_groups);
                }
            }
        }

    }

    // action to sync groups
    public function groupsyncAction()
    {
        // read post data
        $postData = Mage::app()->getRequest()->getPost();

        // initializing
        $this->_syncedUpdatedUsers = $this->_syncedNewUsers = 0;

        if (isset($postData["customer_groups"])) {
            // first count the number of customers in Magento for all the groups we selected
            $total = Mage::getModel("customer/customer")
                ->getCollection()
                ->addAttributeToFilter("group_id", array("in"=>$postData["customer_groups"]))
                ->count();
            if ($total <= 0) {
                // show error
                $url = Mage::helper("adminhtml")->getUrl("*/mrsync/index/key/", array("error"=>$this->__("No customers to sync")));
                $this->_redirectUrl($url);
                die();
            } else {
                // do the sync
                $this->syncCustomers($postData["customer_groups"], $postData["mrsync_groups"]);
            }
        }

        if (isset($postData["customer_stores"])) {
            // first count the number of customers in Magento for all the stores we selected
            $total = Mage::getModel("customer/customer")
                ->getCollection()
                ->addAttributeToFilter("entity_id", array("in"=>$postData["customer_stores"]))
                ->count();
            if ($total <= 0) {
                // show error
                $url = Mage::helper("adminhtml")->getUrl("*/mrsync/index/key/", array("error"=>$this->__("No customers to sync")));
                $this->_redirectUrl($url);
                die();
            } else {
                // do the sync
                $this->syncCustomersByStore($postData["customer_stores"], $postData["mrsync_groups"]);
            }
        }


        // show result
        $this->loadLayout();
        $this->_setActiveMenu("customer/mrsync");

        $this->_addBreadcrumb(Mage::helper('mrsync')->__("Mailrelay sync users"), Mage::helper('mrsync')->__("Mailrelay sync users"));

        $block = $this->getLayout()->getBlock("mrsync");
        $block->setData("new_users", $this->_syncedNewUsers);
        $block->setData("updated_users", $this->_syncedUpdatedUsers);
        $this->renderLayout();

    }

    public function testAction()
    {
        try
        {
            $host = Mage::getStoreConfig("mrsync/smtp/smtp_host");
            $user = Mage::getStoreConfig("mrsync/smtp/smtp_user");
            $pass = Mage::getStoreConfig("mrsync/smtp/smtp_password");

            $smtpConfiguration = array(
                'auth' => 'login',
                'username' => $user,
                'password' => $pass
            );

            if (Mage::getStoreConfig("mrsync/smtp/use_alternative_port")) {
                $smtpConfiguration['port'] = 2525;
            }
            
            $transport = new Zend_Mail_Transport_Smtp(strtolower($host), $smtpConfiguration);

            // email config
            $default_email = Mage::getStoreConfig('trans_email/ident_general/email');
            $mail = Mage::getModel('core/email');
            $mail->setToEmail($default_email);
            $mail->setToName('Mailrelay user');
            $mail->setBody("Mailrelay SMTP Testing");
            $mail->setSubject("Mailrelay SMTP testing");
            $mail->setFromEmail("info@mailrelay.com");
            $mail->setFromName("Mailrelay");
            $mail->setType("html");

            Zend_Mail::setDefaultTransport($transport);

            $mail->send($transport);

            // redirect to config
            $this->_getSession()->addSuccess(Mage::helper("mrsync")->__("A test email has been sent to your default Magento email address."));
        } catch (Exception $e) {
            $this->_mail = null;
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
        }

        $this->_redirect("*/system_config/edit/section/mrsync");
    }
}
