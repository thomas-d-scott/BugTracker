<?php

require_once "controllers/mainController.php"; // Getting the connection.

/**
 * This is more complex as only the creator of a comment can delete their own comments.
 * The id of the comment to be deleted is retrieved and escaped as users can change this in the URL to cause injection attacks.
 * The id of the commentor is then retrieved. 
 * This id of the commentor is compared with the session id to check that the user logged in is trying to delete a comment they have written.
 * If it matches the comment is deleted, if not an error message is shown.
 * The user is redirected to the message page.
 */

if(isset($_GET['did'])) {

    $deleteid = mysqli_real_escape_string( $conn, $_GET['did']);

    $getUID = "SELECT commentor FROM comments WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($getUID);
    $stmt->bind_param('d', $deleteid);
    $stmt->execute();
    $stmt->bind_result($commentorid);
    $stmt->fetch();

    $uid = $commentorid;

    $stmt->close();

    if ($_SESSION['id'] == $uid) {
        
        $sqlOne = "DELETE FROM comments WHERE id=?";
    
        $stmt = $conn->prepare($sqlOne);
        $stmt->bind_param('d', $deleteid);
    
        if ($stmt->execute()) {
    
            $_SESSION['message'] = "Comment successfully deleted";
            $_SESSION['alert-class'] = "alert-success";
    
            header('location: message.php?id='.$_SESSION['ticketid']);
    
            exit();
        } else{
            $errors['db_error'] = "Failed to delete ticket, please try again";
        }

    } else {

        $_SESSION['message'] = "You can only delete comments you have posted.";
        $_SESSION['alert-class'] = "alert-danger";

        header('location: message.php?id='.$_SESSION['ticketid']);
    
        exit();

    }

}
?>