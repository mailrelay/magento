<?php

/**
 * Events Observer model
 *
 */
class Ysoft_Mrsync_Model_Observer
{
        /**
         * Handle save of System -> Configuration, section <monkey>
         *
         * @param Varien_Event_Observer $observer
         * @return void|Varien_Event_Observer
         */
        public function saveConfig(Varien_Event_Observer $observer)
        {
                $post   = Mage::app()->getRequest()->getPost();
                $request = Mage::app()->getRequest();

		// validate user and password
		$user = $post["groups"]["mrsync"]["fields"]["sync_user"]["value"];
		$password = $post["groups"]["mrsync"]["fields"]["sync_pass"]["value"];
		$host = $post["groups"]["mrsync"]["fields"]["sync_host"]["value"];
		$groups = $post["groups"]["mrsync"]["fields"]["sync_groups"]["value"];

                //Check if the api key exist
		$model = Mage::getModel("mrsync/mrsync");
		$model->initCurl($host);
		$api = $model->getApiKey($user, $password);

		if (!$api)
		{
			// error about API
                        $message = Mage::helper('mrsync')->__('There is a problem with your Mailrelay sync data. Please check that the Mailrelay settings are correct, and that you have generated a valid API key');
                        Mage::getSingleton('adminhtml/session')->addError($message);
			return $observer;
                }
		else
		{
			// check groups
			if (!is_array($groups) || count($groups)<=0)
			{
	                        $message = Mage::helper('mrsync')->__('You must choose at least one group to sync');
        	                Mage::getSingleton('adminhtml/session')->addError($message);
				return $observer;

			}
		}
	}

}	
