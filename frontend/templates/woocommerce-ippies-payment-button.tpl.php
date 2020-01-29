<form method="post" action=<?php echo esc_attr($ippies_url) ?>>
    <h3><?php __('Transaction Details', 'ippies-payment-gateway') ?>!!</h3>

    <p><?php __('You chose payment by Ippies. Click Continue do proceed', 'ippies-payment-gateway') ?></p>
	
    <p class="form-submit">
        <input type="hidden" value="<?php echo $pay_shopid; ?>" name="pay_shopid">
        <input type="hidden" value="<?php echo $pay_orderid; ?>" name="pay_orderid">
        <input type="hidden" value="<?php echo $pay_amount; ?>" name="pay_amount">
        <input type="hidden" value="<?php echo $return_normal; ?>" name="return_normal">
        <input type="hidden" value="<?php echo $return_true; ?>" name="return_true">
        <input type="hidden" value="<?php echo $return_false; ?>" name="return_false">
        
        <input type="hidden" value="<?php echo $pay_hash; ?>" name="pay_hash">
        <input class="button" type="submit" value="<?php __('Continue', 'ippies-payment-gateway') ?>" id="submit_ippies_payment_form">
    </p>
</form>