<?php

/**
 * Events Observer model
 *
 */
class Mailrelay_Mrsync_Model_Observer
{
        /**
         * Handle Subscriber object saving process
         *
         * @param Varien_Event_Observer $observer
         * @return void|Varien_Event_Observer
         */
        public function handleSubscriber(Varien_Event_Observer $observer)
        {
        // check if we have the autosync
        $autosync = Mage::getStoreConfig("mrsync/mrsync/autosync_users");
        if ($autosync)
        {
            // read subscriber data
            $subscriber = $observer->getEvent()->getSubscriber();

            if ($subscriber)
            {
                $subscriber->setImportMode(TRUE);

                // read groups
                $groups = Mage::getStoreConfig("mrsync/mrsync/sync_groups");
                $mrsync_groups = array();
                $set = explode(",", $groups);
                foreach($set as $item)
                {
                    $mrsync_groups[$item] = $item;
                }

                $name = $subscriber->getName();
                $email = $subscriber->getEmail();

                // sync only that user
                $model = Mage::getModel("mrsync/mrsync");

                // check if customer exists in mailrelay
                            //$mruser = $model->getUser($userinfo["email"]);
                            $mruser = $model->getUser($email);
                            if ($mruser->email == $email)
                            {
                    // check status
                    if ($subscriber->getStatus()==Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
                    {
                        // remove user
                        $model->removeMailrelayUser($mruser->email);
                    }
                    else
                    {
                                        // update user
                                        $model->updateMailrelayUser( $mruser->id, $email, $name, $mrsync_groups );
                    }
                            }
                            else
                            {
                    if ($subscriber->getStatus()!=Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
                    {
                                        // add user
                                        $model->addMailrelayUser($email, $name, $mrsync_groups);
                    }
                            }

            }
        }

    }

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
        $host = $post["groups"]["mrsync"]["fields"]["sync_host"]["value"];
        $apiKey = $post["groups"]["mrsync"]["fields"]["sync_api_key"]["value"];
        $groups = $post["groups"]["mrsync"]["fields"]["sync_groups"]["value"];

        if ($host != '') {
            $validate = new Zend_Validate_Hostname();
            if (!$validate->isValid($host)) {
                $message = Mage::helper('mrsync')->__('Invalid host.');
                throw new Exception($message);
            }
        }

        if (!is_array($groups) || count($groups)<=0)
        {
            $message = Mage::helper('mrsync')->__('You must choose at least one group to sync');
            Mage::getSingleton('adminhtml/session')->addError($message);
            return $observer;

        }
    }

        /**
         * Update customer after_save event observer
         *
         * @param Varien_Event_Observer $observer
         * @return void|Varien_Event_Observer
         */
    public function updateCustomer(Varien_Event_Observer $observer)
    {
        // check if we have the autosync
        $autosync = Mage::getStoreConfig("mrsync/mrsync/autosync_users");
        if ($autosync)
        {
            // read customer data and update it
            $customer = $observer->getEvent()->getCustomer();

            if ($customer)
            {
                // read groups
                $groups = Mage::getStoreConfig("mrsync/mrsync/sync_groups");
                $mrsync_groups = array();
                $set = explode(",", $groups);
                foreach($set as $item)
                {
                    $mrsync_groups[$item] = $item;
                }

                $name = $customer->getName();
                $email = $customer->getEmail();

                // sync only that user
                $model = Mage::getModel("mrsync/mrsync");

                // check if customer exists in mailrelay
                            //$mruser = $model->getUser($userinfo["email"]);
                            $mruser = $model->getUser($email);
                            if ($mruser->email == $email)
                            {
                                    // update user
                                    $model->updateMailrelayUser( $mruser->id, $email, $name, $mrsync_groups );
                            }

            }
        }
    }

        /**
         * Handle Subscriber deletion from Magento, unsubcribes email from MailChimp
         * and sends the delete_member flag so the subscriber gets deleted.
         *
         * @param Varien_Event_Observer $observer
         * @return void|Varien_Event_Observer
         */
        public function handleSubscriberDeletion(Varien_Event_Observer $observer)
        {
die("here");
                $subscriber->setImportMode(TRUE);

        // check if we have the autosync
        $autosync = Mage::getStoreConfig("mrsync/mrsync/autosync_users");
        if ($autosync)
        {
                    $subscriber = $observer->getEvent()->getSubscriber();

            if ($subscriber)
            {
                // read groups
                $groups = Mage::getStoreConfig("mrsync/mrsync/sync_groups");
                $mrsync_groups = array();
                $set = explode(",", $groups);
                foreach($set as $item)
                {
                    $mrsync_groups[$item] = $item;
                }

                $email = $customer->getEmail();

                // sync only that user
                $model = Mage::getModel("mrsync/mrsync");

                // check if customer exists in mailrelay
                            //$mruser = $model->getUser($userinfo["email"]);
                            $mruser = $model->getUser($email);
                            if ($mruser->email == $email)
                            {
                                    // delete user
                                    $model->removeMailrelayUser( $mruser->email );
                            }

            }
        }

    }
}
