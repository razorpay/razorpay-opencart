<?php
// Heading
$_['heading_title']                                     = 'Razorpay Subscription';
$_['heading_title_transaction']                         = 'View Transaction #%s';

// Column
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

//Breadcrumbs
$_['text_extension']                                    = 'Extensions';
$_['plan_title']                                        = 'Razorpay Plans';
$_['text_daily']                                        = 'Daily';
$_['text_weekly']                                       = 'Weekly';
$_['text_monthly']                                      = 'Monthly';
$_['text_yearly']                                       = 'Yearly';

//text
$_['text_add']                                          = 'Add Plan';
$_['text_enabled']                                      = 'Enable';
$_['text_disabled']                                     = 'Disable';
$_['text_success']                                      = 'Success: You have modified Plan!';
$_['text_list']                                         = 'Plan List';
$_['text_add']                                          = 'Add Plan';
$_['text_edit']                                         = 'Edit Plan';
$_['text_plan_success']                                 = 'Plan add successfully';
$_['text_enable_success']                               = 'Plan enabled successfully';
$_['text_disable_success']                               = 'Plan disabled successfully';
$_['text_select_warning']                               = 'Select plan for enable and disable';



//tooltip
$_['help_product_name']                                      = 'Autocomplete';
$_['help_plan_type']                                         = 'Used together with interval to define how often the customer should be charged';
$_['help_billing_frequency']                                 = 'Used together with plan type to define how often the customer should be charged. For daily plans, the minimum interval is 7.'; 

//entry
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

// Button
$_['button_save']                                       = 'Save';
$_['button_enable']                                     = 'Enable';
$_['button_disable']                                    = 'Disable';
// Error
$_['error_plan_name']                                   = 'Enter Plan Name!';
$_['error_plan_desc']                                   = 'Enter Plan Description';
$_['error_product_name']                                = 'Please Select Product Name';
$_['error_plan_type']                                   = 'Please Select Plan Type';
$_['error_billing_frequency']                           = 'Enter Billing Frequency and it should be an integer.';
$_['error_billing_cycle']                               = 'Enter Billing Cycle and it should be an integer.';
$_['error_billing_amount']                              = 'Enter Billing Amount';
$_['entry_plan_status']                                 = 'Select Status';
$_['error_permission']                                  = 'Warning: You do not have permission to modify Plan!';
$_['error_plan_exists']                                 = 'Warning: Plan already exists!';

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
