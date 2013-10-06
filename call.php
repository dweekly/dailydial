<?
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo("<Response>\n");

// get the phone # that is calling us.
$caller_number = $_REQUEST['From'];

function say($what) {
  echo("<Say>$what</Say>\n");
}

function prompt($text, $options) {
   say($text);
}

// bail out and explain why.
function bail($reason) {
  say($reason);
  exit("</Response>");
}

// if number is unknown, punt the user.
if(substr($caller_number,0,1) != '+') {
  bail("I'm sorry but to use this service you need to call from a cell phone with caller ID turned on.");
}


// attempts to look up information about the caller
// and return it in an object.
function lookup_caller($number) {
  // TODO: actually implement storage/persistence layer ;)
  return(null);
}

$caller = lookup_caller($caller_number);


// if we don't recognize the caller...
if(!$caller) {
  say("Hi, looks like you're new here. This is the Daily Call service; it's pretty simple - we'll call you at 5pm with someone random. One call a day. No profile, no photos, just you talking with a real person. If they're a total skeezoid, just hang up - they don't have your number. After the call we'll text you asking you how the call went and if you both are interested, we'll text you the other's phone number. Now let's get you signed up.");
  prompt("Press 1 if you're 18 or over, press 2 if not", array("1" => signup_over_18, "2" => signup_not_over_18));
  bail("That's it for now.");
} else {
  bail("How are you recognized?");
}

bail("impossibru");