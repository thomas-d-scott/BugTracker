<?php

/**
 * This page sets up the connection to the database. 
 * It also creates the database if it does not exist and will generate the required tables for the web app.
 * The connection is then returned allowing other pages to access the connection to the database.
 */

require 'constants.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);

if($conn->connect_error) {
    die('Database error: ' . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;

if(mysqli_query($conn, $sql)){
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $table1="

        CREATE TABLE IF NOT EXISTS users(
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                firstname VARCHAR(30) NOT NULL,
                lastname VARCHAR(30) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                role VARCHAR(10) NOT NULL,
                password VARCHAR(255) NOT NULL
        );

    ";

    $table2="
    
        CREATE TABLE IF NOT EXISTS tickets(
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tickettype VARCHAR(11) NOT NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'OPEN',
            priority VARCHAR(10) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            description VARCHAR(255) NOT NULL,
            bugfinderid INT(11) NOT NULL,
            developerid INT(11) NOT NULL,
            FOREIGN KEY (bugfinderid) REFERENCES users(id),
            FOREIGN KEY (developerid) REFERENCES users(id)
        );

    ";

    $table3 ="
    
        CREATE TABLE IF NOT EXISTS comments(
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            message VARCHAR(255) NOT NULL,
            commentor INT(11) NOT NULL,
            ticket INT(11) NOT NULL,
            FOREIGN KEY (commentor) REFERENCES users(id),
            FOREIGN KEY (ticket) REFERENCES tickets(id)
        );   
    
    ";

    $queries = [$table1, $table2, $table3];

    foreach ($queries as $key => $sql) {
        if(mysqli_query($conn, $sql)){
            continue;
        }else{
            echo "Cannot create table";
        }   
    }
    
     return $conn;
} else {
    echo "Error creating database".mysqli_error($conn);
}

?>

 