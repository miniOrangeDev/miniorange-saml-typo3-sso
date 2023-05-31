
//when clicking extension name on left panel
$(document).ready(function() {
    let session = document.getElementById('taby').value;

    console.log("Session : in document ready() = "+session);

    if (session == "Service_Provider"){
        openTab(event,'Service_Provider');
    }else if(session == "Identity_Provider"){
        openTab(event,'Identity_Provider');
    }else if(session == "Attribute_Mapping") {
        openTab( event,'Attribute_Mapping');
    }else if(session == "Group_Mapping") {
        openTab( event,'Group_Mapping');       
    }else if(session == "Premium") {
        openTab( event,'Premium');
    }

    if(session == "" || session == "Account" || session==null)
        {
            openTab(event, 'Account');
            $("#account_tab_btn").addClass("active");
            session = "Account";
        }

        if(document.getElementsByClassName("active").length === 0){
            document.getElementById(session).className  += " active";
        }
        console.log("Session in end : ".session);
        document.getElementById(session).className  += " active";

        $("input[name='grant_type']").click(function() {
            $("input[name='grant_type']").not(this).prop('checked', false);
        });
});

//when tabs are clicked
function openTab(evt, activeTab) {
    document.getElementById("leftContainer").classList.add("showElement");
    document.getElementById("leftContainer").classList.replace("hideElement","showElement");

    let tabcontent = document.getElementsByClassName("tabcontent");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    let tablinks = document.getElementsByClassName("tablinks");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    if(activeTab!=="Support"){
        if(activeTab=="Premium")
        {
            document.getElementById(activeTab).style.display = "block";
        }
        else
        {
            document.getElementById(activeTab).style.display = "block";
            document.getElementById("Support").style.display = "block";
            document.getElementById(activeTab+"_Tab").classList.add("active");
        }
    }else{
        document.getElementById(activeTab).style.display = "block";
        document.getElementById("leftContainer").classList.replace("showElement","hideElement");
    }
   // evt.currentTarget.className += " active";
}




//remove flash messages
function removeFlashMessage(){
    document.querySelectorAll('.typo3-messages').forEach(function(a){
        a.remove();
    });
}



function addCustomAttribute(){

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

    var attributeDiv =  $("<div>",{'class':'gm-div','id':name+'Div'});
    var labelForAttr = $("<label>",{'for':name,'class':'form-control gm-input-label'}).text(name);
    var inputAttr = $("<input>",{'id':name,'name':name,'class':'form-control gm-input','type':'text', 'placeholder':'Enter name of IDP attribute'});

    attributeDiv.append(labelForAttr);
    attributeDiv.append(inputAttr);

    return attributeDiv;

}

function deleteCustomAttribute(){
    var val = $("#this_attribute").val();

    if(val.length>1){
        console.log("Deleting mapping for "+val);
        $('#'+val+'Div').remove();
        $("#this_attribute").val("");}
}

function set_acs()
{
    document.getElementById("acs_url").value = document.getElementById("response").value;
}

function set_entityid()
{
    document.getElementById("sp_entity_id").value = document.getElementById("site_base_url").value;
}

function outFunc() 
{
    var tooltip = document.getElementById("myTooltip");
    tooltip.innerHTML = "Copy to clipboard";
}

function uploadMetadata()
{
    document.getElementById("upload_metadata_form").style.display = "block";
    jQuery('#saml_form').css('opacity', '0.6');
}

function uploadFile()
{
    jQuery('#upload_metadata_form').submit();
    document.getElementById("upload_metadata_form").style.display = "none";
    document.getElementById("saml_form").style.display = "block";
    jQuery('#saml_form').css('opacity', '100');
}
        
function cancel()
{
    document.getElementById("saml_form").style.display = "block";
    document.getElementById("upload_metadata_form").style.display = "none";
    jQuery('#saml_form').css('opacity', '100');
}

function setMetadata()
{
    jQuery('#metadata_download').attr('value','mosaml_metadata');
    jQuery('#sp_settings').submit();
}

function set_value()
{
    jQuery('#metadata_download').attr('value','mosaml_metadata_download');
    jQuery('#sp_settings').submit();
}

function save_sp_data()
{
    jQuery('#metadata_download').attr('value','save_sp_settings');
    jQuery('#sp_settings').submit();
}

function copyURL() 
{
    var copyText = document.getElementById("metadata_url");
    navigator.clipboard.writeText(copyText);
    var tooltip = document.getElementById("myTooltip");
    tooltip.innerHTML = "URL Copied: ";
} 

function ifUserRegistered()
{
             
    var checkBox=document.getElementById('registered');
    var login=document.getElementById('confirmPasswordDiv');
    if (checkBox.checked == true)
    {
    login.style.display = "none";
    }
    else 
    {
    login.style.display = "block";
    }
            
}