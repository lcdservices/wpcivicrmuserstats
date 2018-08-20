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
    $employee_list = array();
    $employee_list[] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        (int) $currentEmployer,
        'id', 
        'employer_id'
    );
    $employees = implode(", ",$employee_list);
    $clauses = array();
    //where clause for contact id and participant status(regsitered or attended)
    $clauses[] = "civicrm_participant.contact_id IN ($employees)";
    $clauses[] = "civicrm_participant.status_id IN ( 1, 2 )";
    
    if ( isset($instance['event_type_id']) ) {
      $event_id = $instance['event_type_id'];
      $clauses[] = "civicrm_event.id IN ($event_id)";
    }
    if ( isset($instance['event_year']) ) {
      $date = $instance['event_year'];
      $clauses[] = "DATE_FORMAT(civicrm_event.start_date, '%Y') <= $date AND DATE_FORMAT(civicrm_event.end_date, '%Y') >= $date";
    }
    
    $whereClause = !empty($clauses) ? implode(' AND ', $clauses) : '(1)';
    $query = "SELECT civicrm_participant.id FROM civicrm_participant INNER JOIN civicrm_event ON civicrm_participant.event_id = civicrm_event.id WHERE $whereClause";
    // execute query
    $dao = CRM_Core_DAO::executeQuery($query);
    $count = $dao->N;
    echo 'user stats '.$count; 
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
    </p><?php 
  }

	// save options
	public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance[ 'event_type_id' ] = strip_tags( $new_instance[ 'event_type_id' ] );
    $instance[ 'event_year' ] = strip_tags( $new_instance[ 'event_year' ] );
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