<?php
date_default_timezone_set("Pacific\Auckland");


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


    // google map API
   // wp_enqueue_script('google-map-api', plugins_url('js/google-map-api.js', __FILE__));
    wp_enqueue_script('google-map-api', '//maps.googleapis.com/maps/api/js?&key=AIzaSyDzzaqaN5nAyGCp1gRIsleOJRUeioD-urs', array(), '3', true);
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
                        $var = (($month == $obj["event_month"])) ? "true" : "false";
                        //echo "<script>alert('start Time : ". $year ."<br>".($obj["event_year"])."<br>".$var."/nFinish Time : ".$finishDateTimestamp."')</script>";

                        echo "<script>console.log(".$startDate->format("d").");</script>";
                        echo "<script>console.log($finishDateTimestamp >= $startDateTimestamp);</script>";

                        if ($year == $obj["event_year"] && $month == $obj["event_month"]) {
                            array_push($eventarray, $obj);
                        }
                        $startDate->modify('+7 day');
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

//copy of the WADCal1 function redesigned as a dynamic call
function WadCal1DynamicRedraw($shortcodeattributes){

    $returnText = "";
//echo pr($shortcodeattributes);
    //days of the week used for headings. This particular method is not particulary multilanguage friendly.
    $weekdays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    extract(shortcode_atts(array('year' => '-', 'month' => '-'), $shortcodeattributes));

    //default to the current month and year
    if ($month == '-') $month = date('m');
    if ($year == '-') $year = date('Y');

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

    //calendar heading - note we are using flexbox for the styling
    $thedate = date('F Y', mktime(0, 0, 1, $month, 1, $year));
    $returnText.= '<main id="calendar">
                            <div class="bootstrap-wrapper" style="text-align:center;padding-bottom:2em;">
                                <span style="float:left" class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==1)?12:$month-1).','.(($month==1)?$year-1:$year).')" >Prev Month</span>
                                <span id="calendarHeaderText" data-month='.$month.' data-year='.$year.' style="text-align:center; font-size:2em">' . $thedate . '</span>
                                <span style="float:right"  class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==12)?1:$month+1).','.(($month==12)?$year+1:$year).')" >Next Month</span>
                            </div>
                            

                            <div class="th">';
    //HEADING ROW: print out the week names
    foreach ($weekdays as $wd) {
        $returnText.= '<span>' . $wd . '</span>';
    }
    $returnText.= '</div>';

    //CALENDAR WEEKS: generate the calendar body
    //starting day of the previous month, used to fill the blank day slots

    $startday = $prevdays - ($dow - 1); //calculate the number of days required from the prev month

    //PART 1: first week with initial blank days (cells) or previous month
    $returnText.= '<div class="week">';
    for ($i = 0; $i < $dow; $i++)
        //refer to lines 43-53 in the WADcalendar.css for information regarding the data-date styling
        $returnText.= '<div data-date="' . $startday++ . '"></div>';//!! this increments $startday AFTER the value has been used


    //getting the event list
    $event_list = get_events($year, $month);


    //pr($event_list);

    //PART 2: main calendar calendar body
    for ($i = 0; $i < $days; $i++) {

        //check for the week boundary - % returns the remainder of a division
        if (($i + $dow) % 7 == 0) { //no remainder means end of the week
            $returnText.= '</div><div class="week">';
        }

        //print the actual day (cell) with events
        $returnText.= '<div id="dayCell" data-date="' . ($i + 1) . '" data-toggle="modal" data-target="#events" onclick="populateEventModel(this)">'; //add 1 to the for loop variable as it starts at zero not one


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

    //PART 3: last week with blank days (cells) or couple of days from next month
    $j = 1; //counter for next months days used to fill in the blank days at the end
    for ($i = 0; $i < $lastblankdays; $i++)
        $returnText.= '<div data-date="' . $j++ . '"></div>'; //!! this increments $j AFTER the value has been used
    //close off the calendar    
    $returnText.= '</div>';

    $returnText.= '<script>// initiating the bootstrap tooltip
        jQuery(document).ready(function () {
            

            //stop triggering the parent cell on click function when clicking on a event
            jQuery("#dayCell a").click(function (e) {
                e.stopPropagation();
                jQuery("#eventDetails").modal("show");
            });
        });';

        //destroying already created elements
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
    
    return $returnText;
exit;
}

function WADcal1($shortcodeattributes)
{
    //echo pr($shortcodeattributes);
    //days of the week used for headings. This particular method is not particulary multilanguage friendly.
    $weekdays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    extract(shortcode_atts(array('year' => '-', 'month' => '-'), $shortcodeattributes));

    //default to the current month and year
    if ($month == '-') $month = date('m');
    if ($year == '-') $year = date('Y');

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

    //calendar heading - note we are using flexbox for the styling
    $thedate = date('F Y', mktime(0, 0, 1, $month, 1, $year));
    echo '<main id="calendar">
                            <div class="bootstrap-wrapper" style="text-align:center;padding-bottom:2em;">
                                <span style="float:left" class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==1)?12:$month-1).','.(($month==1)?$year-1:$year).')" >Prev Month</span>
                                <span id="calendarHeaderText" data-month='.$month.' data-year='.$year.' style="text-align:center;font-size:2em">' . $thedate . '</span>
                                <span style="float:right"  class="btn btn-sm btn-primary" onclick="redrawCalander('.(($month==12)?1:$month+1).','.(($month==12)?$year+1:$year).')" >Next Month</span>
                            </div>
                            

                            <div class="th">';
    //HEADING ROW: print out the week names	
    foreach ($weekdays as $wd) {
        echo '<span>' . $wd . '</span>';
    }
    echo '</div>';

    //CALENDAR WEEKS: generate the calendar body
    //starting day of the previous month, used to fill the blank day slots

    $startday = $prevdays - ($dow - 1); //calculate the number of days required from the prev month

    //PART 1: first week with initial blank days (cells) or previous month
    echo '<div class="week">';
    for ($i = 0; $i < $dow; $i++)
        //refer to lines 43-53 in the WADcalendar.css for information regarding the data-date styling
        echo '<div data-date="' . $startday++ . '"></div>';//!! this increments $startday AFTER the value has been used


    //getting the event list
    $event_list = get_events($year, $month);


    //pr($event_list);

    //PART 2: main calendar calendar body
    for ($i = 0; $i < $days; $i++) {

        //check for the week boundary - % returns the remainder of a division
        if (($i + $dow) % 7 == 0) { //no remainder means end of the week
            echo '</div><div class="week">';
        }

        //print the actual day (cell) with events
        echo '<div id="dayCell" data-date="' . ($i + 1) . '" data-toggle="modal" data-target="#events" onclick="populateEventModel(this)">'; //add 1 to the for loop variable as it starts at zero not one


        //event code and such here
        foreach ($event_list as $event) {
            //pr($event["event_day"]);
            if ($event["event_day"] == ($i + 1)) {
                //echo $event["event_name"];
                echo '<div id="eventDetailsModel" class="bootstrap-wrapper" style="margin:0px 0px 5px 0px" >
					<a id="event_' . $event["event_id"] . '" data-toggle="modal" data-target="#eventDetails" class="label ' . (($event["event_status"] == 1) ? "label-info" : "label-default") . '" event-data=\'' . json_encode($event) . '\'" title="' . $event["event_description"] . '" onclick="loadEventData(event_' . $event["event_id"] . ')">' . $event["event_name"] . '</a></div>';

            }
        }


        echo '</div>';
    }

    //PART 3: last week with blank days (cells) or couple of days from next month
    $j = 1; //counter for next months days used to fill in the blank days at the end
    for ($i = 0; $i < $lastblankdays; $i++)
        echo '<div data-date="' . $j++ . '"></div>'; //!! this increments $j AFTER the value has been used
    //close off the calendar	
    echo '</div></main>';


    ?>


    <script>
        // initiating the bootstrap tooltip
        jQuery(document).ready(function () {
            jQuery('[data-toggle="tooltip"]').tooltip();

            //stop triggering the parent cell on click function when clicking on a event
            jQuery("#dayCell a").click(function (e) {
                e.stopPropagation();
                jQuery('#eventDetails').modal('show');
            });
        });


        //turn to inline mode for the x-editable
        jQuery(document).ready(function ($) {
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

    
        //redrawing the calender
        function redrawCalander(month,year){
        
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
        }




        // initializing the google map
        function initialize(add) {

            var myCenter = new google.maps.LatLng(51.508742, -0.120850);
            var mapProp = {
                center: myCenter,
                zoom: 10,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            var map = new google.maps.Map(document.getElementById("googleMap"), mapProp);
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
            geocoder.geocode({'address': address}, function (results, status) {
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
        function loadEventData(element) {
            // alert('test');
            // var element = jQuery(ele)[0];
            // alert('test');

            console.log(element);

            console.log(JSON.parse(jQuery(element).attr('event-data')));
            var eventData = JSON.parse(jQuery(element).attr('event-data'));

            //eventID = eventData.event_id;

            jQuery('#eventModalTitle').text(eventData.event_name);

            jQuery('#eventName').text(eventData.event_name);
            jQuery('#eventStatus').text((eventData.event_status == 0) ? "Draft" : "Published");
            jQuery('#eventType').text((eventData.event_type == 0) ? "Non-Recurring" : (eventData.event_type == 1) ? "Daily" : (eventData.event_type == 2) ? "Weekly" : "Monthly");


            jQuery(document).ready(function ($) {

                //console.log(eventData);


                //initializing form elements

                $('#eventName').editable({
                    type: 'text',
                    pk: eventData.event_id,
                    url: '?eventEdit=true',
                    title: 'Enter eventName',


                    success: function (response, newValue) {
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

                    success: function (response, newValue) {

                        //changing  clander data to new values
                        eventData.event_status = newValue;
                        $(element).attr('event-data', JSON.stringify(eventData));

                        if (newValue == 0) {
                            $(element).removeClass('label-info');
                            $(element).addClass('label-default');

                        } else {
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
                    source: [{value: 0, text: 'Non-Recurring'}, {value: 1, text: 'Daily'}, {value: 2, text: 'Weekly'}, {value: 3, text: 'Monthly'}, {value: 4, text: 'Yearly'}],
                    emptytext: 'Draft',
                    value: eventData.event_type,

                    success: function (response, newValue) {

                        //changing  clander data to new values
                        eventData.event_type = newValue;
                        $(element).attr('event-data', JSON.stringify(eventData));
                        redrawCalander($("#calendarHeaderText").data("month"),$("#calendarHeaderText").data("year"));


                    }
                });


                $("#eventStartDate").editable({
                    type: "datetime",
                    pk: eventData.event_id,
                    url: '?eventEdit=true',
                    format: "yyyy-mm-dd hh:ii:ss",
                    // viewformat: "dd/mm/yyyy hh:ii",
                    value: eventData.event_start,
                    datetimepicker: {
                        weekStart: 1
                    },
                    success: function (response, newValue) {
                        eventData.event_start = newValue;
                        $(element).attr('event-data', JSON.stringify(eventData));
                        redrawCalander($("#calendarHeaderText").data("month"),$("#calendarHeaderText").data("year"));

                    }
                });

                $("#eventFinishDate").editable({
                    type: "datetime",
                    pk: eventData.event_id,
                    url: '?eventEdit=true',
                    format: "yyyy-mm-dd hh:ii:ss",
                    // viewformat: "dd/mm/yyyy hh:ii",
                    value: eventData.event_finish,
                    datetimepicker: {
                        weekStart: 1
                    },
                    success: function (response, newValue) {
                        eventData.event_finish = newValue;
                        $(element).attr('event-data', JSON.stringify(eventData));
                        redrawCalander($("#calendarHeaderText").data("month"),$("#calendarHeaderText").data("year"));
                        
                    }
                });

                $("#eventCategoryId").editable({
                    type: "select",
                    pk: eventData.event_id,
                    source: "?eventEdit=true&eventGetData=true&action=getCategoryList",
                    url: '?eventEdit=true',
                    sourceCache: false,
                    value: eventData.event_category_id,
                    success: function (response, newValue) {
                        eventData.event_category_id = newValue;
                        $(element).attr('event-data', JSON.stringify(eventData));
                    }
                    // viewformat: "dd/mm/yyyy hh:ii",
                    //value:eventData.event_finish,


                });

                $("#eventLocationId").editable({
                    type: "select",
                    pk: eventData.event_id,
                    source: "?eventEdit=true&eventGetData=true&getCategoryList=true&action=getLocationList",
                    url: '?eventEdit=true',
                    sourceCache: false,
                    value: eventData.event_location_id,
                    success: function (response, newValue) {
                        eventData.event_location_id = newValue;
                        $(element).attr('event-data', JSON.stringify(eventData));

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
                


                //initializing elemnts in "Add New tab"
                
                


            });

            



            // disabling the editable functionality fo the guest users from the UI side
            <?php
            global $current_user;
            if ($current_user->ID == 0) {
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


        //populating the event model
        function populateEventModel(element) {
            var _event = "";
            //console.log(jQuery(element).children());
            var eventList = jQuery(element).children();

            jQuery("#eventList").empty();// removing all the events drawn previously

            if (eventList.length == 0) {
                //alert("add new evens");
                jQuery('#show-all-events-tab').hide();
                jQuery('#show-all-events-tab').removeClass("active");
                jQuery('#add-new-events-tab').addClass("active");

                jQuery('#eventsTab').removeClass('active in');
                jQuery('#addNewEventTab').addClass('active in');
            } else {
               // alert("all events");
                jQuery('#show-all-events-tab').show();
                jQuery('#show-all-events-tab').addClass("active");
                jQuery('#add-new-events-tab').removeClass("active");

                jQuery('#eventsTab').addClass('active in');
                jQuery('#addNewEventTab').removeClass('active in');
            }

            for (var i = 0; i < eventList.length; i++) {
                originalEvent = jQuery(eventList[i]).children()[0];
                //console.log(originalEvent);
                originalEventData = JSON.parse(jQuery(originalEvent).attr('event-data'));
                //console.log(originalEventData);
                //var eventData = JSON.parse(jQuery(singleLink).attr('event-data'));


                //_event += singleLink;

                _event = '<a data-toggle="modal" data-target="#eventDetails" href="#" class="' + ((originalEventData.event_status == 0) ? "" : "active") + '"" onclick="loadEventData(' + "event_" + originalEventData.event_id + ')" class="list-group-item">' + originalEventData.event_name + '<span class="badge">' + ((originalEventData.event_status == 0) ? "Draft" : "Published") + '</span></a>';

                jQuery(jQuery(_event).addClass("list-group-item")).appendTo("#eventList");
                //jQuery("#eventList").append("a").addClass("list-group-item");


            }


            //console.log(jQuery("#eventList").append(_event));


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
                                                                           href="#eventsTab">Events</a></li>
                            <li id="add-new-events-tab"><a data-toggle="tab" href="#addNewEventTab">Add New Event</a>
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
                            <div id="addNewEventTab" class="tab-pane fade">
                               


                                <form class="form-horizontal">
                                    <fieldset>

                                        <div class="col-md-6">

                                            <!-- Text input-->
                                            <div class="form-group">
                                              <label class="col-md-6 control-label" for="txtEventName">Event Name</label>  
                                              <div class="col-md-6">
                                              <input id="txtEventName" name="txtEventName" type="text" placeholder="Event Name" class="form-control input-md" required="">
                                               
                                              </div>
                                            </div>

                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-md-6 control-label" for="cmbEventStatus">Event Status</label>
                                              <div class="col-md-6">
                                                <select id="cmbEventStatus" name="cmbEventStatus" class="form-control">
                                                  <option value="0">Draft</option>
                                                  <option value="1">Published</option>
                                                </select>
                                              </div>
                                            </div>

                                            <!-- Select Basic -->
                                            <div class="form-group">
                                              <label class="col-md-6 control-label" for="cmbEventRecurrence">Recurring Frequency</label>
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

                                                <div class="col-md-6">
                                                    <input type="text" id="add-new-start-Date">

                                                    <input type="text" value="2012-05-15 21:05" id="datetimepicker">
                                                </div>
                                            </div>
                                        </div>

                                        <diV class="col-md-6">

                                        </div>

                                        <div class="col-md-12">
                                            <div clas
                                        </div>
                                    </fieldset>
                                </form>






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
function JKT_AJAX_query_handler()
{


        $name = $_REQUEST['name'];
        $value = $_REQUEST['value'];
        $pk = $_REQUEST['pk'];
        $action = $_GET['action'];

    if(isset($_REQUEST['redrawWADCalander']) && !empty($_REQUEST['redrawWADCalander'])){
        $month = $_REQUEST['month'];
        $year =  $_REQUEST['year'];
        if(isset($_REQUEST['month']) || isset($_REQUEST['year']) ){
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

                            $category_list = $wpdb->get_results("SELECT `category_id`,`category_name`, `category_status` FROM `$category_table`");

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
                        default:

                            break;
                    }
                    exit;


                }
            }
            //http_response_code(400);

            //if the events are being edited
            // if (isset($_GET['eventEdit']) && !empty($_GET['eventEdit'])) {
                if ($auth_user_id == $current_user->ID || current_user_can('manage_options')) { // checking for valid authorization
                    if ($value != "") {
                        switch ($name) {

                            case 'eventName':
                                $value = trim($value);
                                $value = stripcslashes($value);
                                $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_name`='$value' WHERE `event_id`=%d", $pk));
                                break;

                            case 'eventStatus':
                                if (!is_nan($value) && $value <= 1 && $value >= 0) {
                                    $value = trim($value);
                                    $value = stripcslashes($value);
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_status`='$value' WHERE `event_id`=%d", $pk));
                                } else {
                                    echo "Please select a valid status";
                                    http_response_code(400);
                                }
                                break;

                            case 'eventType':
                                if (!is_nan($value)) {
                                    $value = trim($value);
                                    $value = stripcslashes($value);
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_recurring`='$value' WHERE `event_id`=%d", $pk));
                                }
                                break;

                            case 'eventStartDate':
                                $value = trim($value);
                                $value = stripcslashes($value);
                                $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_start`='$value' WHERE `event_id`=%d", $pk));

                                break;

                            case 'eventFinishDate':
                                $value = trim($value);
                                $value = stripcslashes($value);
                                $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_finish`='$value' WHERE `event_id`=%d", $pk));

                                break;

                            case 'eventCategoryId':

                                $value = trim($value);
                                $value = stripcslashes($value);
                                if (!is_nan($value)) {
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_category_id`='$value' WHERE `event_id`=%d", $pk));
                                }

                                break;

                            case 'eventLocationId':

                                $value = trim($value);
                                $value = stripcslashes($value);
                                if (!is_nan($value)) {
                                    $wpdb->query($wpdb->prepare("UPDATE `wp_js_events` SET `event_location_id`='$value' WHERE `event_id`=%d", $pk));
                                }

                                break;


                            //http_response_code(400);


                            default:
                                # code...
                                break;
                        }
                    } else {
                        //if the submitted value is empty
                        echo "You are not allowed to enter empty string. Please try again with a valid data";
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


?>
