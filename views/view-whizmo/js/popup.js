
$(document).ready(function () {


    if(!sessionStorage.getItem('firstVisit')){ sessionStorage.setItem('firstVisit', 'Yes'); }else{ sessionStorage.setItem('firstVisit', 'No'); }
    // Retrieve
//    document.getElementById("result").innerHTML = sessionStorage.getItem("firstVisit");

    if (sessionStorage.getItem('firstVisit') === "Yes") {
    //select the POPUP DIV and show it
    $("#thover").fadeIn(3000);
    $("#tpopup").fadeIn(3000);
    
    }
    //close the POPUP buuton  if the button with id="close" is clicked
    $("#tclose").on("click", function (e) {
        e.preventDefault();
        $("#tpopup").fadeOut(500);
        $("#thover").fadeOut(500);
        
    });

});