<?php
/*
Plugin Name: Civicrm User Stats
Plugin URI: https://github.com/lcdservices/wpcivicrmuserstats
Description: The Civicrm User Stats plugin displays number of users attached to event of organsisation for the current user.
Version: 1.2
Author: LCD Services
Author URI: http://www.lcdservices.biz/
*/

// register wpcivicrmuserstats_widget
add_action( 'widgets_init', function(){
	register_widget( 'wpcivicrmuserstats_Widget' );
});

/**
 * The widget class.
 */
class wpcivicrmuserstats_Widget extends WP_Widget {
	// class constructor
	public function __construct() {
    $widget_ops = array( 
      'classname' => 'wpcivicrmuserstats_Widget',
      'description' => 'A plugin to display Civicrm User Stats',
    );
    parent::__construct( 'wpcivicrmuserstats_widget', 'Civicrm User Stats', $widget_ops );
    
    $this->wpcivicrmConstruct();
  }
  
  public function wpcivicrmConstruct() {
		if ( ! function_exists( 'civicrm_initialize' ) ) { return; }
		civicrm_initialize();

		require_once 'CRM/Utils/System.php';
		$this->_civiversion = CRM_Utils_System::version();
	}
	
	// output the widget content on the front-end
	public function widget( $args, $instance ) {
    if ( ! function_exists( 'civicrm_initialize' ) ) { return; }
    //get current user ID
    $current_userID = CRM_Core_Session::singleton()->getLoggedInContactID();
     //get current employer details when
    $currentEmployer = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        (int) $current_userID,
        'employer_id'
    );
    $employee_list = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'employer_id' => $currentEmployer,
    ));
    $employeeID = array();
    if(!empty($employee_list['values']) ){
      foreach($employee_list['values'] as $data) {
        $employeeID[] = CRM_Utils_Array::value('contact_id', $data);
      }
    }
   
    //get event_type_id
    $event_id = array();
    if ( !empty($instance['event_type_id']) ) {
      $events = $instance['event_type_id'];
      $event_id = explode(",",$events);
    }
    else{
      $params = array(
        'sequential' => TRUE,
      );
      $allevents = civicrm_api3('Event', 'get', $params);
      if(!empty($allevents['values']) ){
        foreach($allevents['values'] as $data) {
          $event_id[] = CRM_Utils_Array::value('id', $data);
        }
      }   
    }
    
    //event_year
    $eventsID = array();
    $final_event_id = array();
    if ( !empty($instance['event_year']) ) {
      $date = $instance['event_year'];
      $event_start_date = $date."-01-01";
      $event_end_date = $date."-12-31";
      
      $events_params = array(
        'event_start_date' => array(
            'BETWEEN' => array(
              '0' => $event_start_date,
              '1' => $event_end_date,
            ), 
        )    
      );
      try{
        $eventsList = civicrm_api3('Event', 'get', $events_params);
      }
      catch (CiviCRM_API3_Exception $e) {
        // Handle error here.
        $errorMessage = $e->getMessage();
        $errorCode = $e->getErrorCode();
        $errorData = $e->getExtraParams();
        return array(
          'is_error' => 1,
          'error_message' => $errorMessage,
          'error_code' => $errorCode,
          'error_data' => $errorData,
        );
      }
      
      if(!empty($eventsList['values']) ){
        foreach($eventsList['values'] as $data) {
          $eventsID[] = CRM_Utils_Array::value('id', $data);
        }
      }      
    }
    if( !empty($event_id) && !empty($eventsID) ){
      $final_event_id = array_intersect($event_id, $eventsID);
    }
    else if( !empty($event_id) && empty($eventsID) ){
      $final_event_id = $event_id;
    }
    $params = array(
      'contact_id' => $employeeID,
      'participant_status_id' => array( 1, 2 ),
      'event_id' => $final_event_id,
    );
    try{
      $result = civicrm_api3('Participant', 'getcount', $params);
    }
    catch (CiviCRM_API3_Exception $e) {
      // Handle error here.
      $errorMessage = $e->getMessage();
      $errorCode = $e->getErrorCode();
      $errorData = $e->getExtraParams();
      return array(
        'is_error' => 1,
        'error_message' => $errorMessage,
        'error_code' => $errorCode,
        'error_data' => $errorData,
      );
    }
    echo 'user stats '.$result; 
  }

	// output the option form field in admin Widgets screen
	public function form( $instance ) {
    $event_type_id = ! empty( $instance['event_type_id'] ) ? $instance['event_type_id'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'event_type_id' ); ?>">Event ID(with comma separated list):</label>
      <input type="text" id="<?php echo $this->get_field_id( 'event_type_id' ); ?>" name="<?php echo $this->get_field_name( 'event_type_id' ); ?>" value="<?php echo esc_attr( $event_type_id ); ?>" />
    </p>
    
    <?php 
    $event_year = ! empty( $instance['event_year'] ) ? $instance['event_year'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'event_year' ); ?>">Event Year:</label>
      <input type="text" id="<?php echo $this->get_field_id( 'event_year' ); ?>" name="<?php echo $this->get_field_name( 'event_year' ); ?>" value="<?php echo esc_attr( $event_year ); ?>" />
    </p>
    
    <?php 
    $style = ! empty( $instance['style'] ) ? $instance['style'] : ''; ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'style' ); ?>">Stats Style:</label>
      <input type="text" id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>" value="<?php echo esc_attr( $style ); ?>" />
    </p><?php 
  }

	// save options
	public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance[ 'event_type_id' ] = strip_tags( $new_instance[ 'event_type_id' ] );
    $instance[ 'event_year' ] = strip_tags( $new_instance[ 'event_year' ] );
    $instance[ 'style' ] = strip_tags( $new_instance[ 'style' ] );
    return $instance;
  }
}

add_shortcode( 'wpcivicrmuserstats_widget', 'wpcivicrmuserstats_widget_shortcode' );

function wpcivicrmuserstats_widget_shortcode( $atts ) {

  // Configure defaults and extract the attributes into variables
  extract( shortcode_atts( 
      array( 
          'stat'  => '',
          'event_type_id'  => '',
          'event_year' => '',
          'style' => '',
      ), 
      $atts 
  ));

  $args = array(
      'before_widget' => '<div class="box widget">',
      'after_widget'  => '</div>',
      'before_title'  => '<div class="widget-title">',
      'after_title'   => '</div>',
  );
  ob_start();
  the_widget('wpcivicrmuserstats_Widget', $atts, $args ); 
  $output = ob_get_clean();

  return $output;
}