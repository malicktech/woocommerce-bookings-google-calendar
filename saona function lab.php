<?php

// Malick
/**
 * TODO : github gist
 * Will put bookings into a Confirmed status if they were paid for via Deposit.
 * 
 * @param int $order_id The order id
 * 
 */
function set_deposit_payment_bookings_confirmed_20170825($order_id)
{
    echo "set_deposit_payment_bookings_confirmed_20170825";
    debug_to_console("set_deposit_payment_bookings_confirmed_20170825");
    // Get the order, then make sure its payment method is Deposit.
    $order = wc_get_order($order_id);
    echo $order;
    debug_to_console($order);
    //     if ( ! in_array( $order->get_status(), array( 'wc-partial-payment', 'partial-payment', 'pending-deposit' ) ) )
    //     if ( 'pending' !== $order->get_status()) 
    //     {return;}
    
    
    // Call the data store class so we can get bookings from the order.
    $booking_data = new WC_Booking_Data_Store();
    $booking_ids  = $booking_data->get_booking_ids_from_order_id($order_id);
    // If we have bookings go through each and update the status.
    if (is_array($booking_ids) && count($booking_ids) > 0) {
        foreach ($booking_ids as $booking_id) {
            $booking = get_wc_booking($booking_id);
            //             if($booking->get_status() !== 'paid' ) {
            $booking->update_status('confirmed');
            //             }
        }
    }
}
add_action('woocommerce_order_status_processing', 'set_deposit_payment_bookings_confirmed_20170825', 20);

function monsite_woocommerce_payment_complete($order_id)
{
    //     echo "monsite_woocommerce_payment_complete";
    debug_to_console("monsite_woocommerce_payment_complete");
    // Récupérer la commande
    $order = wc_get_order($order_ID);
    //     echo $order;
    debug_to_console($order);
    
}
add_action('woocommerce_payment_complete', 'monsite_woocommerce_payment_complete');

// mysite_partial_payment show first , then monsite_woocommerce_payment_complete
/* @MALICK */
/*
 * Will put bookings into a Confirmed status if they were paid via Deposit.
 * confirmed status is need to sync with google calendar
 * */
function mysite_partial_payment($order_id)
{
echo $order_id." set to partial payment \n";
    // Call the data store class so we can get bookings from the order.
$booking_data = new WC_Booking_Data_Store();
$booking_ids  = $booking_data->get_booking_ids_from_order_id($order_id);
	// If we have bookings go through each and update the status partial payment.
if (is_array($booking_ids) && count($booking_ids) > 0) {
    foreach ($booking_ids as $booking_id) {
            $booking = get_wc_booking($booking_id);
            echo $booking->get_status()."\n";
            if($booking->get_status() == 'wc-partial-payment' ) {
            	$booking->update_status('confirmed');
			}
        }
    }
}
add_action('woocommerce_order_status_partial-payment', 'mysite_partial_payment', 10, 1);


function debug_to_console($data)
{
    if (is_array($data))
        $output = "<script>console.log( 'Debug Objects: " . implode(',', $data) . "' );</script>";
    else
        $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";
    echo $output;
}
