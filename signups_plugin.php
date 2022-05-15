<?php
/**
 * Plugin Name: Signups
 * Plugin URI: 
 * Description: Signups administration tools.
 * Version: 1.0
 * Author: Ed Sproull
 * Author URI: 
 * Author Email: 
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpccp
 * Domain Path: /languages
 */

// Contains two classes used to create a new session or new class.
include "includes/classitem.php";

if ( ! function_exists( 'formatDate' ) ) :
    function formatDate($timestamp) {
        $timezone = 'America/Phoenix';
        $dt = new DateTime();
        $dt->setTimestamp($timestamp);
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format('Y-m-d g:ia');
    }

endif;
// Adds the one and only menu item for the plugin.
 function signup_plugin_top_menu(){
   add_menu_page('SignUps', 'SignUps', 'manage_options', __FILE__, 'signup_settings_page', plugins_url('/signups/img/frenchie.bmp',__DIR__));
   //add_submenu_page(__FILE__, 'Sessions', 'Sessions', 'manage_options', 'SessionSlug', 'editSessions', null);
 }
 add_action('admin_menu','signup_plugin_top_menu');

// Adds the CSS that is used to style the plug-in.
function add_scripts_and_css(){
    wp_register_style('signup_bs_style', plugin_dir_url( __FILE__ ) . "bootstrap/css/bootstrap.min.css");
    wp_enqueue_style('signup_bs_style');
    wp_register_style('signup_style', plugin_dir_url( __FILE__ ) . "css/style.css");
    wp_enqueue_style('signup_style');
}
add_action('admin_enqueue_scripts', 'add_scripts_and_css');

// When you change the link to a class thumbnail this bit of JS updates the image on the page in real time.
 function addImageScript() {
    ?>
    <script>
        function updateImage() {
            var thumbDisplay = document.getElementById("displayThumb");
            var thumbUrl = document.getElementById("thumbnail");
            thumbDisplay.src = thumbUrl.value;
        }
    </script>
    <?php
}
add_action('admin_print_scripts', 'addImageScript');

/*
*   The main functin of the Plugin. 
*   This delegates all the real work to helper functions.
*   Loading the class selection is the default. 
*   All others are triggered by a form submission.
*/
function signup_settings_page() {

    if (isset($_POST['submitClass'])) {
        submitClass();
    } elseif (isset($_POST['submitSession'])){
        submitSession();
    } elseif (isset($_POST['editClass'])){
        editClass();
    } elseif (isset($_POST['editSession'])){
        editSession();
    } elseif (isset($_POST['addNewClass'])){
        createClassForm(new ClassItem(null));
    } elseif (isset($_POST['addNewSession'])){
        addNewSessionForm();
    } elseif (isset($_POST['attendees'])){
        editSessionAttendees();
    } elseif (isset($_POST['deleteAttendees'])){
        deleteSessionAttendees();
    } elseif (isset($_POST['addAttendee'])){
        addSessionAttendees();
    } elseif (isset($_POST['selectSession'])){
        loadSessionSelection();
    } else {
        loadClassSelection();
    }
}

function submitClass() {
     global $wpdb;
    $where = array();
    $where["class_ID"] =$_POST["id"];
    unset($_POST["submitClass"]);
    unset($_POST["id"]);
    $affectedRows = 0;
    if ($where["class_ID"]) {
        $affectedRowCount = $wpdb->update("wp_scw_classes",
                                          $_POST,
                                          $where);
    } else {
        $affectedRows = $wpdb->insert("wp_scw_classes", $_POST);
    }
    if ($affectedRowCount) {
    ?>
    <div class="text-center mb-4">
        <h1><?php echo $affectedRowCount ?> Rows Updated</h1>
    </div>
    <?php
    }

    ?>
        <input class="button-primary ml-auto mr-auto" style="cursor:pointer;" type="button" onclick="   window.history.go(-0);" value="Back">
    <?php
}

function submitSession() {
    global $wpdb;
     $where = array('session_ID' => $_POST['id']);
     unset($_POST['id']);
     unset($_POST['submitSession']);
     $rowsUpdated = 0;
    if ($where['session_ID']) {
        $rowsUpdated = $wpdb->update("wp_scw_sessions", $_POST, $where);
    } else {
        $rowsUpdated = $wpdb->insert("wp_scw_sessions", $_POST);
    }
    updateMessage($rowsUpdated);
 }

 function editClass() {
    global $wpdb;
    $query = "SELECT *
        FROM awp.wp_scw_classes
        WHERE class_ID = " . $_POST['editClass'];
    $results = $wpdb->get_results($query  , OBJECT );
    createClassForm($results[0]);
 }

function  addNewSessionForm() {
    $sessionItem = new SessionItem($_POST['addNewSession']);
    createSessionForm($sessionItem, $_POST[$_POST['selectSession']], true);
 }

function editSession() {
    global $wpdb;
    $dateTimeZone = new DateTimeZone("America/Phoenix");
    $query = "SELECT *
        FROM awp.wp_scw_sessions
        WHERE session_ID = " . $_POST['editSession'];
    
    $results = $wpdb->get_results($query  , OBJECT );
    createSessionForm($results[0], $_POST["className"], false);
 }

function editSessionAttendees() {
    global $wpdb;
    $querySession = "SELECT *
        FROM awp.wp_scw_sessions
        WHERE session_ID = " . $_POST['attendees'];
    $resultsSession = $wpdb->get_results($querySession  , OBJECT );
    $session = $resultsSession[0];

    $queryClass = "SELECT class_default_slots
        FROM awp.wp_scw_classes
        WHERE class_ID = " . $session->session_class_ID;
    $resultsClass = $wpdb->get_results($queryClass  , OBJECT );
    $defaultAttendeeSlots = $resultsClass[0]->class_default_slots;

    $queryAttendees = "SELECT *
        FROM awp.wp_scw_attendees
        WHERE attendee_session_ID = " . $session->session_ID . " AND
              attendee_email != ''";
    $sessionList = $wpdb->get_results($queryAttendees  , OBJECT );

    $attendees = array_filter($sessionList, function ($obj) {
        if ($obj->attendee_item == "INSTRUCTOR") {
            return false;
        }

        return true;
    });

    $instructors = array_filter($sessionList, function ($obj) {
        if ($obj->attendee_item == "INSTRUCTOR") {
            return true;
        }

        return false;
    });

    //echo "\n\nInstructors : " . var_dump($instructors);
    //echo "\n\nAttendees : " . var_dump($attendees);

    createAttendeeForm($defaultAttendeeSlots, $attendees, $instructors, $_POST['className'], $session->session_ID);
}

function addSessionAttendees() {
    echo "addSessionAttendees";
    echo var_dump($_POST);
}

function deleteSessionAttendees() {
    echo "deleteSessionAttendees";
    echo var_dump($_POST);
}

 function loadClassSelection() {
        global $wpdb;
    $query = "SELECT class_ID,
            class_Name
            FROM awp.wp_scw_classes;";
        
    $results = null;
    $results = $wpdb->get_results($query  , OBJECT );
    createClassSelectForm($results);
 }

 function loadSessionSelection() {
    $className = $_POST[$_POST['selectSession']];
    global $wpdb;
    $query = "SELECT session_ID,
            session_start_formatted,
            session_start_time,
            session_slots
            FROM awp.wp_scw_sessions
            WHERE session_class_ID = " . $_POST['selectSession'];
    
    $instructors = array();
    $attendees = array();
    $sessions = $wpdb->get_results($query  , OBJECT );
    foreach ($sessions as $session){
        $attendees[$session->session_ID] = array();
        $instructors[$session->session_ID] = array();
        $queryAttendees = "SELECT *
        FROM awp.wp_scw_attendees
        WHERE attendee_session_ID = " . $session->session_ID . " AND
              attendee_email != ''";
        $sessionList = $wpdb->get_results($queryAttendees  , OBJECT );
        foreach ($sessionList as $attendee) {
            if ($attendee->attendee_item == "INSTRUCTOR") {
                $instructors[$session->session_ID][] = $attendee;
            } else {
                $attendees[$session->session_ID][] = $attendee;
            }
        }
    }

    createSessionSelectForm($className, $sessions, $attendees, $instructors, $_POST['selectSession']);
 }

/////////// HTML functions below here. /////////////////////////
function createAttendeeForm($defaultAttendeeSlots, $attendees, $instructors, $className, $sessionID) {
    //echo var_dump($attendees);
    ?>
     <form  method="POST">
        <div class="text-center mt-5">
            <h1><?php echo $className ?></h1> <br>
            <h2>Add Remove Attendees</h2>
        <div>
        <div id="content" class="container">
            <table class="mb-100px table table-striped mr-auto ml-auto">
                <?php
                if (count($attendees) < $defaultAttendeeSlots)
                {
                ?>
                    <tr><td>Add Attendee</td>
                        <td></td>
                        <td></td>
                        <td> <input class="submitbutton addItem" type="submit" name="addAttendee" value="<?php echo $sessionID; ?>"></td>
                    </tr>
                <?php
                }

                foreach($instructors as $instructor) {
                ?>
                    <tr><td> <?php echo $instructor->attendee_firstname . " " . $instructor->attendee_lastname; ?></td>
                        <td><?php echo $instructor->attendee_item; ?></td>
                        <td><?php echo $instructor->attendee_email; ?></td>
                        <td> <input class="form-check-input" type="checkbox" name="tobedeleted[]" value="<?php echo $instructor->attendee_ID; ?>"> </td>
                    </tr>
                <?php 
                } 
                ?>

                <?php
                foreach($attendees as $attendee) {
                ?>
                    <tr><td> <?php echo $attendee->attendee_firstname . " " . $attendee->attendee_lastname; ?></td>
                        <td><?php echo $attendee->attendee_item ?></td>
                        <td><?php echo $attendee->attendee_email; ?></td>
                        <td> <input class="form-check-input" type="checkbox" name="tobedeleted[]" value="<?php echo $attendee->attendee_ID; ?>"> </td>
                    </tr>
                <?php 
                } 
                ?>
            </table>
            <input class="btn btn-danger" type="submit" value="Delete" name="deleteAttendees">
        </div>
    </form>
    <?php
}

function updateMessage($rowsUpdated) {
    if ($rowsUpdated == 1) {
        ?>
        <div class="text-center mt-5">
            <h2> Session Updated </h2>
        </div>
        <?php
    } else {
        ?>
        <div class="text-center mt-5">
            <h2> Something went wrong. </h2>
            <h3><?php echo $rowsUpdated; ?> Rows Updated</h3>
        </div>
        <?php
    }
    ?>
     <div class="text-center mr-2">
         <input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="window.history.go(-1);" value="Back">
    </div>
    <?php
}

 function createClassSelectForm($results) {
    ?>
    <form  method="POST">
        <div id="content" class="container">
            <table class="mb-100px table table-striped mr-auto ml-auto">
                <tr><td>Add SignUp</td>
                    <td></td>
                    <td></td>
                    <td> <input class="submitbutton addItem" type="submit" name="addNewClass" value=""></td></tr>
                <?php foreach($results as $result) {
                ?>
                    <tr><td> <?php echo $result->class_Name; ?></td>
                        <td> <input class="submitbutton editImage" type="submit" name="editClass" value="<?php echo $result->class_ID; ?>"> </td>
                        <td> <input class="submitbutton sessionsImage" type="submit" name="selectSession" value="<?php echo $result->class_ID; ?>"> </td>
                        <td> <input class="submitbutton deleteImage" type="submit" name="deleteClass" value="<?php echo $result->class_ID; ?>"> 
                            <input type="hidden" name="<?php echo $result->class_ID; ?>" value="<?php echo $result->class_Name; ?>"></td>
                    </tr>
                <?php 
                } 
                ?>
            </table>
        </div>
    </form>
    <?php
 }
 
 function createSessionSelectForm($className, $sessions, $attendees, $instructors, $classId) {
    ?>
    <form  method="POST">
        <div class="text-center mt-5">
            <h1><?php echo $className ?></h1>
        <div>
        <div id="content" class="container">
            <table class="mb-100px table table-bordered mr-auto ml-auto">
                <tr style="background-color: lightyellow;">
                    <td class="text-left" style="min-width: 200px;">Add Session</td>
                    <td style="width: 200px;"></td>
                    <td></td>
                    <td> <input class="submitbutton addItem" type="submit" name="addNewSession" value="<?php echo $classId; ?>"> 
                        </td>
                </tr>
                <?php foreach($sessions as $session) {
                ?>
                    <tr><td class="text-left"> <?php echo formatDate($session->session_start_time); ?></td>
                        <td> <input class="submitbutton editImage mr-auto ml-auto" type="submit" name="editSession" value="<?php echo $session->session_ID; ?>"> </td>
                        <td> <input class="submitbutton attendeesImage mr-auto ml-auto" type="submit" name="attendees" value="<?php echo $session->session_ID; ?>"> </td>
                        <td> <input class="submitbutton deleteImage" type="submit" name="deleteSession" value="<?php echo $session->session_ID; ?>"> 
                            <input type="hidden" name="className" value="<?php echo $className; ?>"></td>
                    </tr>
                    
                    <?php
                    if (count($attendees[$session->session_ID]) < $session->session_slots)
                    {
                    ?>
                        <tr><td>Add Attendee</td>
                            <td></td>
                            <td></td>
                            <td> <input class="submitbutton addItem" type="submit" name="addAttendee" value="<?php echo $session->session_ID; ?>"></td>
                        </tr>
                    <?php
                    }

                    foreach($instructors[$session->session_ID] as $instructor) {
                    ?>
                        <tr><td> <?php echo $instructor->attendee_firstname . " " . $instructor->attendee_lastname; ?></td>
                            <td><?php echo $instructor->attendee_item; ?></td>
                            <td><?php echo $instructor->attendee_email; ?></td>
                            <td class="centerCheckBox" > <input class="form-check-input" type="checkbox" name="tobedeleted[]" value="<?php echo $instructor->attendee_ID; ?>"> </td>
                        </tr>
                    <?php 
                    } 
                    ?>

                    <?php
                    foreach($attendees[$session->session_ID] as $attendee) {
                    ?>
                        <tr><td> <?php echo $attendee->attendee_firstname . " " . $attendee->attendee_lastname; ?></td>
                            <td><?php echo $attendee->attendee_item ?></td>
                            <td><?php echo $attendee->attendee_email; ?></td>
                            <td class="centerCheckBox"> <input class="form-check-input" type="checkbox" name="tobedeleted[]" value="<?php echo $attendee->attendee_ID; ?>"> </td>
                        </tr>
                    <?php 
                    }
                    ?>
                    <tr style="background: darkgray;"><td></td><td></td><td></td><td></td></tr>
                    <?php
                } 
                ?>
            </table>
        </div>
    </form>
    </div>
        <input class="btn btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go(-0);" value="Back">
        <input class="btn btn-danger" type="submit" value="Delete Selected" name="deleteAttendees">
    </div>
    <?php
}

function createClassForm ($data) {
    ?>
    <div class="text-center mb-4">
        <h1><?php echo $className ?> </h1>
        <img id="displayThumb" src="<?php echo $data->class_thumbnail_url; ?>" alt="Class Thumbnail">
    </div>
    <form  method="POST">
        <table class="table table-striped mr-auto ml-auto">
            <tr><td class="text-right mr-2"><label>Class Name:</label></td>
                <td><input class="w-250px"  type="text" name="class_name" value="<?php echo $data->class_name; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Contact Email:</label></td>
                <td><input class="w-250px"  type="email" name="class_contact_email" value="<?php echo $data->class_contact_email; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Location:</label></td>
                <td><input class="w-250px"  type="text" name="class_location" value="<?php echo $data->class_location; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Description URL: </label></td>
                <td><input class="w-250px"  type="url" name="class_description_url" value="<?php echo $data->class_description_url; ?>" /> </td></tr>
             <tr><td class="text-right mr-2"><label>Thumbnail URL:</label></td>
                <td><input id="thumbnail" class="w-250px"  type="url" name="class_thumbnail_url" value="<?php echo $data->class_thumbnail_url; ?>" onChange="updateImage()" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Cost:</label></td>
                <td><input class="w-75px"  type="number" name="class_cost" value="<?php echo $data->class_cost; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Default Slots:</label></td>
                <td><input class="w-75px"  type="number" name="class_default_slots" value="<?php echo $data->class_default_slots; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Rolling Class: </label></td>
                <td><input class="w-75px"  type="text" name="class_rolling" value="<?php echo $data->class_rolling; ?>" /> </td></tr> 
            <tr><td class="text-right mr-2"><input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go(-0);" value="Back"></td>
                <td><input class="btn bt-md btn-primary mr-auto ml-auto"  type="submit" value="Submit" name="submitClass"></td></tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $data->class_ID; ?>">
    </form>
    <?php
}

function createSessionForm ($data, $className, $addNew) {
    ?>
    <div class="text-center mb-4 mr-100px">
        <h1><?php echo $className ?></h1>
    </div>
    <form  method="POST">
        <table class="table table-striped mr-auto ml-auto">
            <tr><td class="text-right mr-2"><label>Contact Name:</label></td>
                <td><input class="w-250px"  type="text" name="session_contact_name" value="<?php echo $data->session_contact_name; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Contact Email:</label></td>
                <td><input class="w-250px"  type="email" name="session_contact_email" value="<?php echo $data->session_contact_email; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Location:</label></td>
                <td><input class="w-250px"  type="text" name="session_location" value="<?php echo $data->session_location; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>Slots: </label></td>
                <td><input class="w-250px"  type="number" name="session_slots" value="<?php echo $data->session_slots; ?>" /> </td></tr>
             <tr><td class="text-right mr-2"><label>Start Time:</label></td>
                <td><input class="w-250px"  type="datetime-local" name="session_start_formatted" value="<?php echo $data->session_start_formatted; ?>" /> </td></tr>
            <tr><td class="text-right mr-2"><label>End Time:</label></td>
                <td><input class="w-250px"  type="datetime-local" name="session_end_formatted" value="<?php echo $data->session_end_formatted; ?>" /> </td></tr>
            
            <tr><td class="text-right mr-2"><input class="btn bt-md btn-danger" style="cursor:pointer;" type="button" onclick="   window.history.go(-0);" value="Back"></td>
                <td><input class="btn bt-md btn-primary mr-auto ml-auto"  type="submit" value="Submit Session" name="submitSession"></td></tr>
        </table>
        <?php
        if ($addNew) {
            ?>
            <input type="hidden" name="session_class_ID" value="<?php echo $data->session_class_ID; ?>">
            <?php
        }
        ?>
        <input type="hidden" name="id" value="<?php echo $data->session_ID; ?>">
    </form>
    <?php
}
?>