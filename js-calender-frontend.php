<?php


//simple variable debug function
//usage: pr($avariable);
if (!function_exists('pr')) {
  function pr($var) { echo '<pre>'; var_dump($var); echo '</pre>';}
}


//---------------------hooks-------------------------------
add_action( 'wp_enqueue_scripts', 'WAD_2016_scripts' ); // generate the calander UI


//-----------------------------------------------------------


function WAD_2016_scripts() {
	//main calender css
    wp_enqueue_style( 'WAD2016', plugins_url('css/WADcalendar.css',__FILE__));

    //bootstrap css with the '.bootstrap-wrapper' wrapper
    wp_enqueue_style( 'bootstrap', plugins_url('css/bootstrap-wrapper.css',__FILE__));



     // wp_enqueue_style( 'jquery-ui', plugins_url('css/jquery-ui.css',__FILE__));
//add in jquery for the AJAX	
	wp_enqueue_script( 'jquery' );
	// wp_enqueue_script( 'jquery-ui-datepicker');
	// wp_enqueue_script( 'jquery_validate',plugins_url('js/jquery.validate.js',__FILE__) );
	// wp_enqueue_script( 'jquery_forms',plugins_url('js/jquery.form.js',__FILE__) );
	wp_enqueue_script( 'json2' ); //required for AJAX to work with JSON	




    // bootstrap JS
   // wp_register_script('prefix_bootstrap-js', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js');
    wp_enqueue_script('prefix_bootstrap-js', plugins_url('js/bootstrap.min.js',__FILE__));


    // x-editable library
    wp_enqueue_style( 'x-editable-css', plugins_url('css/x-editable.css',__FILE__));
    wp_enqueue_script( 'x-editable-js', plugins_url('js/x-editable.js',__FILE__));
     // datetime picker for x-editable library
    wp_enqueue_style( 'x-editable-datetimepicker-css', plugins_url('css/datetimepicker-bootstrap.css',__FILE__));
    wp_enqueue_script( 'x-editable-datetimepicker-js', plugins_url('js/datetimepicker-bootstrap.js',__FILE__));


    // google map API

    wp_enqueue_script( 'google-map-api', plugins_url('js/google-map-api.js',__FILE__));


}

//----------------------------------------shortcodes---------------------------------
 add_shortcode('wadcal-DJK','WADcal1');

//----------------------------------------------------------------------------------



//retriving the events from the database
function get_events($year, $month){

	global $wpdb, $current_user;

	// Retrieve events from database
	$event_table = $wpdb->prefix . 'js_events';
	$query = "SELECT event_id, event_name, event_status, event_start, event_finish, event_recurring, event_description, event_category_id,event_location_id FROM $event_table ORDER BY event_name DESC";
	$event_list = $wpdb->get_results($query);


	$eventarray = array();
	// restructuring event array and extract year,month and day from the date and filtering the data according to the functions parameteres.
	foreach ($event_list as $event) {
		
			$date = $event->event_start;
			$d = date_parse_from_format("Y-m-d", $date);
			

		//if($year == $d["year"] && $month== $d["month"]){
			$obj = array("event_id" => $event->event_id,
				"event_name" => $event->event_name,
				"event_status" => $event->event_status,
				"event_description" => $event->event_description,
				"event_start" => $event->event_start,
				"event_finish" => $event->event_finish,
				"event_type" => $event->event_recurring,
				"event_category_id" => $event->event_category_id,
				"event_location_id" => $event->event_location_id,



				"event_year" => $d["year"],
				"event_month" => $d["month"],
				"event_day" => $d["day"]);


			if($year == $obj["event_year"] && $month== $obj["event_month"]){
				array_push($eventarray,$obj);
			}

			//displaying recuring events
			if($event->event_recurring>0){
				switch ($event->event_recurring) {
					//inserting daily recuring events to the array
					case '1':
						$startDate = new DateTime($event->event_start);
						$startDateTimestamp = $startDate->getTimestamp();

						$finishDate = new DateTime($event->event_finish);
						$finishDateTimestamp = $finishDate->getTimestamp();
						//echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
						
						while($finishDateTimestamp>=$startDateTimestamp){

						//echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
							
							$startDate->modify('+1 day');
							$startDateTimestamp = $startDate->getTimestamp();

							$obj["event_year"] = $startDate->format("Y");
							$obj["event_month"] = $startDate->format("m");
							$obj["event_day"] = $startDate->format("d");

							if($year == $obj["event_year"] && $month== $obj["event_month"]){
								array_push($eventarray,$obj);
							}

						}

						break;

					//inserting monthly recuring events to the array
					case '2':
						$startDate = new DateTime($event->event_start);
						$startDateTimestamp = $startDate->getTimestamp();

						$finishDate = new DateTime($event->event_finish);
						$finishDateTimestamp = $finishDate->getTimestamp();
						//echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
						
						while($finishDateTimestamp>=$startDateTimestamp){

							
							$startDate->modify('+7 day');
							$startDateTimestamp = $startDate->getTimestamp();

							$obj["event_year"] = $startDate->format("Y");
							$obj["event_month"] = $startDate->format("m");
							$obj["event_day"] = $startDate->format("d");
							$var = (($month == $obj["event_month"]) )?"true":"false";
						//echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";


							if($year == $obj["event_year"] && $month== $obj["event_month"]){
								array_push($eventarray,$obj);
							}

						}

						break;
					
					default:
						# code...
						break;
				}
			}
			
		//}
	}
	return $eventarray;

}


function WADcal1($shortcodeattributes) {
	//days of the week used for headings. This particular method is not particulary multilanguage friendly.
	$weekdays = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	extract(shortcode_atts(array('year' => '-', 'month' => '-'), $shortcodeattributes));	

	//default to the current month and year
    if ($month == '-') $month = date('m');	
    if ($year == '-') $year = date('Y');	

//get the previous month's days - used to fill in the blank days at the start.	
    //make sure we roll over to december in the case of $month being January	
	if ($month == 1) //January?
	   $prevmonth = 12; //December
    else 
	   $prevmonth = $month-1; 
//shortend, harder to read, version of the if ...else... above   
	
	$prevmonth = ($month == 1)?12:$month-1; 

	$prevdays = date('t',mktime(0,0,1,$prevmonth,1,$year));	//days in the previous month	
//calculate a few date values for the current/selected month & year	
	$dow = date('w',mktime(0,0,1,$month,1,$year)); //day of the week
	$days = date('t',mktime(0,0,1,$month,1,$year));	//days in the month
	$lastblankdays = 7-(($dow+$days) % 7); //remaining days in the last week

	$lastblankdays = ($lastblankdays==7)?0:$lastblankdays;

//calendar heading - note we are using flexbox for the styling
    $thedate = date('F Y',mktime(0,0,1,$month,1,$year));
	echo '<main id="calendar"><div>'.$thedate.'</div><div class="th">';
	
//HEADING ROW: print out the week names	
	foreach ($weekdays as $wd) {
	  echo '<span>'.$wd.'</span>';
	}		
	echo '</div>';
	
//CALENDAR WEEKS: generate the calendar body
	//starting day of the previous month, used to fill the blank day slots

     $startday = $prevdays - ($dow-1); //calculate the number of days required from the prev month

	//PART 1: first week with initial blank days (cells) or previous month
    echo '<div class="week">';
  	for ($i=0; $i < $dow; $i++) 
		//refer to lines 43-53 in the WADcalendar.css for information regarding the data-date styling
		echo '<div data-date="'.$startday++.'"></div>';//!! this increments $startday AFTER the value has been used
	

	//getting the event list
	$event_list = get_events($year,$month);


	//pr($event_list);

	//PART 2: main calendar calendar body
	for ($i=0; $i < $days; $i++) {
	   
		//check for the week boundary - % returns the remainder of a division
		if (($i+$dow) % 7 == 0) { //no remainder means end of the week
		  echo '</div><div class="week">';
		}
		
		//print the actual day (cell) with events
		echo '<div data-date="'.($i+1).'">'; //add 1 to the for loop variable as it starts at zero not one




		//event code and such here
		foreach ($event_list as $event) {
			//pr($event["event_day"]);
			if($event["event_day"] == ($i+1)){
				//echo $event["event_name"];
				echo '<div class="bootstrap-wrapper" style="margin:0px 0px 5px 0px"  data-toggle="modal" data-target="#eventDetails">
					<a class="label '.(($event["event_status"]==1)?"label-info":"label-default").'" event-data=\''.json_encode($event).'\'" title="'.$event["event_description"].'" onclick="loadEventData(this)">'.$event["event_name"].'</a></div>';
				
			}
		}
		


		echo '</div>';
	}
	
	//PART 3: last week with blank days (cells) or couple of days from next month
	$j = 1; //counter for next months days used to fill in the blank days at the end
  	for ($i=0; $i < $lastblankdays; $i++) 
		echo '<div data-date="'.$j++.'"></div>'; //!! this increments $j AFTER the value has been used
//close off the calendar	
	echo '</div></main>';




	?>


	<!-- initiating the bootstrap tooltip-->
	<script>
		jQuery(document).ready(function(){
		    jQuery('[data-toggle="tooltip"]').tooltip(); 
		});


		//turn to inline mode for the x-editable		
		jQuery( document ).ready( function( $ ) 
		{ 
		    $.fn.editable.defaults.mode = 'popover';     


		//destroying all the input fields on closing the dialog.
		$('#eventDetails').on('hidden.bs.modal', function () {
	  
	    
	    	$('#eventName').editable("destroy");//destroying the already created fields
			$('#eventStatus').editable("destroy");//destroying the already created fields
			$('#eventType').editable("destroy");//destroying the already created fields
			$('#eventStartDate').editable("destroy");//destroying the already created fields
			//$('#eventStartDate').text(eventData.event_start);//loading the start date of the selected event

			$('#eventFinishDate').editable("destroy");//destroying the already created fields
			//$('#eventFinishDate').text(eventData.event_finish);//loading the finish date of the selected event

			$('#eventCategoryId').editable("destroy");//destroying the already created fields
			$('#eventCategoryId').text("");//loading the finish date of the selected event

			$('#eventLocationId').editable("destroy");//destroying the already created fields
			$('#eventLocationId').text("");//loading the finish date of the selected event




			

		})


		});


	// initializing the google map
	function initialize(add) {
		
	 	var myCenter = new google.maps.LatLng(51.508742,-0.120850);
		  var mapProp = {
		    center:myCenter,
		    zoom:10,
		    mapTypeId:google.maps.MapTypeId.ROADMAP
		  };
		  var map=new google.maps.Map(document.getElementById("googleMap"),mapProp);
		  var marker=new google.maps.Marker({
			  position:myCenter,
			  animation:google.maps.Animation.BOUNCE
			  });
		  var geocoder = new google.maps.Geocoder();
		  geocodeAddress(geocoder, map, add);

		marker.setMap(map);
	  
	}

	//converting the address to longitudes and latitudes 
	function geocodeAddress(geocoder, resultsMap, add) {
	  var address = add;
	  geocoder.geocode({'address': address}, function(results, status) {
	    if (status === google.maps.GeocoderStatus.OK) {
	      resultsMap.setCenter(results[0].geometry.location);
	      var marker = new google.maps.Marker({
	        map: resultsMap,
	        position: results[0].geometry.location
	      });
	    } else {
	      alert('Geocode was not successful for the following reason: ' + status);
	    }
	  });
	}



	//loading event data into the model
	function loadEventData(element){
		
		//console.log(JSON.parse(jQuery(element).attr('event-data')));
		var eventData = JSON.parse(jQuery(element).attr('event-data'));

		//eventID = eventData.event_id;

		jQuery('#eventModalTitle').text(eventData.event_name);
		
		jQuery('#eventName').text(eventData.event_name);
		jQuery('#eventStatus').text((eventData.event_status==0)?"Draft":"Published");
		jQuery('#eventType').text((eventData.event_type==0)?"Non-Recurring":(eventData.event_type==1)?"Daily":(eventData.event_type==2)?"Weekly":"Monthly");

				


		jQuery( document ).ready( function( $ ) 
		{

			console.log(eventData);
			


			$('#eventName').editable({
				    type: 'text',
				    pk: eventData.event_id,
				    url: '?eventEdit=true',
				    title: 'Enter eventName',


				    success: function(response, newValue) {
				    	eventData.event_name = newValue;
				    	//changing  clander data to new values
				    	eventData.event_name = newValue;
				    	$(element).attr('event-data', JSON.stringify(eventData)); 
				    	$(element).text(newValue); 
						$('#eventModalTitle').text(newValue);

				    }
				});


			$('#eventStatus').editable({
				    type: 'select',
				    pk: eventData.event_id,
				    url: '?eventEdit=true',
				    title: 'Event Status',
				    source: [{value: 0, text: 'Draft'}, {value: 1, text: 'Published'}],
    				emptytext: 'Draft',
    				value: eventData.event_status,

    				success: function(response,newValue){

    					//changing  clander data to new values
				    	eventData.event_status = newValue;
				    	$(element).attr('event-data', JSON.stringify(eventData)); 
				    	
				    	if(newValue==0){
							$(element).removeClass('label-info');
							$(element).addClass('label-default');

				    	}else{
				    		$(element).removeClass('label-default');
				    		$(element).addClass('label-info');
				    	}
						



    				}
				});


			$('#eventType').editable({
				    type: 'select',
				    pk: eventData.event_id,
				    url: '?eventEdit=true',
				    title: 'Event Status',
				    source: [{value: 0, text: 'Non-Recurring'}, {value: 1, text: 'Daily'}, {value:2, text: 'Weekly'}, {value:3, text: 'Monthly'}],
    				emptytext: 'Draft',
    				value: eventData.event_type,

    				success: function(response,newValue){

    					//changing  clander data to new values
				    	eventData.event_type = newValue;
				    	$(element).attr('event-data', JSON.stringify(eventData)); 

    				}
				});

			 
			

			 $("#eventStartDate").editable({
				    type: "datetime",
				    pk: eventData.event_id,
					url: '?eventEdit=true',
				    format: "yyyy-mm-dd hh:ii:ss",    
				   // viewformat: "dd/mm/yyyy hh:ii",    
				    value:eventData.event_start,
				    datetimepicker: {
				        weekStart: 1
				    },
				    success: function(response, newValue){
				    	eventData.event_start = newValue;
				    	$(element).attr('event-data', JSON.stringify(eventData)); 
				    }
				});

			 $("#eventFinishDate").editable({
				    type: "datetime",
				    pk: eventData.event_id,
					url: '?eventEdit=true',
				    format: "yyyy-mm-dd hh:ii:ss",    
				   // viewformat: "dd/mm/yyyy hh:ii",    
				    value:eventData.event_finish,
				    datetimepicker: {
				        weekStart: 1
				    },
				    success: function(response, newValue){
				    	eventData.event_finish = newValue;
				    	$(element).attr('event-data', JSON.stringify(eventData)); 
				    }
				});

			  $("#eventCategoryId").editable({
				    type: "select",
				    pk: eventData.event_id,
					source: "?eventEdit=true&action=getCategoryList",
					url: '?eventEdit=true',
					sourceCache:false,
					value: eventData.event_category_id,
					success:function(response,newValue){
						eventData.event_category_id = newValue;
				    	$(element).attr('event-data', JSON.stringify(eventData)); 
					}
				   // viewformat: "dd/mm/yyyy hh:ii",    
				    //value:eventData.event_finish,
				    
				    
				});

			  $("#eventLocationId").editable({
				    type: "select",
				    pk: eventData.event_id,
					source: "?eventEdit=true&action=getLocationList",
					url: '?eventEdit=true',
					sourceCache:false,
					value: eventData.event_location_id,
					success:function(response,newValue){
						eventData.event_location_id = newValue;
				    	$(element).attr('event-data', JSON.stringify(eventData)); 
				    	
					    	 $.ajax({
							      url:"?eventEdit=true&action=getLocationNames",  
							      success:function(data) {
							      	var sourceObj = JSON.parse(data);
							      	for (var i = sourceObj.length - 1; i >= 0; i--) {
							      		
							      		if(sourceObj[i].value==newValue){
							      			
					    					initialize(sourceObj[i].text);

							      		}
							      	};
							         
							       

							      }
							   });
					}
				   // viewformat: "dd/mm/yyyy hh:ii",    
				    //value:eventData.event_finish,
				    
				    
				});



				
				setTimeout(function(){$.ajax({
							      url:"?eventEdit=true&action=getLocationNames",  
							      success:function(data) {
							      	var sourceObj = JSON.parse(data);
							      	for (var i = sourceObj.length - 1; i >= 0; i--) {
							      		
							      		if(sourceObj[i].value==eventData.event_location_id){
							      			
					    					initialize(sourceObj[i].text);

							      		}
							      	};
							         
							         
							      }
							   });},50);
			  // $("#addCategory").editable({
				 //    type: "text",
				 //    pk: eventData.event_id,
					// url: "?eventEdit=true&action=getCategoryList",
					
					// value:"+",
					//  success: function () {
				 //    return {
				 //      newValue:"+"     
				 //    };
					//}
					
				   // viewformat: "dd/mm/yyyy hh:ii",    
				    //value:eventData.event_finish,
				    
				    
				//});

			  

		//initialize();

			 
		});

	setTimeout(function(){


	},100);




	<?php
	global $current_user;
	



	if($current_user->ID == 0){
		echo "jQuery('#eventName').editable('option', 'disabled', true);";
		echo "jQuery('#eventStatus').editable('option', 'disabled', true);";
		echo "jQuery('#eventType').editable('option', 'disabled', true);";
		echo "jQuery('#eventStartDate').editable('option', 'disabled', true);";
		echo "jQuery('#eventFinishDate').editable('option', 'disabled', true);";
		echo "jQuery('#eventCategoryId').editable('option', 'disabled', true);";
		echo "jQuery('#eventLocationId').editable('option', 'disabled', true);";

	}


		



	?>


		

	}


	</script>
		<!-- Event Modal -->
		<div class="bootstrap-wrapper">
		  <div class="modal fade" id="eventDetails" role="dialog" >
		    <div class="modal-dialog" style="margin:15%">
		    
		      <!-- Modal content-->
		      <div class="modal-content">
		        <div class="modal-header">
		          <button type="button" class="close" data-dismiss="modal">&times;</button>
		          <h4 class="modal-title" id="eventModalTitle">Event Name</h4>
		        </div>
		        <div class="modal-body">
		        	<div class="row">
		        		<div class="col-md-12">

							<div class="col-md-6">
						          <div class="form-horizontal" role="form">

						          	<div class="form-group">
									    <label class="control-label " for="eventName">Event Name : </label>
									    <!-- <div class="col-sm-10"> -->
									            <a href="#" id="eventName"></a>
								        <!-- </div> -->
									</div>

									<div class="form-group">
									    <label class="control-label " for="eventStatus">Event Status : </label>
									    <!-- <div class="col-sm-10"> -->
									            <a href="#" id="eventStatus"></a>
								        <!-- </div> -->
									</div>

									<div class="form-group">
									    <label class="control-label " for="eventStatus">Recurring Frequency : </label>
									    <!-- <div class="col-sm-10"> -->
									            <a href="#" id="eventType"></a>
								        <!-- </div> -->
									</div>

									<div class="form-group">
									    <label class="control-label " for="eventStatus">Start Date : </label>
									    <!-- <div class="col-sm-10"> -->
									            <a href="#" id="eventStartDate"></a>
								        <!-- </div> -->
									</div>

									<div class="form-group">
									    <label class="control-label " for="eventStatus">Finish Date : </label>
									    <!-- <div class="col-sm-10"> -->
									            <a href="#" id="eventFinishDate"></a>
								        <!-- </div> -->
									</div>

									<div class="form-group">
									    <label class="control-label " for="eventCategoryId">Event Category : </label>
									    <!-- <div class="col-sm-10"> -->
									            <a href="#" id="eventCategoryId"></a><!-- <a id="addCategory" class="btn btn-primary glyphycon glyphicon-add"></a> -->
								        <!-- </div> -->
									</div>
								</div>

							</div>

							<div class="col-md-6">
								<div class="form-group">
									    <label class="control-label " for="eventLocationId">Event Location : </label>
									    <!-- <div class="col-sm-10"> -->
									            <a href="#" id="eventLocationId"></a><!-- <a id="addCategory" class="btn btn-primary glyphycon glyphicon-add"></a> -->
								        <!-- </div> -->
									</div>


									<div id="googleMap" class="col-md-12" style="height:250px"></div>
							</div>

					</div>
					  
		          </div>
		        </div>
		        <div class="modal-footer">
		          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		        </div>
		      </div>
		      
		    </div>
		  </div>
		</div>

<?php
	
}



//AJAX handler
add_action('parse_request', 'JKT_AJAX_query_handler');
function JKT_AJAX_query_handler() {	
	if (isset($_GET['eventEdit']) && !empty($_GET['eventEdit'])) {
		
		$name = $_REQUEST['name'];
		$value = $_REQUEST['value'];
		$pk = $_REQUEST['pk'];	
		$action = $_GET['action'];


		if(!is_nan($pk)){// checking for valid eventId
			global $wpdb, $current_user;
			$event_table = $wpdb->prefix . 'js_events';
			


			$auth_user_id_array = $wpdb->get_results($wpdb->prepare("SELECT `event_organizer_id` FROM `$event_table` WHERE `event_id`=%d",$pk));
			$auth_user_id = $auth_user_id_array[0]->event_organizer_id;

			


			// returning the event categories
			if(isset($action) && !empty($action)){

				switch ($action) {
					case 'getCategoryList':
						$category_table = $wpdb->prefix . 'js_categories';

						$category_list = $wpdb->get_results("SELECT `category_id`,`category_name`, `category_status` FROM `$category_table`");

						foreach($category_list as $category){
						    $category_list_json[] = array('value' => $category->category_id, 'text' => $category->category_name);
						}
						echo json_encode($category_list_json);
						break;
					case 'getLocationList':
						$venue_table = $wpdb->prefix . 'js_venues';

						$venue_list = $wpdb->get_results("SELECT `venue_id`,`venue_name` FROM `$venue_table`");

						foreach($venue_list as $venue){
						    $venue_list_json[] = array('value' => $venue->venue_id, 'text' => $venue->venue_name);
						}
						echo json_encode($venue_list_json);
						break;

					case 'getLocationNames':
						$venue_table = $wpdb->prefix . 'js_venues';

						$venue_list = $wpdb->get_results("SELECT `venue_id`, `venue_location` FROM `$venue_table`");

						foreach($venue_list as $venue){
						    $venue_list_json[] = array('value' => $venue->venue_id, 'text' => $venue->venue_location);
						}
						echo json_encode($venue_list_json);
						break;
					default:
						
						break;
				}
				

			 
			}
           //http_response_code(400);
			
			



			if($auth_user_id == $current_user->ID || current_user_can('manage_options') ){ // checking for valid authorization
				switch ($name) {
					case 'eventName':
						$value = trim($value);
						$value = stripcslashes($value);
						$wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_name`='$value' WHERE `event_id`=%d",$pk));
						break;
					case 'eventStatus':
						if(!is_nan($value)){
							$value = trim($value);
							$value = stripcslashes($value);
							$wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_status`='$value' WHERE `event_id`=%d",$pk));
						}
						break;
					case 'eventType':
						if(!is_nan($value)){
							$value = trim($value);
							$value = stripcslashes($value);
							$wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_recurring`='$value' WHERE `event_id`=%d",$pk));
						}
						break;
					case 'eventStartDate':
							$value = trim($value);
							$value = stripcslashes($value);	
							$wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_start`='$value' WHERE `event_id`=%d",$pk));
						
						break;

					case 'eventFinishDate':
							$value = trim($value);
							$value = stripcslashes($value);	
							$wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_finish`='$value' WHERE `event_id`=%d",$pk));
						
						break;

					case 'eventCategoryId':
					
							$value = trim($value);
							$value = stripcslashes($value);
							if(!is_nan($value)){	
								$wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_category_id`='$value' WHERE `event_id`=%d",$pk));
							}
						
						break;

					case 'eventLocationId':
					
							$value = trim($value);
							$value = stripcslashes($value);
							if(!is_nan($value)){	
								$wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_location_id`='$value' WHERE `event_id`=%d",$pk));
							}
						
						break;


						//http_response_code(400);


					default:
						# code...
						break;
				}
			}else{
				// if the current user is not an authorized user
				echo "You are not authorized to make changes to this event.";
				http_response_code(400);
			}
		}else{
			// if event id is not a number
			echo "Bad request. :(";
			http_response_code(400);
			exit;
		}
		exit;	//important for our AJAX to work without returning the whole page
	}
}	


	?>
