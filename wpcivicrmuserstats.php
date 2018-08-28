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
    if ( ! $current_userID ) { return; }
    
    // check for stat and call desired function
    $stat = ! empty( $instance['stat'] ) ? $instance['stat'] : '';
    switch ($stat){
      case 'org_registrations':
        $content = wpcivicrmuserstats_widget_data($instance);
      break;
      default:
        $content = '';
      break;      
    }
    echo  $content;
  }

	// output the option form field in admin Widgets screen
	public function form( $instance ) {
    $stat = ! empty( $instance['stat'] ) ? $instance['stat'] : 'org_registrations'; ?>
    <p>
      <input type="hidden" name="<?php echo $this->get_field_name( 'stat' ); ?>" value="<?php echo esc_attr( $stat ); ?>" />
    </p>
    
    <?php
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
        <select class='widefat' id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>" type="text">
          <option value='text'<?php echo ($style=='text')?'selected':''; ?>>
            Text
          </option>
          <option value='graphic'<?php echo ($style=='graphic')?'selected':''; ?>>
            Graphic
          </option> 
        </select>                
    </p>
    <?php 
      $width = ! empty( $instance['width'] ) ? $instance['width'] : ''; ?>
      <p>
        <label for="<?php echo $this->get_field_id( 'width' ); ?>">Width:</label>
        <input type="text" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" value="<?php echo esc_attr( $width ); ?>" />px
      </p>
    <?php
      $height = ! empty( $instance['height'] ) ? $instance['height'] : ''; ?>
      <p>
        <label for="<?php echo $this->get_field_id( 'height' ); ?>">Height:</label>
        <input type="text" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" value="<?php echo esc_attr( $height ); ?>" />px
      </p>
    <?php }

	// save options
	public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance[ 'stat' ] = strip_tags( $new_instance[ 'stat' ] );
    $instance[ 'event_type_id' ] = strip_tags( $new_instance[ 'event_type_id' ] );
    $instance[ 'event_year' ] = strip_tags( $new_instance[ 'event_year' ] );
    $instance[ 'style' ] = strip_tags( $new_instance[ 'style' ] );
    $instance[ 'width' ] = strip_tags( $new_instance[ 'width' ] );
    $instance[ 'height' ] = strip_tags( $new_instance[ 'height' ] );
    return $instance;
  }
}

add_shortcode( 'wpcivicrmuserstats_widget', 'wpcivicrmuserstats_widget_shortcode' );

//callback function for wpcivicrmuserstats_widget shortcode
function wpcivicrmuserstats_widget_shortcode( $atts ) {

  // Configure defaults and extract the attributes into variables
  extract( shortcode_atts( 
    array( 
      'stat'  => 'wpcivicrmuserstats',
      'event_type_id'  => '',
      'event_year' => '',
      'style' => '',
      'width' => '',
      'height' => '',
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

//callback function for events id shortcode
function wpcivicrmuserstats_widget_data($instance) {
  if ( ! function_exists( 'civicrm_initialize' ) ) { return; }
  civicrm_initialize();
 //get current employer details when
  $current_userID = CRM_Core_Session::singleton()->getLoggedInContactID();
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
  $employees = implode(", ",$employeeID);
  
  $clauses = array();
  //where clause for contact id and participant status(registered or attended)
  $clauses[] = "civicrm_participant.contact_id IN ($employees)";
  $clauses[] = "civicrm_participant.status_id IN ( 1, 2 )";
  
  if ( !empty($instance['event_type_id']) ) {
    $event_id = $instance['event_type_id'];
    $clauses[] = "civicrm_event.id IN ($event_id)";
  }
  if ( !empty($instance['event_year']) ) {
    $date = $instance['event_year'];
    $clauses[] = "DATE_FORMAT(civicrm_event.start_date, '%Y') <= $date AND DATE_FORMAT(civicrm_event.end_date, '%Y') >= $date";
  }
  
  $whereClause = !empty($clauses) ? implode(' AND ', $clauses) : '(1)';
  $query = "SELECT civicrm_participant.id FROM civicrm_participant INNER JOIN civicrm_event ON civicrm_participant.event_id = civicrm_event.id WHERE $whereClause";
  // execute query
  $dao = CRM_Core_DAO::executeQuery($query);
  $count = $dao->N;
  if ( !empty($instance['style']) && $instance['style'] == 'graphic') {
    $block_width = '';
    $block_height = '';
    if(!empty($instance['width']) && !empty($instance['height'])){
      $block_width = $instance['width'];
      $block_height = $instance['height'];
    }
    $row_image_count = ceil($block_width/23);
    $total_row_printable = ceil($count/$row_image_count);
    $expected_rows_in_box = $block_height/50;
    if($total_row_printable > $expected_rows_in_box){
      $dynamic_width = floor($block_width/($total_row_printable+$row_image_count))-2;
      $image = '<img src="' . plugins_url( 'images/person.png', __FILE__ ) . '" height="35" width="'.$dynamic_width.'" > ';
    }else{
      $image = '<img src="' . plugins_url( 'images/person.png', __FILE__ ) . '" height="40" width="20" > ';
    }
    $result = '<div class="wpcivicrmuserstats-widget-data-content" style="width: '.$block_width.'px; height: '.$block_height.'px;" >';
  }else{
    $result = '<div class="wpcivicrmuserstats-widget-data-content">';
  }
  
  if ( !empty($instance['style']) && $instance['style'] == 'graphic') {
    for($i=1; $i<= $count; $i++){
      $result .= $image;
    }
  }
  else{
    $result .= $count;
  }
  $result .= '</div>';
  echo $result; 
}
