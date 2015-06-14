<?php 
require_once '../lib/lib.php';

$redirectUrl="";
if(isset($_POST['redirect_url']) && $_POST['redirect_url'] != "") {
    $redirectUrl = $_POST['redirect_url'];
} elseif((strpos($_SERVER['HTTP_REFERER'], getDomain()) > 0)
    && !strpos($_SERVER['HTTP_REFERER'], 'login.php')) {
    $redirectUrl = $_SERVER['HTTP_REFERER'];
}

if(!isset($_POST['user'])) {
    $errorMessage = isset($_GET['failed']) 
        ? "<div style='width:100%;text-align:center;'><span style='color:red;'>Login failed!</span></div>"
        : "";
    $PHP_SELF = $_SERVER['PHP_SELF'];
    ui_header("BQBL Login");
    echo <<< END
        <style is="custom-style">
        :root {
        }
        </style>
<div class="login_box">
    $errorMessage
    <form name="login_form" id="login_form" action="$PHP_SELF" method="post">
        <input type='hidden' name="redirect_url" value="$redirectUrl" />
        <paper-input no-label-float label="User" type="text" name="user">
        </paper-input>
        <paper-input no-label-float label="Password" type="password" name="password">
        </paper-input>
        <div>
            <paper-button 
              style="background: #42A5F5;color:white;margin:5px 0; width:100%;" 
              onclick="document.getElementById('login_form').submit();"
              raised>
                Login
            </paper-button>
            <div style="display:inline;float:right;">
                <a href="forgotpassword.php" style="color: var(--paper-blue-400);;word-wrap:break-word;">Forgot password?</a>
            </div>
        </div>
    </form>
</div>
<style>
#content {
padding-top: 32px;
}

.login_box {
    width: 25%;
    text-align: left;
    align: left;
}

paper-input-container {
    text-align: left;
    background-color: #FFFFFF;
    padding-left: 4px;
    padding-bottom: 0;
    margin-bottom: 8px;
    border: 1px #d9d9d9 solid;
    border-top: 1px solid #c0c0c0;
}

</style>
END;

ui_footer();
} else {
    $hashedPassword=hashedPassword($_POST['password']);
    $user=$_POST['user'];
    $dbuser = pg_escape_string($bqbldbconn, $user);
    $query = "SELECT * FROM users WHERE username='$dbuser' AND password='$hashedPassword';";
    $result = pg_query($bqbldbconn, $query);
    if (pg_num_rows($result) == 1) { 
        $_SESSION['user']=$user;
        $_SESSION['token']=hashedPassword($user);
        $_SESSION['auth'] = 1;
        setcookie('user', $_SESSION['user'], time()+60*60*24*3650, '/bqbl', getDomain());
		setcookie('token', $_SESSION['token'], time()+60*60*24*3650, '/bqbl', getDomain());
		setcookie('auth', $_SESSION['auth'], time()+60*60*24*3650, '/bqbl', getDomain());
        if($redirectUrl != "") {
            header("Location: $redirectUrl");
            exit(0);
        }
		header("Location: /");
        
    }
    else {
        header("Location: $PHP_SELF?failed");
    }
}
?>
