<?php
class Mailrelay_Mrsync_Model_Mrsync extends Mage_Core_Model_Abstract
{
	protected $_apiKey;
	protected $_curl;

	/**
	* Checks if a valid curl conection has been stablished
	* 
	* @param curl $curl 
	*/
	public function checkCurlInit( curl $curl )
	{
        	if ( $curl == null ) 
		{
			return false;
		}
		return true;
    	}

	public function getExtensionVersion()
	{
		return (string) Mage::getConfig()->getNode()->modules->Mailrelay_Mrsync->version;
	}

	public function initCurl($host = "")
	{
		if (!$host)
		{
			$host = Mage::getStoreConfig("mrsync/mrsync/sync_host");
		}

		if ($host)
		{
			if (substr($host, 0, 7)!="http://") $url = "http://";
			else $url = "";
			$url = $url.$host."/ccm/admin/api/version/2/&type=json";
			$curl = curl_init($url);

		        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        		curl_setopt($curl, CURLOPT_POST, 1);

			$headers = array(
				'X-Request-Origin: Magento|'.$this->getExtensionVersion().'|'.Mage::getVersion()
			);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		        $result = $this->checkCurlInit( $curl );
			if ($result)
			{
			        $this->_curl = $curl;
			}
			else
			{
				$this->_curl = "";
			}
		}
	}

	protected function _construct($host = "")
	{
		$this->_init("mrsync/mrsync");
		$this->_apiKey="";
		$this->initCurl($host);
	}

	public function getApiKey($username="", $password="")
	{
		// get config values
		if (!$username)
		{
			$username = Mage::getStoreConfig("mrsync/mrsync/sync_user");
		}
		if (!$password)
		{
			$password = Mage::getStoreConfig("mrsync/mrsync/sync_pass");
		}

	        $params = array(
            		'function' => 'doAuthentication',
            			'username' => $username,
            			'password' => $password
        	); 

		if ($this->_curl)
		{
	        	curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $params);
			curl_setopt($this->_curl, CURLOPT_VERBOSE, TRUE);
                        $headers = array(
                                'X-Request-Origin: Magento|'.$this->getExtensionVersion().'|'.Mage::getVersion()
                        );
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		        $result = curl_exec($this->_curl);
		        $jsonResult = json_decode($result);

		        if (!$jsonResult->status) {
				$this->_apiKey = "";
        		} else {
            			$this->_apiKey = $jsonResult->data;
	        	}
		}
		return $this->_apiKey;
	}

	/**
	* Executes an API call against the API
	* 
	* @param array $params Array with the API methods to execute
	* @return object 
	*/
	public function APICall( $params = array(), $apiKey = NULL )
	{
        	if ( $apiKey == NULL ) {
            		$params['apiKey'] = $this->_apiKey;
        	} else {
            		$params['apiKey'] = $apiKey;
        	}
        	curl_setopt( $this->_curl, CURLOPT_POSTFIELDS, $params );
                $headers = array(
			'X-Request-Origin: Magento|'.$this->getExtensionVersion().'|'.Mage::getVersion()
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec( $this->_curl );
        	$jsonResult = json_decode($result);
        
        	if ($jsonResult->status) {
            		return $jsonResult->data;
        	} else {
            		return NULL;
        	}
    	}

	/**
	* Prepare a json of groups obtained from the API and turn it into an array
	* 
     	* @param json $rawGroups A json of groups obtained from the API
     	* @return array 
     	*/
    	public function apiGroupsToArray( $rawGroups ) 
    	{
        	$groupSelect = array();

        	foreach ( $rawGroups AS $group ) {
            		if ( $group->enable == 1 AND $group->visible == 1) {
                		$groupSelect[$group->id] = $group->name;
        	    	}
        	}
	        return $groupSelect;
	}

	/**
     	* Get MR groups
     	* 
     	* @return object 
     	*/
	public function getGroups()
	{
		if (!$this->_apiKey)
		{
			$this->_apiKey=$this->getApiKey();
		}

		if ($this->_apiKey)
		{
			$params = array(
	            		'function' => 'getGroups',
			            'apiKey' => $this->_apiKey
	        	);
        
			$data = $this->APICall($params);

			if ( ($data == NULL) || (!(count( $this->apiGroupsToArray( $data ) ) > 0)) ) 
			{
				return array();
			}
        		else {
				$groups = $this->apiGroupsToArray( $data );
				$totales = array();
				foreach($groups as $key=>$value)
				{
					$item["value"]=$key;
					$item["label"]=$value;
					$totales[] = $item;
				}
				return $totales;
        		}
		}
		else
		{
			// invalid API key
			return false;
		}
	}

	// check one user
	public function getUser($email)
	{
		if (!$this->_apiKey)
		{
			$this->_apiKey=$this->getApiKey();
		}
		$params = array(
            		'function' => 'getSubscribers',
				'email'=>$email,
				'apiKey'=>$this->_apiKey
        	);
                $data = $this->APICall($params);
		if ($data==NULL) return new StdClass;
		else return $data[0];

	}

    /**
     * Update an already existing Mailrelay user
     * 
     * @param integer $id User id in the Mailrelay system
     * @param string $email User email from the vBulletin database
     * @param string $username Username from the vBulletin database
     * @param array $groups Selected groups to sync the user to
     * return integer
     */
	public function updateMailrelayUser($user_id, $user_email, $user_name, array $user_groups=array())
	{
		if (!$this->_apiKey)
		{
			$this->_apiKey=$this->getApiKey();
		}

		$params = array(
                	'function' => 'updateSubscriber',
                	'apiKey' => $this->_apiKey,
                	'id' => $user_id,
                	'email' => $user_email,
                	'name' => $user_name,
	                'groups' => $user_groups
            	);

            	$post = http_build_query($params);
            	curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);
                $headers = array(
	                'X-Request-Origin: Magento|'.$this->getExtensionVersion().'|'.Mage::getVersion()
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            	$result = curl_exec($this->_curl);
            	$jsonResult = json_decode($result);

            	if ( $jsonResult->status ) {
			return 1;
		}
		else {
			return 0;
		}
	}

    /**
     * Remove an already existing Mailrelay user
     * 
     * @param integer $id User id in the Mailrelay system
     * return integer
     */
	public function removeMailrelayUser($email)
	{
		if (!$this->_apiKey)
		{
			$this->_apiKey=$this->getApiKey();
		}

		$params = array(
                	'function' => 'deleteSubscriber',
                	'apiKey' => $this->_apiKey,
                	'email' => $email
            	);

            	$post = http_build_query($params);
            	curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);
                $headers = array(
 	               'X-Request-Origin: Magento|'.$this->getExtensionVersion().'|'.Mage::getVersion()
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            	$result = curl_exec($this->_curl);
            	$jsonResult = json_decode($result);

            	if ( $jsonResult->status ) {
			return 1;
		}
		else {
			return 0;
		}
	}

    /**
     * Add a new Mailrelay user
     * 
     * @param string $email User email from the vBulletin database
     * @param string $username Username from the vBulletin database
     * @param array $groups Selected groups to sync the user to
     * return integer
     */
    public function addMailrelayUser( $email = '', $username = '', array $groups = array())
    {
	if (!$this->_apiKey)
	{
		$this->_apiKey=$this->getApiKey();
	}

	$params = array(
                'function' => 'addSubscriber',
                'apiKey' => $this->_apiKey,
                'email' => $email,
                'name' => $username,
                'groups' => $groups
            );

            $post = http_build_query($params);
            curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $post);
            $headers = array(
	            'X-Request-Origin: Magento|'.$this->getExtensionVersion().'|'.Mage::getVersion()
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($this->_curl);
            $jsonResult = json_decode($result);

            if ( $jsonResult->status ) {
                return 1;
        } else {
            return 0;
        }
    }

}
?>
