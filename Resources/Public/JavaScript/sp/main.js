document.addEventListener('DOMContentLoaded', function () {
    const uploadMetadataBtn = document.getElementById("upload_metadata");
    const submitUploadBtn = document.getElementById("submit_upload_metadata");
    const cancelBtn = document.getElementById("cancel");
    const submitSamlBtn = document.getElementById("submit_saml_form");
    const testConfigBtn = document.getElementById("test_config");

    if (uploadMetadataBtn) {
        uploadMetadataBtn.addEventListener('click', function () {
            const uploadForm = document.getElementById("upload_metadata_form");
            if (uploadForm) {
                uploadForm.style.display = "block";
            }
            jQuery('#saml_form').css('opacity', '0.6');
            jQuery('#support_form').css('opacity', '0.6');
        });
    }

    if (submitUploadBtn) {
        submitUploadBtn.addEventListener('click', function () {
            jQuery('#upload_metadata_form').submit();
            document.getElementById("upload_metadata_form").style.display = "none";
            document.getElementById("saml_form").style.display = "block";
            jQuery('#saml_form').css('opacity', '100');
            jQuery('#support_form').css('opacity', '100');
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            document.getElementById("saml_form").style.display = "block";
            document.getElementById("upload_metadata_form").style.display = "none";
            jQuery('#saml_form').css('opacity', '100');
            jQuery('#support_form').css('opacity', '100');
        });
    }

    if (submitSamlBtn) {
        submitSamlBtn.addEventListener('click', function () {
            jQuery('#saml_form').submit();
        });
    }

    if (testConfigBtn) {
        testConfigBtn.addEventListener('click', function () {
            const url = document.getElementById("test_config")?.getAttribute("data-test-url") || '';
            if (url) {
                window.open(url + '?RelayState=testconfig');
            }
        });
    }
});

$(document).ready(function () {
    let sessionEl = document.getElementById('taby');
    let session = sessionEl ? sessionEl.value : "";

    switch (session) {
        case "Service_Provider":
        case "Identity_Provider":
        case "Support":
        case "Attribute_Mapping":
        case "Group_Mapping":
            openTab(session);
            break;
        default:
            openTab('Service_Provider');
    }
    document.getElementById("Account_Tab")?.addEventListener("click", function () {
        openTab('Account');
    });
    document.getElementById("Service_Provider_Tab")?.addEventListener("click", function () {
        openTab('Service_Provider');
    });
    document.getElementById("Identity_Provider_Tab")?.addEventListener("click", function () {
        openTab('Identity_Provider');
    });
    document.getElementById("Attribute_Mapping_Tab")?.addEventListener("click", function () {
        openTab('Attribute_Mapping');
    });
    document.getElementById("Group_Mapping_Tab")?.addEventListener("click", function () {
        openTab('Group_Mapping');
    });
    document.getElementById("Premium_Tab")?.addEventListener("click", function () {
        openTab('Premium');
    });
});

function openTab(activeTab) {
    const leftContainer = document.getElementById("leftContainer");
    if (leftContainer) {
        leftContainer.classList.add("showElement");
        leftContainer.classList.remove("hideElement");
    }

    let tabcontent = document.getElementsByClassName("tabcontent");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        console.log(tabcontent[i]);
    }
    

    let tablinks = document.getElementsByClassName("tablinks");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    if (activeTab === "Support") {
        let account = document.getElementById("Account");
        let support = document.getElementById("Support");
        let accountTab = document.getElementById("Account_Tab");

        if (account) account.style.display = "block";
        if (support) support.style.display = "block";
        if (accountTab) accountTab.classList.add("active");

    } else {
        let mainTab = document.getElementById(activeTab);
        let support = document.getElementById("Support");
        let tab = document.getElementById(activeTab + "_Tab");

        if (mainTab) mainTab.style.display = "block";
        if (support) support.style.display = "block";
        if (tab) tab.classList.add("active");
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
            var div = generateAttributeDiv(val);
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

var amSelect = document.getElementById("am_selectedProvider");
if (amSelect) {
    amSelect.addEventListener("change", function () {
        document.getElementById("am_save_provider_form").submit();
    });
}

var gmSelect = document.getElementById("gm_selectedProvider");
if (gmSelect) {
    gmSelect.addEventListener("change", function () {
        document.getElementById("gm_save_provider_form").submit();
    });
}

document.addEventListener("DOMContentLoaded", function () {
    // Register checkbox toggle logic
    const registeredCheckbox = document.getElementById('registered');
    const confirmPasswordDiv = document.getElementById('confirmPasswordDiv');

    if (registeredCheckbox && confirmPasswordDiv) {
        registeredCheckbox.addEventListener('change', function () {
            confirmPasswordDiv.style.display = this.checked ? 'none' : 'block';
        });
    }
});



document.addEventListener('DOMContentLoaded', function () {
    // Set ACS URL
    document.getElementById('acs_url')?.addEventListener('click', function () {
        const response = document.getElementById('response');
        if (response) {
            this.value = response.value;
        }
    });

    // Set Entity ID
    document.getElementById('sp_entity_id')?.addEventListener('click', function () {
        const siteBaseUrl = document.getElementById('site_base_url');
        if (siteBaseUrl) {
            this.value = siteBaseUrl.value;
        }
    });

    // Download SP Certificate
    document.getElementById('download_sp_certificate')?.addEventListener('click', function () {
        const metadataDownload = document.getElementById('metadata_download');
        if (metadataDownload) {
            metadataDownload.value = 'mosaml_sp_cert_download';
            document.getElementById('sp_settings')?.submit();
        }
    });

    // Save SP Settings
    document.getElementById('sp_setting_form')?.addEventListener('click', function () {
        const metadataDownload = document.getElementById('metadata_download');
        if (metadataDownload) {
            metadataDownload.value = 'save_sp_settings';
            document.getElementById('sp_settings')?.submit();
        }
    });

    // Copy Metadata URL
    document.getElementById('copy')?.addEventListener('click', function () {
        const copyText = document.getElementById('metadata_url');
        if (copyText) {
            navigator.clipboard.writeText(copyText.href).then(() => {
                const tooltip = document.getElementById('myTooltip');
                if (tooltip) tooltip.innerHTML = 'URL Copied';
            });
        }
    });

    document.getElementById('copy')?.addEventListener('mouseout', function () {
        const tooltip = document.getElementById('myTooltip');
        if (tooltip) tooltip.innerHTML = 'Copy to clipboard';
    });

    // Download Metadata XML
    document.getElementById('download_metadata')?.addEventListener('click', function () {
        const metadataDownload = document.getElementById('metadata_download');
        if (metadataDownload) {
            metadataDownload.value = 'mosaml_metadata_download';
            document.getElementById('sp_settings')?.submit();
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const testConfigButton = document.getElementById("test_config");

    if (testConfigButton) {
        testConfigButton.addEventListener("click", function () {
            const fesamlUrl = document.getElementById("fesaml").value;
            if (fesamlUrl) {
                window.open(fesamlUrl + '?RelayState=testconfig');
            } else {
                alert("Please enter the Fesaml plugin page URL before testing.");
            }
        });
    }
});

