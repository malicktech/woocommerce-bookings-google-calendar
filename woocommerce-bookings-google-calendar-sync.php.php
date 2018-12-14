<?php
/*
	Plugin Name: WooCommerce Bookings Google Calendar Sync
	Description: Get Bookings events from Google Calender and booked to google calendar
	Version: 1.3
	Author: Wpexperts.io
	Author URI: https://wpexperts.io/
	License: GPL
*/

ob_start();
// ini_set('display_errors',1);
defined( 'ABSPATH' ) OR exit;



function report_error_pro(){
	$class = 'notice notice-error';
	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		$message = __( 'To use "WooCommerce Bookings Google Calendar Sync" WooCommerce must be activated or installed!', 'wpexp_google_sync' );
		printf( '<br><div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}
	if (!in_array('woocommerce-bookings/woocommerce-bookings.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		$message = __( 'To use "WooCommerce Bookings Google Calendar Sync" WooCommerce booking must be activated or installed!', 'wpexp_google_sync' );
		printf( '<br><div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}
	if (version_compare( PHP_VERSION, '5.0', '<' )) {
		$message = __( 'To use "WooCommerce Bookings Google Calendar Sync" PHP version must be 5.0+, Current version is: ' . PHP_VERSION . ". Contact your hosting provider to upgrade your server PHP version.\n", 'wpexp_google_sync' );
		printf( '<br><div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}
	deactivate_plugins('woocommerce-bookings-google-calendar-sync/woocommerce-bookings-google-calendar-sync.php');
	wp_die('','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );

}

if (
	!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
	or 
	!in_array('woocommerce-bookings/woocommerce-bookings.php', apply_filters('active_plugins', get_option('active_plugins')))
	or 
	version_compare( PHP_VERSION, '5.0', '<' )
	) { 
	add_action( 'admin_notices', 'report_error_pro' );
	} else {

add_action( 'admin_menu', 'register_bookings_wpexp' );
add_action( 'admin_enqueue_scripts', 'bookings_wpexp_script' );

register_activation_hook(__FILE__, 'register_activation_hook_function');
register_deactivation_hook( __FILE__, 'register_deactivation_hook_function' );

function register_bookings_wpexp(){
	add_menu_page('Settings', 'Google To Woo', 'manage_options',  'google-to-woocommerce', 'bookings_list_page', "dashicons-external");
	add_submenu_page('google-to-woocommerce', "Logs", "Logs", 'manage_options', 'google-to-woocommerce-logs', 'logs_plugin_page');
}

function logs_plugin_page(){
        $woocommerce_bookings_google_calendar_sync_logs = get_option('woocommerce_bookings_google_calendar_sync_logs');
        include plugin_dir_path(__FILE__) . 'views/logs.php';       
}

function bookings_wpexp_script(){
	wp_enqueue_script( 'bookings_wpexp_custom_script', plugin_dir_url(__FILE__) . 'js/bookings_wpexp_script.js' );
	wp_localize_script('bookings_wpexp_custom_script', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
	wp_enqueue_style('google-to-woocommerce-style', plugin_dir_url(__FILE__)  . 'css/style.css');
}

add_filter( 'woocommerce_bookings_gcalendar_sync', 'overwrite_person_info',20,2);

function overwrite_person_info($data, $booking){
	
	if(!empty($data)){
		// $booking       = get_wc_booking( $booking_id );
		$event_id      = $booking->get_google_calendar_event_id();
		$product_id    = $booking->get_product_id();
		$product       = wc_get_product( $product_id );
		$resource      = wc_booking_get_product_resource( $product_id, $booking->get_resource_id() );
		$description   = '';
		$personarray =  $booking->get_persons();
		$timezone     = wc_booking_get_timezone_string();
		if(empty($personarray[0])){
        	if(is_array($personarray)){
        	    foreach($personarray as $key => $value){
					$posts = get_post($key);
					if($posts->post_type == 'bookable_person'){
						// $personss[$posts->post_title] =  $value;
						$booking_persontype['Persons-'.$posts->post_title] = $booking->has_persons() ? $value : 0;
					}
        	        }
        	    }
	    }
		
		// var_dump($booking);
		// debug_log_wpexperts();
		// $customer = $booking->get_customer();
		$order    = $booking->get_order();
		// debug_log_wpexperts('log-'.__LINE__, $booking);
		// debug_log_wpexperts('log-'.__LINE__, $order);
		$booking_status = $booking->get_status();
		if($booking_status == 'wc-partial-payment') {
			$booking_status = 'Acompte';
		}
		elseif($booking_status == 'paid') {
			$booking_status = 'Payé';
		}
		// else : confirmed

		// TODO if order status = 'partial-payment'
		// TODO if ORDER status = 'PENDING', status = 'completed'

		// Author : Malick
		// info person mis dans la description
		$booking_data = array(
			__( 'Identifiant r&eacute;servation', 'woocommerce-bookings' )   => $booking->get_id(),
			__( 'Statut r&eacute;servation', 'woocommerce-bookings' )   => $booking_status,
			__( 'Client', 'woocommerce-bookings' )    => $booking->get_customer() && ! empty( $booking->get_customer()->name ) ? $booking->get_customer()->name : 'Employe Saona',
			__( 'Salle', 'woocommerce-bookings' ) => is_object( $resource ) ? $resource->get_title() : '',
			__( 'Persons', 'woocommerce-bookings' )      => $booking->has_persons() ? array_sum( $booking->get_persons() ) : 0,
			__( 'Date commande', 'woocommerce-bookings' )   => $order && $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
			__( 'Statut commande', 'woocommerce-bookings' )   => $order && $order->get_status() ? $order->get_status(): '',
			__( 'Montant pay&eacute;', 'woocommerce-bookings' )   => $order && $order->get_formatted_order_total() ? $order->get_formatted_order_total(): '',
		);
		
		if(!empty($booking_persontype) and is_array($booking_persontype)){
			$booking_data =  array_merge($booking_data,$booking_persontype);
			unset($booking_data['Persons']);
		}
		foreach ( $booking_data as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$description .= sprintf( '%s: %s', rawurldecode( $key ), rawurldecode( $value ) ) . PHP_EOL;
		}

		// Set the event data
		$data = array(
			'summary'     => wp_kses_post( '#' . $booking->get_id() . ' - ' . ( $product ? $product->get_title() : __( 'Booking', 'woocommerce-bookings' ) ) ),
			'description' => wp_kses_post( utf8_encode( $description ) ),
		);

		// Set the event start and end dates
		if ( $booking->is_all_day() ) {
			// 1440 min = 24 hours. Bookings includes 'end' in its set of days, where as GCal uses that
			// as the cut off, so we need to add 24 hours to include our final 'end' day.
			// https://developers.google.com/google-apps/calendar/v3/reference/events/insert
			$data['end'] = array(
				'date' => date( 'Y-m-d', ( $booking->get_end() + 1440 ) ),
			);
			$data['start'] = array(
				'date' => date( 'Y-m-d', $booking->get_start() ),
			);
		} else {
			$data['end'] = array(
				'dateTime' => date( 'Y-m-d\TH:i:s', $booking->get_end() ),
				'timeZone' => $timezone,
			);

			$data['start'] = array(
				'dateTime' => date( 'Y-m-d\TH:i:s', $booking->get_start() ),
				'timeZone' => $timezone,
			);
		}
		return $data;
		
	}

}


add_action('wp_ajax_delete_log', "delete_log_function");

function delete_log_function(){
	delete_option( 'woocommerce_bookings_google_calendar_sync_logs' );
	echo "success";
	die();
}


// define the woocommerce_before_single_product callback 
function action_woocommerce_before_single_product(  ) { 

	$t=time();
	$get_transient = get_transient( 'google_sync' );

	if(!$get_transient){
			set_transient( 'google_sync', 'transient_sync_time_'.$t, MINUTE_IN_SECONDS );
				
			$wc_bookings_google_calendar_settings = get_option('wc_bookings_google_calendar_settings');
	
			if(!empty($wc_bookings_google_calendar_settings['client_id']) and !empty($wc_bookings_google_calendar_settings['client_secret'])){
				
				
					// delete_transient( 'wc_bookings_gcalendar_access_token' );
					$access_token = get_transient( 'wc_bookings_gcalendar_access_token' );
				
					if(!empty($access_token)){
						$service = get_goo_list($access_token);
					} else {
						// include_once(__DIR__ .'../../woocommerce-bookings/includes/integrations/class-wc-bookings-google-calendar-integration.php');
						$wooclass = new WC_Bookings_Google_Calendar_Integration();
						$wooclass->get_access_token();
						$access_token = get_transient( 'wc_bookings_gcalendar_access_token' );
						//again sync function.
						$service = get_goo_list($access_token);
					}
				$updated_events = 0;
				if(!empty($service->items)){		
					/*
					{
						"kind": "calendar#event",
						"etag": "\"3080314687694000\"",
						"id": "5ctten3jvt2p6bbh9lbp4soa9r",
						"status": "confirmed",
						"htmlLink": "https://www.google.com/calendar/event?eid=NWN0dGVuM2p2dDJwNmJiaDlsYnA0c29hOXIgdGVzdC5zbmVjb21tZXJjZUBt",
						"created": "2018-10-21T21:29:03.000Z",
						"updated": "2018-10-21T21:29:03.847Z",
						"summary": "HOTE",
						"creator": {
						"email": "test.snecommerce@gmail.com",
						"displayName": "Compte de test SNE",
						"self": true
						},
						"organizer": {
						"email": "test.snecommerce@gmail.com",
						"displayName": "Compte de test SNE",
						"self": true
						},
						"start": {
						"dateTime": "2018-11-01T15:00:00+01:00"
						},
						"end": {
						"dateTime": "2018-11-01T16:00:00+01:00"
						},
						"iCalUID": "5ctten3jvt2p6bbh9lbp4soa9r@google.com",
						"sequence": 0,
						"extendedProperties": {
						"private": {
						"everyoneDeclinedDismissed": "-1"
						}
					}
					*/
					foreach ($service->items as $event) {
						$event_id = $event->id;		
						$event_title = @$event->summary; 
						// Author = @Malick
						// $event_titlearray = explode('-',$event_title);
						// if(trim($event_titlearray[0]) == 'Booking' or trim($event_titlearray[0]) == 'booking'){
							
						$posted = array();
						if(!empty($event->start->date)){
							$start = $event->start->date;
							$end = $event->end->date;
							$end = date('Y-m-d', strtotime('-1 day', strtotime($end)));
							$_booking_all_day = true;
						} elseif($event->start->dateTime) {
							
							$startdatetimeexploded = explode('T',$event->start->dateTime);
							$enddatetimeexploded = explode('T',$event->end->dateTime);
							
							$start = $startdatetimeexploded[0];
							$end = $enddatetimeexploded[0];
							$end = date('Y-m-d', strtotime(($end)));
							
							
							
							$startdatetimeexploded_time = explode(':',str_replace('Z','',$startdatetimeexploded[1]));
							$enddatetimeexploded_time = explode(':',str_replace('Z','',$enddatetimeexploded[1]));
							
							$posted['wc_bookings_field_start_date_time'] = $startdatetimeexploded_time[0].':'.$startdatetimeexploded_time[1];
							$posted['wc_bookings_field_end_date_time'] = $enddatetimeexploded_time[0].':'.$enddatetimeexploded_time[1];
							$_booking_all_day = false;
							
						}		
						
						$check_booking_status = explode('-', $event_title); // TODO : sert a quoi ? DONT KNOW
						// retrieve product by title TRIMmed and converted to uppercase before
						// https://codex.wordpress.org/Function_Reference/get_page_by_title
						/* Because this function uses the MySQL '=' comparison the $page_title will usually be matched as case insensitive  */
						$product = get_page_by_title( strtoupper(trim($event_title)), OBJECT, 'product' );
						if($product){
							if(!empty($event->description)){
								
								$re = '/^(\w+):|(.+)/';
								preg_match_all($re, $event->description, $matches, PREG_SET_ORDER, 0);
								$return = array();
								
								
								foreach ($matches as $a) {
									foreach ($matches as $b) {
									if ($a !== $b) continue;
										$return = array_merge($return, array_intersect($a, $b));
									}
								}
								$returns = array_filter(array_unique($return));
								
								if(!empty($returns) and is_array($returns)){
									$persontypeid = array();
									if(count($returns) == 3){
										if(strtolower(@$returns[1]) == 'persons'){
											$returns = array( 'persons :'.$returns[2] );
										}
									}
									foreach($returns as $key => $value){
										$value = strtolower($value);
										$valuexploded = explode(":",$value);
										
										
										$valuexploded[0] = strtolower($valuexploded[0]);
										$valuexploded[1] = strtolower($valuexploded[1]);
										
					
										$valuexploded[0] = trim($valuexploded[0]);
										$valuexploded[1] = trim(@$valuexploded[1]);
											
										if(in_array('persons',($valuexploded))){
											$person = $valuexploded[1];
										}
										if(in_array('booking type',($valuexploded)) or in_array('resource type',($valuexploded))){
											$resource_name = trim($valuexploded[1]);
										}
										
										
										
										$getting_persontype = explode('-',$valuexploded[0]);
										
										if(is_array($getting_persontype) and count($getting_persontype) >= 2){
											
												$args = array('post_type' => 'bookable_person');
												$loop = new WP_Query( $args );
												
											
										if(is_array($loop->posts)){
											foreach($loop->posts as $posts){
												if(strtolower($posts->post_title) == strtolower($getting_persontype[1])){
													$persontypeid[$posts->ID] = $valuexploded[1];
													$personfor_calculate['wc_bookings_field_persons_'.$posts->ID] = $valuexploded[1];
												}
											}
										}								
											
										}
										
									
									}
									
													
								}
												
								// custom - set ressource automatically
								$resource_name = strtoupper(trim($event_title));
								if(empty($resource_name)){
									$resource_id = null;
								} else {
									$resource_name = get_page_by_title($resource_name, OBJECT, 'bookable_resource');
									
									if(!empty($resource_name->ID)){ $resource_id = $resource_name->ID;}else{ $resource_id = null; }
								}
							} else {
								// custom - set ressource automatically
								$resource_name = strtoupper(trim($event_title));
								$resource_name = get_page_by_title($resource_name, OBJECT, 'bookable_resource');					
								if(!empty($resource_name->ID)){ $resource_id = $resource_name->ID;}else{ $resource_id = null; }					
							}

							

								if(empty($person)){
									$person = 1;
								}
								
								
								if(!empty($persontypeid)){
									$qty = $persontypeid;
								} else {
									$qty = array(trim(@$person));
								}
								
								$new_booking_data = array(
													'start_date' => strtotime($start.' '.@$posted['wc_bookings_field_start_date_time']),
													'persons' =>  $qty,
													'resource_id' => @$resource_id,
													'end_date' =>  strtotime($end.' '.@$posted['wc_bookings_field_end_date_time'])
												);
								$new_booking_data['_booking_all_day'] = $_booking_all_day;
								$strdate = 	$start;
								$enddate =	$end;
							
								$date1 = new DateTime($strdate);
								$date2 = new DateTime($enddate);

								$duration = $date2->diff($date1)->format("%a");
								
								$duration = $duration+1;
									
								$startdataexploded = explode('-',$start);
								$stryearss = $startdataexploded[0];
								$strmonth = $startdataexploded[1];
								$strday = explode('T',$startdataexploded[2])[0];
								$enddataexploded = explode('-',$end);
								$endyearss = $enddataexploded[0];
								$endmonth = $enddataexploded[1];
								$endday = explode('T',$enddataexploded[2])[0];
								
								$productsss    = wc_get_product( $product->ID );
								$booking_form     = new WC_Booking_Form( $productsss );
								
								$posted['wc_bookings_field_duration'] = $duration;
								$posted['wc_bookings_field_persons'] = $qty;
								$posted['wc_bookings_field_start_date_month'] = $strmonth;
								$posted['wc_bookings_field_start_date_day'] = $strday;
								$posted['wc_bookings_field_start_date_year'] = $stryearss;
								$posted['wc_bookings_field_start_date_to_month'] = $endmonth;
								$posted['wc_bookings_field_resource'] = @$resource_id;
								$posted['wc_bookings_field_start_date_to_day'] = $endday;
								$posted['wc_bookings_field_start_date_to_year'] = $endyearss;

								
								if(!empty($personfor_calculate) and is_array($personfor_calculate)){
									unset($posted['wc_bookings_field_persons']);
									$posted = array_merge($posted,$personfor_calculate);
								}
							$cost = $booking_form->calculate_booking_cost( $posted );
					
							
							debug_log_wpexperts('log-'.__LINE__, "The response of get cost of bookable product : " . json_encode($cost));
							if ( is_wp_error( $cost ) ) {
								update_log_in_db($cost,$event_title,$event_id);
								update_google_calendar(
								array(
									'summary'=>'booking_Not_available '.$event_title,
									'colorId'=> 11
									)
								,$event_id,get_transient( 'wc_bookings_gcalendar_access_token' ));
							}
							if( is_numeric($cost) ){
								$new_booking = create_wpexp_wc_booking( $product->ID, $new_booking_data, @$status, $exact = false,$event_id );
							
								if ( is_wp_error( $new_booking ) ) {
									update_log_in_db($new_booking,$event_title,$event_id);
								}
								debug_log_wpexperts('log-'.__LINE__, "The response of create_wpexp_wc_booking : " . json_encode($new_booking));
							} else {
												
							}
							
							if(@$new_booking and is_numeric($cost)){
								
								
								$order_id = wpexp_create_booking_order($product->ID,$event_title,$cost, $event->description);
								if($order_id){
									// Update post 37
									$my_post = array(
										'ID'           => $new_booking->id,
										'post_parent' => $order_id
									);
									// Update the post into the database
									wp_update_post( $my_post );
									
									
									if(true){
										$event_idies[] = $event_id;
										$updated_events++;
									}
									
								}
							}
						}
							
							// }
					}
				}
					
					
					if($updated_events){
						echo '#'.$updated_events.' bookings has been added.';
						$wooclass = new WC_Bookings_Google_Calendar_Integration();
						foreach($event_idies as $event_id){
							
							$api_url      = $wooclass->calendars_uri . $wooclass->calendar_id . '/events/' . $event_id;
							$params       = array(
									'method'    => 'DELETE',
									'sslverify' => false,
									'timeout'   => 60,
									'headers'   => array(
									'Authorization' => 'Bearer ' . $access_token,
													),
									);
							
								$response = wp_remote_post( $api_url, $params );
								if ( ! is_wp_error( $response ) && 204 == $response['response']['code'] ) {
									//delete booking on google calendar
								} else {
									//error not delete booking on google calendar
								}
							
						}
					} else {
							echo '#0 booking has been added.';
					}
			}
	}
} 
         
// add the action 
add_action( 'woocommerce_before_single_product', 'action_woocommerce_before_single_product', 10, 2 ); 

function bookings_list_page(){
	
	
	if(false){
		global $wpdb;
		$allbookin_post = $wpdb->get_results( 'SELECT * FROM  `wp_posts` WHERE  `post_type` =  \'wc_booking\'', OBJECT );
		if(!empty($allbookin_post)){
			foreach($allbookin_post as $posts){
				// $get_post_meta = get_post_meta($posts->ID);
				wp_delete_post($posts->ID,true); 
				
			}
		}
		$allbookin_post = $wpdb->get_results( 'SELECT * FROM  `wp_posts` WHERE  `post_type` =  \'shop_order\'', OBJECT );
		if(!empty($allbookin_post)){
			foreach($allbookin_post as $posts){
				// $get_post_meta = get_post_meta($posts->ID);
				wp_delete_post($posts->ID,true); 
				
			}
		}
		die();
	}
	
	echo '<h1>Authorized & import Google Calendar </h1>';
	
	
	$wc_bookings_google_calendar_settings = get_option('wc_bookings_google_calendar_settings');
	if(!empty($wc_bookings_google_calendar_settings['client_id']) && !empty($wc_bookings_google_calendar_settings['client_secret'])){
	if(@$_POST['import']){
		$access_token = get_transient( 'wc_bookings_gcalendar_access_token' );
		if(!empty($access_token)){
			$service = get_goo_list($access_token);
		} else {
			$wooclass = new WC_Bookings_Google_Calendar_Integration();
			$wooclass->get_access_token();
			$access_token = get_transient( 'wc_bookings_gcalendar_access_token' );
			//again sync function.
			$service = get_goo_list($access_token);
		}
	}
	}else{
		$service = '';
		echo 'Please update your required values <a href="admin.php?page=wc-settings&tab=integration">here</a> to start import bookings from Google Calendar.';
	}
	if(@$service){
		
		$wc_bookings_google_calendar_settings = get_option('wc_bookings_google_calendar_settings');
		$calendar_id = $wc_bookings_google_calendar_settings['calendar_id'];
	
		$updated_events = 0;
		
		
		
		
					
	if(!empty($service->items)) {
							
		  foreach ($service->items as $event) {
			$event_id = $event->id;			
			$event_title = @$event->summary;
			// Author = @Malick 
			// $event_titlearray = explode('-',$event_title);
			// if(trim($event_titlearray[0]) == 'Booking' or trim($event_titlearray[0]) == 'booking'){
			$posted = array();
			if(!empty($event->start->date)){
				$start = $event->start->date;
				$end = $event->end->date;
				$end = date('Y-m-d', strtotime('-1 day', strtotime($end)));
				$_booking_all_day = true;
			} elseif($event->start->dateTime) {
				
				$startdatetimeexploded = explode('T',$event->start->dateTime);
				$enddatetimeexploded = explode('T',$event->end->dateTime);
				
				$start = $startdatetimeexploded[0];
				$end = $enddatetimeexploded[0];
				$end = date('Y-m-d', strtotime(($end)));
				
				
				
				$startdatetimeexploded_time = explode(':',str_replace('Z','',$startdatetimeexploded[1]));
				$enddatetimeexploded_time = explode(':',str_replace('Z','',$enddatetimeexploded[1]));
				
				$posted['wc_bookings_field_start_date_time'] = $startdatetimeexploded_time[0].':'.$startdatetimeexploded_time[1];
				$posted['wc_bookings_field_end_date_time'] = $enddatetimeexploded_time[0].':'.$enddatetimeexploded_time[1];
				$_booking_all_day = false;
				
			}		
			
			$check_booking_status = explode('-', $event_title);// TODO ?
			$product = get_page_by_title( strtoupper(trim($event_title)), OBJECT, 'product' );
			if($product){
				if(!empty($event->description)){
					/* $event_description = explode('Persons',$event->description);
					if(!empty($event_description)){
						$person=str_replace(':','',$event_description[1]);
					} */
					
					
					$re = '/^(\w+):|(.+)/';
					preg_match_all($re, $event->description, $matches, PREG_SET_ORDER, 0);
					$return = array();
					
					
					foreach ($matches as $a) {
						foreach ($matches as $b) {
						   if ($a !== $b) continue;
							$return = array_merge($return, array_intersect($a, $b));
						}
					}
				    $returns = array_filter(array_unique($return));
					
					
					
					
					
					if(!empty($returns) and is_array($returns)){
						$persontypeid = array();
						if(count($returns) == 3){
							if(strtolower($returns[1]) == 'persons'){
								$returns = array( 'persons :'.$returns[2] );
							}
						}
						foreach($returns as $key => $value){
							
							$valuexploded = explode(":",$value);
							
							
							$valuexploded[0] = strtolower($valuexploded[0]);
							$valuexploded[1] = strtolower($valuexploded[1]);
							
		
							$valuexploded[0] = trim($valuexploded[0]);
							$valuexploded[1] = trim(@$valuexploded[1]);
								
							if(in_array('persons',($valuexploded))){
								$person = $valuexploded[1];
							}
							if(in_array('booking type',($valuexploded))){
								 $resource_name = trim($valuexploded[1]);
							}
							
							
							
							$getting_persontype = explode('-',$valuexploded[0]);
							
							if(is_array($getting_persontype) and count($getting_persontype) >= 2){
								
									$args = array('post_type' => 'bookable_person');
									$loop = new WP_Query( $args );
									  
								
							if(is_array($loop->posts)){
								foreach($loop->posts as $posts){
									if(strtolower($posts->post_title) == strtolower($getting_persontype[1])){
										$persontypeid[$posts->ID] = $valuexploded[1];
										$personfor_calculate['wc_bookings_field_persons_'.$posts->ID] = $valuexploded[1];
									}
								}
							}
									
								
								
								
							}
							
						
						}
						
										
					}
					
	
				
					if(empty($resource_name)){
						$resource_id = null;
					} else {
						$resource_name = get_page_by_title($resource_name, OBJECT, 'bookable_resource');
						
						 if(!empty($resource_name->ID)){ $resource_id = $resource_name->ID;}else{ $resource_id = null; }
					}
				}
				
					if(empty($person)){
						$person = 1;
					}
					
					
					if(!empty($persontypeid)){
						$qty = $persontypeid;
					} else {
						$qty = array(trim(@$person));
					}
					
					$new_booking_data = array(
										'start_date' => strtotime($start.' '.@$posted['wc_bookings_field_start_date_time']),
										'persons' =>  $qty,
										'resource_id' => @$resource_id,
										'end_date' =>  strtotime($end.' '.@$posted['wc_bookings_field_end_date_time'])
									);
					$new_booking_data['_booking_all_day'] = $_booking_all_day;
					$strdate = 	$start;
					$enddate =	$end;
				
					$date1 = new DateTime($strdate);
					$date2 = new DateTime($enddate);

					$duration = $date2->diff($date1)->format("%a");
					
					$duration = $duration+1;
						
					$startdataexploded = explode('-',$start);
					$stryearss = $startdataexploded[0];
					$strmonth = $startdataexploded[1];
					$strday = explode('T',$startdataexploded[2])[0];
					$enddataexploded = explode('-',$end);
					$endyearss = $enddataexploded[0];
					$endmonth = $enddataexploded[1];
					$endday = explode('T',$enddataexploded[2])[0];
					
					$productsss    = wc_get_product( $product->ID );
					$booking_form     = new WC_Booking_Form( $productsss );
					
					$posted['wc_bookings_field_duration'] = $duration;
					$posted['wc_bookings_field_persons'] = $qty;
					$posted['wc_bookings_field_start_date_month'] = $strmonth;
					$posted['wc_bookings_field_start_date_day'] = $strday;
					$posted['wc_bookings_field_start_date_year'] = $stryearss;
					$posted['wc_bookings_field_start_date_to_month'] = $endmonth;
					$posted['wc_bookings_field_resource'] = @$resource_id;
					$posted['wc_bookings_field_start_date_to_day'] = $endday;
					$posted['wc_bookings_field_start_date_to_year'] = $endyearss;

					
					if(!empty($personfor_calculate) and is_array($personfor_calculate)){
						unset($posted['wc_bookings_field_persons']);
						$posted = array_merge($posted,$personfor_calculate);
					}
				$cost = $booking_form->calculate_booking_cost( $posted );
				
		
		
				
				
				debug_log_wpexperts('log-'.__LINE__, "The response of get cost of bookable product : " . json_encode($cost));
				if ( is_wp_error( $cost ) ) {
					update_log_in_db($cost,$event_title,$event_id);
				}
				if( is_numeric($cost) ){
					$new_booking = create_wpexp_wc_booking( $product->ID, $new_booking_data, @$status, $exact = false,$event_id );
				
					if ( is_wp_error( $new_booking ) ) {
						update_log_in_db($new_booking,$event_title,$event_id);
					}
					debug_log_wpexperts('log-'.__LINE__, "The response of create_wpexp_wc_booking : " . json_encode($new_booking));
				} else {
									
				}
				
				if(@$new_booking and is_numeric($cost)){
					
					
					$order_id = wpexp_create_booking_order($product->ID,$event_title,$cost, $event->description);
					if($order_id){
						// Update post 37
						$my_post = array(
							'ID'           => $new_booking->id,
							'post_parent' => $order_id
						);
						// Update the post into the database
						wp_update_post( $my_post );
						
						
						if(true){
							$event_idies[] = $event_id;
							$updated_events++;
						}
						
					}
				}
			}
				
				// }
		  }
	}
		  
		
		if($updated_events){
			echo '#'.$updated_events.' bookings has been added.';
			$wooclass = new WC_Bookings_Google_Calendar_Integration();
			foreach($event_idies as $event_id){
				
				$api_url      = $wooclass->calendars_uri . $wooclass->calendar_id . '/events/' . $event_id;
				$params       = array(
						'method'    => 'DELETE',
						'sslverify' => false,
						'timeout'   => 60,
						'headers'   => array(
						'Authorization' => 'Bearer ' . $access_token,
										),
						);
				
					$response = wp_remote_post( $api_url, $params );
					if ( ! is_wp_error( $response ) && 204 == $response['response']['code'] ) {
						//delete booking on google calendar
					} else {
						//error not delete booking on google calendar
					}
				
			}
		} else {
			echo '#0 booking has been added.';
		}
		
	}?>
	
	<div class="wrap">
		<div class="bookings_options_panel">
			<form action="" method="post">
				
				<p class="form-field">
					<input type="submit" value="Authorized  & Import" accesskey="p" id="publish" class="button button-primary button-large" name="import">
				</p>
			</form>
		</div>
	</div>
	<?php 
}

// Récupération de la liste des events google
// timemIN FORMAT = 2018-10-20T14:30:00Z
function get_goo_list($access_token){
			
			// Google calendar api GET event
			// https://developers.google.com/calendar/v3/reference/events/list
			$wc_bookings_google_calendar_settings = get_option('wc_bookings_google_calendar_settings');
	        $calendar_id = $wc_bookings_google_calendar_settings['calendar_id'];
	        $today = date("Y-m-d", strtotime("-1 days"));
	        
			$curl = curl_init();
			curl_setopt_array($curl, array(
			CURLOPT_URL => "https://www.googleapis.com/calendar/v3/calendars/".$calendar_id."/events?access_token=".$access_token."&timeMin=".$today."T00%3A00%3A00%2B00%3A00&maxResults=2450",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
			"cache-control: no-cache"
			),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:" . $err;
			} else {
				return $service = json_decode($response);
			}
}

function wpexp_create_booking_order( $pid,$event_title ,$cost, $eventdescriptionnote){
	$order_date = new DateTime();
	// build order data
	$order_data = array(
		'post_name'     => 'order-' . date_format($order_date, 'M-d-Y-hi-a'), //'order-jun-19-2014-0648-pm'
		'post_type'     => 'shop_order',
		'post_title'    => 'Order &ndash; ' . date_format($order_date, 'F d, Y @ h:i A'), //'June 19, 2014 @ 07:19 PM'
		'post_status'   => 'wc-completed',
		'ping_status'   => 'publish',
		'post_excerpt'  => $event_title,
		'post_author'   => 1,
		'post_password' => uniqid( 'order_' ),   // Protects the post just in case
		'post_date'     => date_format($order_date, 'Y-m-d H:i:s e'), //'order-jun-19-2014-0648-pm'
		'comment_status' => 'open',
		'meta_input'   => array(
			'eventdescriptionnote' =>  $eventdescriptionnote
		)
	);

	// create order
	$order_id = wp_insert_post( $order_data, true );
	
	if ( !is_wp_error( $order_id ) ) {
		$order = new WC_Order( $order_id );
		
		// add a bunch of meta data
		// add_post_meta($order_id, 'transaction_id', $order->transaction_id, true); 
		add_post_meta($order_id, '_payment_method_title', 'Import-via-google-calendar', true);
		add_post_meta($order_id, '_order_total', wc_format_decimal( $cost ), true);
		// add_post_meta($order_id, '_customer_user', $account->user_id, true);
		add_post_meta($order_id, '_completed_date', date_format( $order_date, 'Y-m-d H:i:s e'), true);
		add_post_meta($order_id, '_order_currency', $order->get_currency(), true);
		add_post_meta($order_id, '_paid_date', date_format( $order_date, 'Y-m-d H:i:s e'), true); 
		$orderby = explode('-',$event_title);
		add_post_meta($order_id, '_billing_last_name', $orderby[1], true);
		// billing info
		

		// get product by item_id
		$product = get_wpexp_product( $pid );

		if( $product ) {

			// add item
			$item_id = wc_add_order_item( $order_id, array(
				'order_item_name'       => $product->get_title(),
				'order_item_type'       => 'line_item'
			) );

			if ( $item_id ) {

				// add item meta data
				wc_add_order_item_meta( $item_id, '_qty', 1 ); 
				wc_add_order_item_meta( $item_id, '_tax_class', $product->get_tax_class() );
				wc_add_order_item_meta( $item_id, '_product_id', $product->get_id() );
				wc_add_order_item_meta( $item_id, '_variation_id', '' );
				wc_add_order_item_meta( $item_id, '_line_subtotal', wc_format_decimal( $cost ) );
				wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $cost ) );
				wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( 0 ) );
				wc_add_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( $cost ) );

			}

			// set order status as completed
			wp_set_object_terms( $order_id, 'completed', 'shop_order_status' );
			
			return $order_id;

		}
	}else{
		return false;
	}
}

function get_wpexp_product( $product_id ) {

    if ( $product_id ) return new WC_Product( $product_id );

    return null;
}


function create_wpexp_wc_booking( $product_id, $new_booking_data = array(), $status = 'completed', $exact = false ,$event_id) {
	// Merge booking data
	$status = 'confirmed';
	$defaults = array(
		'product_id'  => $product_id, // Booking ID
		'start_date'  => '',
		'end_date'    => '',
		'resource_id' => '',
	);

	
	$new_booking_data = wp_parse_args( $new_booking_data, $defaults );
	
	$product          = wc_get_product( $product_id );
	$booking = new WC_Product_Booking($product_id);		
	
	$start_date       = $new_booking_data['start_date'];
	$end_date         = $new_booking_data['end_date'];
	// $max_date         = $product->get_max_date();
	
	
	
	
	if(!$new_booking_data['_booking_all_day']){
		$bookable_product = $product;
		$min_date = $bookable_product->get_min_date();
		$min_date = empty( $min_date ) ? array( 'unit' => 'minute', 'value' => 1 ) : $min_date ;
		$min_date = strtotime( "midnight +{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );

		$max_date = $bookable_product->get_max_date();
		$max_date = empty( $max_date ) ? array( 'unit' => 'month', 'value' => 12 ) : $max_date;
		$max_date = strtotime( "+{$max_date['value']} {$max_date['unit']}", current_time( 'timestamp' ) );
		$WC_Bookings_Controller = new WC_Bookings_Controller();
		$find_bookings_for     = array( $bookable_product->get_id() );
		$existing_bookings = $WC_Bookings_Controller->get_bookings_for_objects( $find_bookings_for, get_wc_booking_statuses( 'fully_booked' ), $min_date, $max_date );

		$available_bookings = true;
		foreach ( $existing_bookings as $existing_booking ) {
				 $check_date = $existing_booking->get_start(); 
				 $end_date_existing_booking 	= $existing_booking->is_all_day() ? strtotime( 'tomorrow midnight', $existing_booking->end ) : $existing_booking->end;
				
				// Loop over all booked days in this booking
				while ( $check_date < $end_date_existing_booking ) {
					$js_date = date( 'Y-n-j', $check_date );

					// if the check date is in the past, unless we are looking at daily bookings, skip to the next one
					if ( $check_date < current_time( 'timestamp' ) && 'day' !== $bookable_product->get_duration_unit() ) {
						$check_date = strtotime( '+1 day', $check_date );
						continue;
					}

					// set the resource ID, main product always has resource of 0
					$resource_id = 0;
					if ( $bookable_product->has_resources() ) {
						$resource_id = $existing_booking->get_resource_id();
					}

					// Skip if we've already found this resource is unavailable
					if ( ! empty( $fully_booked_days[ $js_date ][ $resource_id ] ) ) {
						$check_date = strtotime( '+1 day', $check_date );
						continue;
					}

					$midnight                 = strtotime( 'midnight', $check_date ); // Midnight on the date being checked is 00:00 start of day.
					$before_midnight_tomorrow = strtotime( '23:59', $check_date );    // End of the date being checked, not the following morning.

					// Regardless of duration unit, we need to pass all blocks of bookings so that the availability rules are properly calculated against.
					
					
					
					
					$booking_start_and_end    = $WC_Bookings_Controller->get_bookings_star_and_end_times( $existing_bookings );
					//this product already have timebooked slot check below condition
					if(is_array($booking_start_and_end)){
						$counter =0;
						foreach($booking_start_and_end as $bookingss){
							$entereddate       = $new_booking_data['start_date'];
							$enddate       = $new_booking_data['end_date'];
							$contractDateBegin = $bookingss[0];
							$contractDateEnd   = $bookingss[1];

							//if this product already have slot time between already booked slot check below
							if ($entereddate >= $contractDateBegin &&
								$entereddate <= $contractDateEnd || 
								$enddate >= $contractDateBegin &&
								$enddate <= $contractDateEnd)
							{
								//if this product already have slot time between already booked slot max availability of slot;
								$counter++;
								// print_r($counter);
								if($counter >= get_post_meta($bookable_product->get_id(),'_wc_booking_qty',true)){
									update_log_in_db('no booking available in this slot '.gmdate("Y-m-d H:i:s a",$entereddate).' To '. gmdate("Y-m-d H:i:s a",$enddate), $bookable_product->get_title(),'none');
									$available_bookings = false;
								} 
								
							} 
						}
						
					}
						
					$check_date = strtotime( '+1 day', $check_date );
				}
		}
	} else {
		$available_bookings = $booking->get_available_bookings( $start_date, $end_date,$new_booking_data['resource_id'],@$new_booking_data['qty'][0]);
	}
	
	if ( is_wp_error( $available_bookings ) ) {
		$error_string = $result->get_error_message();
		debug_log_wpexperts('error-'.__LINE__, "The error response of get_available_bookings : " . ($error_string));
	} else {
		debug_log_wpexperts('log-'.__LINE__, "The response of get_available_bookings : " . json_encode($available_bookings));
	}
	
	

						
	$date_diff = $end_date - $start_date;

	
	// Set dates
	if(empty(is_wp_error( $available_bookings )) and $available_bookings == 1){
		$new_booking_data['start_date'] = $start_date;
		$new_booking_data['end_date']   = $end_date;
		// Create it
		$new_booking = get_wc_booking( $new_booking_data );
		debug_log_wpexperts('log-'.__LINE__, "The response of get_wc_booking : " . json_encode($new_booking));
		$new_booking ->create( $status );
		update_post_meta($new_booking->id,'_booking_all_day',$new_booking_data['_booking_all_day']);
		return $new_booking;
	} else {
		debug_log_wpexperts('log-'.__LINE__, "The response of get_wc_booking : " . 'false');
		update_google_calendar(
					array(
						'summary'=>'booking_Not_available '.$bookable_product->get_title(),
						'colorId'=> 11
						)
					,$event_id,get_transient( 'wc_bookings_gcalendar_access_token' ));
		return false;
	}
	
}

function register_activation_hook_function(){
		//for the access of Gooogle access_token 
		$str=file_get_contents(__DIR__ .'../../woocommerce-bookings/includes/integrations/class-wc-bookings-google-calendar-integration.php');
		$strreplaced=str_replace("protected function get_access_token", "public function get_access_token",$str);
		file_put_contents(__DIR__ .'../../woocommerce-bookings/includes/integrations/class-wc-bookings-google-calendar-integration.php', $strreplaced);
			
}
function register_deactivation_hook_function(){
		//for the access of Gooogle access_token 
		$str=file_get_contents(__DIR__ .'../../woocommerce-bookings/includes/integrations/class-wc-bookings-google-calendar-integration.php');
		$strreplaced=str_replace("public function get_access_token", "protected function get_access_token",$str);
		file_put_contents(__DIR__ .'../../woocommerce-bookings/includes/integrations/class-wc-bookings-google-calendar-integration.php', $strreplaced);
			
}

// Enregistre les résultats de la synchro dans les options wordpress :
// table rs_options, option woocommerce_bookings_google_calendar_sync_logs
function update_log_in_db($logs,$event_title,$event_id){
	$logs = array('currenttime_date'  => date("Y-m-d h:i:sa"), 'event_id' => $event_id, 'log' => $logs , 'Event_Title' => $event_title);
	if(!empty($logs)){
		$get_op = get_option('woocommerce_bookings_google_calendar_sync_logs');
		if(!empty($get_op)){
			$get_op = array_merge(array($logs),$get_op);
			update_option('woocommerce_bookings_google_calendar_sync_logs',$get_op,false);
		} else {
			update_option('woocommerce_bookings_google_calendar_sync_logs',array($logs),false);
		}
	}
}
function update_google_calendar($form_data=array(),$event_id,$access_token){
		
	$curl = curl_init();
	$data_string = json_encode($form_data);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://www.googleapis.com/calendar/v3/calendars/primary/events/".$event_id,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "PATCH",
	  CURLOPT_POSTFIELDS => $data_string,
	  CURLOPT_HTTPHEADER => array(
		"authorization: Bearer ".$access_token,
		"cache-control: no-cache",
		"content-type: application/json"
	  ),
	));
	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	if ($err) {
	  // echo "cURL Error #:" . $err;
	} else {
			
	}
}
function debug_log_wpexperts($type, $data) {
    error_log("[$type] [" . date("Y-m-d H:i:s") . "] " . print_r($data, true) . "\n", 3, dirname(__FILE__) . '/logs.log');
}

if (!function_exists('pre')) {
	function pre($arr = array()){
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	}
}
} 