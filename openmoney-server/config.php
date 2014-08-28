<? 
unset($CFG);  // Ignore this line                                                                                                           
global $CFG;  // This is necessary here for PHPUnit execution         
$CFG = new stdClass();
           
// fill in the uppercase variables

$CFG->admin_email = 'deefactorial@gmail.com';
$CFG->maintainer = 'deefactorial@gmail.com';
$CFG->system_email = '"openmoney"<noreply@openmoney.org>';
$CFG->site_name = 'Open Money'; 
$CFG->site_type = 'sandbox';  //Live OR sandbox   
$CFG->site_Xname = 'couchbase.triskaideca.com';
$CFG->url = 'http://couchbase.triskaideca.com'; //base URL
$CFG->default_currency = 'cc';
$CFG->default_space = '';


?>
