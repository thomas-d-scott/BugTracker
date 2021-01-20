<?php 

require 'controllers/mainController.php'; // Gets the connection

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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Bug Tracker</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

</head>

<body>

    <!-- Navbar  -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <h1 class="navbar-brand">Bug Tracker Application</h1>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav ml-auto">
            <a class="nav-item nav-link active" href="index.php?logout=1"><button class="btn btn-md btn-outline-warning" type="submit">Logout</button></a>
            </div>
        </div>
    </nav>

    <!-- Main area -->
    <main>

        <!-- Error messages and current session info -->
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

                <h3>Welcome, <?php echo $_SESSION['name']; ?> (<?php echo $_SESSION['role'] ?>)</h3>
        
        </div>

        <!-- Project info (not in this as is a prototype) and the create ticket button modal popup -->
        <div class="container mt-5">

            <div class="row">
            
                    <div class="col-sm-12 col-md-6 text-center">
                        
                         <h3>Project Name: ---------------</h3>
                    
                    </div>
            
                    <div class="col-sm-12 col-md-6 text-center">

                        <button type="button" class="btn btn-lg btn-success" data-toggle="modal" data-target="#newTicket">
                        Create Ticket
                        </button>

                        <div class="modal" id="newTicket">
                        <div class="modal-dialog">
                            <div class="modal-content">

                            <div class="modal-header">
                                <h4 class="modal-title">Create New Bug Ticket</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>

                            <div class="modal-body">

                                <form action="index.php" method="post">

                                     <div class="form-group">
                                        <label for="type">Select Ticket Type</label>
                                        <select name="type" id="type" class="form-control form-control-lg">
                                            <option value="Development">Development</option>
                                            <option value="Testing">Testing</option>
                                            <option value="Production">Production</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Enter Bug Description</label><br>
                                        <textarea class="form-control" name="description" id="description" cols="30" rows="3"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="priority">Select Priority</label>
                                        <select name="priority" id="priority" class="form-control form-control-lg">
                                            <option value="High">High</option>
                                            <option value="Medium">Medium</option>
                                            <option value="Low">Low</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bugfinder">Select Bug Finder</label>
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
                                        <label for="developer">Select Developer</label>
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
                                        <button type="submit" name="btnCreateTicket" id="btnCreateTicket" class="btn btn-success">Create Ticket</button>
                                    </div>

                                </form>

                            
                            </div>

                            </div>
                        </div>
                        </div>
                    
                    </div>

            </div>
        
        </div>

        <!-- The table containing the tickets. Uses PHP to get the data and store in rows. -->
        <div class="container text-center mb-5">
            <h1 class="py-4">Bug Tickets</h1>

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
                            <th>View Ticket</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">

                    <?php 
                        
                        $result = getTickets();

                        if($result){
                            while($row = mysqli_fetch_assoc($result)){ ?>

                            <!-- HTMLSPECIALCHARS escapes the output to prevent XSS attacks -->
                            <tr>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['id']) ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['timestamp']) ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['tickettype']) ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['status']) ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['description']) ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['priority']) ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars(getName((int)$row['bugfinderid'])); ?></td>
                                <td data-id="<?php echo $row['id'] ?>"><?php echo htmlspecialchars(getName((int)$row['developerid'])); ?></td>
                                <td><a href="message.php?id=<?php echo $row['id'] ?>"><button class="btn btn-sm btn-info" type="submit">View Ticket</a</td>
                            </tr>

                            <?php

                            }
                        }
                    
                    ?>             

                    </tbody>
                </table>
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