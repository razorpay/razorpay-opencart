<?php

// Heading
$_['heading_title'] = 'Razorpay';

// Text 
$_['text_payment'] = 'Payment';
$_['text_extension'] = 'Extensions';
$_['text_edit'] = 'Edit Razorpay';
$_['text_success'] = 'Success: You have modified Razorpay account details!';
$_['text_razorpay'] = '<a href="https://www.razorpay.com" target="_blank"><img src="view/image/payment/razorpay.png" alt="Razorpay" title="Razorpay" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_authorize'] = 'Authorize Only';
$_['text_capture'] = 'Authorize and Capture';

// Entry
$_['entry_key_id'] = 'Razorpay Key Id';
$_['entry_key_secret'] = 'Razorpay Key Secret';
$_['entry_order_status'] = 'Order Status';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_webhook_secret'] = 'Razorpay Webhook Secret';
$_['entry_webhook_status'] = 'Webhook Status';
$_['entry_webhook_url'] = 'Webhook URL';
$_['entry_payment_action'] = 'Payment Action';
$_['entry_max_capture_delay'] = 'Max Delay in Payment Capture';
$_['entry_max_capture_delay1'] = 'Max Delay in Payment Capture in minutes';

//tooltips
$_['help_key_id'] = 'The Api Key Id and Key Secret you will recieve from the API keys section of Razorpay Dashboard. Use test Key for testing purposes.';
$_['help_order_status'] = 'The status of the order to be marked on completion of payment.';
$_['help_webhook_url'] = 'Set Razorpay \'order.paid\' webhooks to call this URL with the below secret.';
$_['help_max_delay'] = 'It will gets used by \'payment.authorized\' webhooks to capture the payment after this much time, in case of Authorize Only Pament Action.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify payment Razorpay!';
$_['error_key_id'] = 'Key Id Required!';
$_['error_key_secret'] = 'Key Secret Required!';
$_['error_webhook_secret'] = 'Webhook Secret Required!';

//Subscription
$_['enable_subscription_flag'] = 'Enable Subscription in Razorpay dashboard!';

// Heading
//$_['heading_title']                                     = 'Razorpay Subscription';
$_['heading_title_transaction']                         = 'View Transaction #%s';

// Plan Column
$_['column_plan_id']                                     = 'Plan ID';
$_['column_plan_name']                                   = 'Plan Name';
$_['column_plan_desc']                                   = 'Plan Description';
$_['column_product_name']                                = 'Product Name';
$_['column_plan_type']                                   = 'Plan Type';
$_['column_billing_frequence']                           = 'Billing Frequency';
$_['column_billing_cycle']                               = 'Billing Cycle';
$_['column_trial']                                       = 'Trial';
$_['column_billing_amount']                              = 'Billing Amount';
$_['column_plan_addons']                                 = 'Addons';
$_['column_status']                                      = 'Status';
$_['column_created_at']                                  = 'Created At';
$_['column_action']                                      = 'Action';
$_['column_id']                                          = 'ID';


// Subscription Column
$_['column_subscription_id']                             = 'Subscription ID';
$_['column_customer']                                    = 'Customer';
$_['column_subscription_status']                         = 'Status';
$_['column_total_count']                                 = 'Total Count';
$_['column_paid_count']                                  = 'Paid Count';
$_['column_remaining_count']                             = 'Remaining Count';
$_['column_subscription_created_at']                     = 'Created At';
$_['column_next_charge_at']                              = 'Next Charge At';
$_['column_start_at']                                    = 'Start At';
$_['column_end_at']                                      = 'Ends At';
$_['column_invoice_id']                                  = 'Invoice Id';
$_['column_recurring_amt']                               = 'Recurring Amount';
$_['column_date']                                        = 'Date';
$_['column_total_amount']                                 = 'Total Amount';

//Breadcrumbs
$_['text_extension']                                    = 'Extensions';
$_['plan_title']                                        = 'Razorpay Plans';
$_['subscription_title']                                = 'Razorpay Subscriptions';
$_['text_daily']                                        = 'Daily';
$_['text_weekly']                                       = 'Weekly';
$_['text_monthly']                                      = 'Monthly';
$_['text_yearly']                                       = 'Yearly';

//text
$_['text_add']                                          = 'Add Plan';
$_['text_enabled']                                      = 'Enable';
$_['text_disabled']                                     = 'Disable';
$_['text_update_plan_success']                          = 'Success: You have modified Plan!';
$_['text_list']                                         = 'Plan List';
$_['text_subscription_list']                            = 'Subscription List';
$_['text_add']                                          = 'Add Plan';
$_['text_edit']                                         = 'Edit Plan';
$_['text_plan_success']                                 = 'Plan add successfully';
$_['text_enable_success']                               = 'Plan/s enabled successfully';
$_['text_disable_success']                               = 'Plan/s disabled successfully';
$_['text_select_warning']                               = 'Select plan for enable and disable';
$_['text_subscription']                                 = 'Subscription View';
$_['text_resume_success']                               = 'Subscription resume successfully';
$_['text_pause_success']                                = 'Subscription pause successfully';
$_['text_cancel_success']                               = 'Subscription cancelled successfully';
$_['text_cancel_warning']                               = 'Once cancelled, the subscription cannot be renewed or reactivated.';
$_['text_select']                                       = ' --- Please Select --- ';
$_['text_active']                                       = 'Active';
$_['text_cancelled']                                    = 'Cancel';
$_['text_pause']                                        = 'Pause';
$_['text_resume']                                       = 'Resume';
$_['text_invoice']                                      = 'Invoice Details';
$_['text_not_select_sub_ID']                             = 'Please Select Subscription ';
$_['text_subscription_status']                          = 'Enable Subscription in Settings.';
$_['text_webhook_cron_header']                          = 'Set the cron job in your OpenCart site server to call the Cron URL in every 5 mins frequncy.';
$_['text_webhook_cron']                                 = '<ol><li>In CLI run <strong>crontab -e</strong></li><li>Add the command: <strong>%s</strong></li><li>Save.</li><li>Run: <strong>crontab -l</strong> confirm below if cron is added.</li></ol>';
$_['text_webhook_cron_confirm']                         = 'Confirm Cron created.';


//tooltip
$_['help_product_name']                                      = 'Autocomplete';
$_['help_plan_type']                                         = 'Used together with interval to define how often the customer should be charged';
$_['help_billing_frequency']                                 = 'Used together with plan type to define how often the customer should be charged. For daily plans, the minimum interval is 7.'; 

//entry - Filters
$_['entry_plan_id']                                     = 'Plan ID';
$_['entry_plan_name']                                   = 'Plan Name';
$_['entry_plan_desc']                                   = 'Plan Description';
$_['entry_product_name']                                = 'Product Name';
$_['entry_plan_type']                                   = 'Plan Type';
$_['entry_billing_frequency']                           = 'Billing Frequency';
$_['entry_billing_cycle']                               = 'Billing Cycle';
$_['entry_trial']                                       = 'Trial';
$_['entry_billing_amount']                              = 'Billing Amount';
$_['entry_plan_addons']                                 = 'Addons';
$_['entry_plan_status']                                 = 'Plan Status';
$_['entry_subscription_id']                             = 'Subscription ID';
$_['entry_customer_name']                               = 'Customer Name';

//integration 
$_['entry_subscription_status'] = 'Subscription Status';

// Button
$_['button_save']                                       = 'Save';
$_['button_enable']                                     = 'Enable';
$_['button_disable']                                    = 'Disable';
$_['button_resume']                                     = 'Resume';
$_['button_pause']                                      = 'Pause';
$_['button_cancel']                                     = 'Cancel';
$_['button_back']                                       = 'Back';


// Error
$_['error_plan_name']                                   = 'Enter Plan Name!';
$_['error_plan_desc']                                   = 'Enter Plan Description';
$_['error_product_name']                                = 'Please Select product name using autocomplete!';
$_['error_plan_type']                                   = 'Please Select Plan Type';
$_['error_billing_frequency']                           = 'Enter Billing Frequency and it should be an number.';
$_['error_billing_cycle']                               = 'Enter Billing Cycle and it should be an number.';
$_['error_billing_amount']                              = 'The amount must be at least ₹1';
$_['entry_plan_status']                                 = 'Select Status';
$_['error_permission']                                  = 'Warning: You do not have permission to modify Plan!';
$_['error_plan_exists']                                 = 'Warning: Plan already exists!';
$_['error_billing_frequency_daily']                           = 'For daily plans, the minimum interval is 7.';
// Statuses
$_['razorpay_subscription_status_comment_authorized']                = 'The card transaction has been authorized but not yet captured.';
$_['razorpay_subscription_status_comment_captured']                  = 'The card transaction was authorized and subsequently captured (i.e., completed).';
$_['razorpay_subscription_status_comment_voided']                    = 'The card transaction was authorized and subsequently voided (i.e., canceled).   ';
$_['razorpay_subscription_status_comment_failed']                    = 'The card transaction failed.';

// Entry
$_['entry_plan_id']                                    = 'Plan ID';
$_['entry_plan_name']                                  = 'Plan Name';
$_['entry_plan_status']                                = 'Status';
$_['entry_date_created']                                = 'Date Created';


$_['entry_total']                                       = 'Total';
$_['entry_geo_zone']                                    = 'Geo Zone';
$_['entry_sort_order']                                  = 'Sort Order';
$_['entry_merchant']                                    = 'Merchant ID';
$_['entry_transaction_id']                              = 'Transaction ID';
$_['entry_order_id']                                    = 'Order ID';
$_['entry_partner_solution_id']                         = 'Partner Solution ID';
$_['entry_type']                                        = 'Transaction Type';
$_['entry_currency']                                    = 'Currency';
$_['entry_amount']                                      = 'Amount';
$_['entry_browser']                                     = 'Customer User Agent';
$_['entry_ip']                                          = 'Customer IP';

$_['entry_billing_address_company']                     = 'Billing Company';
$_['entry_billing_address_street']                      = 'Billing Street';
$_['entry_billing_address_city']                        = 'Billing City';
$_['entry_billing_address_postcode']                    = 'Billing ZIP';
$_['entry_billing_address_province']                    = 'Billing Province/State';
$_['entry_billing_address_country']                     = 'Billing Country';
$_['entry_status_authorized']                           = 'Authorized';
$_['entry_status_captured']                             = 'Captured';
$_['entry_status_voided']                               = 'Voided';
$_['entry_status_failed']                               = 'Failed';
$_['entry_setup_confirmation']                          = 'Setup confirmation:';

// Error
$_['error_permission']                                  = '<strong>Warning:</strong> You do not have permission to modify payment Razorpay Subscription!';
$_['error_permission_recurring']                        = '<strong>Warning:</strong> You do not have permission to modify recurring payments!';
$_['error_transaction_missing']                         = 'Transaction not found!';
$_['error_no_ssl']                                      = '<strong>Warning:</strong> SSL is not enabled on your admin panel. Please enable it to finish your configuration.';
$_['error_user_rejected_connect_attempt']               = 'Connection attempt was canceled by the user.';
$_['error_possible_xss']                                = 'We detected a possible cross site attack and have terminated your connection attempt. Please verify your application ID and secret and try again using the buttons in the admin panel.';
$_['error_invalid_email']                               = 'The provided e-mail address is not valid!';
$_['error_cron_acknowledge']                            = 'Please confirm you have set up a CRON job.';
$_['error_client_id']                                   = 'The app client ID is a required field';
$_['error_client_secret']                               = 'The app client secret is a required field';
$_['error_sandbox_client_id']                           = 'The sandbox client ID is a required field when sandbox mode is enabled';
$_['error_sandbox_token']                               = 'The sandbox token is a required field when sandbox mode is enabled';
$_['error_no_location_selected']                        = 'The location is a required field';
$_['error_refresh_access_token']                        = "An error occurred when trying to refresh the extension's connection to your Razorpay Subscription account. Please verify your application credentials and try again.";
$_['error_form']                                        = 'Please check the form for errors and try to save agian.';
$_['error_token']                                       = 'An error was encountered while refreshing the token: %s';
$_['error_no_refund']                                   = 'Refund failed.';

// Button
$_['button_void']                                       = 'Void';
$_['button_refund']                                     = 'Refund';
$_['button_capture']                                    = 'Capture';
$_['button_connect']                                    = 'Connect';
$_['button_reconnect']                                  = 'Reconnect';
$_['button_refresh']                                    = 'Refresh token';
