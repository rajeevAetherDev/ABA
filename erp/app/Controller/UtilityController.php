<?php

/*
 * @copyright     Copyright (c) Aether Software Solution Pvt. Ltd.
 * @link          http://aetherss.com
 * @version       Trufood 1.0.0.0
 */


App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');
/**
 * CakePHP ApisController
 * @author aether2
 */
class UtilityController extends AppController {
   
    public $components = array('Paginator');
    public function beforeFilter() {
            parent::beforeFilter();
            // Allow users to register and logout.
            $this->Auth->allow();
            $this->layout = false;
            $this->autoRender=false;
	
            $this->response->disableCache();
           
        }
        
    
       public function message($message=null,$phone=null) {
			
					try{
					$url = "http://www.smsalert.co.in/api/push.json?apikey=5c6e9c39ef7eb&route=transactional&sender=phylos&mobileno=$phone&text=".urlencode($message);
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$output = curl_exec($ch);
					curl_close($ch);
					$resultsms =  json_decode($output,true);
					} catch (Exception $ex) { }
					
			
			return $resultsms;
	   }

        public function createpinlinkmsg($message=null,$phone=null) {
			
					try{
					$url = "http://www.smsalert.co.in/api/push.json?apikey=5c6e9c39ef7eb&route=transactional&sender=phylos&mobileno=$phone&text=".urlencode($message);
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$output = curl_exec($ch);
					curl_close($ch);
					$resultsms =  json_decode($output,true);
					} catch (Exception $ex) { }
					
			
			return $resultsms;
	   }



  
}