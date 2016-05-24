<?php

$timezone = "Pacific/Auckland";
date_default_timezone_set($timezone);


//simple variable debug function
//usage: pr($avariable);
if (!function_exists('pr')) {
    function pr($var)
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}


//---------------------hooks-------------------------------
add_action('wp_enqueue_scripts', 'WAD_2016_scripts'); // generate the calander UI


//-----------------------------------------------------------

//----------------------------------------shortcodes---------------------------------
add_shortcode('wadcal-DJK', 'WADcal1');

//----------------------------------------------------------------------------------



// ------ widget starts-----//

class DJK_upcoming_events extends WP_Widget {

    function __construct(){
        parent::__construct(
                'djk_upcoming_events',

                __('DJK- Upcoming Events', 'djkwidget'),

                array(
                        'description' => __('This widget is a part of DJK-Plugin and will display all the upcoming events', 'djkwidget')
                    )
            );
    }

    //displaying the backend option
    function form($instance){ 
            $no_of_events_to_show = esc_attr( $instance['no_of_events_to_show'] );
            ?>
            <p>
                <label for="<?php echo $this->get_field_id('no_of_events_to_show'); ?>"><?php _e('No of events to show (default = 5):'); ?></label> 
                <input class="" id="<?php echo $this->get_field_id('no_of_events_to_show'); ?>" name="<?php echo $this->get_field_name('no_of_events_to_show'); ?>" type="text" value="<?php echo $no_of_events_to_show; ?>" />
            </p>


            <?php
    }

    //updating the backend option
    function update($new_instance, $old_instance){
        $instance = $old_instance;

        $newValue = floor(strip_tags($new_instance['no_of_events_to_show']));
        $oldValue = strip_tags($old_instance['no_of_events_to_show']);

        $instance['no_of_events_to_show'] = (is_numeric($newValue)  )?$newValue:((is_numeric($oldValue))?$oldValue:"5");     



        return $instance;
    }

    //displaying the fron end
    function widget($args, $instance){

        echo '<!-- Search event modal -->
                    <div class="bootstrap-wrapper">
                        <div id="eventSearch" class="modal fade" role="dialog">
                          <div class="modal-dialog" style="margin:15%" >

                            <!-- Modal content-->
                            <div class="modal-content">
                              <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Search Event</h4>
                              </div>
                              <div class="modal-body">
                                <div>
                                    <div class="col-lg-6">
                                        <div class="input-group">
                                          <input id="searchEvents" type="text" class="form-control" placeholder="Search description...">
                                          <span class="input-group-btn">
                                            <button class="btn btn-default" type="button" onclick="searchDesc()">GoS</button>
                                          </span>
                                        </div><!-- /input-group -->
                                      </div><!-- /.col-lg-6 -->
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table">
                                            <th>Event Name</th>
                                            <th>Event Description</th>
                                            <th>Event Date</th>
                                            
                                            <tbody id="searchEventsBody">

                                            </tbody>
                                        </table>
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
';
       
        echo '<script>
                    function searchDesc(){
                        desc = jQuery("#searchEvents").val();
                        d = {description: desc};
                        jQuery.ajax({
                                url: "?eventEdit=true&eventGetData=true&action=getEventsByDescription&pk=1",
                                data:d,
                                success: function (data) {
                                    var events =  JSON.parse(data);
                                    jQuery("#searchEventsBody").empty();
                                    for(var i=0; i<events.length; i++ ){
                                        jQuery("#searchEventsBody").append("<tr><td>"+events[i].event_name+"</td><td>"+events[i].event_description+"</td><td>"+events[i].event_date+"</td></tr>")

                                    }
                                   

                                }
                            });
                    }

              </script>';

        $allEvents = get_all_events(false,false);
        $counter =0;
        
        if(isset($instance['no_of_events_to_show']) && !empty($instance['no_of_events_to_show'])){
            $no_of_events_to_show = $instance['no_of_events_to_show'];
        }else{
            $no_of_events_to_show ="5";

        }

        //pr($allEvents);

        $currentTimestamp = strtotime(date("Y-m-d H:i:s"));
        echo '<div class="bootstrap-wrapper">
                <div class="well widget">
                    <div class="row">
                        <div class="col-md-12">
                            <h4>
                                Upcoming Events 
                                <button class="btn btn-primary btn-xs pull-right" data-toggle="modal" data-target="#eventSearch">
                                    <i class="glyphicon glyphicon-search"></i> Search
                                </button>
                                
                            </h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            
                            <div class="list-group widget-upcoming-event-list">';
                                                


        foreach ($allEvents as $event) {

            if( ($counter < $no_of_events_to_show) && ($event['event_timestamp'] > $currentTimestamp)){
                $counter+=1;
               
                echo '<div class="list-group-item bs-callout bs-callout-info">
                                                    <a href="#" class="col-md-12">
                                                        <span class="truncate pull-left">'.$event['event_name'].'</span>
                                                        <span class="badge pull-right">'.$event['event_date'].'</span>
                                                    </a>

                                                    <div class="widget-item-description">
                                                        
                                                        '.$event['event_description'].'
                                                        
                                                    </div>
                                                </div>';



            }

            # code...
        }
        echo '
                                                
                            </div>
                        </div>
                    </div>    
                </div>
              </div>';
        
    }
}


// action hook
add_action('widgets_init', function(){
        register_widget('DJK_upcoming_events');
});
//-------- widget ends-----//



//loading css files and js files
function WAD_2016_scripts()
{
    //main calender css
    wp_enqueue_style('WAD2016', plugins_url('css/WADcalendar.css', __FILE__));

    //bootstrap css with the '.bootstrap-wrapper' wrapper
    wp_enqueue_style('bootstrap', plugins_url('css/bootstrap-wrapper.css', __FILE__));
    wp_enqueue_script('jquery');
    wp_enqueue_script('json2'); //required for AJAX to work with JSON


    // bootstrap JS
    wp_enqueue_script('prefix_bootstrap-js', plugins_url('js/bootstrap.min.js', __FILE__));


    // x-editable library
    wp_enqueue_style('x-editable-css', plugins_url('css/x-editable.css', __FILE__));
    wp_enqueue_script('x-editable-js', plugins_url('js/x-editable.js', __FILE__));

    // datetime picker for x-editable library
    wp_enqueue_style('x-editable-datetimepicker-css', plugins_url('css/datetimepicker-bootstrap.css', __FILE__));
    wp_enqueue_script('x-editable-datetimepicker-js', plugins_url('js/datetimepicker-bootstrap.js', __FILE__));


    // datetime picker
    // wp_enqueue_style('newEvent-datetimepicker-css', plugins_url('css/bootstrap-datepicker3.css', __FILE__));
    // wp_enqueue_script('newEvent-datetimepicker-js', plugins_url('js/bootstrap-datepicker.js', __FILE__));
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');


    // google map API
   // wp_enqueue_script('google-map-api', plugins_url('js/google-map-api.js', __FILE__));
    wp_enqueue_script('google-map-api', '//maps.googleapis.com/maps/api/js?&key=AIzaSyDzzaqaN5nAyGCp1gRIsleOJRUeioD-urs', array(), '3', true);

    //form validation
    wp_enqueue_script( 'jquery_validate',plugins_url('js/jquery.validate.js',__FILE__) );
}




//retrieving the events from the database and adding those to another array with considering recuring events
function get_events($year, $month)
{

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
        $startDate = new DateTime($event->event_start);

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
            "event_day" => $d["day"],
            "event_time" => $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a"),
            "event_date" => $d["year"]."-".$d["month"]."-".$d["day"]);


        if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
            array_push($eventarray, $obj);
        }

        //displaying recuring events
        if ($event->event_recurring > 0) {
            switch ($event->event_recurring) {
                //inserting daily recuring events to the array
                case '1':
                    $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+1 day');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();

                    while ($finishDateTimestamp >= $startDateTimestamp) {

                        //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");

                        if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        }

                        $startDate->modify('+1 day');
                        $startDateTimestamp = $startDate->getTimestamp();

                    }

                    break;

                //inserting weekly recuring events to the array
                case '2':
                    $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+7 day');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();



                    while ($finishDateTimestamp >= $startDateTimestamp) {

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");

                        $var = (($month == $obj["event_month"])) ? "true" : "false";
                        //echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       // echo "<script>console.log(".$startDate->format("d").");</script>";
                        //echo "<script>console.log($finishDateTimestamp >= $startDateTimestamp);</script>";

                        if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        }
                        $startDate->modify('+7 day');
                        $startDateTimestamp = $startDate->getTimestamp();



                    }

                    break;

                 //inserting monthly recuring events to the array
                case '3':
                 $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+1 month');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();



                    while ($finishDateTimestamp >= $startDateTimestamp) {

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");

                        $var = (($month == $obj["event_month"])) ? "true" : "false";
                        //echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       // echo "<script>console.log(".$startDate->format("d").");</script>";
                        //echo "<script>console.log($finishDateTimestamp >= $startDateTimestamp);</script>";

                        if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        }
                        $startDate->modify('+1 month');
                        $startDateTimestamp = $startDate->getTimestamp();



                    }

                    break;

                 //inserting Yearly recuring events to the array
                case '4':
                 $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+1 year');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();



                    while ($finishDateTimestamp >= $startDateTimestamp) {

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");

                        $var = (($month == $obj["event_month"])) ? "true" : "false";
                        //echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       // echo "<script>console.log(".$startDate->format("d").");</script>";
                        //echo "<script>console.log($finishDateTimestamp >= $startDateTimestamp);</script>";

                        if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        }
                        $startDate->modify('+1 year');
                        $startDateTimestamp = $startDate->getTimestamp();



                    }

                    break;

                default:

                    break;
            }
        }

        //}
    }
    return $eventarray;

}

//date sorting function for usort()
function date_compare($a, $b)
{
    $t1 = strtotime($a['event_date']);
    $t2 = strtotime($b['event_date']);
    return $t1 - $t2;
}    

//retrieving all the events from the database and adding those to another array with considering recuring events
function get_all_events($desc, $date){
    global $wpdb, $current_user;

    // Retrieve events from database
    $event_table = $wpdb->prefix . 'js_events';
    $query = "SELECT event_id, event_name, event_status, event_start, event_finish, event_recurring, event_description, event_category_id,event_location_id FROM $event_table ORDER BY event_name DESC";
    
    //search events according to the description
    if($desc!= false && !empty($desc)){
        $query = "SELECT event_id, event_name, event_status, event_start, event_finish, event_recurring, event_description, event_category_id,event_location_id FROM $event_table WHERE event_description LIKE '%$desc%' ORDER BY event_name DESC";
    }
    $event_list = $wpdb->get_results($query);


    $eventarray = array();
    // restructuring event array and extract year,month and day from the date and filtering the data according to the functions parameteres.
    foreach ($event_list as $event) {

        $date = $event->event_start;
        $d = date_parse_from_format("Y-m-d :H:i", $date);
        $startDate = new DateTime($event->event_start);

        $event_recurring_date = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d")." ".$startDate->format("H").":".$startDate->format("i").":".$startDate->format("s");
        $dd = new DateTime($event_recurring_date);
        

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


            "event_year" => $startDate->format("y"),
            "event_month" => $startDate->format("m"),
            "event_day" => $startDate->format("d"),
            "event_time" => $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a"),
            "event_date" => $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d"),
            "event_timestamp" => $dd->getTimestamp()
            );


        //if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
            array_push($eventarray, $obj);
        //}

        //displaying recuring events
        if ($event->event_recurring > 0) {
            switch ($event->event_recurring) {
                //inserting daily recuring events to the array
                case '1':
                    $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+1 day');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();

                    $event_recurring_date = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d")." ".$startDate->format("H").":".$startDate->format("i").":".$startDate->format("s");
                    $dd = new DateTime($event_recurring_date);

                    while ($finishDateTimestamp >= $startDateTimestamp) {

                        //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");
                        $obj["event_timestamp"] = $dd->getTimestamp();

                        //if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        //}

                        $startDate->modify('+1 day');
                        $startDateTimestamp = $startDate->getTimestamp();

                    }

                    break;

                //inserting weekly recuring events to the array
                case '2':
                    $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+7 day');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();

                    $event_recurring_date = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d")." ".$startDate->format("H").":".$startDate->format("i").":".$startDate->format("s");
                    $dd = new DateTime($event_recurring_date);



                    while ($finishDateTimestamp >= $startDateTimestamp) {

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");
                        $obj["event_timestamp"] = $dd->getTimestamp();

                        $var = (($month == $obj["event_month"])) ? "true" : "false";
                        //echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       // echo "<script>console.log(".$startDate->format("d").");</script>";
                        //echo "<script>console.log($finishDateTimestamp >= $startDateTimestamp);</script>";

                        //if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        //}
                        $startDate->modify('+7 day');
                        $startDateTimestamp = $startDate->getTimestamp();



                    }

                    break;

                 //inserting monthly recuring events to the array
                case '3':
                 $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+1 month');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();

                    $event_recurring_date = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d")." ".$startDate->format("H").":".$startDate->format("i").":".$startDate->format("s");
                    $dd = new DateTime($event_recurring_date);




                    while ($finishDateTimestamp >= $startDateTimestamp) {

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");
                        $obj["event_timestamp"] = $dd->getTimestamp();

                        $var = (($month == $obj["event_month"])) ? "true" : "false";
                        //echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       // echo "<script>console.log(".$startDate->format("d").");</script>";
                        //echo "<script>console.log($finishDateTimestamp >= $startDateTimestamp);</script>";

                        //if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        //}
                        $startDate->modify('+1 month');
                        $startDateTimestamp = $startDate->getTimestamp();



                    }

                    break;

                 //inserting Yearly recuring events to the array
                case '4':
                 $startDate = new DateTime($event->event_start);

                    $finishDate = new DateTime($event->event_finish);
                    //echo "<script>alert('start Time : ". $startDateTimestamp."/nFinish Time : ".$finishDateTimestamp."')</script>";
                    $startDate->modify('+1 year');
                    $startDateTimestamp = $startDate->getTimestamp();
                    $finishDateTimestamp = $finishDate->getTimestamp();

                    $event_recurring_date = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d")." ".$startDate->format("H").":".$startDate->format("i").":".$startDate->format("s");
                    $dd = new DateTime($event_recurring_date);



                    while ($finishDateTimestamp >= $startDateTimestamp) {

                       

                        $obj["event_year"] = $startDate->format("Y");
                        $obj["event_month"] = $startDate->format("m");
                        $obj["event_day"] = $startDate->format("d");
                        $obj["event_time"] = $startDate->format("h").":".$startDate->format("i")." ".$startDate->format("a");
                        $obj["event_date"] = $startDate->format("Y")."-".$startDate->format("m")."-".$startDate->format("d");
                        $obj["event_timestamp"] = $dd->getTimestamp();

                        $var = (($month == $obj["event_month"])) ? "true" : "false";
                        //echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";

                       // echo "<script>console.log(".$startDate->format("d").");</script>";
                        //echo "<script>console.log($finishDateTimestamp >= $startDateTimestamp);</script>";

                        //if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        //}
                        $startDate->modify('+1 year');
                        $startDateTimestamp = $startDate->getTimestamp();



                    }

                    break;

                default:

                    break;
            }
        }

        //}
    }

    usort($eventarray, 'date_compare');// sorting array according to the event_date
    return $eventarray;
}

//copy of the WADCal1 function redesigned as a dynamic call
function WadCal1DynamicRedraw($shortcodeattributes){

    $returnText = "";

    $returnText.= '<div id="contentWrapper"><!-- start of contentWrapper-->
                    <script>
                    // initiating the bootstrap tooltip
                    jQuery(document).ready(function () {
                        jQuery(\'[data-toggle="tooltip"]\').tooltip();
                        

                        //stop triggering the parent cell on click function when clicking on a event
                        jQuery("#dayCell a").click(function (e) {
                            e.stopPropagation();
                            jQuery(\'#eventDetails\').modal(\'show\');
                        });

                    
                        //validation settings for the add new event form
                        jQuery("#add-new-event-form").validate({
                            rules: {
                                txtEventName: {           
                                    required: true,   
                                         
                                },
                                txtAddNewStartDate:{
                                    required:true,
                                },
                                txtAddNewFinishDate:{
                                    required:true,
                                },
                                cmbEventCategory:{
                                    required:true,
                                },
                                cmbEventLocation:{
                                    required:true,
                                },
                            },
                            messages: {               
                                txtEventName: {
                                      required:" <span class=\"label label-danger\">Required</span>",
                                      
                                      },               
                                txtAddNewStartDate: {
                                        required: "<span class=\"label label-danger\">Required</scpan>",
                                        },
                                txtAddNewFinishDate: {
                                        required: "<span class=\"label label-danger\">Required</scpan>",
                                        },
                                cmbEventCategory: {
                                        required: "<span class=\"label label-danger\">Required</scpan>",
                                        },
                                cmbEventLocation: {
                                        required: "<span class=\"label label-danger\">Required</scpan>",
                                        },
                            },
                            submitHandler: function(form) {
                                               // form.submit();

                                                var d = {
                                                    eventName : jQuery("#txtEventName").val(),
                                                    eventDescription : jQuery("#textareaDescription").val(),
                                                    eventStatus : jQuery("#cmbEventStatus").val(),
                                                    eventRecurrenceFrequency : jQuery("#cmbEventRecurrence").val(),
                                                    eventStartDate : jQuery("#add-new-start-Date").val(),
                                                    eventFinishDate : jQuery("#add-new-finish-Date").val(),
                                                    eventCategoryId : jQuery("#cmbEventCategory").val(),
                                                    eventLocationId : jQuery("#cmbEventLocation").val(),

                                                
                                                };
                                                jQuery("#btnAddNewEvent").attr("disabled",true);



                                                jQuery.ajax({
                                                  type: "POST",
                                                  url: "?eventNew=true",
                                                  data: d,
                                                  success: function(response){
                                                    jQuery("#btnAddNewEvent").attr("disabled",false);
                                                    jQuery("#responseMsg").empty();
                                                    jQuery("#responseMsg").append("<div class=\"alert alert-success alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><strong>Success!</strong> "+response+"</div>");


                                                    jQuery("#events").animate({
                                                                        scrollTop: jQuery("#responseMsg").offset().top
                                                                    }, 1000);

                                                    redrawCalander(jQuery("#calendarHeaderText").data("month"),jQuery("#calendarHeaderText").data("year"))
                                                   

                                                  },
                                                  error:function(response){
                                                    jQuery("#responseMsg").empty();
                                                    jQuery("#responseMsg").append("<div class=\"alert alert-danger alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><strong>Error!</strong> "+response.responseText+"</div>");


                                                    jQuery("#events").animate({
                                                                        scrollTop: jQuery("#responseMsg").offset().top
                                                                    }, 1000);

                                                    jQuery("#btnAddNewEvent").attr("disabled",false);
                                                    
                                                  },
                                                  
                                                });
                                        
                                        }

                        });  


                        //validation settings and the submit handler for the commentSection
                        jQuery("#commentForm").validate({
                            rules: {
                                comment: {           
                                    required: true,   
                                         
                                },
                            },
                            messages: {               
                                comment: {
                                      required:" <span class=\"label label-danger\">Required</span>",
                                      
                                      },
                            },
                            submitHandler: function(form) {

                                            var d = {
                                                    comment : jQuery("#comment").val(),
                                                    pk : jQuery("#commentForm").attr("event")
                                                 
                                                };
                                                jQuery("#btnCommentSend").attr("disabled",true);



                                                jQuery.ajax({
                                                  type: "POST",
                                                  url: "?commentNew=true",
                                                  data: d,
                                                  success: function(response){
                                                    
                                                    jQuery("#btnCommentSend").attr("disabled",false);
                                                    jQuery("#commentResponseMsg").empty();
                                                    jQuery("#commentResponseMsg").append("<div class=\"alert alert-success alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><strong>Success!</strong> "+response+"</div>");


                                                     jQuery(".commentSection").animate({
                                                                         scrollTop: jQuery("#commentResponseMsg").offset().top- jQuery(".commentSection").offset().top
                                                                     }, 1000);




                                                    //loading comments
                                                    var eventData_eventId = jQuery("#commentForm").attr("event")
                                                    jQuery.ajax({
                                                            url: "?eventEdit=true&eventGetData=true&action=getComments&pk="+eventData_eventId,
                                                            success: function (data) {
                                                                var messages = JSON.parse(data);
                                                                jQuery("#messagesList").empty();

                                                                if( messages != null && messages.length != undefined){ // check to see whether there are comments for the event
                                                                    for(var i =0; i<messages.length;i++){

                                                                        jQuery("#messagesList").append("<li class=\"list-group-item \">"+
                                                                                                "<div class=\"row\">"+
                                                                                                    "<div class=\"col-xs-2 col-md-1\">"+
                                                                                                        "<img src=\"http://0.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=80\" class=\"img-circle img-responsive\" alt=\"\" /></div>"+
                                                                                                    "<div class=\"col-xs-10 col-md-11\">"+
                                                                                                        "<div>"+
                                                                                                            "<div class=\"mic-info\">"+
                                                                                                                "By: <a href=\"#\">"+messages[i].messageAuthor+"</a> on "+messages[i].messageDate+
                                                                                                            "</div>"+
                                                                                                        "</div>"+
                                                                                                        "<div class=\"comment-text\">"+
                                                                                                            messages[i].messageContent+
                                                                                                        "</div>"+
                                                                                                        "<div class=\"action\">"+
                                                                                                    "</div>"+
                                                                                                "</div>"+
                                                                                            "</li>");

                                                                    }
                                                                }
                                                              


                                                            }
                                                        });
                                                   

                                                  },
                                                  error:function(response){
                                                    
                                                    jQuery("#commentResponseMsg").empty();
                                                    jQuery("#commentResponseMsg").append("<div class=\"alert alert-danger alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><strong>Error!</strong> "+response.responseText+"</div>");


                                                    jQuery(".commentSection").animate({
                                                                        scrollTop: jQuery("#commentResponseMsg").offset().top- jQuery(".commentSection").offset().top
                                                                    }, 1000);

                                                    jQuery("#btnCommentSend").attr("disabled",false);
                                                    
                                                  },
                                                  
                                                });

                            }
                        });






                        //loading the google map onto the div on change
                        jQuery(document).on("change", "#cmbEventLocation", function() {
                            console.log(jQuery(this).val()); // the selected options"s value
                            var newValue = jQuery(this).val();

                            jQuery.ajax({
                                url: "?eventEdit=true&eventGetData=true&getCategoryList=true&action=getLocationNames&pk=1",
                                success: function (data) {
                                    var sourceObj = JSON.parse(data);
                                    for (var i = sourceObj.length - 1; i >= 0; i--) {

                                        if (sourceObj[i].value == newValue) {
                                            console.log(sourceObj[i].text);
                                            initialize(sourceObj[i].text,true);

                                        }
                                    }
                                    ;


                                }
                            });

                           
                        });




                    
                       
                        
                    });


                    //turning on inline mode of the x-editable
                    jQuery(document).ready(function ($) {
                        $.fn.editable.defaults.mode = \'popover\';
                        $.fn.editable.defaults.placement = \'bottom\';


                        //destroying all the input fields on closing the dialog.
                        $(\'#eventDetails\').on(\'hidden.bs.modal\', function () {


                            $(\'#eventName\').editable("destroy");//destroying the already created fields
                            $(\'#eventDescription\').editable("destroy");//destroying the already created fields
                            $(\'#eventStatus\').editable("destroy");//destroying the already created fields
                            $(\'#eventType\').editable("destroy");//destroying the already created fields
                            $(\'#eventStartDate\').editable("destroy");//destroying the already created fields
                            //$(\'#eventStartDate\').text(eventData.event_start);//loading the start date of the selected event

                            $(\'#eventFinishDate\').editable("destroy");//destroying the already created fields
                            //$(\'#eventFinishDate\').text(eventData.event_finish);//loading the finish date of the selected event

                            $(\'#eventCategoryId\').editable("destroy");//destroying the already created fields
                            $(\'#eventCategoryId\').text("");//loading the finish date of the selected event

                            $(\'#eventLocationId\').editable("destroy");//destroying the already created fields
                            $(\'#eventLocationId\').text("");//loading the finish date of the selected event


                        })


                    });';


        //destroying already created elements
        global $current_user,$wpdb;
        if ($current_user->ID == 0) {
            $returnText.= "jQuery('#eventName').editable('option', 'disabled', true);";
            $returnText.= "jQuery('#eventStatus').editable('option', 'disabled', true);";
            $returnText.= "jQuery('#eventType').editable('option', 'disabled', true);";
            $returnText.= "jQuery('#eventStartDate').editable('option', 'disabled', true);";
            $returnText.= "jQuery('#eventFinishDate').editable('option', 'disabled', true);";
            $returnText.= "jQuery('#eventCategoryId').editable('option', 'disabled', true);";
            $returnText.= "jQuery('#eventLocationId').editable('option', 'disabled', true);";
        }





            // disabling the editable functionality fo the guest users from the UI side
            
            global $current_user;
            if ($current_user->ID == 0) {
                $returnText.= "jQuery('#eventName').editable('option', 'disabled', true);";
                $returnText.= "jQuery('#eventStatus').editable('option', 'disabled', true);";
                $returnText.= "jQuery('#eventType').editable('option', 'disabled', true);";
                $returnText.= "jQuery('#eventStartDate').editable('option', 'disabled', true);";
                $returnText.= "jQuery('#eventFinishDate').editable('option', 'disabled', true);";
                $returnText.= "jQuery('#eventCategoryId').editable('option', 'disabled', true);";
                $returnText.= "jQuery('#eventLocationId').editable('option', 'disabled', true);";
            }


        $returnText.="</script>";

        $returnText .= '<script>





    
                        //redrawing the calender
                        function redrawCalander(month,year,week,day){
                            //alert(week);
                            if(week==undefined && day==undefined){// if default view is month
                               // console.log(\'not set\');
                                //alert(\'not set\');


                                jQuery.ajax({
                                        url: "?redrawWADCalander=true&month="+month+"&year="+year,
                                        success: function (data) {
                                           // alert("redraw success");
                                           //jQuery("#calendar").fadeOut("normal");
                                           jQuery("#calendar").empty();
                                            
                                            jQuery("#calendar").html(data);
                                            //console.log(data);
                                            
                                        }
                                    });
                            }else if(week != undefined && day==undefined){//if deafult view is week
                                

                               

                                jQuery.ajax({
                                        url: "?redrawWADCalander=true&month="+month+"&year="+year+"&week="+week,
                                        success: function (data) {
                                           // alert("redraw success");
                                           //jQuery("#calendar").fadeOut("normal");
                                           jQuery("#calendar").empty();
                                            
                                            jQuery("#calendar").html(data);
                                            //console.log(data);
                                            
                                        }
                                    });

                            }else{// if default view is day

                                


                                jQuery.ajax({
                                        url: "?redrawWADCalander=true&month="+month+"&year="+year+"&week="+week+"&day="+day,
                                        success: function (data) {
                                           // alert("redraw success");
                                           //jQuery("#calendar").fadeOut("normal");
                                           jQuery("#calendar").empty();
                                            
                                            jQuery("#calendar").html(data);
                                            //console.log(data);
                                            
                                        }
                                    });

                            }
                        
                            
                        }




                        // initializing the google map
                        function initialize(add,newEvent) {

                           
                            var myCenter = new google.maps.LatLng(51.508742, -0.120850);
                            //var myCenter = "";
                            var mapProp = {
                                center: myCenter,
                                zoom: 10,
                                mapTypeId: google.maps.MapTypeId.ROADMAP
                            };

                            if(newEvent!=undefined && newEvent==true){
                                var map = new google.maps.Map(document.getElementById("googleMap2"), mapProp);
                            }else{
                                var map = new google.maps.Map(document.getElementById("googleMap"), mapProp);
                            }
                           
                            var marker = new google.maps.Marker({
                                position: myCenter,
                                animation: google.maps.Animation.BOUNCE
                            });
                            var geocoder = new google.maps.Geocoder();
                            geocodeAddress(geocoder, map, add);

                            marker.setMap(map);

                        }

                        //converting the address to longitudes and latitudes
                        function geocodeAddress(geocoder, resultsMap, add) {
                            var address = add;
                            console.log(address);
                            geocoder.geocode({\'address\': address}, function (results, status) {
                                if (status === google.maps.GeocoderStatus.OK) {
                                    resultsMap.setCenter(results[0].geometry.location);
                                    var marker = new google.maps.Marker({
                                        map: resultsMap,
                                        position: results[0].geometry.location
                                    });
                                } else {
                                    alert(\'Please enter a valid address \');
                                }
                            });
                        }


                        //loading event data into the model
                        function loadEventData(element) {
                            
                            
                            var eventData = JSON.parse(jQuery(element).attr(\'event-data\'));

                            //eventID = eventData.event_id;

                            jQuery(\'#eventModalTitle\').text(eventData.event_name);

                            jQuery(\'#eventName\').text(eventData.event_name);
                            jQuery(\'#eventDescription\').text(eventData.event_description);

                            jQuery(\'#eventStatus\').text((eventData.event_status == 0) ? "Draft" : "Published");
                            jQuery(\'#eventType\').text((eventData.event_type == 0) ? "Non-Recurring" : (eventData.event_type == 1) ? "Daily" : (eventData.event_type == 2) ? "Weekly" : "Monthly");

                            jQuery(\'#eventStartDate\').text(eventData.event_start);
                            jQuery(\'#eventFinishDate\').text(eventData.event_finish);//setting default value when opening the modal



                            //resetting comment commentSection
                            jQuery("#commentResponseMsg").empty();
                            jQuery("#comment").val("");

                            jQuery(document).ready(function ($) {

                                //console.log(eventData);


                                //initializing view event form elements

                                $(\'#eventName\').editable({
                                    type: \'text\',
                                    pk: eventData.event_id,
                                    url: \'?eventEdit=true\',
                                    title: \'Enter eventName\',


                                    success: function (response, newValue) {
                                        eventData.event_name = newValue;
                                        //changing  clander data to new values
                                        eventData.event_name = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));
                                        $(element).text(newValue);
                                        $(\'#eventModalTitle\').text(newValue);

                                    }
                                });

                                
                                $(\'#eventDescription\').editable({
                                    type: \'textarea\',
                                    pk: eventData.event_id,
                                    url: \'?eventEdit=true\',
                                    title: \'Enter event Description\',



                                    success: function (response, newValue) {
                                       
                                        eventData.event_description = newValue;
                                        //changing  clander data to new values
                                        eventData.event_description = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));
                                        
                                        $(element).attr("title", newValue);
                                        

                                    }
                                });



                                $(\'#eventStatus\').editable({
                                    type: \'select\',
                                    pk: eventData.event_id,
                                    url: \'?eventEdit=true\',
                                    title: \'Event Status\',
                                    source: [{value: 0, text: \'Draft\'}, {value: 1, text: \'Published\'}],
                                    emptytext: \'Draft\',
                                    value: eventData.event_status,

                                    success: function (response, newValue) {

                                        //changing  clander data to new values
                                        eventData.event_status = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));

                                        if (newValue == 0) {
                                            $(element).removeClass(\'label-info\');
                                            $(element).addClass(\'label-default\');

                                        } else {
                                            $(element).removeClass(\'label-default\');
                                            $(element).addClass(\'label-info\');
                                        }


                                    }
                                });


                                $(\'#eventType\').editable({
                                    type: \'select\',
                                    pk: eventData.event_id,
                                    url: \'?eventEdit=true\',
                                    title: \'Event Status\',
                                    source: [{value: 0, text: \'Non-Recurring\'}, {value: 1, text: \'Daily\'}, {value: 2, text: \'Weekly\'}, {value: 3, text: \'Monthly\'}, {value: 4, text: \'Yearly\'}],
                                    emptytext: \'Draft\',
                                    value: eventData.event_type,

                                    success: function (response, newValue) {

                                        //changing  clander data to new values
                                        eventData.event_type = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));
                                        redrawCalander($("#calendarHeaderText").data("month"),$("#calendarHeaderText").data("year"));


                                    }
                                });


                                $("#eventStartDate").editable({

                                    type: "datetime",
                                    pk: eventData.event_id,
                                    url: \'?eventEdit=true\',
                                    format: "yyyy-mm-dd hh:ii:ss",
                                    // viewformat: "dd/mm/yyyy hh:ii",
                                    value: eventData.event_start,
                                    datetimepicker: {
                                        weekStart: 1
                                    },
                                    success: function (response, newValue) {
                                        eventData.event_start = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));
                                        redrawCalander($("#calendarHeaderText").data("month"),$("#calendarHeaderText").data("year"));

                                    }
                                });

                                $("#eventFinishDate").editable({
                                    type: "datetime",
                                    pk: eventData.event_id,
                                    url: \'?eventEdit=true\',
                                    format: "yyyy-mm-dd hh:ii:ss",
                                    // viewformat: "dd/mm/yyyy hh:ii",
                                    value: eventData.event_finish,
                                    datetimepicker: {
                                        weekStart: 1
                                    },
                                    success: function (response, newValue) {
                                        eventData.event_finish = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));
                                        redrawCalander($("#calendarHeaderText").data("month"),$("#calendarHeaderText").data("year"));

                                    }
                                });

                                $("#eventCategoryId").editable({
                                    type: "select",
                                    pk: eventData.event_id,
                                    source: "?eventEdit=true&eventGetData=true&action=getCategoryList",
                                    url: \'?eventEdit=true\',
                                    sourceCache: false,
                                    value: eventData.event_category_id,
                                    success: function (response, newValue) {
                                        eventData.event_category_id = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));
                                    }
                                    // viewformat: "dd/mm/yyyy hh:ii",
                                    //value:eventData.event_finish,


                                });

                                $("#eventLocationId").editable({
                                    type: "select",
                                    pk: eventData.event_id,
                                    source: "?eventEdit=true&eventGetData=true&getCategoryList=true&action=getLocationList",
                                    url: \'?eventEdit=true\',
                                    sourceCache: false,
                                    value: eventData.event_location_id,
                                    success: function (response, newValue) {
                                        eventData.event_location_id = newValue;
                                        $(element).attr(\'event-data\', JSON.stringify(eventData));

                                        $.ajax({
                                            url: "?eventEdit=true&eventGetData=true&getCategoryList=true&action=getLocationNames&pk=1",
                                            success: function (data) {
                                                var sourceObj = JSON.parse(data);
                                                for (var i = sourceObj.length - 1; i >= 0; i--) {

                                                    if (sourceObj[i].value == newValue) {

                                                        initialize(sourceObj[i].text);

                                                    }
                                                }
                                                ;


                                            }
                                        });
                                    }

                                });


                                //loading comments
                                $("#commentForm").attr("event",eventData.event_id)
                                $.ajax({
                                        url: "?eventEdit=true&eventGetData=true&action=getComments&pk="+eventData.event_id,
                                        success: function (data) {
                                            var messages = JSON.parse(data);
                                            $("#messagesList").empty();

                                            if( messages != null && messages.length != undefined){ // check to see whether there are comments for the event
                                                for(var i =0; i<messages.length;i++){

                                                    $("#messagesList").append("<li class=\"list-group-item \">"+
                                                                            "<div class=\"row\">"+
                                                                                "<div class=\"col-xs-2 col-md-1\">"+
                                                                                    "<img src=\"http://0.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=80\" class=\"img-circle img-responsive\" alt=\"\" /></div>"+
                                                                                "<div class=\"col-xs-10 col-md-11\">"+
                                                                                    "<div>"+
                                                                                        "<div class=\"mic-info\">"+
                                                                                            "By: <a href=\"#\">"+messages[i].messageAuthor+"</a> on "+messages[i].messageDate+
                                                                                        "</div>"+
                                                                                    "</div>"+
                                                                                    "<div class=\"comment-text\">"+
                                                                                        messages[i].messageContent+
                                                                                    "</div>"+
                                                                                    "<div class=\"action\">"+
                                                                                "</div>"+
                                                                            "</div>"+
                                                                        "</li>");

                                                }
                                            }
                                          


                                        }
                                    });


                                setTimeout(function () {
                                    $.ajax({
                                        url: "?eventEdit=true&eventGetData=true&action=getLocationNames&pk=1",
                                        success: function (data) {
                                            var sourceObj = JSON.parse(data);
                                            for (var i = sourceObj.length - 1; i >= 0; i--) {

                                                if (sourceObj[i].value == eventData.event_location_id) {

                                                    initialize(sourceObj[i].text);

                                                }
                                            }
                                            ;


                                        }
                                    });
                                }, 50);
                                


                              
                                


                            });';    

        $returnText .=  '
                    }


                    //populating the event model
                    function populateEventModel(element) {


                         
                       

                        //initializing elemnts in "Add New tab"

                        jQuery("#add-new-start-Date").datepicker({
                                                            changeMonth: true,
                                                            changeYear: true,
                                                            dateFormat: "DD, d MM, yy",
                                                            minDate: 0,
                                                            
                                                            onSelect: function(selected) {
                                                                jQuery("#add-new-finish-Date").datepicker("option","minDate", selected)
                                                            }
                                                        });
                        jQuery("#add-new-finish-Date").datepicker({
                                                            changeMonth: true,
                                                            changeYear: true,
                                                            dateFormat: "DD, d MM, yy",
                                                            minDate: 0,
                                                            
                                                            onSelect: function(selected) {
                                                                jQuery("#add-new-start-Date").datepicker("option","maxDate", selected)
                                                            }
                                                        });

                        


                        //resetting(emptying) the fields
                        jQuery("#txtEventName").val("");
                        jQuery("#responseMsg").empty();




                        //resetting the validation
                        jQuery("#add-new-event-form").validate().resetForm();


                        //populating combo boxes
                        populateComboBox(jQuery("#cmbEventCategory"));
                        populateComboBox(jQuery("#cmbEventLocation"));
                        
                        
       


                        var _event = "";

                        //console.log(jQuery(element).children());
                        var eventList = jQuery(element).children();

                        jQuery("#eventList").empty();// removing all the events drawn previously

                        if (eventList.length == 0) {
                            //alert("add new evens");
                            jQuery(\'#show-all-events-tab\').hide();
                            jQuery(\'#show-all-events-tab\').removeClass("active");
                            jQuery(\'#add-new-events-tab\').addClass("active");
                            jQuery(\'#btnAddNewEvent\').show();


                            jQuery(\'#eventsTab\').removeClass(\'active in\');
                            jQuery(\'#addNewEventTab\').addClass(\'active in\');
                        } else {
                           // alert("all events");
                            jQuery(\'#show-all-events-tab\').show();
                            jQuery(\'#show-all-events-tab\').addClass("active");
                            jQuery(\'#add-new-events-tab\').removeClass("active");
                            jQuery(\'#btnAddNewEvent\').hide();

                            jQuery(\'#eventsTab\').addClass(\'active in\');
                            jQuery(\'#addNewEventTab\').removeClass(\'active in\');
                        }

                        for (var i = 0; i < eventList.length; i++) {
                            originalEvent = jQuery(eventList[i]).children()[0];
                            //console.log(originalEvent);
                            originalEventData = JSON.parse(jQuery(originalEvent).attr(\'event-data\'));
                            //console.log(originalEventData);
                            //var eventData = JSON.parse(jQuery(singleLink).attr(\'event-data\'));


                            //_event += singleLink;

                            _event = \'<a data-toggle="modal" data-target="#eventDetails" href="#" class="\' + ((originalEventData.event_status == 0) ? "" : "active") + \'"" onclick="loadEventData(\' + "event_" + originalEventData.event_id + \')" class="list-group-item">\' + originalEventData.event_name + \'<span class="badge">\' + ((originalEventData.event_status == 0) ? "Draft" : "Published") + \'</span></a>\';

                            jQuery(jQuery(_event).addClass("list-group-item")).appendTo("#eventList");
                            //jQuery("#eventList").append("a").addClass("list-group-item");


                        }


                        //console.log(jQuery("#eventList").append(_event));


                    }


                    //populating comboboxes categories/location to the combo box
                    function populateComboBox(element){
                        jQuery(element).empty();

                        console.log(element.selector);

                        var URL = "";
                        if(element.selector =="#cmbEventCategory"){
                            //retriving the catetories
                            URL = "?eventEdit=true&eventGetData=true&action=getCategoryList&pk=1";
                        }else if(element.selector =="#cmbEventLocation"){
                            //retriving the location names
                            URL = "?eventEdit=true&eventGetData=true&getCategoryList=true&action=getLocationList";
                        }

                        jQuery.ajax({
                            url: URL,
                            success: function (data) {
                                var sourceObj = JSON.parse(data);


                                jQuery.each(sourceObj, function (i, item) {
                                    jQuery(element).append(jQuery("<option>", { 
                                        value: item.value,
                                        text : item.text 
                                    }));


                                
                                });

                                
                                if(element.selector = "#cmbEventLocation"){
                                    var newValue = jQuery("#cmbEventLocation").val();

                                        jQuery.ajax({
                                            url: "?eventEdit=true&eventGetData=true&getCategoryList=true&action=getLocationNames&pk=1",
                                            success: function (data) {
                                                var sourceObj = JSON.parse(data);
                                                for (var i = sourceObj.length - 1; i >= 0; i--) {

                                                    if (sourceObj[i].value == newValue) {
                                                        console.log(sourceObj[i].text);
                                                        initialize(sourceObj[i].text,true);

                                                    }
                                                }
                                                ;


                                            }
                                        });
                                }         

                            }
                        });
                    }


                    </script>


                    <!-- Events  Modal -->
                    <div class="bootstrap-wrapper">
                        <div class="modal fade" id="events" role="dialog">
                            <div class="modal-dialog" style="margin:15%">

                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header hidden">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title" id="allEventsModalTitle">Event List</h4>
                                    </div>
                                    <div class="modal-body">


                                        <ul class="nav nav-tabs">
                                            <li id="show-all-events-tab" class="active"><a data-toggle="tab"
                                                                                           href="#eventsTab" onclick="jQuery(\'#btnAddNewEvent\').hide();">Events</a></li>
                                            <li id="add-new-events-tab" ><a data-toggle="tab" href="#addNewEventTab" onclick="jQuery(\'#btnAddNewEvent\').show();">Add New Event</a>
                                            </li>

                                        </ul>

                                        <div class="tab-content">
                                            <div id="eventsTab" class="tab-pane fade in active">
                                                <br>
                                                <!-- list of events-->
                                                <div id="eventList" class="list-group">

                                                    <a href="#" class="list-group-item">Dapibus ac facilisis in</a>

                                                </div>
                                            </div>
                                            <div id="addNewEventTab" class="tab-pane fade " >
                                               '.(($current_user->ID==0)? "<div class=\"alert alert-warning\" style=\"margin-top:1em;\">You need to be loged in to create new events. Please log in and refresh the page.</div>":"" ).'

                                                <form class="form-horizontal '.(($current_user->ID==0)?" hide ":"").'" id="add-new-event-form" >
                                                    <fieldset>

                                                        <div class="row">
                                                            <div id="responseMsg" class="col-md-12">

                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                             <div class="col-md-6">


                                                                <!-- Text input-->
                                                                <div class="form-group">
                                                                  <label class="col-md-6 " for="txtEventName">Event Name</label>  
                                                                  <div class="col-md-6">
                                                                  <input id="txtEventName" name="txtEventName" type="text" placeholder="Enter event name" class="form-control input-md" required="">
                                                                  <small>3-100 characters</small>
                                                                  </div>
                                                                </div>
                                                            </div>

                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="form-group">

                                                                  <label class="col-md-12 " for="textareaDescription">Event Description</label>
                                                                  <div class="col-md-12">
                                                                        <textarea class="wp-editor-area col-md-12" rows="7" cols="40" placeholder="Enter event description" name="textareaDescription" id="textareaDescription"></textarea>
                                                                        <small>0-1000 characters</small>
                                                                  </div>
                                                                    
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <!-- first half start -->
                                                            <div class="col-md-6">

                                                               
                                                                <!-- Select Basic -->
                                                                <div class="form-group">
                                                                  <label class="col-md-6 " for="cmbEventStatus" >Event Status
                                                                  <img title="Published events are vissible to all members while draft events are visible only to the author" data-toggle="tooltip" style="height:1em;" src="'. plugins_url( 'img/help.png', __FILE__ ) .'" />
                                                                  </label>
                                                                  <div class="col-md-6">
                                                                    <select id="cmbEventStatus" name="cmbEventStatus" class="form-control">
                                                                      <option value="0">Draft</option>
                                                                      <option value="1">Published</option>
                                                                    </select>
                                                                  </div>
                                                                </div>

                                                                <!-- Select Basic -->
                                                                <div class="form-group">
                                                                  <label class="col-md-6 " for="cmbEventRecurrence">Recurring Frequency
                                                                  <img title="Determine how often each event needs to be repeat" data-toggle="tooltip" style="height:1em;" src="'. plugins_url( 'img/help.png', __FILE__ ) .'" />
                                                                  </label>
                                                                  <div class="col-md-6">
                                                                    <select id="cmbEventRecurrence" name="cmbEventRecurrence" class="form-control">
                                                                      <option value="0">None</option>
                                                                      <option value="1">Daily</option>
                                                                      <option value="2">Weekly</option>
                                                                      <option value="3">Monthly</option>
                                                                      <option value="4">Yearly</option>
                                                                    </select>
                                                                  </div>
                                                                </div>


                                                                <div class="form-group">
                                                                    <label class="col-md-6 " for="add-new-start-Date">Start Date</label>

                                                                    <div class="col-md-6">
                                                                        <input type="text" name="txtAddNewStartDate" id="add-new-start-Date">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label class="col-md-6 " for="add-new-finish-Date">Finish Date</label>

                                                                    <div class="col-md-6">
                                                                        <input type="text" name="txtAddNewFinishDate" id="add-new-finish-Date">
                                                                    </div>
                                                                </div>


                                                                <div class="form-group">
                                                                  <label class="col-md-6 " for="cmbEventCategory">Event Category
                                                                  <img title="All published categories will be available to select" data-toggle="tooltip" style="height:1em;" src="'. plugins_url( 'img/help.png', __FILE__ ) .'" />
                                                                  </label>
                                                                  <div class="col-md-6">
                                                                    <select id="cmbEventCategory"  name="cmbEventCategory" class="form-control">
                                                                      
                                                                    </select>
                                                                  </div>
                                                                </div>

                                                            </div> <!-- first half ends-->

                                                            <!-- second half starts-->
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                  <label class="col-md-6 " for="cmbEventLocation">Event Location
                                                                  <img title="All published locations will be available to select" data-toggle="tooltip" style="height:1em;" src="'. plugins_url( 'img/help.png', __FILE__ ) .'" />
                                                                  </label>
                                                                  <div class="col-md-6">
                                                                    <select id="cmbEventLocation"  name="cmbEventLocation" class="form-control">
                                                                      
                                                                    </select>
                                                                  </div>
                                                                </div>

                                                                <div id="googleMap2" class="col-md-12" style="height:250px"></div>

                                                            </div>
                                                            <!--second half ends-->
                                                        </div>


                                                        


                                                       
                                                    </fieldset>
                                                </form>




                                            </div>

                                        </div>


                                    </div>
                                    <div class="modal-footer">
                                        '.(($current_user->ID!=0)? "<button id =\"btnAddNewEvent\" type=\"button\" onclick=\"jQuery('#add-new-event-form').submit();\" class=\"btn btn-primary\">Add</button>":"").'
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>


                    <!-- Event details Modal -->
                    <div class="bootstrap-wrapper">
                        <div class="modal fade" id="eventDetails" role="dialog">
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
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                                <label class="control-label " for="eventDescription">Event Description : </label>
                                                                <!-- <div class="col-sm-10"> -->
                                                                <a href="#" id="eventDescription"></a>
                                                                <!-- </div> -->
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">

                                                <div class="col-md-6">
                                                    <div class="form-horizontal" role="form">

                                                       

                                                        <div class="form-group">
                                                            <label class="control-label " for="eventStatus">Event Status : </label>
                                                            <!-- <div class="col-sm-10"> -->
                                                            <a href="#" id="eventStatus"></a>
                                                            <!-- </div> -->
                                                        </div>

                                                        <div class="form-group">
                                                            <label class="control-label " for="eventStatus">Recurring Frequency
                                                                : </label>
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
                                                            <label class="control-label " for="eventCategoryId">Event Category
                                                                : </label>
                                                            <!-- <div class="col-sm-10"> -->
                                                            <a href="#" id="eventCategoryId"></a>
                                                            <!-- <a id="addCategory" class="btn btn-primary glyphycon glyphicon-add"></a> -->
                                                            <!-- </div> -->
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label " for="eventLocationId">Event Location : </label>
                                                        <!-- <div class="col-sm-10"> -->
                                                        <a href="#" id="eventLocationId"></a>
                                                        <!-- <a id="addCategory" class="btn btn-primary glyphycon glyphicon-add"></a> -->
                                                        <!-- </div> -->
                                                    </div>


                                                    <div id="googleMap" class="col-md-12" style="height:250px"></div>
                                                </div>

                                            </div>

                                        </div>

                                        <div class="row">

                                            <div col-md-12 style="padding-top:2em">
                                                    <div class="panel panel-default widget" id="panelMsg">
                                                        <div class="panel-heading">
                                                            <span class="glyphicon glyphicon-comment"></span>
                                                            <h3 class="panel-title">
                                                                <a data-toggle="collapse" data-target="#collapseMsgSection" href="#collapseMsgSection" class="collapsed">
                                                                    Comments
                                                                </a>
                                                            </h3>
                                                        </div>
                                                        <div id="collapseMsgSection" class="panel-collapse collapse">
                                                            <div class="panel-body commentSection">

                                                                <!--posting comments-->
                                                                <div>
                                                                    <div id="commentResponseMsg"></div>
                                                                     <form id="commentForm"'.(($current_user->ID==0)?" class=\"hide\"":"").'>
                                                                      <div class="form-group">
                                                                        <label for="comment">Your Comment</label>
                                                                        <textarea id="comment" name="comment" required="true" class="form-control" rows="3"></textarea>
                                                                      </div>
                                                                      <button id="btnCommentSend" type="submit" class="btn btn-default">Send</button>
                                                                    </form>

                                                                </div>

                                                                <ul class="list-group" id="messagesList">

                                                                    

                                                                    
                                                                    
                                                                </ul>
                                                                
                                                            </div>
                                                        </div>
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



                    
                    </div><!-- end of contentWrapper-->
                    ';

    
   // echo pr($shortcodeattributes);
    //days of the week used for headings. This particular method is not particulary multilanguage friendly.
    $weekdays = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    extract(shortcode_atts(array('year' => '-', 'month' => '-', 'week' => weekOfMonth(date("Y-m-d")), 'defaultview' => '-', 'dayfordayview' => '-'), $shortcodeattributes));
    
    
    if ($month == '-') $month = date('m');
    if ($year == '-') $year = date('Y');
    if ($defaultview == '-') $defaultview=0;
    if ($dayfordayview == '-') $dayfordayview=date('d');


    //retrieving the default view of the user
    $users_table = $wpdb->prefix . 'js_users';
    $query = "SELECT default_view FROM $users_table WHERE user_id=$current_user->ID";
    $settings_list = $wpdb->get_results($query);

    if(!empty($settings_list)){
        $defaultview = $settings_list[0]->default_view;
    }

    //get the previous month's days - used to fill in the blank days at the start.  
    //make sure we roll over to december in the case of $month being January    
    if ($month == 1) //January?
        $prevmonth = 12; //December
    else
        $prevmonth = $month - 1;
    //shortend, harder to read, version of the if ...else... above   

    $prevmonth = ($month == 1) ? 12 : $month - 1;

    $prevdays = date('t', mktime(0, 0, 1, $prevmonth, 1, $year));    //days in the previous month
    //calculate a few date values for the current/selected month & year 
    $dow = date('w', mktime(0, 0, 1, $month, 1, $year)); //day of the week
    $days = date('t', mktime(0, 0, 1, $month, 1, $year));    //days in the month
    $lastblankdays = 7 - (($dow + $days) % 7); //remaining days in the last week

    $lastblankdays = ($lastblankdays == 7) ? 0 : $lastblankdays;

    $no_of_weeks_in_the_month = ceil(($dow + $days+$lastblankdays)/7);// no of weeks in the given month
   
    
    //ho  date('t', mktime(0, 0, 1, (($month==1 && $week==1)?12:($week==1)?$month-1: $month), 1, (($month==1 && $week==1)?$year-1:$year)));

    //previous month
    $dowPrevMonth = date('w', mktime(0, 0, 1, (($month==1 && $week==1)?12:(($week==1)?$month-1: $month)), 1, (($month==1 && $week==1)?$year-1:$year))); //day of the week
    $daysPrevMonth = date('t', mktime(0, 0, 1, (($month==1 && $week==1)?12:(($week==1)?$month-1: $month)), 1, (($month==1 && $week==1)?$year-1:$year)));    //days in the month
    $lastblankdaysPrevMonth =  7 - (($dowPrevMonth + $daysPrevMonth) % 7); //remaining days in the last week of the previous month

    $lastblankdaysPrevMonth = ($lastblankdaysPrevMonth == 7) ? 0 : $lastblankdaysPrevMonth;


    //echo $lastblankdaysPrevMonth;
    $no_of_weeks_in_the_prev_month = ceil(($dowPrevMonth + $daysPrevMonth+$lastblankdaysPrevMonth)/7);// no of weeks in the previous month


    // //next month
    // $dowNextMonth = date('w', mktime(0, 0, 1, (($month==12)?1:$month+1), 1, (($month==12)?$year+1:$year))); //day of the week
    // $daysNextMonth = date('t', mktime(0, 0, 1, (($month==12)?1:$month+1), 1, (($month==12)?$year+1:$year)));    //days in the month
    // $lastblankdaysNextMonth = 7 - (($dowNextMonth + $daysNextMonth) % 7); //remaining days in the last week of the previous month

    // $no_of_weeks_in_the_next_month = ceil(($dowNextMonth + $daysNextMonth+$lastblankdaysNextMonth)/7);// no of weeks in the next month

    //echo "<script>console.log('$no_of_weeks_in_the_month');</script>;";

    //calendar heading - note we are using flexbox for the styling
    switch ($defaultview) {
        //month view
        case '0':
            $thedate = date('F Y', mktime(0, 0, 1, $month, 1, $year));

            $returnText.= '<main ><div id="calendar">
                            <div class="bootstrap-wrapper" style="text-align:center;padding-bottom:2em;">
                                <span style="float:left" class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==1)?12:$month-1).','.(($month==1)?$year-1:$year).')" ><i class="glyphicon glyphicon-chevron-left"></i><span class="hidden-xs"> Prev Month</span></span>
                                <span id="calendarHeaderText" data-month='.$month.' data-year='.$year.' style="text-align:center;font-size:1.3em">' . $thedate . '</span>
                                <span style="float:right"  class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==12)?1:$month+1).','.(($month==12)?$year+1:$year).')" ><span class="hidden-xs">Next Month </span><i class="glyphicon glyphicon-chevron-right"></i></span>
                            </div>
                            

                            <div class="th">';
            break;

        //week view
        case '1':
            $thedate = date('F Y', mktime(0, 0, 1, $month, 1, $year));


            if(1<$week && $week<$no_of_weeks_in_the_month){
                $prevWeek = $week-1;
                $nextWeek = $week+1;
            }else if($week==1){
                $prevWeek = $no_of_weeks_in_the_prev_month;
                $nextWeek = $week+1;
            }else if($week==$no_of_weeks_in_the_month){
                $prevWeek = $week-1;
                $nextWeek = 1;

            }

            
            $returnText.= '<main id="calendar">
                                <div class="bootstrap-wrapper" style="text-align:center;padding-bottom:2em;">
                                    <span style="float:left" class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==1 && $week==1)?"12":(($week==1)?$month-1: $month)).','.(($month==1 && $week==1)?$year-1:$year).','.$prevWeek.')" ><i class="glyphicon glyphicon-chevron-left"></i><span class="hidden-xs"> Prev Week</span></span>
                                    <span id="calendarHeaderText" data-week='.$week.' data-month='.$month.' data-year='.$year.' style="text-align:center;font-size:1.3em">' . $thedate . '</span>
                                    <span style="float:right"  class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==12 && $week == $no_of_weeks_in_the_month)?1:(($week == $no_of_weeks_in_the_month)?$month+1:$month)).','.(($month==12 && $week==$no_of_weeks_in_the_month)?$year+1:$year).','.$nextWeek.')" ><span class="hidden-xs">Next Week </span><i class="glyphicon glyphicon-chevron-right"></i></span>
                                </div>
                                

                                <div class="th">';
            break;

        //day view
        case '2':
         $thedate = date('d F Y', mktime(0, 0, 1, $month, $dayfordayview, $year));


            if(1<$week && $week<$no_of_weeks_in_the_month){
                $prevWeek = $week-1;
                $nextWeek = $week+1;
            }else if($week==1){
                $prevWeek = $no_of_weeks_in_the_prev_month;
                $nextWeek = $week+1;
            }else if($week==$no_of_weeks_in_the_month){
                $prevWeek = $week-1;
                $nextWeek = 1;

            }

            //for previous day button
            $prevDay = date('d', strtotime('-1 day', strtotime($thedate)));
            $prevMon = date('m', strtotime('-1 day', strtotime($thedate)));
            $prevYear = date('Y', strtotime('-1 day', strtotime($thedate)));

            //for next day button
            $nextDay = date('d', strtotime('+1 day', strtotime($thedate)));
            $nextMon = date('m', strtotime('+1 day', strtotime($thedate)));
            $nextYear = date('Y', strtotime('+1 day', strtotime($thedate)));


            $returnText.= '<main id="calendar">
                                <div class="bootstrap-wrapper" style="text-align:center;padding-bottom:2em;">
                                    <span style="float:left" class="btn btn-sm btn-primary" onclick="redrawCalander('.$prevMon.','.$prevYear.','.$prevWeek.','.$prevDay.')" >Prev Day</span>
                                    <span id="calendarHeaderText" data-week='.$week.' data-month='.$month.' data-year='.$year.' style="text-align:center;font-size:1.3em">' . $thedate . '</span>
                                    <span style="float:right"  class="btn btn-sm btn-primary" onclick="redrawCalander('.$nextMon.','.$nextYear.','.$nextWeek.','.$nextDay.')" >Next Day</span>
                                </div>
                                

                                ';
            break;

        default:
            # code...
            break;
    }
    
    //HEADING ROW: print out the week names 
    if($defaultview != "3" && $defaultview != "2"){//preventing from printing the days if the default view is day/list
        foreach ($weekdays as $wd) {
            $returnText.= '<span>' . $wd . '</span>';
        }
        $returnText.= '</div>';
    }

    //CALENDAR WEEKS: generate the calendar body
    //starting day of the previous month, used to fill the blank day slots

    $startday = $prevdays - ($dow - 1); //calculate the number of days required from the prev month



    //$returnText.= "<script>console.log('".weekOfMonth(date("Y-m-d"))."');</script>";





    //PART 1: first week with initial blank days (cells) or previous month
    if($defaultview != "2"  && $defaultview != "3"){
        $returnText.= '<div class="week">';
    }else if($defaultview ==3){
        $returnText.="<div>&nbsp;</div>";
    }

    for ($i = 0; $i < $dow; $i++)
        //refer to lines 43-53 in the WADcalendar.css for information regarding the data-date styling
        if($defaultview==0 ){
            //if default view is month prints the last days of the previous month
            $returnText.= '<div class="disabledDay" data-date="' . $startday++ . '"></div>';//!! this increments $startday AFTER the value has been used

        }else if($defaultview==1 && $week==1){
            //if default view is week and current week is the first week of the month, prints the last days of the previous month 
            $returnText.= '<div class="disabledDay" data-date="' . $startday++ . '"></div>';//!! this increments $startday AFTER the value has been used

        }


    //getting the event list
    $event_list = get_events($year, $month);


    if($dow!=0)
        $tempWeekCount = 1;//temporary weeek counter
    else
        $tempWeekCount = 0;//temporary weeek counter if the 1st week starts from sundy 

    //PART 2: main calendar calendar body
    for ($i = 0; $i < $days; $i++) {

        //check for the week boundary - % returns the remainder of a division
        if (($i + $dow) % 7 == 0) { //no remainder means end of the week
            if($defaultview!= "2" && $defaultview != "3"){
                $returnText.= '</div><div class="week">';
            }
            $tempWeekCount += 1;
        }

        switch ($defaultview) {
            case '0'://if default view is month

                //print the actual day (cell) with events
                $returnText.= '<div id="dayCell" data-date="' . ($i + 1) . '"'.(( ($year == date("Y")) && ($month == date("m")) && (($i+1) == date("d")) )?" style=\"border:1px solid #93CA81;\" ":"").' data-toggle="modal" data-target="#events" onclick="populateEventModel(this)">'; //add 1 to the for loop variable as it starts at zero not one

                ;
                //event code and such here
                foreach ($event_list as $event) {
                    //pr($event["event_day"]);
                    if ($event["event_day"] == ($i + 1)) {
                        //$returnText.= $event["event_name"];
                        $returnText.= '<div id="eventDetailsModel" class="bootstrap-wrapper" style="margin:0px 0px 5px 0px" >
                            <a id="event_' . $event["event_id"] . '" data-toggle="modal" data-target="#eventDetails" class="label ' . (($event["event_status"] == 1) ? "label-info" : "label-default") . '" event-data=\'' . json_encode($event) . '\'" title="' . $event["event_description"] . '" onclick="loadEventData(event_' . $event["event_id"] . ')">' . $event["event_name"] . '</a></div>';

                    }
                }


                $returnText.= '</div>';
                
                break;

            case '1':// if default view is weeek

                if($tempWeekCount==$week){

                    //print the actual day (cell) with events
                    $returnText.= '<div id="dayCell" data-date="' . ($i + 1) . '"'.(( ($year == date("Y")) && ($month == date("m")) && (($i+1) == date("d")) )?" style=\"border:1px solid #93CA81;\" ":"").' data-toggle="modal" data-target="#events" onclick="populateEventModel(this)">'; //add 1 to the for loop variable as it starts at zero not one


                    //event code and such here
                    foreach ($event_list as $event) {
                        //pr($event["event_day"]);
                        if ($event["event_day"] == ($i + 1)) {
                            //$returnText.= $event["event_name"];
                            $returnText.= '<div id="eventDetailsModel" class="bootstrap-wrapper" style="margin:0px 0px 5px 0px" >
                                <a id="event_' . $event["event_id"] . '" data-toggle="modal" data-target="#eventDetails" class="label ' . (($event["event_status"] == 1) ? "label-info" : "label-default") . '" event-data=\'' . json_encode($event) . '\'" title="' . $event["event_description"] . '" onclick="loadEventData(event_' . $event["event_id"] . ')">' . $event["event_name"] . '</a></div>';

                        }
                    }


                    $returnText.= '</div>';


                    


                }


                break;

            case '2'://if default view is list
                

                foreach ($event_list as $event) {
                            //pr($event["event_day"]);
                    if ($event["event_day"] == $dayfordayview && $dayfordayview==($i+1)) {

                        $events_exist = 1;

                        //retrieving the month name by the month no.
                        $dateObj   = DateTime::createFromFormat('!m', $event['event_month']);
                        $monthName = $dateObj->format('M');

                        $returnText.="<div class=\" \">
                                        <div class=\"row\">
                                            <div class=\"[ col-xs-12 col-sm-offset-2 col-sm-8 ]\">
                                                <ul class=\"event-list\">
                                                    <li >
                                                        <time class=\"".(($event['event_status'] == 0)? 'draft':'')."\">
                                                            <span class=\"day\">".$event['event_day']."</span>
                                                            <span class=\"month\">".$monthName."</span>
                                                            <span class=\"year\">".$event['event_year']."</span>
                                                            <span class=\"time\">".$event['event_time']."</span>
                                                        </time>
                                                        <div class=\"info\">
                                                            <h2 class=\"title\">".'<a id="event_' . $event["event_id"] . '" data-toggle="modal" data-target="#eventDetails" class="label ' . (($event["event_status"] == 1) ? "label-info" : "label-default") . '" event-data=\'' . json_encode($event) . '\'" title="' . $event["event_description"] . '" onclick="loadEventData(event_' . $event["event_id"] . ')">' . $event["event_name"] . '</a>'."</h2>
                                                            <p class=\"desc\">".$event['event_description']."</p>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>";
                    }


                }

               

                break;
            
            default:
                # code...
                break;
        }

        
    }

    //printing "NO events div " if there are no events in the day view
    if($defaultview=="2" && !isset($events_exist)){

        $returnText.="<div class=\" \">
                                        <div class=\"row\">
                                            <div class=\"[ col-xs-12 col-sm-offset-2 col-sm-8 ]\">
                                                <ul class=\"event-list\">
                                                    <li >
                                                        
                                                        <div class=\"info\">
                                                            <h2 class=\"title\" style='text-align:center;padding-top:1em;'>No events</h2>
                                                            <p class=\"desc\"></p>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>";
    }


    // printing the view if the default view is list view
    if($defaultview=="3"){

        $returnText.="<div id=\"list-view-container\" class=\"list-view-container\" >";

        $event_list = get_all_events(false,false);

        $isIdSet=false;
        foreach ($event_list as $event) {
            $upComingId = "";
            if( (strtotime($event['event_date']) >strtotime( date('Y-m-d H:i:s'))) && !$isIdSet){
                $upComingId = "next-up-comming-event";
                $isIdSet =true;
            }

            //retrieving the month name by the month no.
            $dateObj   = DateTime::createFromFormat('!m', $event['event_month']);
            $monthName = $dateObj->format('M');
           
            $returnText.="<div id=\"".$upComingId."\" class=\"\">
                                    <div class=\"row\">
                                        <div class=\"[ col-xs-12 col-sm-offset-2 col-sm-8 ]\">
                                            <ul class=\"event-list\">
                                                <li >
                                                    <time class=\"".(($event['event_status'] == 0)? 'draft':'')."\">
                                                        <span class=\"day\">".$event['event_day']."</span>
                                                        <span class=\"month\">".$monthName."</span>
                                                        <span class=\"year\">".$event['event_year']."</span>
                                                        <span class=\"time\">".$event['event_time']."</span>
                                                    </time>
                                                    <div class=\"info\">
                                                        <h2 class=\"title\">".'<a id="event_' . $event["event_id"] . '" data-toggle="modal" data-target="#eventDetails" class="label ' . (($event["event_status"] == 1) ? "label-info" : "label-default") . '" event-data=\'' . json_encode($event) . '\'" title="' . $event["event_description"] . '" onclick="loadEventData(event_' . $event["event_id"] . ')">' . $event["event_name"] . '</a>'."</h2>
                                                        <p class=\"desc\">".$event['event_description']."</p>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>";

        }
        //automatically scrolling to the next up coming event 
        $returnText.="</div> <script>jQuery(\"#list-view-container\").animate({
                                                                        scrollTop: (jQuery(\"#next-up-comming-event\").offset().top-jQuery(\"#list-view-container\").offset().top)
                                                                    }, 1000);</script>";

    }



    if($defaultview==0){
        //if default view is month prints the first days of the next month
        //PART 3: last week with blank days (cells) or couple of days from next month
        $j = 1; //counter for next months days used to fill in the blank days at the end
        for ($i = 0; $i < $lastblankdays; $i++)
            $returnText.= '<div class="disabledDay" data-date="' . $j++ . '"></div>'; //!! this increments $j AFTER the value has been used
        //close off the calendar    
        $returnText.= '</div></main>';
    }else if ($defaultview==1 && $week == $no_of_weeks_in_the_month) {
        //if default view is week and current week is the last week of the month, prints the frst days of the next month 
        //PART 3: last week with blank days (cells) or couple of days from next month
        $j = 1; //counter for next months days used to fill in the blank days at the end
        for ($i = 0; $i < $lastblankdays; $i++)
            $returnText.= '<div class="disabledDay" data-date="' . $j++ . '"></div>'; //!! this increments $j AFTER the value has been used
        //close off the calendar    
        $returnText.= '</div></div> </main>';
    }
    


    
    return $returnText;
exit;
}

//retriving the week of the month
function weekOfMonth($date) {
    //echo "<script>alert('$date');</script>";
    //Get the first day of the month.

    $firstOfMonth = date("Y-m-01",strtotime($date));
    // echo "<script>alert('$firstOfMonth');</script>";
    // echo "<script>alert('".strftime("%U", strtotime($date))."');</script>";
    // echo "<script>alert('".(strftime("%U", strtotime($firstOfMonth)))."');</script>";

    return intval(strftime("%U", strtotime($date))) - intval(strftime("%U", strtotime($firstOfMonth))) + 1;
}

function WADcal1($shortcodeattributes)
{
    echo WadCal1DynamicRedraw($shortcodeattributes);

}


//AJAX handler
add_action('parse_request', 'JKT_AJAX_query_handler');
function JKT_AJAX_query_handler()
{
    global $wpdb,$current_user;


        $name = $_REQUEST['name'];
        $value = $_REQUEST['value'];
        $pk = $_REQUEST['pk'];
        $action = $_GET['action'];


        $newEvent = $_REQUEST['eventNew']; // check to see the request is to add a new event
        $newComment = $_REQUEST['commentNew']; // check to see whether the request is to add a comment

        //adding new comments
        if(isset($newComment)){
            $comment = $_REQUEST['comment'];

            if(validatePK($pk) && validateComment($comment)){

                if($current_user->ID !=0){

                    $messages_table = $wpdb->prefix . 'js_messages'; 
                   
                    $wpdb->insert($messages_table,
                          array( 'message_date' => date('Y-m-d H:i:s'),
                                 'message_content' => $comment,
                                 'message_author' => $current_user->ID,
                                 'event_id' => $pk,
                                 ),
                          array( '%s', '%s', '%d', '%d') );

                    echo "Your comment was successfuly added";
                }else{
                    http_response_code(400);
                    echo "You need to logged into add comments";
                }



            }else{
                http_response_code(400);
                echo "Something went wrong. Pl. try again. And this time, make sure to enter valid data. ;)";
            }

            

            
            exit;

        }

        //adding new events
        if(isset($newEvent)){
            //adding new events
            $eventName = $_REQUEST['eventName'];
            $eventDescription = $_REQUEST['eventDescription'];
            $eventStatus = $_REQUEST['eventStatus'];
            $eventRecurrenceFrequency = $_REQUEST['eventRecurrenceFrequency'];

            $eventStartDate = $_REQUEST['eventStartDate'];
            $eventFinishDate = $_REQUEST['eventFinishDate'];
            $eventCategoryId = $_REQUEST['eventCategoryId'];
            $eventLocationId = $_REQUEST['eventLocationId'];

            $event_table = $wpdb->prefix . 'js_events';


            $validator = validate($eventName, $eventDescription, $eventStatus, $eventRecurrenceFrequency, $eventStartDate,$eventFinishDate,$eventCategoryId,$eventLocationId);



            // A little data validation. Check if all fields contains something.
            // user must be a logged in
            if( ($validator!=1) || ($current_user->ID == 0)) { 
                $msg = "Your event was not inserted. Please check the data and try again.";
                
                if($validator!=1){
                    $msg = "Please enter valid data when adding a new event.";
                }

                if($current_user->ID ==0){
                    $msg = "You are not authorized to add events. Please login.";
                }

               // $msg = "Event was not inserted to the DB. Something went wrong. Make sure all your inputs are valid and please try again."; 
                //$msg = date('Y-m-d', strtotime($eventFinishDate));
                //$msg = strtotime($eventFinishDate);
                http_response_code(400);
                
                //echo $validator;
                echo $msg;      
                exit;      
            //.
            } else {            
                
                $wpdb->insert($event_table,
                      array( 'event_name' => $eventName,
                             'event_start' => date('Y-m-d', strtotime($eventStartDate)),
                             'event_finish' => date('Y-m-d', strtotime($eventFinishDate)),
                             'event_recurring' => $eventRecurrenceFrequency,
                             'event_category_id' => $eventCategoryId,
                             'event_location_id' => $eventLocationId,
                             'event_description' => $eventDescription,
                             'event_organizer_id' => $current_user->ID,
                             'event_status' => $eventStatus),
                      array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%d') );
                      $event_id = $wpdb->insert_id;
                $msg = "A new event has been added.";
                echo $msg;
                $user_info = get_userdata($current_user->ID);
                $email_subject = $user_info->user_login . ' has added a new event';
                $content = $user_info->user_login . ' has added an event! Here are the details:\n\n
                Event Name: ' . $event_name . 
                '\nEvent Start Date: ' . $event_start . 
                '\nEvent Finish Date: ' . $event_finish . 
                '\n\nYou can view the rest of the details here: <a href="'.get_admin_url().'/admin.php?page=manage_events&id='.$event_id.'&command=view">' . $event_name . '</a>';
                $admin_email = get_option( 'admin_email' );
                //jscal_send_email($admin_email, $email_subject, $content);

               
            }













            exit;

        }

    if(isset($_REQUEST['redrawWADCalander']) && !empty($_REQUEST['redrawWADCalander'])){
        $month = $_REQUEST['month'];
        $year =  $_REQUEST['year'];
        $day = $_REQUEST['day'];

        if(isset($_REQUEST['week']) && isset($_REQUEST['month']) && isset($_REQUEST['year']) && isset($_REQUEST)){
            $week = $_REQUEST['week'];
            $month = $_REQUEST['month'];
            $year =  $_REQUEST['year'];
            $day = $_REQUEST['day'];

            $param = array("month"=> $month, "year" => $year, "week"=>$week, "dayfordayview"=> $day);
            $result = WadCal1DynamicRedraw($param);
            echo $result;
        }else if(isset($_REQUEST['week']) && isset($_REQUEST['month']) && isset($_REQUEST['year'])){
            $week = $_REQUEST['week'];
            $month = $_REQUEST['month'];
            $year =  $_REQUEST['year'];
            $param = array("month"=> $month, "year" => $year, "week"=>$week);
            $result = WadCal1DynamicRedraw($param);
            echo $result;

        }elseif(isset($_REQUEST['month']) && isset($_REQUEST['year']) ){
            $month = $_REQUEST['month'];
            $year =  $_REQUEST['year'];
            $param = array("month"=> $month, "year" => $year);
            $result = WadCal1DynamicRedraw($param);
            echo $result;
        }else{
            $result = WadCal1DynamicRedraw();
            echo $result;
        }
        

       // echo "test";
        //http_response_code(400);
        
        exit;
    }

    //if the event being editing or retriving data lists
    if(isset($_REQUEST['eventEdit'])){

        if (!is_nan($pk)) { // checking for valid eventId
            global $wpdb, $current_user;
            $event_table = $wpdb->prefix . 'js_events';


            $auth_user_id_array = $wpdb->get_results($wpdb->prepare("SELECT `event_organizer_id` FROM `$event_table` WHERE `event_id`=%d", $pk));
            $auth_user_id = $auth_user_id_array[0]->event_organizer_id;

            //if client request data lists
            if (isset($_GET['eventGetData']) && !empty($_GET['eventGetData'])) {
                // returning data lists
                if (isset($action) && !empty($action)) {

                    switch ($action) {
                        //returning category list
                        case 'getCategoryList':
                            $category_table = $wpdb->prefix . 'js_categories';

                            $category_list = $wpdb->get_results("SELECT `category_id`,`category_name`, `category_status` FROM `$category_table` WHERE category_status=1");

                            foreach ($category_list as $category) {
                                $category_list_json[] = array('value' => $category->category_id, 'text' => $category->category_name);
                            }
                            echo json_encode($category_list_json);
                            break;


                        //returning location list(addresses)
                        case 'getLocationList':
                            $venue_table = $wpdb->prefix . 'js_venues';

                            $venue_list = $wpdb->get_results("SELECT `venue_id`,`venue_name` FROM `$venue_table`");

                            foreach ($venue_list as $venue) {
                                $venue_list_json[] = array('value' => $venue->venue_id, 'text' => $venue->venue_name);
                            }
                            echo json_encode($venue_list_json);
                            break;

                        //returning location names
                        case 'getLocationNames':
                            $venue_table = $wpdb->prefix . 'js_venues';

                            $venue_list = $wpdb->get_results("SELECT `venue_id`, `venue_location` FROM `$venue_table`");

                            foreach ($venue_list as $venue) {
                                $venue_list_json[] = array('value' => $venue->venue_id, 'text' => $venue->venue_location);
                            }
                            echo json_encode($venue_list_json);
                            break;

                        //returning comments list for a event
                        case 'getComments':
                            $messages_table = $wpdb->prefix . 'js_messages';
                            $wp_user_table = $wpdb->prefix. 'users';
//                             
                            $messages_list = $wpdb->get_results("SELECT $messages_table.message_date, $messages_table.message_content, $wp_user_table.display_name
                                                                    FROM `$messages_table`
                                                                    INNER JOIN `$wp_user_table`
                                                                    ON $messages_table.message_author = $wp_user_table.ID

                                                                    WHERE event_id=$pk

                                                                    ORDER BY $messages_table.message_date DESC");

                            foreach ($messages_list as $message) {
                                $messages_list_json[] = array('messageDate' => $message->message_date, 'messageContent' => $message->message_content, 'messageAuthor'=> $message->display_name);
                            }
                            echo json_encode($messages_list_json);
                            
                            break;

                        case 'getEventsByDescription':
                            $searcchDescription = $_REQUEST['description'];
                            //echo $searcchDescription;
                            echo json_encode(get_all_events($searcchDescription,false));
                            //echo "get events by desc";
                            break;
                        default:

                            break;
                    }
                    exit;


                }
            }
            //http_response_code(400);

            //if events are being edited
            // if (isset($_GET['eventEdit']) && !empty($_GET['eventEdit'])) {
                if ($auth_user_id == $current_user->ID || current_user_can('manage_options')) { // checking for valid authorization
                    if ($value != "") {
                        switch ($name) {

                            case 'eventName':
                                if(validateEventName($value)){
                                    $value = trim($value);
                                    $value = stripcslashes($value);
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_name`='%s' WHERE `event_id`=%d",$value , $pk));    
                                }else{
                                    echo "Please enter a valid event name and try again.";
                                    http_response_code(400);
                                }
                                
                                break;

                            case 'eventDescription':
                                if(validateEventDescription($value)){
                                    $value = trim($value);
                                    $value = stripcslashes($value);
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_description`='%s' WHERE `event_id`=%d", $value ,$pk));
                                }else{
                                    echo "Please enter a valid event description and try again.";
                                    http_response_code(400);
                                }
                                
                                break;

                            case 'eventStatus':
                                if (!is_nan($value) && $value <= 1 && $value >= 0) {
                                    $value = trim($value);
                                    $value = stripcslashes($value);
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_status`='%d' WHERE `event_id`=%d",$value , $pk));
                                } else {
                                    echo "Please select a valid status";
                                    http_response_code(400);
                                }
                                break;

                            case 'eventType':
                                if (!is_nan($value)) {
                                    $value = trim($value);
                                    $value = stripcslashes($value);
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_recurring`='%d' WHERE `event_id`=%d",$value , $pk));
                                }else{
                                    echo "Please select a valid recurring frequency";
                                    http_response_code(400);
                                }
                                break;

                            case 'eventStartDate':
                                $value = trim($value);
                                $value = stripcslashes($value);
                                $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_start`='%s' WHERE `event_id`=%d",$value , $pk));

                                break;

                            case 'eventFinishDate':
                                $value = trim($value);
                                $value = stripcslashes($value);
                                $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_finish`='%s' WHERE `event_id`=%d",$value , $pk));

                                break;

                            case 'eventCategoryId':

                                $value = trim($value);
                                $value = stripcslashes($value);
                                if (!is_nan($value)) {
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_category_id`='%d' WHERE `event_id`=%d",$value , $pk));
                                }else{
                                    echo "Please select a valid category";
                                    http_response_code(400);
                                }

                                break;

                            case 'eventLocationId':

                                $value = trim($value);
                                $value = stripcslashes($value);
                                if (!is_nan($value)) {
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_location_id`='%d' WHERE `event_id`=%d",$value , $pk));
                                }else{
                                    echo "Please select a valid location";
                                    http_response_code(400);
                                }

                                break;


                            //http_response_code(400);


                            default:
                                # code...
                                break;
                        }
                    } else {
                        //if the submitted value is empty
                        echo "You are not allowed to enter empty string. Please try again with valid data";
                        http_response_code(400);
                    }
                } else {
                    // if the current user is not an authorized user
                    echo "You are not authorized to make changes to this event.";
                    http_response_code(400);
                }
            //}

        } else {
            // if event id is not a number
            echo "Bad request. :(";
            http_response_code(400);
            exit;
        }
        exit;    //important for our AJAX to work without returning the whole page
    }
}



//validating query data(8 args)
function validate($eventName, $eventDescription,$eventStatus, $eventRecurrence, $eventStartDate, $eventFinishDate, $eventCategoryId, $eventLocationId){

   $returnStatment = false;

   //validation rules for the event name
   if( validateEventName($eventName) ){
        $returnStatment = true;
   }else{
        $returnStatment = false;
   }


   //validation rules for event description
   if( validateEventDescription($eventDescription) ){ 
        $returnStatment = $returnStatment && true;
   }else{
        $returnStatment = false;
   }

   //validation rules for event status
   if( !is_nan($eventStatus) && ($eventStatus>=0 ) && ($eventStatus<=1) ){
        $returnStatment = $returnStatment && true;
   }else{
        $returnStatment = false;
   }


   //validation rules for event reccuring frequency
   if( !is_nan($eventRecurrence) && ($eventRecurrence >= 0) && ($eventRecurrence<=4)){
        $returnStatment = $returnStatment && true;
   }else{
        $returnStatment = false;
   }


   //validation rules for event start date
   if( ($eventStartDate!= "") && (strtotime($eventStartDate) != undefined) && (strtotime($eventStartDate) > 0) && (strtotime($eventStartDate) !="") &&  !is_nan(strtotime($eventStartDate)) ){
        $returnStatment = $returnStatment && true;
   }else{
        $returnStatment = false;
   }

   //validation rules for event finish date
   if( ($eventFinishDate!= "") && (strtotime($eventFinishDate) != undefined) && (strtotime($eventFinishDate) > 0) && (strtotime($eventFinishDate) !="") &&  !is_nan(strtotime($eventFinishDate)) ){
        $returnStatment = $returnStatment && true;
   }else{
        $returnStatment = false;
   }

   //validation rules for event category
   if( !empty($eventCategoryId) && !is_nan($eventCategoryId)){
        $returnStatment = $returnStatment && true;
   }else{
        $returnStatment = false;
   }

   //validation rules for event location
   if( !empty($eventCategoryId) && !is_nan($eventLocationId)){
        $returnStatment = $returnStatment && true;
   }else{
        $returnStatment = false;
   }
   

   return $returnStatment;
}

function validateEventDescription(){
    if((strlen($eventDescription) <=1000) ){ //&& (strlen($eventDescription) >=3)
        return true;
    }else{
        return false;
    }
}

function validateEventName($eventName){
    if( (strlen($eventName) <=100) && (strlen($eventName)>=3) ){
        return true;
    }else{
        return false;
    }
}

function validatePK($pk){
    if($pk!= null && $pk!="" && !is_nan($pk) && $pk > 0){
        return true;
    }else{
        return false;
    }
}

function validateComment($comment){
    if($comment != ""){
        return true;
    }else{
        return false;
    }
}


?>
