<?php


function js_cal() {
	echo 'Test';
}

function jscal_manage_events() {
	echo 'Test manage Events';
}

function jscal_add_event() {
	echo '<h1>Add new event</h1>';
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

    echo '
		<form name="jscal_form" method="post" action="?page=manage_venues">
		<input type="hidden" name="ven_id" value="'.$id.'"/>
		<input type="hidden" name="command" value="'.$command.'"/>

		<p>Venue Name: <input type="text" name="venue_name" value="'.$row->venue_name.'" size="20" class="large-text"/>
		<p>Venue Location: <input type="text" name="venue_location" value="'.$row->venue_location.'" size="20" class="large-text"/>

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
		if ($cat->category_author == 0) $cat->category_author = $current_user->ID;
		$user_info = get_userdata($cat->category_author);
	   
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
	
    echo '
		<form name="jscal_form" method="post" action="?page=manage_categories">
		<input type="hidden" name="cat_id" value="'.$id.'"/>
		<input type="hidden" name="command" value="'.$command.'"/>

		<p>Category Name: <input type="text" name="category_name" value="'.$row->category_name.'" size="20" class="large-text"/>
		<hr />
		<p>
		<label><input type="radio" name="status" value="0" '.$draft_status.'> Draft</label> 
		<label><input type="radio" name="status" value="1" '.$publish_status.'> Published</label> 
		</p>
		<p class="submit"><input type="submit" name="Submit" value="Save Changes" class="button-primary" /></p>
		</form>
	';
	echo '<p><a href="?page=manage_categories">&laquo; Back to Categories</p>';
} else {
		echo 'Something went wrong.<p><a href="?page=manage_categories">&laquo; Back to Categories';
	}	
}

?>