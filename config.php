<?php
unset($CFG);  // Ignore this line                                                                                                           
global $CFG;  // This is necessary here for PHPUnit execution         
$CFG = new stdClass();
           
// fill in the uppercase variables
$CFG->admin_email = 'deefactorial@gmail.com';
$CFG->maintainer = 'deefactorial@gmail.com';
$CFG->system_email = '"openmoney"<noreply@openmoney.cc>';
$CFG->site_name = 'Open Money'; 
$CFG->site_type = 'sandbox';  //Live OR sandbox   
$CFG->site_Xname = 'cloud.openmoney.cc';
$CFG->url = 'http://cloud.openmoney.cc'; //base URL
$CFG->default_currency = 'cc';
$CFG->default_space = '';