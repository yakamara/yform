<?php

/**
 * XForm
 * @author p.schulze@bitshifters.de
 * @author <a href="http://www.bitshifters.de">www.bitshifters.de</a>
 */

class rex_yform_validate_recaptcha extends rex_yform_validate_abstract
{

	function enterObject()
	{
		if ($this->params['send'] == '1') {
			$label = $this->getElement('name');
			$privateKey = $this->getElement('private_key');

			$Object = $this->getValueObject();
			
			if ($privateKey == "") {
				$this->params['warning'][$Object->getId()] = $this->params['error_class'];
				$this->params['warning_messages'][$Object->getId()] = 'ERROR: private key for element '.$Object->getId().' not provided!';
			} else {
				try {
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, [
						'secret' => $privateKey,
						'response' => $_POST['g-recaptcha-response'],
						'remoteip' => $_SERVER['REMOTE_ADDR']
					]);

					$res = json_decode(curl_exec($ch));
					$res = $res->success;
					
					if(!$res) {
						$this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
					}
				}
				catch (Exception $e) {
					$this->params['warning'][$Object->getId()] = $this->params['error_class'];
					$this->params['warning_messages'][$Object->getId()] = 'ERROR: security check failed (2)!';
				}
			}
		}
	}

	function getDescription()
	{
		return 'validate|recaptcha|label|private_key|warning_message';
	}
	
	function getDefinitions()
	{
		return array(
			'type' => 'validate',
			'name' => 'recaptcha',
			'values' => array(
				'name' => array( 'type' => 'select_name', 'label' => 'Name' ),
				'private_key'=> array('type' => 'text',	'label' => 'Private Key'),
				'message' => array( 'type' => 'text', 'label' => 'Fehlermeldung'),
			),
			'description' => 'Prüft, ob ein gesetztes reCaptcha korrekt ausgefüllt wurde.',
			'famous' => false
		);

	}
}