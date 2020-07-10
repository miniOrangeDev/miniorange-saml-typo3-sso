
//when clicking extension name on left panel
$(document).ready(function() {
    let session = document.getElementById('taby').value;
    console.log("Session : in document ready() = "+session);

    if (session == "Service_Provider") {
        openTab(event, 'Service_Provider');
    }else if (session == "Identity_Provider"){
        openTab(event, 'Identity_Provider');
    }else if(session == "Support"){
        openTab(event, 'Support');
    }else if(session == "Attribute_Mapping") {
        openTab(event, 'Attribute_Mapping');
    }else if(session == "Group_Mapping") {
        openTab(event, 'Group_Mapping');
    }else if(session == "" || session == "Account" || session===null){
        openTab(event, 'Account');
    }
});

//when tabs are clicked
function openTab(evt, activeTab) {

    document.getElementById("leftContainer").classList.replace("hideElement","showElement");

    let tabcontent = document.getElementsByClassName("tabcontent");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    let tablinks = document.getElementsByClassName("tablinks");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    document.getElementById(activeTab).style.display = "block";

    if(activeTab==="Support"){
        document.getElementById("Support_Tab").className  += " active";
        document.getElementById("leftContainer").hidden = true;
    }else{
        document.getElementById("Support").style.display = "block";
        document.getElementById(activeTab + "_Tab").className  += " active";
        document.getElementById("leftContainer").hidden = false;
    }
}

//remove flash messages
function removeFlashMessage(){
    document.querySelectorAll('.typo3-messages').forEach(function(a){
        a.remove();
    });
}

//is user registered
function ifUserRegistered(){
    if (document.getElementById('registered').checked){
        document.getElementById('confirmPasswordDiv').style.display = "none";
    } else {
        document.getElementById('confirmPasswordDiv').style.display = "block";
    }
}


function mo_saml_valid_query(f) {
    !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(
        /[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
}