<?php 
    include("../../config/config.php");
    include("../classes/User.php");
    include("../classes/Message.php");

    $limit = 7; //num of message to be loaded

    $message = new Message($con, $_REQUEST['userLoggedIn']);
    echo $message->getConvosDropdown($_REQUEST, $limit)

?>