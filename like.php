

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <title>Document</title>
</head>
<body>

    <style type="text/css">
        * {
            font-family: Arial, Helvetica, Sans-serif;
        }

        body {
            background-color: #fff;
        }

        form{
            position: absolute;
            top: 0;

        }
    </style>

<?php
        require 'config/config.php';
	    include("includes/classes/Notification.php");  
	    include("includes/classes/User.php");
	    include("includes/classes/Post.php");  

            // if this session variasnble is set, make the user loggedin = username
        if (isset($_SESSION['username'])){
            $userLoggedIn = $_SESSION['username'];
            $user_details_query = mysqli_query($con,"SELECT * FROM users WHERE username='$userLoggedIn'");
            // returns all information of user logged in
            $user = mysqli_fetch_array($user_details_query);
        } else {
            // send back to the register page if not log in
            header('Location: register.php');
        }

        if(isset($_GET['post_id'])){
            $post_id = $_GET['post_id'];
        } 

        $get_likes = mysqli_query($con, "SELECT likes, added_by FROM posts WHERE id='$post_id'");
        $row = mysqli_fetch_array($get_likes);
        $total_likes = $row['likes'];
        //person who posted thos post
        $user_liked = $row['added_by'];

        $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$user_liked'");
        $row = mysqli_fetch_array($user_details_query);
        $total_user_likes = $row['num_likes'];

        //like button
        if(isset($_POST['like_button'])) {
            // if clicked/ add total likes and update the db
            $total_likes++;
            $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
            $total_user_likes++;
            $user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes' WHERE username='$user_liked'");
            //we put the user in the likes table, just to be updated
            $insert_user = mysqli_query($con,"INSERT INTO likes VALUES('','$userLoggedIn','$post_id')");

            //insert notification;
            if($user_liked != $userLoggedIn){
                $notification = new Notification($con, $userLoggedIn);
                $notification->insertNotification($post_id, $user_liked, "like");
            }
        }
        //unlike
        if(isset($_POST['unlike_button'])) {
            // if clicked/ add total unlikes and update the db
            $total_likes--;
            $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
            $total_user_likes--;
            $user_likes = mysqli_query($con, "UPDATE users SET num_likes='total_user_likes' WHERE username='$user_liked'");
            //we put the user in the likes table, just to be updated
            $inser_user = mysqli_query($con,"DELETE FROM likes WHERE username='$userLoggedIn' AND post_id='$post_id'");


        }

        //check previous like  
        // var_dump($userLoggedIn);
        $check_query = mysqli_query($con,"SELECT * FROM likes WHERE username='$userLoggedIn' AND post_id='$post_id'");
        $num_rows = mysqli_num_rows($check_query);

        if($num_rows>0){
            echo '<form action="like.php?post_id='.$post_id.'" method="POST">
                    <input type="submit" class="comment_like" name="unlike_button" value="Unlike">
                    <div class="like_value">
                        '. $total_likes.' Likes
                    </div>
                </form>
            ';
        } else {
            echo '<form action="like.php?post_id='.$post_id.'" method="POST">
                    <input type="submit" class="comment_like" name="like_button" value="Like">
                    <div class="like_value">
                        '. $total_likes.' Likes
                    </div>
                </form>
            ';
        }
?>

    
</body>
</html>