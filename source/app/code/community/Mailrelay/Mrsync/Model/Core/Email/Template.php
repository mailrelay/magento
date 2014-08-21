<?php
/**
 *
 * DISCLAIMER
 *
 * Use at your own risk
 *
 */
class Mailrelay_Mrsync_Model_Core_Email_Template extends Mage_Core_Model_Email_Template {

    /**
     * Send mail to recipient
     *
     * @param   string      $email        E-mail
     * @param   string|null $name         receiver name
     * @param   array       $variables    template variables
     * @return  boolean
     **/

    public function send($email, $name = null, array $variables = array()) {
        if (!Mage::getStoreConfig("mrsync/smtp/smtp_enabled")) {
            return parent::send($email, $name, $variables);
        }

        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array)$email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        $mail = $this->getMail();

        foreach ($emails as $key => $email) {
            $mail->addTo($email, '=?utf-8?B?' . base64_encode($names[$key]) . '?=');
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject('=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=');
        $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

        try {
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

            $mail->send($transport);
            $this->_mail = null;
        } catch (Exception $e) {
            $this->_mail = null;
            Mage::logException($e);
            return false;
        }

        return true;
    }
}
