
$(document).ready(function () {

    $(".popguide").on("click", function (e) {
        $("#phover").show();
        $("#ppopup").show();
    });

    //close the POPUP buuton  if the button with id="close" is clicked
    $("#pclose").on("click", function (e) {
        e.preventDefault();
        $("#ppopup").hide();
        $("#phover").hide();
        
    });

});