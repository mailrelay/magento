<?php 
class Mailrelay_Mrsync_Block_Test extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('adminhtml/mrsync/test');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Check SMTP settings')
		    ->setOnClick("setLocation('$url')")
                    ->toHtml();

        return $html;
    }
}
?>
