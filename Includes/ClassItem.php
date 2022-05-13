<?php
class ClassItem {
    function __construct() {
        //$this->class_thumbnail_url = $thumbnail;
    }
    public $class_ID;
    public $class_contact_email;
    public $class_location;
    public $class_description_url;
    public $class_thumbnail_url;
    public $class_cost;
    public $class_default_slots; 
    public $class_rolling;
}

class SessionItem {
     function __construct($classID) {
        $this->session_class_ID = $classID;
    }
    public $session_ID;
    public $session_class_ID;
    public $session_contact_email;
    public $session_contact_name;
    public $session_end_formatted;
    public $session_location;
    public $session_sig_slotitemid;
    public $session_slots;
    public $session_start_formatted;
}
