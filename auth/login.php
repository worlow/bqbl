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
    $loginMessage = isset($_GET['failed']) 
        ? "Login failed."
        : "Login.";
    $PHP_SELF = $_SERVER['PHP_SELF'];
    echo <<< END
<html><head>
<title>BQBL Login</title></head>
    <table align=center style="position: relative; top: 40%;">
        <tr>
            <td>
                <form name="login_form" action="$PHP_SELF" method="post">
                    <input type='hidden' name="redirect_url" value="$redirectUrl" />
                    <div align=center>$loginMessage</div>
                    <table id="login_box">
                        <tr>
                            <td>User:</td>
                            <td><input type=text name="user" /></td>
                        </tr>
                        <tr>
                            <td>Password:</td>
                            <td><input name="password" type="password" /></td>
                        </tr>
                        <tr>
                            <td align="right" colspan="2"><input type="submit" value="Login" /></td>
                        </tr>
                    </table>
                </form>
                <script language="javascript" type="text/javascript">
                    document.login_form.user.focus();
                </script>
            </td>
        </tr>
    </table>
END;
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
