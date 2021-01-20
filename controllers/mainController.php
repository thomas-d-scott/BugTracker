<?php

/**
 * This is the main controller and deals with the sessions, register, login and creation of tickets and displaying.
 */

session_start();

require 'configuration/database.php'; // Gets the connection.

$errors = array(); // Used to store the errors in an array which each page can access.

// These variables will be used to create the session on successful login and register.

$firstname = "";
$lastname = "";
$email = "";
$role = "";

/**
 * This is getting lists of developers and developers/testers so that the select options can be populated. 
 */

$devQuery = "SELECT * FROM users WHERE role = 'Developer'";

$devRes = mysqli_query($conn, $devQuery);

$finderQuery = "SELECT * FROM users WHERE role = 'Tester' OR role = 'Developer'";

$finRes = mysqli_query($conn, $finderQuery);

/**
 * Function used to retrieve all tickets in the database for displaying to the user. 
 */

function getTickets(){
    $sql = "SELECT * FROM tickets";

    $result = mysqli_query($GLOBALS['conn'], $sql);

    if(mysqli_num_rows($result) > 0){
            return $result;
    }
}

/**
 * As the finder and developer are stored via id, it is more user friendly to display their names as opposed to number.
 * This function takes the id and will return the name of the user. 
 */

function getName($id){
    $sql = "SELECT firstname, lastname FROM users WHERE id='$id' LIMIT 1";

    $result = mysqli_query($GLOBALS['conn'], $sql);

    while($row = $result->fetch_assoc()){
        return $row['firstname'] . " " . $row['lastname'];
    }   
}

/**
 * This is for registering a new user when the register button on register page is clicked.
 * It assigns the values the user enters to the variables - ESCAPING THEM TO PREVENT SQL INJECTIONS. 
 * Then it validates to ensure there are no empty fields and that the appropriate data has been entered. Also checks if the email already exists.
 * If the validation is not correct, then errors are stored in the error array and displayed to the user with messages.
 * If there are no errors, then the new user is created and a session is started and the user is logged in. 
 */

if(isset($_POST['btnRegister'])){
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $passwordConfirm = mysqli_real_escape_string($conn, $_POST['passwordConfirm']);

    if(empty($firstname)){
        $errors['firstname'] = "First Name required";
    }
    if(empty($lastname)){
        $errors['lastname'] = "Last Name required";
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors['emailInvalid'] = "Email invalid";
    }

    if(empty($email)){
        $errors['email'] = "Email required";
    }

    if(empty($password)){
        $errors['password'] = "Password required";
    }

    if(strlen($password) < 8 || strlen($password) > 16){
        $errors['passwordLength'] = "Password must be between 8 and 16 characters";
    }

    if (!preg_match("/\d/", $password)) {
        $errors['passwordNo'] = "Password must include at least 1 number";
    }

    if (!preg_match("/\W/", $password)) {
        $errors['passwordChar'] = "Password must include at least 1 special character";
    }

    if($password != $passwordConfirm){
        $errors['passwordMatch'] = "Passwords must match";
    }

    $emailQuery = "SELECT * FROM users WHERE email=? LIMIT 1";
    $stmt = $conn->prepare($emailQuery);

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $userCount = $result->num_rows;
    $stmt->close();

    if($userCount > 0){
        $errors['email'] = "Email account already registered";
    }

    if (count($errors) === 0) {
        $password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (firstname, lastname, email, role, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $firstname, $lastname, $email, $role, $password);

        if ($stmt->execute()) {

            $user_id = $conn->insert_id;
            $_SESSION['id'] = htmlspecialchars($user_id);
            $_SESSION['name'] = htmlspecialchars($firstname) . " " . htmlspecialchars($lastname);
            $_SESSION['email'] = htmlspecialchars($email);
            $_SESSION['role'] = htmlspecialchars($role);

            $_SESSION['message'] = "You are now logged in";
            $_SESSION['alert-class'] = "alert-success";

            header('location: index.php');

            exit();
        } else{
            $errors['db_error'] = "Database error: Failed to register";
        }
    }

}

/**
 * This logs the user in and creates a session whent he login button is clicked.
 * Will escape the values and validate to ensure they are not empty. 
 * If there are no errors, it will retrieve the password linked to the email and compare with the password the user entered. 
 * If they match a session is created and the user is logged in.
 */

if(isset($_POST['btnLogin'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    if(empty($email)){
        $errors['email'] = "Email required";
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors['emailInvalid'] = "Email invalid";
    }

    if(empty($password)){
        $errors['password'] = "Password required";
    }

    if (count($errors) === 0) {
        

        $sql = "SELECT * FROM users WHERE email=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);

        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['id'] = htmlspecialchars($user['id']);
            $_SESSION['name'] = htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']);
            $_SESSION['email'] = htmlspecialchars($user['email']);
            $_SESSION['role'] = htmlspecialchars($user['role']);

            $_SESSION['message'] = "You are now logged in";
            $_SESSION['alert-class'] = "alert-success";

            header('location: index.php');

            exit();
        } else {
            $errors['loginFail'] = "Wrong email or password";
        }

    }

}

/**
 * Session is destroyed and global session variables are removed then the user is redirected to the login page.
 */

if(isset($_GET['logout'])){

    session_destroy();
    unset($_SESSION['id']);
    unset($_SESSION['name']);
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    header('location: login.php');
    exit();

}

/**
 * When the create ticket button is clicked, a new ticket is created.
 * Gets inputs and escapes them to be safe from SQL injections, and further validates to ensure no empty fields.
 * If there are no errors, the ticket is created and success message shows.
 */

if(isset($_POST['btnCreateTicket'])){ 

    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $bugfinder = mysqli_real_escape_string($conn, $_POST['bugfinder']);
    $developer = mysqli_real_escape_string($conn, $_POST['developer']);

    if(empty($type)){
        $errors['type'] = "Please select ticket type";
    }

    if(empty($description)){
        $errors['description'] = "Please enter bug ticket description";
    }

    if(empty($priority)){
        $errors['priority'] = "Please select ticket priority";
    }

    if(empty($bugfinder)){
        $errors['bugfinder'] = "Please choose who found the bug";
    }

    if(empty($developer)){
        $errors['developer'] = "Please choose who will be fixing the bug";
    }

    if(count($errors) === 0){

        $sql = "INSERT INTO tickets (tickettype, priority, description, bugfinderid, developerid) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssdd', $type, $priority, $description, $bugfinder, $developer);

        if ($stmt->execute()) {

            $_SESSION['message'] = "New ticket successfully added";
            $_SESSION['alert-class'] = "alert-success";

            header('location: index.php');

            exit();
        } else{
            $errors['db_error'] = "Failed to create ticket, please try again";
        }

    }

}

?>