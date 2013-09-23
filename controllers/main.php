<?php

require_once (dirname(__FILE__) . '/../models/user.php');
require_once (dirname(__FILE__) . '/../include/utils.php');
require_once (dirname(__FILE__) . '/../settings/config.php');

function main_welcome() {
    sr_response('views/main/index.php', null);
}


function main_signin() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        global $sr_regex_email;
        global $sr_regex_password;

        $user = new User();
        $context = array();

        if (!preg_match($sr_regex_email, $_POST['signin_email'])) {
            $context['result'] = 4;
            $context['msg'] = 'Invalid Email Adress';
        } else if (!preg_match($sr_regex_password, $_POST['signin_password'])) {
            $context['result'] = 5;
            $context['msg'] = 'Invalid Password';
        } else {
            try {
                $db = sr_pdo();

                $stmt = $db->prepare('SELECT * FROM user WHERE email = :email');
                $stmt->bindParam(':email', $_POST['signin_email']);
                $stmt->setFetchMode(PDO::FETCH_CLASS, 'User');
                $stmt->execute();

                $user = $stmt->fetch();

                if ($user == Null || $user == false) {
                    $context['result'] = 2;
                    $context['msg'] = 'Couldn\'t Find Email Address';
                } else if ($user->password != md5($_POST['signin_password'])) {
                    $context['result'] = 3;
                    $context['msg'] = 'Wrong Password';
                } else {
                    $context['result'] = 0;
                    $context['msg'] = 'Successfully signed in';

                    sr_signin($user);
                }

            } catch (PDOException $e) {
                $context['result'] = 1;
                $context['msg'] = 'Failed to signin. Please try it again.';
            }
        }

        if ($context['result'] === 0) {
            sr_redirect('/d/');
        } else {
            sr_response('views/main/signin.php', $context);
        }

    } else {
        // Show signin view
        sr_response('views/main/signin.php', null);
    }
}


function main_signup() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        global $sr_regex_name;
        global $sr_regex_email;
        global $sr_regex_password;

        $user = new User();
        $context = array();

        if (!preg_match($sr_regex_email, $_POST['signup_email'])) {
            $context['result'] = 3;
            $context['msg'] = 'Invalid Email Adress';
        } else if (!preg_match($sr_regex_password, $_POST['signup_password'])) {
            $context['result'] = 4;
            $context['msg'] = 'Invalid Password';
        } else if (!preg_match($sr_regex_name, $_POST['first_name'])) {
            $context['result'] = 5;
            $context['msg'] = 'Invalid Name';
        } else if (!preg_match($sr_regex_name, $_POST['last_name'])) {
            $context['result'] = 6;
            $context['msg'] = 'Invalid Name';
        } else if ($_POST['signup_password'] != $_POST['repeat_password']) {
            $context['result'] = 7;
            $context['msg'] = 'Check Repeat Password';
        } else {

            //TODO: Manage is_authorized, last_active_date
            $user->first_name = $_POST['first_name'];
            $user->last_name = $_POST['last_name'];
            $user->email = $_POST['signup_email'];
            $user->password = md5($_POST['signup_password']);
            $user->is_authorized = 0;
            $user->is_admin = 0;
            $user->join_date = Model::getCurrentTime();
            $user->last_active_date = Model::getCurrentTime();

            try {
                $db = sr_pdo();
                $id = $user->add($db);

                $context['result'] = 0;
                $context['msg'] = 'Successfully registered';

            } catch (PDOException $e) {
                switch ($e->errorInfo[1]) {
                case 1062:
                    // Duplicated entry
                    $context['result'] = 1;
                    $context['msg'] = 'The email address is already registered.';
                    break;
                default:
                    // Other exceptions
                    $context['result'] = 2;
                    $context['msg'] = 'Failed to signup. Please try it again.';
                }
            }
        }

        if ($context['result'] === 0) {
            sr_signin($user);
            sr_redirect('/d/');
        } else {
            sr_response('views/main/signup.php', $context);
        }

    } else {
        // Show signup view
        sr_response('views/main/signup.php', null);
    }
}


function main_signout() {
    $context = array();

    if ($_SESSION['is_logged']) {
        $context['result'] = 0;
        $context['msg'] = 'Email: ' . $_SESSION['user_email'] .
                    '<br />Name:' . $_SESSION['user_name'] .
                    '<br />Successfully signed out';
        sr_signout();
        sr_response('views/main/signout.php', $context);
    } else {
        sr_response_error(404);
    }
}

function main_goto_room() {
    global $sr_root;

    if ($room_name = $_GET['room_name']) {
        $room_name = str_replace(' ', '_', $room_name);
    } else {
        $room_name = uniqid('r');
    }

    if (isset($_GET['user_name']) && strlen($_GET['user_name']) > 0) {
        $_SESSION['chat_name'] = $_GET['user_name'];

    } else {
        if (isset($_SESSION['user_name'])) {
            $_SESSION['chat_name'] = $_SESSION['user_name'];
        } else {
            $_SESSION['chat_name'] = 'Anonymous';
        }
    }

    if (!isset($_SESSION['user_name'])) {
        $_SESSION['user_name'] = $_SESSION['chat_name'];
    }

    header('Location: ' . $sr_root . '/d/room/?name=' . $room_name);
}

?>
