<?php
    class Post {
        // private means this class only could access $user, etc
        private $user_obj;
        private $con;

        // creating an instance of User Class, para magamit yung  user class
        public function __construct($con, $user){
			$this->con = $con;
			//getin the user who is logged in?
            $this->user_obj = new User($con, $user);
        }
								//post_Text from index.php, none if index.php
        public function submitPost($body, $user_to){
         	$body = strip_tags($body); //removes html tags
         	// escape the single quotes, Escape special characters in a string, if sql statement has quote or special character, sql will think that its another string following and that would messed up. so thats why use mysqlirealescapestring
         	$body = mysqli_real_escape_string($this->con, $body);
         	// forwards slash srounding the text you want to replace \s+ is space? and will replace it by nothing
         	$check_empty = preg_replace('/\s+/', '', $body); //Delete all spaces

         	// if empty yung post walang ggawin or dont post anything
         	if($check_empty != "") {
         		//current  date and time
         		$date_added = date("Y-m-d H:i:s");
         		//get username to the User class, we were able to use this function because of the construct above
         		$added_by = $this->user_obj->getUserName();
         		//if user is not on own profile, user_to is 'none', user_to = $added_by kapag ung user is nasa own profile the set user_to = none, 
         		if($user_to==$added_by){
         			$user_to = 'none';
         		}


         		// insert post
         		$query = mysqli_query($this->con, "INSERT INTO posts VALUES(NULL, '$body', '$added_by', '$user_to', '$date_added', 'no', 'no', '0')");
         		// Return the id used in the last query:
         		$returned_id = mysqli_insert_id($this->con);

         		//insert notification
				if($user_to != 'none'){
					$notification = new Notification($this->con, $added_by);
					$notification->insertNotification($returned_id, $user_to, "profile_post");
				}

         		//update post count for user
         		$num_posts = $this->user_obj->getNumPosts();
         		// if we post therefore + 1 to the counnt of exisiting post
         		$num_posts++;
         		// put the updated number of post to the user table
         		$update_query = mysqli_query($this->con, "UPDATE users SET num_posts='$num_posts' WHERE username = '$added_by'");
         	}
		}
		
		//load post by friends
		public function loadPostsFriends($data, $limit) {

			$page = $data['page']; 
			$userLoggedIn = $this->user_obj->getUsername();
	
			if($page == 1) {
				$start = 0;
			} else {
				$start = ($page - 1) * $limit;
			}
	
	
			$str = ""; //String to return , initializing to prvent errors
			//get all data ng hindi pa na ddelete
			$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' ORDER BY id DESC");
	
			if(mysqli_num_rows($data_query) > 0) {
	
	
				$num_iterations = 0; //Number of results checked (not necasserily posted)
				$count = 1;
				//loop through each data querry, next row resuts
				while($row = mysqli_fetch_array($data_query)) {
					// var_dump($count);
					//this column table is assigning to variables
					$id = $row['id'];
					$body = $row['body'];
					$added_by = $row['added_by'];
					$date_time = $row['date_added'];
	
					//Prepare user_to string so it can be included even if not posted to a user
					if($row['user_to'] == "none") {
						$user_to = "";
					}
					else {
						//user_to infos, if may user_to sino siya
						$user_to_obj = new User($this->con, $row['user_to']);
						$user_to_name = $user_to_obj->getFirstAndLastName();
						//return a link whoever the user is
						$user_to = "to <a href='" . $row['user_to'] ."'>" . $user_to_name . "</a>";
					}
	
					//Check if user who posted, has their account closed, do not show if close
					// check kapag yung account niya is close. close means, didnt deactivated?
					//object of the user who posted
					$added_by_obj = new User($this->con, $added_by);
					if($added_by_obj->isClosed()) { //will return a true or false from user class
						//if close get back to while, next iteration
						continue;
					}


					// this checks is their friends? if it returns true do all inside of true. inside of true is loading all the post 
					$user_logged_obj = new User($this->con, $userLoggedIn);
					// echo $user_logged_obj->isFriend($added_by); 
					if($user_logged_obj->isFriend($added_by)){

					
					
						// if greater that start create new page
						if($num_iterations++ < $start)
							continue; 
	
	
						// //Once 10 posts have been loaded, break
						if($count > $limit) {
							break; //leave the loop
						}
						else {
							$count++;
						}

						if($userLoggedIn==$added_by){
							$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
						} else {
							$delete_button = "";
							
						}
						// geting the information who posted
						$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE username='$added_by'");
						$user_row = mysqli_fetch_array($user_details_query);
						$first_name = $user_row['first_name'];
						$last_name = $user_row['last_name'];
						$profile_pic = $user_row['profile_pic'];
	

						?>
						<!-- script html block -->
						<script>
							//this will be the toggle 1 23 etc, thats how , which comment to show. this is going to what happens when you click show comments
							function toggle<?php echo $id;?>(){
								//if this was user click					//this is where the person's colelct
								// var.target = $(event.target)
								//if target is not a href or not a link dont show comments
								// if(!target.is("a")){
									var element = document.getElementById("toggleComment<?php echo $id;?>");
									//block is like the paragraph tags where ther is a new line before and after it
									if(element.style.display == "block"){
										// if showing hide it 
										element.style.display = "none";
									} else {
										// if hidden show it
										element.style.display = "block";
									}
								// }
					
							}
						
						</script>

						<?php 
							//find comments thats belongs to this post
							$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
							//number of results
							$comments_check_num = mysqli_num_rows($comments_check);

						
						?>
						
						<?php
						//Timeframe
						$date_time_now = date("Y-m-d H:i:s");
						$start_date = new DateTime($date_time); //Time of post
						$end_date = new DateTime($date_time_now); //Current time
						$interval = $start_date->diff($end_date); //Difference between dates 
						if($interval->y >= 1) {
							if($interval->y == 1)
								$time_message = $interval->y . " year ago"; //1 year ago
							else 
								$time_message = $interval->y . " years ago"; //1+ year ago
						}
						else if ($interval->m >= 1) {
							if($interval->d == 0) {
								$days = " ago";
							}
							else if($interval->d == 1) {
								$days = $interval->d . " day ago";
							}
							else {
								$days = $interval->d . " days ago";
							}
	
	
							if($interval->m == 1) {
								$time_message = $interval->m . " month". $days;
							}
							else {
								$time_message = $interval->m . " months". $days;
							}
	
						}
						else if($interval->d >= 1) {
							if($interval->d == 1) {
								$time_message = "Yesterday";
							}
							else {
								$time_message = $interval->d . " days ago";
							}
						}
						else if($interval->h >= 1) {
							if($interval->h == 1) {
								$time_message = $interval->h . " hour ago";
							}
							else {
								$time_message = $interval->h . " hours ago";
							}
						}
						else if($interval->i >= 1) {
							if($interval->i == 1) {
								$time_message = $interval->i . " minute ago";
							}
							else {
								$time_message = $interval->i . " minutes ago";
							}
						}
						else {
							if($interval->s < 30) {
								$time_message = "Just now";
							}
							else {
								$time_message = $interval->s . " seconds ago";
							}
						}
						// nadadag dagan yung str every time mag loop
						$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
									<div class='post_profile_pic'>
										<img src='$profile_pic' width='50'>
									</div>
	
									<div class='posted_by' style='color:#ACACAC;'>
										<a href='$added_by'> $first_name $last_name </a> $user_to &nbsp;&nbsp;&nbsp;&nbsp;$time_message
										$delete_button
									</div>
									<div id='post_body'>
										$body
										<br>
										<br>
										<br>
									</div>

									<div class='newsfeedPostOptions'>
										Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
										<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
									
									</div>
	
								</div>
								<div class='post_comment' id='toggleComment$id' style='display:none'>
									<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' frameborder='0'></iframe>
								</div>
								<hr>";

					}

					?>
						<script>
							$(document).ready(function() {
								$('#post<?php echo $id; ?>').on('click', function() {
									bootbox.confirm("Are you sure you want to delete this post?", function(result) {
										$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});
										if(result)
											location.reload();
									});
								});
							});
						</script>
					<?php
					
	
				} //End while loop
	
				if($count > $limit) 
					$str .= "<input type='hidden' class='nextPage' value='" . ($page + 1) . "'>
								<input type='hidden' class='noMorePosts' value='false'>";
				else 
					$str .= "<input type='hidden' class='noMorePosts' value='true'><p style='text-align: centre;' > No more posts to show! </p>";
			}
	
			echo $str;
	
	
		}

		public function loadProfilePosts($data, $limit) {

			$page = $data['page']; 
			$profileUser = $data['profileUsername'];
			$userLoggedIn = $this->user_obj->getUsername();
	
			if($page == 1) {
				$start = 0;
			} else {
				$start = ($page - 1) * $limit;
			}
	
	
			$str = ""; //String to return , initializing to prvent errors
			//review this part
			$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' AND ((added_by='$profileUser' AND user_to='none') OR user_to='$profileUser') ORDER BY id DESC");
	
			if(mysqli_num_rows($data_query) > 0) {
	
	
				$num_iterations = 0; //Number of results checked (not necasserily posted)
				$count = 1;
				//loop through each data querry, next row resuts
				while($row = mysqli_fetch_array($data_query)) {
					// var_dump($count);
					//this column table is assigning to variables
					$id = $row['id'];
					$body = $row['body'];
					$added_by = $row['added_by'];
					$date_time = $row['date_added'];

						// if greater that start create new page
						if($num_iterations++ < $start)
							continue; 
	
	
						// //Once 10 posts have been loaded, break
						if($count > $limit) {
							break; //leave the loop
						}
						else {
							$count++;
						}

						if($userLoggedIn==$added_by){
							$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
						} else {
							$delete_button = "";
							
						}
						// geting the information who posted
						$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE username='$added_by'");
						$user_row = mysqli_fetch_array($user_details_query);
						$first_name = $user_row['first_name'];
						$last_name = $user_row['last_name'];
						$profile_pic = $user_row['profile_pic'];
	

						?>
						<!-- script html block -->
						<script>
							//this will be the toggle 1 23 etc, thats how , which comment to show. this is going to what happens when you click show comments
							function toggle<?php echo $id;?>(){
								//if this was user click					//this is where the person's colelct
								// var.target = $(event.target)
								//if target is not a href or not a link dont show comments
								// if(!target.is("a")){
									var element = document.getElementById("toggleComment<?php echo $id;?>");
									//block is like the paragraph tags where ther is a new line before and after it
									if(element.style.display == "block"){
										// if showing hide it 
										element.style.display = "none";
									} else {
										// if hidden show it
										element.style.display = "block";
									}
								// }
					
							}
						
						</script>

						<?php 
							//find comments thats belongs to this post
							$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
							//number of results
							$comments_check_num = mysqli_num_rows($comments_check);
						
						?>
						
						<?php
						//Timeframe
						$date_time_now = date("Y-m-d H:i:s");
						$start_date = new DateTime($date_time); //Time of post
						$end_date = new DateTime($date_time_now); //Current time
						$interval = $start_date->diff($end_date); //Difference between dates 
						if($interval->y >= 1) {
							if($interval->y == 1)
								$time_message = $interval->y . " year ago"; //1 year ago
							else 
								$time_message = $interval->y . " years ago"; //1+ year ago
						}
						else if ($interval->m >= 1) {
							if($interval->d == 0) {
								$days = " ago";
							}
							else if($interval->d == 1) {
								$days = $interval->d . " day ago";
							}
							else {
								$days = $interval->d . " days ago";
							}
	
	
							if($interval->m == 1) {
								$time_message = $interval->m . " month". $days;
							}
							else {
								$time_message = $interval->m . " months". $days;
							}
	
						}
						else if($interval->d >= 1) {
							if($interval->d == 1) {
								$time_message = "Yesterday";
							}
							else {
								$time_message = $interval->d . " days ago";
							}
						}
						else if($interval->h >= 1) {
							if($interval->h == 1) {
								$time_message = $interval->h . " hour ago";
							}
							else {
								$time_message = $interval->h . " hours ago";
							}
						}
						else if($interval->i >= 1) {
							if($interval->i == 1) {
								$time_message = $interval->i . " minute ago";
							}
							else {
								$time_message = $interval->i . " minutes ago";
							}
						}
						else {
							if($interval->s < 30) {
								$time_message = "Just now";
							}
							else {
								$time_message = $interval->s . " seconds ago";
							}
						}
						// nadadag dagan yung str every time mag loop
						$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
									<div class='post_profile_pic'>
										<img src='$profile_pic' width='50'>
									</div>
	
									<div class='posted_by' style='color:#ACACAC;'>
										<a href='$added_by'> $first_name $last_name </a> &nbsp;&nbsp;&nbsp;&nbsp;$time_message
										$delete_button
									</div>
									<div id='post_body'>
										$body
										<br>
										<br>
										<br>
									</div>

									<div class='newsfeedPostOptions'>
										Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
										<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
									
									</div>
	
								</div>
								<div class='post_comment' id='toggleComment$id' style='display:none'>
									<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' frameborder='0'></iframe>
								</div>
								<hr>";

					

					?>
						<script>
							$(document).ready(function() {
								$('#post<?php echo $id; ?>').on('click', function() {
									bootbox.confirm("Are you sure you want to delete this post?", function(result) {
										$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});
										if(result)
											location.reload();
									});
								});
							});
						</script>
					<?php
					
	
				} //End while loop
	
				if($count > $limit) 
					$str .= "<input type='hidden' class='nextPage' value='" . ($page + 1) . "'>
								<input type='hidden' class='noMorePosts' value='false'>";
				else 
					$str .= "<input type='hidden' class='noMorePosts' value='true'><p style='text-align: centre;' > No more posts to show! </p>";
			}
	
			echo $str;
	
	
		}

		public function getSinglePost($post_id) {

			$userLoggedIn = $this->user_obj->getUsername();

			$opened_query = mysqli_query($this->con, "UPDATE notifications SET opened='yes' WHERE user_to='$userLoggedIn' AND link LIKE '%=$post_id'");
	
			$str = ""; //String to return , initializing to prvent errors
			//get all data ng hindi pa na ddelete
			$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' AND id='$post_id'");
	
			if(mysqli_num_rows($data_query) > 0) {
	
				//loop through each data querry, next row resuts
				$row = mysqli_fetch_array($data_query);
					// var_dump($count);
					//this column table is assigning to variables
					$id = $row['id'];
					$body = $row['body'];
					$added_by = $row['added_by'];
					$date_time = $row['date_added'];
	
					//Prepare user_to string so it can be included even if not posted to a user
					if($row['user_to'] == "none") {
						$user_to = "";
					}
					else {
						//user_to infos, if may user_to sino siya
						$user_to_obj = new User($this->con, $row['user_to']);
						$user_to_name = $user_to_obj->getFirstAndLastName();
						//return a link whoever the user is
						$user_to = "to <a href='" . $row['user_to'] ."'>" . $user_to_name . "</a>";
					}
	
					//Check if user who posted, has their account closed, do not show if close
					// check kapag yung account niya is close. close means, didnt deactivated?
					//object of the user who posted
					$added_by_obj = new User($this->con, $added_by);
					if($added_by_obj->isClosed()) { //will return a true or false from user class
						//leave the function
						return;
					}


					// this checks is their friends? if it returns true do all inside of true. inside of true is loading all the post 
					$user_logged_obj = new User($this->con, $userLoggedIn);
					// echo $user_logged_obj->isFriend($added_by); 
					if($user_logged_obj->isFriend($added_by)){

						if($userLoggedIn==$added_by){
							$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
						} else {
							$delete_button = "";
							
						}
						// geting the information who posted
						$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE username='$added_by'");
						$user_row = mysqli_fetch_array($user_details_query);
						$first_name = $user_row['first_name'];
						$last_name = $user_row['last_name'];
						$profile_pic = $user_row['profile_pic'];
	

						?>
						<!-- script html block -->
						<script>
							//this will be the toggle 1 23 etc, thats how , which comment to show. this is going to what happens when you click show comments
							function toggle<?php echo $id;?>(){
								//if this was user click					//this is where the person's colelct
								// var.target = $(event.target)
								//if target is not a href or not a link dont show comments
								// if(!target.is("a")){
									var element = document.getElementById("toggleComment<?php echo $id;?>");
									//block is like the paragraph tags where ther is a new line before and after it
									if(element.style.display == "block"){
										// if showing hide it 
										element.style.display = "none";
									} else {
										// if hidden show it
										element.style.display = "block";
									}
								// }
					
							}
						
						</script>

						<?php 
							//find comments thats belongs to this post
							$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
							//number of results
							$comments_check_num = mysqli_num_rows($comments_check);

						
						?>
						
						<?php
						//Timeframe
						$date_time_now = date("Y-m-d H:i:s");
						$start_date = new DateTime($date_time); //Time of post
						$end_date = new DateTime($date_time_now); //Current time
						$interval = $start_date->diff($end_date); //Difference between dates 
						if($interval->y >= 1) {
							if($interval->y == 1)
								$time_message = $interval->y . " year ago"; //1 year ago
							else 
								$time_message = $interval->y . " years ago"; //1+ year ago
						}
						else if ($interval->m >= 1) {
							if($interval->d == 0) {
								$days = " ago";
							}
							else if($interval->d == 1) {
								$days = $interval->d . " day ago";
							}
							else {
								$days = $interval->d . " days ago";
							}
	
	
							if($interval->m == 1) {
								$time_message = $interval->m . " month". $days;
							}
							else {
								$time_message = $interval->m . " months". $days;
							}
	
						}
						else if($interval->d >= 1) {
							if($interval->d == 1) {
								$time_message = "Yesterday";
							}
							else {
								$time_message = $interval->d . " days ago";
							}
						}
						else if($interval->h >= 1) {
							if($interval->h == 1) {
								$time_message = $interval->h . " hour ago";
							}
							else {
								$time_message = $interval->h . " hours ago";
							}
						}
						else if($interval->i >= 1) {
							if($interval->i == 1) {
								$time_message = $interval->i . " minute ago";
							}
							else {
								$time_message = $interval->i . " minutes ago";
							}
						}
						else {
							if($interval->s < 30) {
								$time_message = "Just now";
							}
							else {
								$time_message = $interval->s . " seconds ago";
							}
						}
						// nadadag dagan yung str every time mag loop
						$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
									<div class='post_profile_pic'>
										<img src='$profile_pic' width='50'>
									</div>
	
									<div class='posted_by' style='color:#ACACAC;'>
										<a href='$added_by'> $first_name $last_name </a> $user_to &nbsp;&nbsp;&nbsp;&nbsp;$time_message
										$delete_button
									</div>
									<div id='post_body'>
										$body
										<br>
										<br>
										<br>
									</div>

									<div class='newsfeedPostOptions'>
										Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
										<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
									
									</div>
	
								</div>
								<div class='post_comment' id='toggleComment$id' style='display:none'>
									<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' frameborder='0'></iframe>
								</div>
								<hr>";

					

					?>
						<script>
							$(document).ready(function() {
								$('#post<?php echo $id; ?>').on('click', function() {
									bootbox.confirm("Are you sure you want to delete this post?", function(result) {
										$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});
										if(result)
											location.reload();
									});
								});
							});
						</script>
					<?php
					} else {
						echo "<p>You cannot see this post because you are not friends with this user.</p>";
						return;
					}
			} else {
				echo "<p>No Post Found. if you clicked a link, it may be broken. </p>";
				return;
			}
	
			echo $str;
		}
    }

?>

