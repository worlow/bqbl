<?php
require_once "../lib/lib.php";
// unset cookies
FOREACH($_COOKIE AS $key => $value) {
     SETCOOKIE($key,$value,TIME()-10000,"/bqbl",getDomain());
}
 
session_destroy();
header("Location: /");