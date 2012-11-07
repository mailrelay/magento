<?php
class Ysoft_Mrsync_Adminhtml_MrsyncController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->loadLayout();
		$this->_setActiveMenu("customer/mrsync");

		$this->_addBreadcrumb(Mage::helper('mrsync')->__("Mailrelay sync users"), Mage::helper('mrsync')->__("Mailrelay sync users"));

		// check if we have API key
		$model = Mage::getModel("mrsync/mrsync");
		$api = $model->getApiKey();

		if ($api)
		{
			$block = $this->getLayout()->getBlock("mrsync");
			$block->setData("sync_host", Mage::getStoreConfig("mrsync/mrsync/sync_host"));
			$block->setData("sync_user", Mage::getStoreConfig("mrsync/mrsync/sync_user"));
			$block->setData("sync_pass", Mage::getStoreConfig("mrsync/mrsync/sync_pass"));
			$this->renderLayout();
		}
		else
		{
			$this->_getSession()->addError(Mage::helper("mrsync")->__("There has been an error with your Mailrelay sync settings. Please check that they are correct and that you have generated a valid API key"));
			$this->_redirect("*/system_config/edit/section/mrsync");
		}
	}

	public function startsyncAction()
	{
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
		$block = $this->getLayout()->getBlock("startsync");
		$block->setData("customer_groups", $groups);

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
		$this->_syncedUpdatedUsers = $this->_syncedNewUsers = 0;

		// get the customers in that groups
                $customers = Mage::getModel("customer/customer")
                        ->getCollection()
                        ->addAttributeToFilter("group_id", array("in"=>$customer_groups))
			->addAttributeToSelect("*");

                $mrsync_model = Mage::getModel("mrsync/mrsync");

		foreach($customers as $customer)
		{
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

	// action to sync groups
	public function groupsyncAction()
	{
		// read post data
		$postData = Mage::app()->getRequest()->getPost();

		// first count the number of customers in Magento for all the groups we selected
		$total = Mage::getModel("customer/customer")
			->getCollection()
			->addAttributeToFilter("group_id", array("in"=>$postData["customer_groups"]))
			->count();
		if ($total<=0)
		{
			// show error
			$url = Mage::helper("adminhtml")->getUrl("adminhtml/mrsync/startsync/", array("error"=>$this->__("No customers to sync")));
			$this->_redirectUrl($url);
		}
		else
		{
			// do the sync
			$this->syncCustomers($postData["customer_groups"], $postData["mrsync_groups"]);

			// show result
	                $this->loadLayout();
        	        $this->_setActiveMenu("customer/mrsync");

                	$this->_addBreadcrumb(Mage::helper('mrsync')->__("Mailrelay sync users"), Mage::helper('mrsync')->__("Mailrelay sync users"));

	                $block = $this->getLayout()->getBlock("mrsync");
        	        $block->setData("new_users", $this->_syncedNewUsers);
                	$block->setData("updated_users", $this->_syncedUpdatedUsers);
                	$this->renderLayout();

		}
	}
}
