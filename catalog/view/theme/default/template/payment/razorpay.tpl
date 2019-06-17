<script data-cfasync='false' type='text/javascript' src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script data-cfasync='false' type='text/javascript'>
  var razorpay_options = {
    key: "<?php echo $key_id; ?>",
    amount: "<?php echo $total; ?>",
    name: "<?php echo $name; ?>",
    description: "Order # <?php echo $merchant_order_id; ?>",
    netbanking: true,
    currency: "<?php echo $currency_code; ?>",
    prefill: {
      name:"<?php echo $card_holder_name; ?>",
      email: "<?php echo $email; ?>",
      contact: "<?php echo $phone; ?>"
    },
    notes: {
      opencart_order_id: "<?php echo $merchant_order_id; ?>"
    },
    callback_url: "<?php echo $return_url; ?>",
    order_id: "<?php echo $razorpay_order_id; ?>",
    handler: function (transaction) {
        document.getElementById('razorpay_payment_id').value = transaction.razorpay_payment_id;
        document.getElementById('razorpay_signature').value = transaction.razorpay_signature;
        document.getElementById('razorpay-form').submit();
    }
  };
  var razorpay_submit_btn, razorpay_instance;

  function razorpaySubmit(el){
    if(typeof Razorpay == 'undefined'){
      setTimeout(razorpaySubmit, 200);
      if(!razorpay_submit_btn && el){
        razorpay_submit_btn = el;
        el.disabled = true;
        el.value = 'Please wait...';  
      }
    } else {
      <?php if ($display_currency !== $currency_code) { ?>
          razorpay_options.display_currency = "<?php echo $display_currency; ?>";
          razorpay_options.display_amount = "<?php echo $display_total; ?>";
          <?php 
      } ?>
      if(!razorpay_instance){
        razorpay_instance = new Razorpay(razorpay_options);
        if(razorpay_submit_btn){
          razorpay_submit_btn.disabled = false;
          razorpay_submit_btn.value = "<?php echo $button_confirm; ?>";
        }
      }
      razorpay_instance.open();
    }
  }

</script>
<form name="razorpay-form" id="razorpay-form" action="<?php echo $return_url; ?>" method="POST">
  <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" />
  <input type="hidden" name="razorpay_signature" id="razorpay_signature" />
</form>
<div class="buttons">
  <div class="pull-right">
    <input type="submit" onclick="razorpaySubmit(this);" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
  </div>
</div>
