
plugin.tx_miniorangesaml_fekey {
    view; {
        templateRootPaths;.0 = EXT:miniorange_saml/Resources/Private/Templates/
        templateRootPaths.1 = {$plugin.tx_miniorangesaml_fekey.view.templateRootPath}
        partialRootPaths.0 = EXT:miniorange_saml/Resources/Private/Partials/
        partialRootPaths.1 = {$plugin.tx_miniorangesaml_fekey.view.partialRootPath}
        layoutRootPaths.0 = EXT:miniorange_saml/Resources/Private/Layouts/
        layoutRootPaths.1 = {$plugin.tx_miniorangesaml_fekey.view.layoutRootPath}
    }
    persistence {
        storagePid = {$plugin.tx_miniorangesaml_fekey.persistence.storagePid}
        #recursive = 1
    }
    features {
        #skipDefaultArguments = 1
        # if set to 1, the enable fields are ignored in BE context
        ignoreAllEnableFieldsInBe = 0
        # Should be on by default, but can be disabled if all action in the plugin are uncached
        requireCHashArgumentForActionArguments = 1
    }
    mvc {
        #callDefaultActionIfActionCantBeResolved = 1
    }
}

plugin.tx_miniorangesaml_responsekey {
    view {
        templateRootPaths.0 = EXT:miniorange_saml/Resources/Private/Templates/
        templateRootPaths.1 = {$plugin.tx_miniorangesaml_responsekey.view.templateRootPath}
        partialRootPaths.0 = EXT:miniorange_saml/Resources/Private/Partials/
        partialRootPaths.1 = {$plugin.tx_miniorangesaml_responsekey.view.partialRootPath}
        layoutRootPaths.0 = EXT:miniorange_saml/Resources/Private/Layouts/
        layoutRootPaths.1 = {$plugin.tx_miniorangesaml_responsekey.view.layoutRootPath}
    }
    persistence {
        storagePid = {$plugin.tx_miniorangesaml_responsekey.persistence.storagePid}
        #recursive = 1
    }
    features {
        #skipDefaultArguments = 1
        # if set to 1, the enable fields are ignored in BE context
        ignoreAllEnableFieldsInBe = 0
        # Should be on by default, but can be disabled if all action in the plugin are uncached
        requireCHashArgumentForActionArguments = 1
    }
    mvc {
        #callDefaultActionIfActionCantBeResolved = 1
    }
}

# these classes are only used in auto-generated templates
plugin.tx_miniorangesaml._CSS_DEFAULT_STYLE (
    textarea.f3-form-error {
        background-color:#FF9F9F;
        border: 1px #FF0000 solid;
    }

    input.f3-form-error {
        background-color:#FF9F9F;
        border: 1px #FF0000 solid;
    }

    .tx-miniorange-saml table {
        border-collapse:separate;
        border-spacing:10px;
    }

    .tx-miniorange-saml table th {
        font-weight:bold;
    }

    .tx-miniorange-saml table td {
        vertical-align:top;
    }

    .typo3-messages .message-error {
        color:red;
    }

    .typo3-messages .message-ok {
        color:green;
    }
)

# Module configuration
module.tx_miniorangesaml_tools_miniorangesamlbekey {
    persistence {
        storagePid = {$module.tx_miniorangesaml_bekey.persistence.storagePid}
    }
    view {
        templateRootPaths.0 = EXT:miniorange_saml/Resources/Private/Backend/Templates/
        templateRootPaths.1 = {$module.tx_miniorangesaml_bekey.view.templateRootPath}
        partialRootPaths.0 = EXT:miniorange_saml/Resources/Private/Backend/Partials/
        partialRootPaths.1 = {$module.tx_miniorangesaml_bekey.view.partialRootPath}
        layoutRootPaths.0 = EXT:miniorange_saml/Resources/Private/Backend/Layouts/
        layoutRootPaths.1 = {$module.tx_miniorangesaml_bekey.view.layoutRootPath}
    }
}
