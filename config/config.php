<?php  
	ob_start(); //this turns on output buffering, saves the php data and pass all to the browser  
	session_start();

	$timezone = date_default_timezone_set('Asia/Manila');
	//displays the date
	//echo "<span style='color:red;font-weight:bold;'>Date: </span>". date('F j, Y g:i:a  ');

	$con = mysqli_connect("xq7t6tasopo9xxbs.cbetxkdyhwsb.us-east-1.rds.amazonaws.com","uqe283jjwcy9tsu9","cr0j5i9v4l9os2e6","dphskh4be06hup8z");

	/*return an error*/
	if(mysqli_connect_errno()){
		echo "failed to connect: " . mysqli_connect_errno();
	}
?>