
//when clicking extension name on left panel
$(document).ready(function() {
    let session = document.getElementById('taby').value;

    console.log("Session : in document ready() = "+session);

    if (session == "Service_Provider"){
        openTab('Service_Provider');
    }else if(session == "Support"){
        openTab( 'Support');
    }else if(session == "Identity_provider"){
        openTab( 'Identity_Provider');
    }else if(session == "Attribute_Mapping") {
        openTab( 'Attribute_Mapping');
    }else if(session == "Group_Mapping") {
        openTab( 'Group_Mapping');
    }else if(session == "Premium") {
        openTab( 'Premium');
    }else{
        openTab( 'Account');
    }
});

//when tabs are clicked
function openTab(activeTab) {

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

    // if(activeTab==="Support"){
    //     document.getElementById("Support_Tab").className  += " active";
    //     document.getElementById("leftContainer").hidden = true;
    // }else{
    //     document.getElementById(activeTab).style.display = "block";
     document.getElementById(activeTab + "_Tab").className  += " active";
        // document.getElementById("leftContainer").hidden = false;
    // }
}

//remove flash messages
function removeFlashMessage(){
    document.querySelectorAll('.typo3-messages').forEach(function(a){
        a.remove();
    });
}

//is user registered
 function ifUserRegistered(){
    
    var checkBox=document.getElementById('registered');
    var login=document.getElementById('confirmPasswordDiv');
    if (checkBox.checked == true){
        login.style.display = "none";
        
      } else {
         login.style.display = "block";
         
      }
 }


function addCustomAttribute(){
    // console.log("Appending a Custom Attribute.");

    var val = $("#this_attribute").val();

    if(val.length>0){
        if($("#custom_attrs_form").has($("#"+val)).length){
            console.log("Element exists with this id");
        }else{
            var div = generateAttributeDiv($("#this_attribute").val())
            $("#submit_custom_attr").before(div);
            $("#this_attribute").val("");
        }
    }else{
        console.log("Enter a valid name.");
    }

}

function generateAttributeDiv(name){
    // var txt1 = "<p>Text.</p>";               // Create element with HTML
    // var txt2 = $("<p></p>").text("Text.");   // Create with jQuery
    // var txt3 = document.createElement("p");  // Create with DOM

    // console.log("Genrating Custom Attribute: "+name);

    var attributeDiv =  $("<div>",{'class':'gm-div','id':name+'Div'});
    var labelForAttr = $("<label>",{'for':name,'class':'form-control gm-input-label'}).text(name);
    var inputAttr = $("<input>",{'id':name,'name':name,'class':'form-control gm-input','type':'text', 'placeholder':'Enter name of IDP attribute'});

    attributeDiv.append(labelForAttr);
    attributeDiv.append(inputAttr);

    return attributeDiv;

}

function deleteCustomAttribute(){
    // console.log("Removing Attribute: ");
    var val = $("#this_attribute").val();

    if(val.length>1){
        console.log("Deleting mapping for "+val);
        $('#'+val+'Div').remove();
        $("#this_attribute").val("");}
}
