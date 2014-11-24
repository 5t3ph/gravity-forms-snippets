<?php

/**
 * Plugin Name: Auto-reminders for Gravity Forms
 * Description: Enables re-sending notifications before a date selected from the form, such as for webinars.
 * Version: 1.0
 * Author: 5t3ph
 */
 
/*
 * h/t @link http://www.stevenhenty.com/gravity-forms-approvals/
 */
 
// FOR TESTING DATE ARRAYS & SCHEDULES
//echo wp_next_scheduled( 'gfar_daily_notifications' );
/*$date = strtotime('today');
//var_dump(get_option( 'gfar_'.$date ));
foreach(get_option( 'gfar_'.$date ) as $date) {
  echo $date['form'].' '.$date['entry'];  
}*/
/*global $wpdb;
$sql = "SELECT `option_name` AS `name`, `option_value` AS `value`
		FROM  $wpdb->options
		WHERE `option_name` LIKE '%gfar_%'
		ORDER BY `option_name`";

$results = $wpdb->get_results( $sql );
//var_dump($results);
foreach($results as $result) {
	//echo $date.' '.$result->name;
	//var_dump(get_option( $result->name ));
	//delete_option($result->name);
}*/

/**
 * On plugin activation, begin schedule
 */
register_activation_hook( __FILE__, 'gfar_activation' );
function gfar_activation() {
	// wp_schedule goes by GMT, so 3 PM = 9 AM Central time
	// @link http://codex.wordpress.org/Function_Reference/wp_schedule_event
	// TODO: Make send time adjustable as plugin option
	wp_schedule_event( strtotime('today 3 PM'), 'daily', 'gfar_daily_notifications' );
}
add_action( 'gfar_daily_notifications', 'gfar_send_daily_notifications' );

/**
 * On deactivation, remove all functions from the scheduled action hook.
 */
register_deactivation_hook( __FILE__, 'gfar_deactivation' );
function gfar_deactivation() {
	wp_clear_scheduled_hook( 'gfar_daily_notifications' );
}

/**
 * On schedule, get notifcations to send, and trigger send function
 */
function gfar_send_daily_notifications() {
	
	// Clear out old notifications
	$yesterday = strtotime('yesterday');
	delete_option( 'gfar_'.$yesterday );
	
	// Send today's notifications
	$date = strtotime('today');
	$dateNotifications = get_option( 'gfar_'.$date );
	foreach($dateNotifications as $date) {
		gfar_send_notifications ( $date['form'], $date['entry'] );
	}
}
 
// Add event to define a notification as an Autoreminder
add_filter("gform_notification_events", "gfar_add_gf_event");
function gfar_add_gf_event($notification_events){
	$notification_events["gfar_event"] = __("Autoreminder", "gravityforms");
	return $notification_events;
}

/*
 * On form submit, save entry details to date-specific options array
 * 
 * @link http://www.gravityhelp.com/documentation/page/Gform_after_submission
 */
add_action('gform_after_submission', 'gfar_save_entry', 10, 2);
function gfar_save_entry($entry, $form) {
	
	$hasDate = false;
	$dateID = '';
    
    // Get field ID for user chosen date fields
	foreach($form['fields'] as &$field){
		// We want the date selected, which is a select with specified class
		if($field['type'] != 'select' || strpos($field['cssClass'], 'webinar-date') === false)
    		continue;
			
		// Set $hasDate to true, because if we don't make it this far then form doesn't have a date field
		$hasDate = true;
		$dateID = $field['id'];
		
	} // End $form['fields'] loop
	
	// If includes date field, save values
	if($hasDate) {
		$chosenDate = strtotime($entry[$dateID]);
		$reminderDate = strtotime('-1 day', $chosenDate);
		// Get existing array for other entries submitted for this date
		// Else create array
		$dateArray = get_option( 'gfar_'.$reminderDate );
		if(!$dateArray)
			$dateArray = array();
		
		// Append current entry details
		$dateArray[] = array(
			'form' => $form['id'],
			'entry' => $entry['id']
		);
		
		// Save updated array
		update_option( 'gfar_'.$reminderDate, $dateArray);
		
	} // End check for $hasDate
    
}

// Send notifications
// h/t @link https://gravityplus.pro/how-to/programmatically-trigger-gravity-forms-notification/
function gfar_send_notifications ( $form_id, $entry_id ) {
	
	// h/t @link http://www.snip2code.com/Snippet/173922/Manually-create-entries-and-send-notific/
	$form = RGFormsModel::get_form_meta($form_id);
	$entry = RGFormsModel::get_lead($entry_id);
	
	// Custom event to narrow down which notification to send
	// Likely a duplicate of the notification immediately sent on signup
	$event = 'gfar_event';
	
	$notifications         = GFCommon::get_notifications_to_send( $event, $form, $entry );
	$notifications_to_send = array();
 
	//running through filters that disable form submission notifications
	foreach ( $notifications as $notification ) {
		if ( apply_filters( "gform_disable_notification_{$form['id']}", apply_filters( 'gform_disable_notification', false, $notification, $form, $entry ), $notification, $form, $entry ) ) {
			//skip notifications if it has been disabled by a hook
			continue;
		}
 
		$notifications_to_send[] = $notification['id'];
	}
 
	GFCommon::send_notifications( $notifications_to_send, $form, $entry, true, $event );
}