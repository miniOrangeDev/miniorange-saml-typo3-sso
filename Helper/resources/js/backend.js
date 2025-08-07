function set_value() {
    jQuery('#metadata_download').attr('value', 'mosaml_metadata_download');
    jQuery('#sp_settings').submit();
}

function save_sp_data() {
    jQuery('#metadata_download').attr('value', 'save_sp_settings');
    jQuery('#sp_settings').submit();
}

function set_acs() {
    document.getElementById("acs_url").value = document.getElementById("response").value;
}

function set_entityid() {
    document.getElementById("sp_entity_id").value = document.getElementById("site_base_url").value;
}

function copyURL() {
    var copyText = document.getElementById("metadata_url");

    navigator.clipboard.writeText(copyText);

    var tooltip = document.getElementById("myTooltip");
    tooltip.innerHTML = "URL Copied: ";
}

function outFunc() {
    var tooltip = document.getElementById("myTooltip");
    tooltip.innerHTML = "Copy to clipboard";
}

function uploadMetadata() {
    document.getElementById("upload_metadata_form").style.display = "block";
    jQuery('#saml_form').css('opacity', '0.6');
    jQuery('#support_form').css('opacity', '0.6');
}

function uploadFile() {
    jQuery('#upload_metadata_form').submit();
    document.getElementById("upload_metadata_form").style.display = "none";
    document.getElementById("saml_form").style.display = "block";
    jQuery('#saml_form').css('opacity', '100');
    jQuery('#support_form').css('opacity', '100');
}

function cancel() {
    document.getElementById("saml_form").style.display = "block";
    document.getElementById("upload_metadata_form").style.display = "none";
    jQuery('#saml_form').css('opacity', '100');
    jQuery('#support_form').css('opacity', '100');
}

$(document).ready(function () {
    let session = document.getElementById('taby').value;
    if (session == "Service_Provider") {
        openTab('Service_Provider');
    } else if (session == "Identity_Provider") {
        openTab('Identity_Provider');
    } else if (session == "Support") {
        openTab('Support');
    } else if (session == "Attribute_Mapping") {
        openTab('Attribute_Mapping');
    } else if (session == "Group_Mapping") {
        openTab('Group_Mapping');
    } else {
        openTab('Service_Provider');
    }

});

function openTab(activeTab) {

    alert("in resources1");

    document.getElementById("leftContainer").classList.add("showElement");
    document.getElementById("leftContainer").classList.remove("hideElement");

    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");

    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    if (activeTab !== "Support") {
        document.getElementById(activeTab).style.display = "block";
        document.getElementById("Support").style.display = "block";
        document.getElementById(activeTab + "_Tab").classList.add("active");
    } else {
        document.getElementById(activeTab).style.display = "block";
        document.getElementById("leftContainer").classList.replace("showElement", "hideElement");
        document.getElementById("Support_Tab").classList.add("active");
    }
}

function removeFlashMessage() {
    document.querySelectorAll('.typo3-messages').forEach(function (a) {
        a.remove();
        console.log("remove typo3 messages.");
    });
}

function addCustomAttribute() {

    var val = $("#this_attribute").val();

    if (val.length > 0) {
        if ($("#custom_attrs_form").has($("#" + val)).length) {
            console.log("Element exists with this id");
        } else {
            var div = generateAttributeDiv($("#this_attribute").val())
            $("#submit_custom_attr").before(div);
            $("#this_attribute").val("");
        }
    } else {
        console.log("Enter a valid name.");
    }

}

function generateAttributeDiv(name) {

    var attributeDiv = $("<div>", {'class': 'gm-div', 'id': name + 'Div'});
    var labelForAttr = $("<label>", {'for': name, 'class': 'form-control gm-input-label'}).text(name);
    var inputAttr = $("<input>", {
        'id': name,
        'name': name,
        'class': 'form-control gm-input',
        'type': 'text',
        'placeholder': 'Enter name of IDP attribute'
    });

    attributeDiv.append(labelForAttr);
    attributeDiv.append(inputAttr);

    return attributeDiv;

}

function deleteCustomAttribute() {
    var val = $("#this_attribute").val();

    if (val.length > 1) {
        console.log("Deleting mapping for " + val);
        $('#' + val + 'Div').remove();
        $("#this_attribute").val("");
    }
}

document.getElementById("am_selectedProvider").addEventListener("change", function () {
    document.getElementById("am_save_provider_form").submit(); // Submit the form
});

document.getElementById("gm_selectedProvider").addEventListener("change", function () {
    document.getElementById("gm_save_provider_form").submit(); // Submit the form
});