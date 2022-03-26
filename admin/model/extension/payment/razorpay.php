<?php

class ModelExtensionPaymentRazorpay extends Model
{
    public function addWebhookColumn()
    {
        $result = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'razorpay_webhook_count'"); 
        if($result->num_rows == 0){
              
              $sql = "ALTER TABLE `".DB_PREFIX."order` ADD `razorpay_webhook_count` INT( 11 ) NOT NULL DEFAULT 0";
              $this->db->query($sql);  
        }
    }

    public function removeWebhookColumn(){

        $result = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "order LIKE 'razorpay_webhook_count'"); 
        if($result->num_rows > 0){
        $sql = "ALTER TABLE `".DB_PREFIX."order` DROP COLUMN `razorpay_webhook_count`";
        $this->db->query($sql);
        }
    }
}