<?php

use PDO;

//connect to mySQL
try {
	$db = new PDO('mysql:host=localhost;dbname=MemeSlam;charset=utf8','root','');
} catch (PDOException $e) {
	die($e->getMessage());	
}

