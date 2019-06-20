$(document).ready(function() {
    // ajaax call will submit the form for us
    //button for  profile post
    
    $('#submit_profile_post').click(function(){
        console.log('success');
        $.ajax({
            type: "POST",
            url: "includes/handlers/ajax_submit_profile_post.php",
            data: $('form.profile_post').serialize(),
            success: function(msg) {
                // alert(msg);
                $("#post_form").modal('hide');
                location.reload();
            },
            error: function() {
                alert('failure');
            }
        });
    });
});

function getUsers(value, user) {
    //send a request to this page , with the values
    // console.log(value);
    $.post("includes/handlers/ajax_friend_search.php", {query:value , userLoggedIn:user}, function(data) {
        //and when it retusn it going to appends the value of data to results
        // console.log(query);

        $(".results").html(data);
    });
}

                        //username , itmight be notification, messages
function getDropdownData(user, type) {
    //dropdown 
    if($(".dropdown_data_window").css("height")=="0px"){
        var pageName;

        if(type == 'notification') {
            pageName = "ajax_load_notifications.php";
            $("span").remove("#unread_notification")
        } else if(type == 'message') {
            pageName = "ajax_load_messages.php";
            $("span").remove("#unread_message")
        }

        var ajaxreq = $.ajax({
            url: "includes/handlers/" + pageName,
            type: "POST",
            data: "page=1&userLoggedIn=" + user,
            cache: false,

            success: function(response) {
                $(".dropdown_data_window").html(response);
                $(".dropdown_data_window").css({"padding" : "0px", "height" : "280px", "border" : "1px solid #DADADA"});
                $(".dropdown_data_type").val(type);
            }
        })
    } else {
        $(".dropdown_data_window").html("");
        $(".dropdown_data_window").css({"padding" : "0px", "height" : "0px", "border" : "none"});

    }
}