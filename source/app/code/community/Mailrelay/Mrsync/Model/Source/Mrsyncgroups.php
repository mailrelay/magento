<?php
class Mailrelay_Mrsync_Model_Source_Mrsyncgroups
{
    // options getter
    public function toOptionArray()
    {
        $model = Mage::getModel("mrsync/mrsync");
        $groups = $model->getGroups();

        if (!$groups && $model->_apiKey == '')
        {
            $groups = array(array("value"=>"", "label"=>Mage::helper("mrsync")->__("-- Enter your Mailrelay sync data first --")));
        } else {
            if (!$groups) {
                $groups = array(array("value"=>"", "label"=>Mage::helper("mrsync")->__("-- Invalid API key --")));
            }
        }
        return $groups;
    }
}
