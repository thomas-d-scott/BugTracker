<?php 

require 'controllers/mainController.php'; // Get connection

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Register</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

        <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
       <h1 class="text-light">Bug Tracker Application</h1>
</nav>
    <!-- Errors and register form -->
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-md-4 offset-md-4 form-div">
                <form action="register.php" method="post">
                    <h3 class="text-center">Register</h3>

                    <?php if(count($errors) > 0): ?>
                    <div class="alert alert-danger">

                    <?php foreach($errors as $error): ?>
                        <li><?php echo $error ?></li>
                    <?php endforeach ?>

                    </div>
                    <?php endif ?>

                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" class="form-control form-control-lg">
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" class="form-control form-control-lg">
                    </div>

                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control form-control-lg">
                    </div>

                    <div class="form-group">
                        <label for="role">Select Role</label>
                        <select name="role" id="role" class="form-control form-control-lg">
                            <option value="Developer">Developer</option>
                            <option value="Tester">Tester</option>
                            <option value="Client">Client</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg">
                    </div>
                    
                    <div class="form-group">
                        <label for="passwordConfirm">Confirm Password</label>
                        <input type="password" name="passwordConfirm" class="form-control form-control-lg">
                    </div>

                    <div class="form-group">
                        <button type="submit" name="btnRegister" class="btn btn-primary btn-block btn-lg">Register</button>
                    </div>

                    <p class="text-center">Already Signed up? <a href="login.php">Sign In</a></p>

                </form>
            </div>
        </div>
    </div>

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