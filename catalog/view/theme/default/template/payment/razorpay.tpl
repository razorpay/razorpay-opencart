<script src="https://checkout.razorpay.com/v1/checkout.js"> </script>
<script>
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
    handler: function (transaction) {
        document.getElementById('razorpay_payment_id').value = transaction.razorpay_payment_id;
        document.getElementById('razorpay-form').submit();
    }
  };
  
  function razorpaySubmit(){                  
    var myInterval = setInterval(function(){
      if (typeof Razorpay != 'undefined') {
        clearInterval(myInterval);
        var rzp1 = new Razorpay(razorpay_options);
        rzp1.open();
      }
    },100);
  }  

</script>
<form name="razorpay-form" id="razorpay-form" action="<?php echo $return_url; ?>" method="POST">
  <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" />
  <input type="hidden" name="merchant_order_id" id="merchant_order_id" value="<?php echo $merchant_order_id ?>"/>
</form>
<div class="buttons">
  <div class="pull-right">
    <input type="submit" onclick="razorpaySubmit();" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
  </div>
</div>