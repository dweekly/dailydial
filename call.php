<?
error_reporting(E_ALL);
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo("<Response>\n");

// get the phone # that is calling us.
$caller_number = $_REQUEST['From'];

function say($what) {
  echo("<Say>$what</Say>\n");
}

function redirect($explain,$state) {
  say($explain);
  echo("<Redirect>call.php?state=".$state."</Redirect>\n");
}

function digitPrompt($curState, $nextState, $text) {
  echo("<Gather action=\"call.php?state=".$nextState."\" numDigits=\"1\" timeout=\"10\" >\n");
  say($text);
  echo("</Gather>\n");
  redirect("Sorry, I didn't get that.", $curState);
  die("</Response>");
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
function lookup_caller($db, $number) {
  $s = $db->prepare("SELECT * FROM users WHERE number='".$db->real_escape_string($number)."' LIMIT 1");
  if(!$s){
    bail("db prep error: ".$db->error);
  }
  if(!$s->execute()){
    bail("db exec error: ".$s->error);
  }
  if(!($r = $s->get_result())) {
    bail("db get res error: ".$s->error);
  }
  $u = $r->fetch_assoc();
  $r->free();
  $s->close();
  return($u);
}

// connect to the mysql db
$db = new mysqli("localhost","root","","dailydial");
if($db->connect_error) {
  bail("could not connect: ".$db->connect_error);
}

// try to look up the caller.
$caller = lookup_caller($db, $caller_number);

$state = $_REQUEST['state'];
if(!$state){
  bail("hm?");
}

// a new call incoming
function newCall($caller) {
  // if we don't recognize the caller...
  if(!$caller) {
//    say("Hi, looks like you're new here. This is the Daily Call service; it's pretty simple - we'll call you at 5pm with someone random. One call a day. No profile, no photos, just you talking with a real person. If they're a total skeezoid, just hang up - they don't have your number. After the call we'll text you asking you how the call went and if you both are interested, we'll text you the other's phone number. Now let's get you signed up.");
    digitPrompt("newCall", "ageSelect", "Press 1 if you're 18 or over, press 2 if not.");
  } else {
    bail("Welcome back. Just wait for your 5pm call.");
  }
}

function ageSelect($digit) {
  if($digit !== '1') {
    bail("I'm sorry but this service is only for people over 18.");
  }
  digitPrompt($state, "genderSelect", "Press 1 if you're male, 2 if you're female.");
}

function genderSelect($digit) {
  switch($digit) {
    case '1':
      $gender = 'M';
      break;
    case '2':
      $gender = 'F';
      break;
    default:
      bail("you need a gender");
  }
  // todo: retry gender entry if timeout or invalid
  digitPrompt("genderSelect&amp;gender=".$gender, "interestedSelect&amp;gender=".$gender,
     "Press 1 if you're interested in talking to men, 2 for women, 3 if you're happy to talk to either.");
}

function interestedSelect($db, $gender, $digit) {
  switch($digit) {
    case '1':
      $interest = 'M';
      say("Got it, you want to talk to men.");
      break;
    case '2':
      $interest = 'F';
      say("Got it, you want to talk to women.");
      break;
    case '3':
      $interest = 'B';
      say("Got it, you want to talk to both men and women.");
      break;
    default:
      bail("I didn't get that.");
  }

  $r = $db->query("INSERT INTO users (number,gender,interest,whenJoined) VALUES ('".$_REQUEST['From']."','".$gender."','".$interest."',UNIX_TIMESTAMP())");
  if($r !== TRUE) {
    bail("Crap, there's been a database error: ". $db->error);
  }
  say("Great, you're all set. You'll get a call at 5pm if we have a match for you. I'll try and send you a text to give you a heads up when you have a call pending.");
  say("Now here's an important bit about how the service works - if the person you talk to doesn't like how the call went, you won't be able to get more calls. You get more points the better the call went and you're dinged points if things go badly. So don't be sketchy.");
  say("It's optional, and you can hang up now if you'd like - but we'd love if you could record a message for our team about how you found this service and what you're hoping to get out of it. It'll help us make this service awesome. When you're done giving us your thoughts, you can just hang up.");
  echo("<Record action=\"call.php?state=signupFeedback\" />\n");
  exit("</Response>");
}

function signupFeedback($db, $url) {
  $r = $db->query("INSERT INTO feedback (number, url, stage, whenGiven) VALUES ('".$caller_number."','".$db->real_escape_string($url)."','signup',UNIX_TIMESTAMP())");
  if(!$r) {
    bail("database error on feedback: " . $db->error);
  }
  bail("Thank you for your feedback!");
}

switch($state) {
  case "newcall":
     newCall($caller);
     break;
  case "ageSelect":
     ageSelect($_REQUEST['Digits']);
     break;
  case "genderSelect":
     genderSelect($_REQUEST['Digits']);
     break;
  case "interestedSelect":
     interestedSelect($db, $_REQUEST['gender'], $_REQUEST['Digits']);
     break;
  case "signupFeedback":
     signupFeedback($db, $_REQUEST['RecordingUrl']);
     break;
  default:
     bail("what state?");
}
bail("impossibru");