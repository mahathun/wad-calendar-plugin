<?php

add_action( 'admin_enqueue_scripts', 'jscal_load_scripts' );
function jscal_load_scripts() {	
	wp_enqueue_script( 'jquery' );
	wp_enqueue_style( 'jscal', plugins_url('css/jscal.css',__FILE__));
	wp_enqueue_style( 'jquery-ui', plugins_url('css/jquery-ui.css',__FILE__));
	wp_enqueue_script( 'jquery-ui-datepicker');
	wp_enqueue_script( 'jquery_validate',plugins_url('js/jquery.validate.js',__FILE__) );
	wp_enqueue_script( 'jquery_forms',plugins_url('js/jquery.form.js',__FILE__) );
	wp_enqueue_script( 'json2' ); //required for AJAX to work with JSON	
}

//======Dan - Function to manage settings ======
function jscal_manage_settings() {
	global $wpdb, $current_user;

	echo  '
		<div id="msg" style="overflow: auto"></div>
		<div class="wrap">
			<h2>Settings</h2>
			<div style="clear: both"></div><hr>
	';
	

	$users_table = $wpdb->prefix . 'js_users';

	$user_id = $current_user->ID;
	$query = "SELECT default_view FROM $users_table WHERE user_id=$user_id";


	
	if(isset($_REQUEST["settingsSubmit"])){
		$settings_list = $wpdb->get_results($query);
		//pr($settings_list);

		if(!empty($settings_list) && isset($_REQUEST['default_view'])){
			//update the table
			$default_view = $_REQUEST['default_view'];
			$wpdb->query($wpdb->prepare("UPDATE $users_table SET default_view='%d' WHERE user_id=$user_id",$default_view));

		}else if(empty($settings_list) && isset($_REQUEST['default_view'])){
			//insert to the table
			$default_view = $_REQUEST['default_view'];
			$wpdb->query($wpdb->prepare("INSERT INTO $users_table
									 	( user_id, default_view)
										VALUES ( %d, %d )"
									 	,array($user_id,$default_view)));

		}else{
			echo "Something went wrong";
		}

	}	


	$settings_list = $wpdb->get_results($query);
	//echo $settings_list[0]->default_view;
	if(!empty($settings_list)){
		//displaying the currently selected value
		show_settings($settings_list[0]->default_view);
	}else{
		//if there isnt a record
		show_settings(null);
	}

	echo '</div>';
}


function show_settings($default){
	if(isset($_REQUEST["settingsSubmit"])){
		echo '<div id="message" class="updated notice notice-success is-dismissible below-h2"><p>Your Changes were updated successfully.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	}
	?>
	
	<form name="jscal_form_settings" id="jscal_form_settings" method="post" action="?page=manage_settings">
		<table class="form-table">
			<tbody>
				<tr>
					<td scope="row"><label for="default_view">Default View</label></td>
					<td>
						<select id="default_view" name="default_view">
							
							<option value="0" <?php echo (isset($default) && $default==0)? "selected":"" ?>>Month View</option>
							<option value="1" <?php echo (isset($default) && $default==1)? "selected":"" ?>>Week View</option>
							<option value="2" <?php echo (isset($default) && $default==2)? "selected":"" ?>>List View</option>
						</select>
					</td>
				</tr>
				
				<tr>
					<td>
						
					</td>
				</tr>
			</tbody>
		</table>
		<input type="submit" name="settingsSubmit" value="Save" class="button-primary" />
	</form>
	
	<?php

}

// ===== Dan -  Function to manage the events ends=====
function jscal_manage_events() {
	echo  '
		<div id="msg" style="overflow: auto"></div>
		<div class="wrap">
			<h2>Manage Events <a href="?page=manage_events&command=new" class="add-new-h2">Add New</a></h2>
			<div style="clear: both"></div><hr>
	';
		
	$data = $_POST;

	if (isset($_REQUEST['id'])) {
		$id = $_REQUEST['id']; 
		//$id = trim($id); 
	} else {
		$id = '';
	}
	if (isset($_REQUEST["command"])) {
		$command = $_REQUEST["command"];
		//$command = trim($command); 
		//$command = substr($command, 0, 6);
	} else {
		$command = '';
	}
		
    switch ($command) {
		case 'view':
			jscal_event_view($id);
		break;		
		case 'edit':
			$msg = jscal_event_form('update', $id);
		break;
		case 'new':
			$msg = jscal_event_form('insert',null);
		break;
		case 'delete':
			$msg = jscal_event_delete($id); 
			$command = '';
		break;
		case 'update':
			$msg = jscal_event_update($data);
			$command = '';
		break;
		case 'insert':	
			$msg = jscal_event_insert($data);
			$command = '';
		break;
	}
	
// If there's no command, we'll just list the events
	if (empty($command)) jscal_event_list();

// Messages go here
	if (!empty($msg)) {
      echo $msg;      
	}
	echo '</div>';
}

// Insert a new Venue
function jscal_event_insert($data) {
    global $wpdb, $current_user;
	$event_table = $wpdb->prefix . 'js_events';
	$category_table = $wpdb->prefix . 'js_categories';
	$venue_table = $wpdb->prefix . 'js_venues';
	
	$event_name = trim($data['event_name']); // Removes white space from either end of input.
	$event_name = filter_var($event_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	$event_start = trim($data['event_start']); // Removes white space from either end of input.
	
	$event_finish = trim($data['event_finish']); // Removes white space from either end of input.
	
	$event_recurring = trim($data['event_recurring']); // Removes white space from either end of input.
	$event_recurring = filter_var($event_recurring, FILTER_SANITIZE_NUMBER_INT); // Applies a sanitization filter to strip tags.
	
	$event_category_id = trim($data['event_category_id']); // Removes white space from either end of input.
	$event_category_id = filter_var($event_category_id, FILTER_SANITIZE_NUMBER_INT); // Applies a sanitization filter to strip tags.
	
	$event_location_id = trim($data['event_location_id']); // Removes white space from either end of input.
	$event_location_id = filter_var($event_location_id, FILTER_SANITIZE_NUMBER_INT); // Applies a sanitization filter to strip tags.
	
	$event_description = trim($data['event_description']); // Removes white space from either end of input.
	$event_description = filter_var($event_description, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	$event_status = trim($data['event_status']); // Removes white space from either end of input.
	$event_status = filter_var($event_status, FILTER_SANITIZE_NUMBER_INT); // Applies a sanitization filter to strip tags.
	
	if($event_status > 1 || $event_status < 0) {
		$event_status = 0;
	} 
	
	// A little data validation. Check if all fields contains something.
	if(($event_name == "") || ($event_start == "") || ($event_finish == "") || ($event_location_id == "")) { 
		$msg = "Something went wrong. Please try again."; 
		return $msg; 			
	// If they do, carry on...
	} else {			
		if(!empty($data['category_name'])) {
			$category_name = trim($data['category_name']); // Removes white space from either end of input.
			$category_name = filter_var($category_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
			$wpdb->insert($category_table,
				array( 'category_name' => $category_name,
					   'category_author' => $current_user->ID,
					   'category_status' => 1),
				array( '%s', '%d', '%d') );
				$event_category_id = $wpdb->insert_id;
		}
		$wpdb->insert($event_table,
			  array( 'event_name' => $event_name,
				     'event_start' => date('Y-m-d', strtotime($event_start)),
				     'event_finish' => date('Y-m-d', strtotime($event_finish)),
				     'event_recurring' => $event_recurring,
				     'event_category_id' => $event_category_id,
				     'event_location_id' => $event_location_id,
				     'event_description' => $event_description,
					 'event_organizer_id' => $current_user->ID,
					 'event_status' => $event_status),
			  array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%d') );
			  $event_id = $wpdb->insert_id;
		$msg = "A new event has been added.";
		return $msg;
		$user_info = get_userdata($current_user->ID);
		$email_subject = $user_info->user_login . ' has added a new event';
		$content = $user_info->user_login . ' has added an event! Here are the details:\n\n
		Event Name: ' . $event_name . 
		'\nEvent Start Date: ' . $event_start . 
		'\nEvent Finish Date: ' . $event_finish . 
		'\n\nYou can view the rest of the details here: <a href="'.get_admin_url().'/admin.php?page=manage_events&id='.$event_id.'&command=view">' . $event_name . '</a>';
		$admin_email = get_option( 'admin_email' );
		jscal_send_email($admin_email, $email_subject, $content);
	}
}

// Updates an existing event
function jscal_event_update($data) {
    global $wpdb, $current_user;	
	$event_table = $wpdb->prefix . 'js_events';
	
	$event_name = trim($data['event_name']); // Removes white space from either end of input.
	$event_name = filter_var($event_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	$event_start = trim($data['event_start']); // Removes white space from either end of input.
	
	$event_finish = trim($data['event_finish']); // Removes white space from either end of input.
	
	$event_recurring = trim($data['event_recurring']); // Removes white space from either end of input.
	$event_recurring = filter_var($event_recurring, FILTER_SANITIZE_NUMBER_INT); // Applies a sanitization filter to strip tags.
	
	$event_category_id = trim($data['event_category_id']); // Removes white space from either end of input.
	$event_category_id = filter_var($event_category_id, FILTER_SANITIZE_NUMBER_INT); // Applies a sanitization filter to strip tags.
	
	$event_location_id = trim($data['event_location_id']); // Removes white space from either end of input.
	$event_location_id = filter_var($event_location_id, FILTER_SANITIZE_NUMBER_INT); // Applies a sanitization filter to strip tags.
	
	$event_description = trim($data['event_description']); // Removes white space from either end of input.
	$event_description = wp_kses_post($event_description);
	
	// A little data validation. Check if all fields contains something.
	if(($event_name == "") || ($event_start == "") || ($event_finish == "") || ($event_recurring == "") || ($event_category_id == "") || ($event_location_id == "")) {  
		$msg = "All fields are required. Please try again."; 
		return $msg; 
	// If they do, carry on...
	} else {		
		if(!empty($data['category_name'])) {
		$category_name = trim($data['category_name']); // Removes white space from either end of input.
		$category_name = filter_var($category_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
		$wpdb->insert($category_table,
			  array( 'category_name' => $category_name,
				     'category_author' => $current_user->ID,
				     'category_status' => 1),
			  array( '%s', '%d', '%d') );
			 $event_category_id = $wpdb->insert_id;
	}
	
		$wpdb->update($event_table,
			  array( 'event_name' => $event_name,
				     'event_start' => date('Y-m-d', strtotime($event_start)),
				     'event_finish' => date('Y-m-d', strtotime($event_finish)),
				     'event_recurring' => $event_recurring,
				     'event_category_id' => $event_category_id,
				     'event_location_id' => $event_location_id,
				     'event_description' => $event_description,
					 'event_organizer_id' => $current_user->ID),
			  array( 'event_id' => $data['event_id']));
		$msg = "Event ID# ".$data['event_id']." has been updated";
		return $msg;
	}
}

// Deletes an event
function jscal_event_delete($id) {
	global $wpdb;
	$event_table = $wpdb->prefix . 'js_events';
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	
	$results = $wpdb->query($wpdb->prepare("DELETE FROM $event_table WHERE event_id=%s",$id));
	if ($results) {
		$msg = "Event ID# $id was deleted.";
	}
	return $msg;
}

// Lists the events
function jscal_event_list() {
	global $wpdb, $current_user;

	// Retrieve events from database
	$event_table = $wpdb->prefix . 'js_events';
	$category_table = $wpdb->prefix . 'js_categories';
	$query = "SELECT $event_table.event_id, $event_table.event_name, $event_table.event_start, $event_table.event_finish, $event_table.event_recurring, $category_table.category_name, $event_table.event_location_id, $event_table.event_description, $event_table.event_organizer_id, $event_table.event_status FROM $event_table INNER JOIN $category_table ON $event_table.event_category_id=$category_table.category_id ORDER BY event_start DESC";
	$event_list = $wpdb->get_results($query);
   
	// Build the table
	echo '<table class="wp-list-table widefat">
		<thead>
		<tr>
			<th scope="col" class="manage-column">Event Name</th>
			<th scope="col" class="manage-column">Start</th>
			<th scope="col" class="manage-column">End</th>
			<th scope="col" class="manage-column">Recurs Every</th>
			<th scope="col" class="manage-column">Category</th>
			<th scope="col" class="manage-column">Organizer</th>
			<th scope="col" class="manage-column">Status</th>
		</tr>
		</thead>
		<tbody>';
	
	// Run through each venue
	foreach ($event_list as $event) {
		$user_info = get_userdata($event->event_organizer_id);
		
		if($current_user->ID == $user_info->ID || current_user_can('manage_options')) {
	   
		// Set up the command links
		$edit_link = '?page=manage_events&id=' . $event->event_id . '&command=edit';
		$view_link ='?page=manage_events&id=' . $event->event_id . '&command=view';
		$delete_link = '?page=manage_events&id=' . $event->event_id . '&command=delete';
	   
		echo '<tr>';
		echo '<td><strong><a href="'.$edit_link.'" title="Edit Event">' . $event->event_name . '</a></strong>';
		echo '<div class="row-actions">';
		echo '<span class="edit"><a href="'.$edit_link.'" title="Edit this event">Edit</a></span> | ';
		echo '<span class="view"><a href="'.$view_link.'" title="View this event">View</a></span> | ';
		echo '<span class="trash"><a href="'.$delete_link.'" title="Delete this event" onclick="return doDelete();">Trash</a></span>';
		echo '</div>';
		echo '</td>';
		echo '<td>' . date('d/m/y', strtotime($event->event_start)) . '</td>';
		echo '<td>' . date('d/m/y', strtotime($event->event_finish)) . '</td>';
		echo '<td>' . $event->event_recurring . ' days</td>';
		echo '<td>' . $event->category_name . '</td>';
		echo '<td>' . $user_info->user_login . '</td>';
		$status = array('Draft', 'Published');
		echo '<td>' . $status[$event->event_status] . '</td></tr>';
		
		}
    }
	echo '</tbody></table>';
	
	echo "
		<script type='text/javascript'>
			function doDelete() { if (!confirm('Are you sure?')) return false; }
		</script>
	";
}

// View an event
function jscal_event_view($id) {
	global $wpdb, $current_user;
	$delete_link = '?page=manage_events&id=' . $id . '&command=delete';
	$event_table = $wpdb->prefix . 'js_events';
	$category_table = $wpdb->prefix . 'js_categories';
	$venue_table = $wpdb->prefix . 'js_venues';
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	if (!filter_var($id, FILTER_VALIDATE_INT) === false) {	
		$qry = $wpdb->prepare("SELECT $event_table.event_id, $event_table.event_name, $event_table.event_start, $event_table.event_finish, $event_table.event_recurring, $category_table.category_name, $venue_table.venue_name, $venue_table.venue_location, $event_table.event_description, $event_table.event_organizer_id, $event_table.event_status FROM $event_table INNER JOIN $category_table ON $event_table.event_category_id=$category_table.category_id INNER JOIN $venue_table ON $event_table.event_location_id=$venue_table.venue_id WHERE event_id = %s",$id);
		$row = $wpdb->get_row($qry);	
		$user_info = get_userdata($row->event_organizer_id);
		if($current_user->ID == $row->event_organizer_id || current_user_can('manage_options')) {
			echo '<h3>Viewing Event</h3>
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="event_name">Event Name</label></th>
						<td>'.$row->event_name.'</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_start">Start Date</label></th>
						<td>'.date('l, j F, Y', strtotime($row->event_start)).'</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_finish">Finish Date</label></th>
						<td>'.date('l, j F, Y', strtotime($row->event_finish)).'</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_recurring">Recurs Every</label></th>
						<td>'.$row->event_recurring.' days</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_category_id">Category</label></th>
						<td>'.$row->category_name.'</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_location_id">Location</label></th>
						<td>'.$row->venue_name . " - " . $row->venue_location.'</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_description">Description</label></th>
						<td>'.$row->event_description.'</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_organizer">Organized By</label></th>
						<td>'.$user_info->user_login.'</td>
					</tr>
					<tr>
						<th scope="row"><label for="event_status">Event Status</label></th>
						<td>';
							$status = array('Draft', 'Published');
							echo $status[$row->event_status];
						echo '</td>
					</tr>
				</tbody>
			</table>
			';
			echo '<a href="'.$delete_link.'" title="Delete this event" onclick="return doDelete();">Delete Event</a>';
			echo '<p><a href="?page=manage_events">&laquo; Back to Events</p>';
		} else {
			echo 'Something went wrong.<p><a href="?page=manage_events" class="button-primary">&laquo; Back to Events</a>';
		}
	} else {
		echo 'Something went wrong.<p><a href="?page=manage_events" class="button-primary">&laquo; Back to Events</a>';
	}
	echo "
		<script type='text/javascript'>
			function doDelete() { if (!confirm('Are you sure?')) return false; }
		</script>
	";
}



// Displays the form for editing/inserting an event
function jscal_event_form($command, $id = null) {
    global $wpdb, $current_user;
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	if ($id == 0 || !filter_var($id, FILTER_VALIDATE_INT) === false) {	
	
	$can_edit = '0';
	
	// Sets default values if command is insert
    if ($command == 'insert') {
		$event_header = 'Add New Event';
		$can_edit = '1';
    }
	
	if ($command == 'update') {
		$event_table = $wpdb->prefix . 'js_events';
		$category_table = $wpdb->prefix . 'js_categories';
		$venue_table = $wpdb->prefix . 'js_venues';
        $event = $wpdb->prepare("SELECT $event_table.event_id, $event_table.event_name, $event_table.event_start, $event_table.event_finish, $event_table.event_recurring, $category_table.category_id, $category_table.category_name, $venue_table.venue_id, $venue_table.venue_name, $venue_table.venue_location, $event_table.event_description, $event_table.event_organizer_id, $event_table.event_status FROM $event_table INNER JOIN $category_table ON $event_table.event_category_id=$category_table.category_id INNER JOIN $venue_table ON $event_table.event_location_id=$venue_table.venue_id WHERE event_id = %s",$id);
		$row = $wpdb->get_row($event);
		$event_header = 'Update Event';		
	}	
		if($command == 'update' && ($current_user->ID == $row->event_organizer_id || current_user_can('manage_options'))) {
			$can_edit = '1';
		}
		$category_table = $wpdb->prefix . 'js_categories';
		$venue_table = $wpdb->prefix . 'js_venues';
		$content = $row->event_description;
		$editor_id = "asdasdsdgadsfas";
		echo '<h3>'.$event_header.'</h3>';
    
	if($can_edit == '1') {

?>
	<script>
function checkAvailability() {
jQuery.ajax({
url: "?action=A",
data:'category_name='+jQuery("#category_name").val(),
success:function(data){
jQuery("#user-availability-status").val(data);
},
error:function (){}
});
}

	jQuery(document).ready(function() {
		jQuery( "#category_name").hide();
		jQuery("#jscal_form").validate({
            rules: {
                event_name: {           //input name: fullName
                    required: true,   //required boolean: true/false
                    minlength: 3,       
                },
                event_start: {              //input name: email
                    required: true,   //required boolean: true/false
					date: true,
                },
                event_finish: {            //input name: some random one liner
                    required: true,   //required boolean: true/false
					date: true
                },
                event_category_id: {            //input name: multi line/textarea
                    required: true,
                },
                event_location_id: {            //input name: multi line/textarea
                    required: true,
                }
            },
            messages: {               //messages to appear on error
                event_name: {
                      required:" Event name is required",
                      minlength:" Enter a longer name please."
                      },               
                event_start: {
                      required:" Start date is required",
					  date: " Please enter a date"
                      },             
                event_finish: {
                      required:" End date is required"
                      },               
                event_category_id: {
                      required:" Event Category is required"
                      },             
                event_location_id: {
                      required:" Event location is required"
                      }
            },
            submitHandler: function(form) {
			form.submit();
            }

        });  
	});
	function jQueryMethod() {
		jQuery( "#category_name").show();
	};
	jQuery(function() {
		jQuery( "#start_date" ).datepicker({
			changeMonth: true,
			changeYear: true,
			dateFormat: "DD, d MM, yy",
			minDate: 0,
			// Make sure people can't select greater than the end date
			onSelect: function(selected) {
				jQuery("#finish_date").datepicker("option","minDate", selected)
			}
		});
		jQuery( "#finish_date" ).datepicker({
			changeMonth: true,
			changeYear: true,
			dateFormat: "DD, d MM, yy",
			minDate: 0,
			// Make sure people can't select less than the start date
			onSelect: function(selected) {
				jQuery("#start_date").datepicker("option","maxDate", selected)
			}
		});
	});
  </script>
  <?php
	echo '<form name="jscal_form" id="jscal_form" method="post" action="?page=manage_events">
	<input type="hidden" name="event_id" value="'.$id.'"/>
	<input type="hidden" name="command" value="'.$command.'"/>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="event_name">Event Name*</label></th>
					<td><input type="text" name="event_name" value="'.$row->event_name.'" size="20" class="regular-text"/></td>
				</tr>
				<tr>
					<th scope="row"><label for="event_start">Start Date*</label></th>
					<td><input type="text" name="event_start" style="cursor:pointer" onfocus="this.blur();" value="'; if($row->event_start=="") { echo ""; } else { echo date('l, j F, Y', strtotime($row->event_start));} echo '" size="20" class="regular-text" id="start_date"/></td>
				</tr>
				<tr>
					<th scope="row"><label for="event_finish">Finish Date*</label></th>
					<td><input type="text" name="event_finish" style="cursor:pointer" onfocus="this.blur();" value="'; if($row->event_finish=="") { echo ""; } else { echo date('l, j F, Y', strtotime($row->event_finish));} echo '" size="20" class="regular-text" id="finish_date"/></td>
				</tr>
				<tr>
					<th scope="row"><label for="event_recurring">Recurs Every</label></th>
					<td><input type="number" step="1" min ="0" name="event_recurring" value="'.$row->event_recurring.'" size="20" class="small-text"/> days</td>
				</tr>
				<tr>
					<th scope="row"><label for="event_category_id">Category*</label></th>
					<td><select name="event_category_id" onchange="jQueryMethod()"><option value="">Select Category</option>';
						$list_cats = $wpdb->get_results("SELECT category_id, category_name FROM $category_table");
 
						foreach ($list_cats as $list_cat)
						{	
							echo "<option value='" . $list_cat->category_id . "'>" . $list_cat->category_name . "</option>";
						}
						echo '
						<option value="add_new">+ Add New</option></select>';?>
						<input type="text" name="category_name" onBlur="checkAvailability()" id="category_name"/><span id="user-availability-status"></span>  
						<?php echo '
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="event_location_id">Location*</label></th>
					<td><select name="event_location_id"><option value="">Select Location</option>';
						$list_vens = $wpdb->get_results("SELECT venue_id, venue_name FROM $venue_table");
						foreach ($list_vens as $list_ven)
						{
							echo "<option value='" . $list_ven->venue_id . "'>" . $list_ven->venue_name . "</option>";
						}
						echo '</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="event_description">Description</label></th>
					<td>';
						wp_editor( $row->event_description, 'event_description', $settings = array( 'tinymce' => false, 'media_buttons' => false, 'textarea_name' => event_description, 'textarea_rows' => 7) );
						echo '
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="event_status">Status</label></th>
					<td><input type="radio" name="event_status" value="0"'; if($row->event_status == 0) { echo 'checked'; } echo '>Draft <input type="radio" name="event_status" value="1"'; if($row->event_status == 1) { echo 'checked'; } echo '>Published</td>
				</tr>
				<tr>
					<th scope="row">* field required</th>
					<td><p class="submit"><input type="submit" name="Submit" value="'; if($command=='insert') { echo 'Add New Event'; } else { echo 'Update Event';} echo '" class="button-primary" /></td>
				</tr>
			</tbody>
		</table>
	</form>
			';
	echo '<p><a href="?page=manage_events">&laquo; Back to Events</a></p>';
} else {
		echo 'Something went wrong.<p><a href="?page=manage_events">&laquo; Back to Events</a>';
	}
} else {
		echo 'Something went wrong.<p><a href="?page=manage_events">&laquo; Back to Events</a>';
	}	
}

// Function to send event emails
function jscal_send_email($to,$subject,$content) {
	$headers = 'From: Jamie <lordtopcat@gmail.com>' . "\r\n";
	wp_mail($to, $subject, $content, $headers);
}

// ===== Function to manage the venues =====
function jscal_manage_venues() {
	echo  '
		<div id="msg" style="overflow: auto"></div>
		<div class="wrap">
			<h2>Manage Venues <a href="?page=manage_venues&command=new" class="add-new-h2">Add New</a></h2>
			<div style="clear: both"></div>
	';
		
	$data = $_POST;

	if (isset($_REQUEST['id'])) 
		$id = $_REQUEST['id']; 
	else 
		$id = '';

		if (isset($_REQUEST["command"])) 
		$command = $_REQUEST["command"]; 
	else 
		$command = '';
			
    switch ($command) {
		case 'view':
			jscal_ven_view($id);
		break;		
		case 'edit':
			$msg = jscal_ven_form('update', $id);
		break;
		case 'new':
			$msg = jscal_ven_form('insert',null);
		break;
		case 'delete':
			$msg = jscal_ven_delete($id); 
			$command = '';
		break;
		case 'update':
			$msg = jscal_ven_update($data);
			$command = '';
		break;
		case 'insert':	
			$msg = jscal_ven_insert($data);
			$command = '';
		break;
	}
	
// If there's no command, we'll just list the categories
	if (empty($command)) jscal_ven_list();

// Messages go here
	if (!empty($msg)) {
      echo $msg;      
	}
	echo '</div>';
}

// Insert a new Venue
function jscal_ven_insert($data) {
    global $wpdb, $current_user;
	$venue_table = $wpdb->prefix . 'js_venues';
	
	$ven_name = trim($data['venue_name']); // Removes white space from either end of input.
	$ven_name = filter_var($ven_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	$ven_location = trim($data['venue_location']); // Removes white space from either end of input.
	$ven_location = filter_var($ven_location, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	// A little data validation. Check if all fields contains something.
	if(($ven_name == "") || ($ven_location == "")) { 
		$msg = "All fields are required. Please try again."; 
		return $msg; 
		
	// If they do, carry on...
	} else {
		$wpdb->insert($venue_table,
			  array( 'venue_name' => $ven_name,
				     'venue_location' => $ven_location,
					 'venue_author' => $current_user->ID),
			  array( '%s', '%s', '%d') );
		$msg = "A new venue has been added.";
		return $msg;
	}
}

// Updates an existing venue
function jscal_ven_update($data) {
    global $wpdb, $current_user;	
	$venue_table = $wpdb->prefix . 'js_venues';
	
	$ven_name = trim($data['venue_name']); // Removes white space from either end of input.
	$ven_name = filter_var($ven_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	$ven_location = trim($data['venue_location']); // Removes white space from either end of input.
	$ven_location = filter_var($ven_location, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	// A little data validation. Check if all fields contains something.
	if(($ven_name == "") || ($ven_location == "")) { 
		$msg = "All fields are required. Please try again."; 
		return $msg; 
		
	// If they do, carry on...
	} else {
		$wpdb->update($venue_table,
			  array( 'venue_name' => $ven_name,
					 'venue_location' => $ven_location),
			  array( 'venue_id' => $data['ven_id']));
		$msg = "Venue ID# ".$data['ven_id']." has been updated";
		return $msg;
	}
}

// Deletes a venue
function jscal_ven_delete($id) {
	global $wpdb;
	$venue_table = $wpdb->prefix . 'js_venues';
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	
	$results = $wpdb->query($wpdb->prepare("DELETE FROM $venue_table WHERE venue_id=%s",$id));
	if ($results) {
		$msg = "Venue ID# $id was deleted.";
	}
	return $msg;
}

// Lists the venues
function jscal_ven_list() {
	global $wpdb, $current_user;

	// Retrieve venues from database
	$venue_table = $wpdb->prefix . 'js_venues';
	$query = "SELECT venue_id, venue_name, venue_location, venue_author FROM $venue_table ORDER BY venue_name DESC";
	$ven_list = $wpdb->get_results($query);
   
	// Build the table
	echo '<table class="wp-list-table widefat">
		<thead>
		<tr>
			<th scope="col" class="manage-column">Venue Name</th>
			<th scope="col" class="manage-column">Venue Address</th>
			<th scope="col" class="manage-column">Venue Author</th>
		</tr>
		</thead>
		<tbody>';
	
	// Run through each venue
	foreach ($ven_list as $ven) {
		if ($ven->venue_author == 0) $ven->venue_author = $current_user->ID;
		$user_info = get_userdata($ven->venue_author);
	   
		// Set up the command links
		$edit_link = '?page=manage_venues&id=' . $ven->venue_id . '&command=edit';
		$view_link ='?page=manage_venues&id=' . $ven->venue_id . '&command=view';
		$delete_link = '?page=manage_venues&id=' . $ven->venue_id . '&command=delete';
	   
		echo '<tr>';
		echo '<td><strong><a href="'.$edit_link.'" title="Edit Venue">' . $ven->venue_name . '</a></strong>';
		echo '<div class="row-actions">';
		echo '<span class="edit"><a href="'.$edit_link.'" title="Edit this venue">Edit</a></span> | ';
		echo '<span class="view"><a href="'.$view_link.'" title="View this venue">View</a></span> | ';
		echo '<span class="trash"><a href="'.$delete_link.'" title="Delete this venue" onclick="return doDelete();">Trash</a></span>';
		echo '</div>';
		echo '</td>';
		echo '<td>' . $ven->venue_location . '</td>';
		echo '<td>' . $user_info->user_login . '</td></tr>';
    }
	echo '</tbody></table>';
	
	echo "
		<script type='text/javascript'>
			function doDelete() { if (!confirm('Are you sure?')) return false; }
		</script>
	";
}

// View the venue
function jscal_ven_view($id) {
	global $wpdb;
	$delete_link = '?page=manage_venues&id=' . $id . '&command=delete';
	$venues_table = $wpdb->prefix . 'js_venues';
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	if (!filter_var($id, FILTER_VALIDATE_INT) === false) {	
		$qry = $wpdb->prepare("SELECT * FROM $venues_table WHERE venue_id = %s",$id);
		$row = $wpdb->get_row($qry);	
		$user_info = get_userdata($row->venue_author);
		
		echo '<p>';
		echo "Venue Name: ";
		echo $row->venue_name;
		echo '<p>';
		echo "Author: ";
		echo $user_info->user_login;
		echo '<p><a href="'.$delete_link.'" title="Delete this venue" onclick="return doDelete();">Delete Venue</a></p>';
		echo '<p><a href="?page=manage_venues">&laquo; Back to Venues</p>';
	} else {
		echo 'Something went wrong.<p><a href="?page=manage_venues">&laquo; Back to Venues';
	}
	echo "
		<script type='text/javascript'>
			function doDelete() { if (!confirm('Are you sure?')) return false; }
		</script>
	";
}

// Displays the form for editing/inserting a venue
function jscal_ven_form($command, $id = null) {
    global $wpdb;
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	if ($id == 0 || !filter_var($id, FILTER_VALIDATE_INT) === false) {	
	
	// Sets default values if command is insert
    if ($command == 'insert') {
		$ven->venue_name = '';
		$ven->venue_location = '';
    }
	
	if ($command == 'update') {
		$venue_table = $wpdb->prefix . 'js_venues';
        $ven = $wpdb->prepare("SELECT * FROM $venue_table WHERE venue_id = %s",$id);
		$row = $wpdb->get_row($ven);
	}
?>
<script>
	jQuery(document).ready(function() {
		jQuery("#jscal_form").validate({
            rules: {
                venue_name: {           //input name: fullName
                    required: true,   //required boolean: true/false
                    minlength: 3,       
                },
                venue_location: {              //input name: email
                    required: true,   //required boolean: true/false
                }
            },
            messages: {               //messages to appear on error
                venue_name: {
                      required:" Location name is required",
                      minlength:" Enter a longer name please."
                      },               
                venue_location: {
                      required:" Location is required",
                      }
            },
            submitHandler: function(form) {
			form.submit();
            }

        });  
	});
	</script>
	<?php
    echo '
		<form name="jscal_form" id="jscal_form" method="post" action="?page=manage_venues">
		<input type="hidden" name="ven_id" value="'.$id.'"/>
		<input type="hidden" name="command" value="'.$command.'"/>

		<p>Venue Name* <input type="text" name="venue_name" value="'.$row->venue_name.'" size="20" class="large-text"/>
		<p>Venue Address*: <input type="text" name="venue_location" value="'.$row->venue_location.'" size="20" class="large-text"/>
		<p>* required field</p>
		<p class="submit"><input type="submit" name="Submit" value="Save Changes" class="button-primary" /></p>
		</form>
	';
	echo '<p><a href="?page=manage_venues">&laquo; Back to Venues</p>';
} else {
		echo 'Something went wrong.<p><a href="?page=manage_venues">&laquo; Back to Venues';
	}	
}

// ===== Function to manage the categories ====

function jscal_manage_categories() { 
	echo  '
		<div id="msg" style="overflow: auto"></div>
		<div class="wrap">
			<h2>Manage Categories <a href="?page=manage_categories&command=new" class="add-new-h2">Add New</a></h2>
			<div style="clear: both"></div>
	';
		
	$catdata = $_POST;

	if (isset($_REQUEST['id'])) 
		$id = $_REQUEST['id']; 
	else 
		$id = '';

		if (isset($_REQUEST["command"])) 
		$command = $_REQUEST["command"]; 
	else 
		$command = '';
			
    switch ($command) {
		case 'view':
			jscal_cat_view($id);
		break;		
		case 'edit':
			$msg = jscal_cat_form('update', $id);
		break;
		case 'new':
			$msg = jscal_cat_form('insert',null);
		break;
		case 'delete':
			$msg = jscal_cat_delete($id); 
			$command = '';
		break;
		case 'update':
			$msg = jscal_cat_update($catdata);
			$command = '';
		break;
		case 'insert':	
			$msg = jscal_cat_insert($catdata);
			$command = '';
		break;
	}
	
// If there's no command, we'll just list the categories
	if (empty($command)) jscal_cat_list();

// Messages go here
	if (!empty($msg)) {
      echo $msg;      
	}
	echo '</div>';
}

// Insert a new Category
function jscal_cat_insert($data) {
    global $wpdb, $current_user;
	$category_table = $wpdb->prefix . 'js_categories';
	
	$cat_name = trim($data['category_name']); // Removes white space from either end of input.
	$cat_name = filter_var($cat_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	// A little data validation. Check if Category name contains something.
	if($cat_name == "") { 
		$msg = "Category Name can not be blank! Please try again."; 
		return $msg; 
		
	// If it does, carry on...
	} else {
		$wpdb->insert($category_table,
			  array( 'category_name' => $cat_name,
					 'category_author' => $current_user->ID,
					 'category_status' => $data['status']),
			  array( '%s', '%d', '%d' ) );
		$msg = "A new category has been added.";
		return $msg;
	}
}

// Updates an existing category
function jscal_cat_update($data) {
    global $wpdb, $current_user;	
	$category_table = $wpdb->prefix . 'js_categories';
	
	$cat_name = trim($data['category_name']); // Removes white space from either end of input.
	$cat_name = filter_var($cat_name, FILTER_SANITIZE_STRING); // Applies a sanitization filter to strip tags.
	
	// A little data validation. Check if Category name contains something.
	if($cat_name == "") { 
		$msg = "Category Name can not be blank! Please try again.";
		return $msg; 
		
	// If it does, carry on...
	} else {
		$wpdb->update($category_table,
			  array( 'category_name' => $cat_name,
					 'category_status' => $data['status']),
			  array( 'category_id' => $data['cat_id']));
		$msg = "Category ID# ".$data['cat_id']." has been updated";
		return $msg;
	}
}

// Deletes a category
function jscal_cat_delete($id) {
	global $wpdb;
	$category_table = $wpdb->prefix . 'js_categories';
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	
	$results = $wpdb->query($wpdb->prepare("DELETE FROM $category_table WHERE category_id=%s",$id));
	if ($results) {
		$msg = "Category # $id was deleted.";
	}
	return $msg;
}

// Lists the categories
function jscal_cat_list() {
	global $wpdb, $current_user;

	// Retrieve categories from database
	$category_table = $wpdb->prefix . 'js_categories';
	$query = "SELECT category_id, category_name, category_author, category_status FROM $category_table ORDER BY category_name DESC";
	$cat_list = $wpdb->get_results($query);
   
	// Build the table
	echo '<table class="wp-list-table widefat">
		<thead>
		<tr>
			<th scope="col" class="manage-column">Category Name</th>
			<th scope="col" class="manage-column">Category Author</th>
			<th scope="col" class="manage-column">Category Status</th>
		</tr>
		</thead>
		<tbody>';
	
	// Run through each category
	foreach ($cat_list as $cat) {
		$user_info = get_userdata($cat->category_author);		
		if($current_user->ID == $user_info->ID || current_user_can('manage_options')) {
	   
		// Set up the command links
		$edit_link = '?page=manage_categories&id=' . $cat->category_id . '&command=edit';
		$view_link ='?page=manage_categories&id=' . $cat->category_id . '&command=view';
		$delete_link = '?page=manage_categories&id=' . $cat->category_id . '&command=delete';
	   
		echo '<tr>';
		echo '<td><strong><a href="'.$edit_link.'" title="Edit Category">' . $cat->category_name . '</a></strong>';
		echo '<div class="row-actions">';
		echo '<span class="edit"><a href="'.$edit_link.'" title="Edit this category">Edit</a></span> | ';
		echo '<span class="view"><a href="'.$view_link.'" title="View this category">View</a></span> | ';
		echo '<span class="trash"><a href="'.$delete_link.'" title="Delete this category" onclick="return doDelete();">Trash</a></span>';
		echo '</div>';
		echo '</td>';
		echo '<td>' . $user_info->user_login . '</td>';
   
		// Sets the status. Status is stored as an array. 0 being draft, 1 being published
		$status = array('Draft', 'Published');
		echo '<td>' . $status[$cat->category_status] . '</td></tr>';  
		}
    }
	echo '</tbody></table>';
	
	echo "
		<script type='text/javascript'>
			function doDelete() { if (!confirm('Are you sure?')) return false; }
		</script>
	";
}

// View the category
function jscal_cat_view($id) {
	global $wpdb;
	$delete_link = '?page=manage_categories&id=' . $id . '&command=delete';
	$category_table = $wpdb->prefix . 'js_categories';
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	if (!filter_var($id, FILTER_VALIDATE_INT) === false) {	
		$qry = $wpdb->prepare("SELECT * FROM $category_table WHERE category_id = %s",$id);
		$row = $wpdb->get_row($qry);	
		$user_info = get_userdata($row->category_author);
		
		echo '<p>';
		echo "Category Name: ";
		echo $row->category_name;
		echo '<p>';
		echo "Author: ";
		echo $user_info->user_login;
		echo '<p><a href="'.$delete_link.'" title="Delete this category" onclick="return doDelete();">Delete Category</a></p>';
		echo '<p><a href="?page=manage_categories">&laquo; Back to Categories</p>';
	} else {
		echo 'Something went wrong.<p><a href="?page=manage_categories">&laquo; Back to Categories';
	}
	echo "
		<script type='text/javascript'>
			function doDelete() { if (!confirm('Are you sure?')) return false; }
		</script>
	";
}

// Displays the form for editing/inserting a category
function jscal_cat_form($command, $id = null) {
    global $wpdb;
	
	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT); // Sanitize the id number to ensure it only contains an integer
	if ($id == 0 || !filter_var($id, FILTER_VALIDATE_INT) === false) {	
	
	// Sets default values if command is insert
    if ($command == 'insert') {
		$cat->category_name = '';
		$cat->category_status = 0;
    }
	
	if ($command == 'update') {
		$category_table = $wpdb->prefix . 'js_categories';
        $cat = $wpdb->prepare("SELECT * FROM $category_table WHERE category_id = %s",$id);
		$row = $wpdb->get_row($cat);
	}
	
	if (isset($cat)) {
		$draft_status = ($row->status == 0)?"checked":"";
		$publish_status   = ($row->status == 1)?"checked":"";
	}
?>
<script>
// Let's do some preliminary data validation. Provides error messaging to the user.
	jQuery(document).ready(function() {
		jQuery("#jscal_form").validate({
            rules: {
                category_name: {
                    required: true,
                    minlength: 3,       
                }
            },
            messages: {
                category_name: {
                    required:" Category name is required",
                    minlength:" Enter a longer name please."
                }
            },
            submitHandler: function(form) {
			form.submit();
            }

        });  
	});
	</script>
	<?php	
    echo '
		<form name="jscal_form" id="jscal_form" method="post" action="?page=manage_categories">
		<input type="hidden" name="cat_id" value="'.$id.'"/>
		<input type="hidden" name="command" value="'.$command.'"/>

		<p>Category Name* <input type="text" name="category_name" value="'.$row->category_name.'" size="20" class="large-text"/>
		<hr />
		<p>
		<label><input type="radio" name="status" value="0" '.$draft_status.'> Draft</label> 
		<label><input type="radio" name="status" value="1" '.$publish_status.'> Published</label> 
		</p><p>* = required field</p>
		<p class="submit"><input type="submit" name="Submit" value="Save Changes" class="button-primary" /></p>
		</form>
	';
	echo '<p><a href="?page=manage_categories">&laquo; Back to Categories</p>';
} else {
		echo 'Something went wrong.<p><a href="?page=manage_categories">&laquo; Back to Categories';
	}	
}

?>