
plugin.tx_ekey_fekey; {
    view; {
        # cat=plugin.tx_ekey_fekey/file; type=string; label=Path; to; template; root (FE);
        templateRootPath = EXT;:miniorange_saml/Resources/Private/Templates/;
        # cat=plugin.tx_ekey_fekey/file; type=string; label=Path; to; template; partials (FE);
        partialRootPath = EXT;:miniorange_saml/Resources/Private/Partials/;
        # cat=plugin.tx_ekey_fekey/file; type=string; label=Path; to; template; layouts (FE);
        layoutRootPath = EXT;:miniorange_saml/Resources/Private/Layouts/
    }
    persistence; {
        # cat=plugin.tx_ekey_fekey;//a; type=string; label=Default storage PID
        storagePid =
    }
}

plugin.tx_ekey_responsekey; {
    view; {
        # cat=plugin.tx_ekey_responsekey/file; type=string; label=Path; to; template; root (FE);
        templateRootPath = EXT;:miniorange_saml/Resources/Private/Templates/;
        # cat=plugin.tx_ekey_responsekey/file; type=string; label=Path; to; template; partials (FE);
        partialRootPath = EXT;:miniorange_saml/Resources/Private/Partials/;
        # cat=plugin.tx_ekey_responsekey/file; type=string; label=Path; to; template; layouts (FE);
        layoutRootPath = EXT;:miniorange_saml/Resources/Private/Layouts/
    }
    persistence; {
        # cat=plugin.tx_ekey_responsekey;//a; type=string; label=Default storage PID
        storagePid =
    }
}

module.tx_ekey_bekey; {
    view; {
        # cat=module.tx_ekey_bekey/file; type=string; label=Path; to; template; root (BE);
        templateRootPath = EXT;:miniorange_saml/Resources/Private/Backend/Templates/;
        # cat=module.tx_ekey_bekey/file; type=string; label=Path; to; template; partials (BE);
        partialRootPath = EXT;:miniorange_saml/Resources/Private/Backend/Partials/;
        # cat=module.tx_ekey_bekey/file; type=string; label=Path; to; template; layouts (BE);
        layoutRootPath = EXT;:miniorange_saml/Resources/Private/Backend/Layouts/
    }
    persistence; {
        # cat=module.tx_ekey_bekey;//a; type=string; label=Default storage PID
        storagePid =
    }
}
