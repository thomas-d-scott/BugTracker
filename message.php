<?php 

require_once 'controllers/mainController.php'; // Gets the connection

// If the session id is not set, then the user is not logged in and does not have access - is then redirected to the login page.
if (!isset($_SESSION['id'])) {
    header('location: login.php');
    exit();
} 

/**
 * This sets the timeout to 2 minutes. if the user refreshes or clicks a button after 2 minutes, they will be redirected to the login page to sign in again.
 */

$limit = 120; 

if(isset($_SESSION['limit'])) {

    $duration = time() - (int)$_SESSION['limit'];
    if($duration > $limit) {

        session_destroy();
        unset($_SESSION['id']);
        unset($_SESSION['name']);
        unset($_SESSION['email']);
        unset($_SESSION['role']);
        header('location: login.php');
        exit();
    
    }
}
 
$_SESSION['limit'] = time();

$ticketid = mysqli_real_escape_string($conn, $_GET['id']); // Gets the ticket id and escapes to prevent SQL injection.
$_SESSION['ticketid'] = $ticketid; // Assigns ticket id to a global session variable so that the delete.php can access.

/**
 * Getting the ticket data and storing in variables.
 */

$getticketdetails = "SELECT * FROM tickets WHERE id=? LIMIT 1;";
$stmt = mysqli_stmt_init($conn);

if(!mysqli_stmt_prepare($stmt, $getticketdetails)) {

    $_SESSION['message'] = "Cannot get ticket details";
    $_SESSION['alert-class'] = "alert-danger";

    header('location: index.php');

    exit();

}else {

    mysqli_stmt_bind_param($stmt, "d", $ticketid);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $tickettype = $row['tickettype'];
        $timestamp = $row['timestamp'];
        $status = $row['status'];
        $description = $row['description'];
        $priority = $row['priority'];
        $bugfinderid = $row['bugfinderid'];
        $developerid = $row['developerid'];

    }
}

/**
 * If the user clicks to add new comment, it will check if the ticket status is closed and if so new comments cannot be added.
 * If it is not closed, then the comment will be obtained and escaped and stored. It will then be checked if it is valid.
 * If there are no errors, the comment will be added and the user redirected to the current page.
 */

if(isset($_POST['btnNewComment'])){ 

    if ($status === "CLOSED") {

        $_SESSION['message'] = "You cannot comment on a closed ticket";
        $_SESSION['alert-class'] = "alert-warning";

        header('location: message.php?id='.$ticketid);

        exit();

    } else {

        $comment = mysqli_real_escape_string($conn, $_POST['comment']);

        if(empty($comment)){
            $errors['comment'] = "Please add your comment";
        }

        if(count($errors) === 0){

            $sql = "INSERT INTO comments (message, commentor, ticket) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sdd', $comment, $_SESSION['id'], $ticketid);

            if ($stmt->execute()) {

                $_SESSION['message'] = "New comment successfully added";
                $_SESSION['alert-class'] = "alert-success";

                header('location: message.php?id='.$ticketid);

                exit();
            } else{
                $errors['db_error'] = "Failed to create comment, please try again";
            }

        } 

    }

}

/**
 * If the user clicks to delete a ticket, it will check if the users role is a client as they will not be allowed to delete tickets.
 * If they are not a client, then the ticket (and thus all that tickets comments) will be deleted.
 * The user will be redirected to the home page upon deletion with a confirmation message displaying.
 */

if(isset($_POST['btnDeleteTicket'])){ 

    if ($_SESSION['role'] === "Client") {

        $_SESSION['message'] = "You do not have the necessarry access rights to delete tickets";
        $_SESSION['alert-class'] = "alert-danger";

        header('location: message.php?id='.$ticketid);

        exit();

    } else {

        $sqlOne = "DELETE FROM comments WHERE ticket=?";
        $sqlTwo = "DELETE FROM tickets WHERE id=?";

        $stmt = $conn->prepare($sqlOne);
        $stmt->bind_param('d', $ticketid);

        $stmt2 = $conn->prepare($sqlTwo);
        $stmt2->bind_param('d', $ticketid);

        if ($stmt->execute() && $stmt2->execute()) {

            $_SESSION['message'] = "Ticket successfully deleted";
            $_SESSION['alert-class'] = "alert-success";

            header('location: index.php');

            exit();
        } else{
            $errors['db_error'] = "Failed to delete ticket, please try again";
        }

    }

}

/**
 * Will get values from the user and update the ticket. Each will be escaped and validated.
 * If there are no errors, the ticket will be updated.
 * NOTE: The way the system was coded, the user will have to update every item again if they wish to update a ticket - this is most
 * likely because the use of select statements made it more complicated for me to set the current value for each area to the one stored in the db.
 */

if(isset($_POST['btnUpdateTicket'])){ 

    $typeUpd = mysqli_real_escape_string($conn, $_POST['type']);
    $statusUpd = mysqli_real_escape_string($conn, $_POST['status']);
    $descriptionUpd = mysqli_real_escape_string($conn, $_POST['description']);
    $priorityUpd = mysqli_real_escape_string($conn, $_POST['priority']);
    $bugfinderUpd = mysqli_real_escape_string($conn, $_POST['bugfinder']);
    $developerUpd = mysqli_real_escape_string($conn, $_POST['developer']);

    if(empty($typeUpd)){
        $errors['type'] = "Please select ticket type";
    }

    if(empty($statusUpd)){
        $errors['status'] = "Please select ticket status";
    }

    if(empty($descriptionUpd)){
        $errors['description'] = "Please enter bug ticket description";
    }

    if(empty($priorityUpd)){
        $errors['priority'] = "Please select ticket priority";
    }

    if(empty($bugfinderUpd)){
        $errors['bugfinder'] = "Please choose who found the bug";
    }

    if(empty($developerUpd)){
        $errors['developer'] = "Please choose who will be fixing the bug";
    }

    if(count($errors) === 0){

        $sql = "UPDATE tickets SET tickettype = ?, status = ?, priority = ?, description = ?, bugfinderid = ?, developerid = ? WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssddd', $typeUpd, $statusUpd, $priorityUpd, $descriptionUpd, $bugfinderUpd, $developerUpd, $ticketid);

        if ($stmt->execute()) {

            $_SESSION['message'] = "Ticket successfully updated";
            $_SESSION['alert-class'] = "alert-success";

            header('location: message.php?id='.$ticketid);

            exit();
        } else{
            $errors['db_error'] = "Failed to update ticket, please try again";
        }

    } 

}

/**
 * Gets all the comments for the ticket the user is looking at, and returns them.
 */

function getComments($ticketid){
    $sql = "SELECT * FROM comments WHERE ticket=$ticketid";

    $result = mysqli_query($GLOBALS['conn'], $sql);

    if(mysqli_num_rows($result) > 0){
            return $result;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Ticket Message</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <h1 class="navbar-brand">Bug Tracker Application</h1>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav ml-auto">
            <a class="nav-item nav-link" href="index.php"><button class="btn btn-md btn-outline-success" type="submit">Return to App</button></a>
            <a class="nav-item nav-link" href="index.php?logout=1"><button class="btn btn-md btn-outline-warning" type="submit">Logout</button></a>
            </div>
        </div>
    </nav>

    <!-- Main area -->
    <main>
        <!-- Error messages and session info -->
        <div class="container mt-5 text-center">

                <?php if(isset($_SESSION['message'])): ?>
                    <div class="alert <?php echo $_SESSION['alert-class']; ?>">
                        <?php 
                        
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['alert-class']);
                        
                        ?>
                    </div>
                <?php endif ?>

                <?php if(count($errors) > 0): ?>
                    <div class="alert alert-danger">

                    <?php foreach($errors as $error): ?>
                        <li><?php echo $error ?></li>
                    <?php endforeach ?>

                    </div>
                <?php endif ?>

                <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
                <h3>This is the page for ticket no. <?php echo htmlspecialchars($ticketid) ?></h3>
        
        </div>

        <hr class="my-4">

        <div class="container text-center mt-5">

        <!-- Current ticket info -->
        <div class="table-data table-bordered table-responsive">
                <table class="table table-striped table-light">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>Ticket Type</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Priority</th>
                            <th>Bug Finder</th>
                            <th>Developer</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">

                            <tr>
                                <td><?php echo htmlspecialchars($ticketid) ?></td>
                                <td><?php echo htmlspecialchars($timestamp) ?></td>
                                <td><?php echo htmlspecialchars($tickettype) ?></td>
                                <td><?php echo htmlspecialchars($status) ?></td>
                                <td><?php echo htmlspecialchars($description) ?></td>
                                <td><?php echo htmlspecialchars($priority) ?></td>
                                <td><?php echo htmlspecialchars(getName($bugfinderid)); ?></td>
                                <td><?php echo htmlspecialchars(getName($developerid)); ?></td>
                            </tr>
               
                    </tbody>
                </table>
            </div>

            <hr class=my-4>
        
            <div class="row">
            
                <div class="col-md-6 col-sm-12 mb-5">
                
                    <h2>Update Ticket</h2>

                    <hr class="my-4">
                    <!-- Form to update current ticket -->
                    <form action="message.php?id=<?php echo $ticketid ?>" method="POST">

                    <div class="form-group">
                        <label for="ticketid" class="float-left">Ticket ID</label>
                        <input class="form-control" type="number" name="ticketid" id="ticketid" value="<?php echo htmlspecialchars($ticketid); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="type" class="float-left">Select Ticket Type</label>
                        <select name="type" id="type" class="form-control form-control-lg">
                            <option value="Development">Development</option>
                            <option value="Testing">Testing</option>
                            <option value="Production">Production</option>
                        </select>
                    </div>

                        <div class="form-group">
                        <label for="status" class="float-left">Select Status</label>
                        <select name="status" id="status" class="form-control form-control-lg">
                            <option value="OPEN">Open</option>
                            <option value="RESOLVED">Resolved</option>
                            <option value="CLOSED">Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description" class="float-left">Enter Bug Description</label>
                        <textarea class="form-control" name="description" id="description" cols="30" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="priority" class="float-left">Select Priority</label>
                        <select name="priority" id="priority" class="form-control form-control-lg">
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bugfinder" class="float-left">Select Bug Finder</label>
                        <select name="bugfinder" id="bugfinder" class="form-control form-control-lg">
                            
                        <?php 
                        
                        
                            while ($row = $finRes->fetch_assoc()) {                                        

                        ?>

                            <option value="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['firstname']) . " " . htmlspecialchars($row['lastname']) ?></option>

                        <?php 
                        
                        }


                        ?>

                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="developer" class="float-left">Select Developer</label>
                        <select name="developer" id="developer" class="form-control form-control-lg">

                        <?php 
                        
                        
                            while ($row = $devRes->fetch_assoc()) {                                        

                        ?>

                            <option value="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['firstname']) . " " . htmlspecialchars($row['lastname']) ?></option>

                        <?php 
                        
                        }

                        ?>
                            
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="btnUpdateTicket" id="btnUpdateTicket" class="btn btn-info form-control">Update Ticket</button>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="btnDeleteTicket" id="btnDeleteTicket" class="btn btn-danger form-control">Delete Ticket</button>
                    </div>
                    
                    
                    </form>
                
                </div>
                <!-- Create new comment -->
                <div class="col-md-6 col-sm-12 mb-5">
                
                    <h2>Ticket Comments</h2>

                    <hr class="my-4">

                    <form action="message.php?id=<?php echo $ticketid ?>" method="POST">

                    <div class="form-group">
                        <label for="comment" class="float-left">New Comment</label>
                        <textarea class="form-control" name="comment" id="comment" cols="30" rows="3" placeholder="Enter new comment text here..."></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="btnNewComment" id="btnNewComment" class="btn btn-success form-control">Post New Comment</button>
                    </div>


                    </form>
                    <!-- Getting comments for ticket and displaying -->
                    <div class="table-data table-bordered table-responsive">
                    <table class="table table-striped table-light">
                    <thead class="thead-dark">
                        <tr>
                            <th>Comment</th>
                            <th>By</th>
                            <th>On</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">   

                    <?php 
                        
                        $result = getComments($ticketid);

                        if($result){
                            while($row = mysqli_fetch_assoc($result)){ ?>

                            <tr>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['message']); ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars(getName((int)$row['commentor'])); ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['timestamp']); ?></td>
                                <td><a href="delete.php?did=<?php echo $row['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Delete</button></a</td>
                            </tr>

                            <?php

                            }
                        }
                    
                    ?> 

                    </tbody>
                </table>
            </div>
                
                </div>
            
            </div>
        
        </div>

    </main>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
        integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous">
    </script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
        integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous">
    </script>

</body>

</html>