<?php
class Ysoft_Mrsync_Block_Adminhtml_Form_Index extends Mage_Adminhtml_Block_Widget_Form_Container
{
	/**
	* Constructor
	*/
	public function __construct()
	{
		parent::__construct();
		$this->_blockGroup = 'mrsync_adminform';
		$this->_controller = 'adminhtml_form';
		$this->_headerText = Mage::helper('mrsync_adminform')->__('Sync Form');
	}
}
