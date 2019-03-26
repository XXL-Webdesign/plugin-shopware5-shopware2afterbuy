Ext.define('Shopware.apps.viaebConfigForm.view.ConfigWindow', {
    extend: 'Enlight.app.Window',

    id: 'config_window',

    snippets: {
        config_window_title: '{s namespace="backend/viaebConfigForm" name="config_window_title"}Konfiguration{/s}',
        error: '{s namespace="backend/afterbuy" name="error"}Fehler{/s}',
        getConfigValuesError: '{s namespace="backend/afterbuy" name="getConfigValuesError"}Konfigurationsdaten konnten nicht gelesen werden!{/s}',
        config_connection_title: '{s namespace="backend/viaebConfigForm" name="config_connection_title"}Verbindung{/s}',
        connection_settings: '{s namespace="backend/viaebConfigForm" name=connection_settings}Verbindungsdaten{/s}',
        config_general_title: '{s namespace="backend/viaebConfigForm" name="config_general_title"}Allg. Einstellungen{/s}',
        general_settings: '{s namespace="backend/viaebConfigForm" name=general_settings}Einstellungen{/s}',
        config_payment_mapping_title: '{s namespace="backend/viaebConfigForm" name="config_payment_mapping_title"}Zahlungsarten Zuordnungen{/s}',
        payment_mapping: '{s namespace="backend/viaebConfigForm" name=payment_mapping}Zahlungsarten{/s}',
        yes: '{s namespace="backend/viaebConfigForm" name=yes}ja{/s}',
        no: '{s namespace="backend/viaebConfigForm" name=no}nein{/s}',
        shopware: '{s namespace="backend/viaebConfigForm" name=shopware}Shopware{/s}',
        afterbuy: '{s namespace="backend/viaebConfigForm" name=afterbuy}Afterbuy{/s}',
    },

    height: 600,
    width: 800,
    border: true,
    layout: 'fit',
    autoShow: true,

    /**
     * The body padding is used in order to have a smooth side clearance.
     * @integer
     */
    bodyPadding: 1,

    /**
     * Disable window resize
     * @boolean
     */
    resizable: false,

    /**
     * Disables the maximize button in the window header
     * @boolean
     */
    maximizable: false,
    /**
     * Disables the minimize button in the window header
     * @boolean
     */
    minimizable: true,

    initComponent: function () {
        const me = this;
        me.getConfigValues();

        me.registerEvents();

        me.title = me.snippets.config_window_title;

        me.form = me.createForm();

        me.items = me.form;

        me.callParent(arguments);
    },

    getConfigValues: function () {
        const me = this;

        Ext.Ajax.request({
            url: '{url controller="viaebConfigForm" action="getConfigValues"}',
            method: 'POST',
            timeout: 180000,
            scope: me,
            success: function (resp) {
                const me = this;

                me.configValues = JSON.parse(resp.responseText).data;

                me.configCollection.each(function (form) {
                    const me = this;

                    form.getForm().getFields().each(me.resetFieldValues, me);
                }, me);
            },
            failure: function () {
                Shopware.Notification.createGrowlMessage(
                    me.snippets.error,
                    me.snippets.getConfigValuesError,
                    'Afterbuy Conncetor'
                );
            },
        });
    },

    resetFieldValues: function (item) {
        const me = this;

        if (item.name in me.configValues) {
            item.setValue(me.configValues[item.name]);
        }
    },

    registerEvents: function () {
        this.addEvents(
            'saveAfterbuyConfig'
        );
    },

    createForm: function () {
        const me = this;

        me.tabPanel = me.createTabPanel();

        return Ext.create('Ext.form.Panel', {
            url: '{url controller="viaebConfigForm" action="saveConnectionConfig"}',
            layout: 'fit',
            items: me.tabPanel,
            buttons: [
                me.createTestButton(),
                me.createSubmitButton(),
            ],
        });
    },

    setActiveTab: function (index) {
        this.tabPanel.setActiveTab(index);
    },

    createTabPanel: function () {
        const me = this;

        me.configCollection = new Ext.util.MixedCollection();
        me.configCollection.add(me.createConnectionConfigPanel());
        me.configCollection.add(me.createGeneralConfigPanel());
        me.configCollection.add(me.createPaymentMappingConfigPanel());

        return Ext.create('Ext.tab.Panel', {
            layout: 'fit',
            defaults: {
                overflowY: 'scroll',
                htmlEncode: true,
                bodyPadding: 10,
            },

            items: me.configCollection.getRange(),
        });
    },

    createConnectionConfigPanel: function () {
        const me = this;

        return Ext.create('Ext.form.Panel', {
            title: me.snippets.config_connection_title,
            items: [
                Ext.create('Shopware.apps.viaebConfigForm.view.ColumnFieldSet', {
                    title: me.snippets.connection_settings,
                    childDefaults: {
                        xtype: 'textfield',
                        forceSelection: true,
                        allowBlank: false,
                    },
                    items: me.createConnectionConfigFields(),
                }),
            ],
        });
    },

    createGeneralConfigPanel: function () {
        const me = this;

        return Ext.create('Ext.form.Panel', {
            title: me.snippets.config_general_title,
            items: [
                Ext.create('Shopware.apps.viaebConfigForm.view.ColumnFieldSet', {
                    title: me.snippets.general_settings,
                    childDefaults: {
                        xtype: 'combobox',
                        forceSelection: true,
                        allowBlank: false,
                        displayField: 'name',
                        valueField: 'id',
                    },
                    items: me.createGeneralConfigFields(),
                }),
            ],
        });
    },

    createPaymentMappingConfigPanel: function () {
        const me = this;
        const fields = me.createPaymentMappingConfigFields();

        return Ext.create('Ext.form.Panel', {
            title: me.snippets.config_payment_mapping_title,
            items: [
                Ext.create('Shopware.apps.viaebConfigForm.view.ColumnFieldSet', {
                    title: me.snippets.payment_mapping,
                    items: fields,
                    childDefaults: {
                        xtype: 'combo',
                        forceSelection: true,
                        allowBlank: false,
                        displayField: 'description',
                        valueField: 'id',
                        store: me.createRemoteStore(Shopware.apps.Base.store.Payment),
                    },
                }),
            ],
        });
    },

    createSystemsStore: function () {
        const me = this;

        return Ext.create('Ext.data.Store', {
            fields: [
                'value',
                'display',
            ],
            data: [
                {
                    'value': 1,
                    'display': me.snippets.shopware,
                },
                {
                    'value': 2,
                    'display': me.snippets.afterbuy,
                },
            ]
        });
    },

    createYesNoStore: function () {
        const me = this;

        return Ext.create('Ext.data.Store', {
            fields: [
                'value',
                'display',
            ],
            data: [
                {
                    'value': 1,
                    'display': me.snippets.yes,
                },
                {
                    'value': 0,
                    'display': me.snippets.no,
                },
            ]
        });
    },

    createRemoteStore: function (storeCls) {
        const store = Ext.create(storeCls);

        store.load();

        return store;
    },

    createConnectionConfigFields: function () {
        return [
            {
                xtype: 'textfield',
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=label_user}Afterbuy User{/s}',
                name: 'userName',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=label_userpw}User Password{/s}',
                name: 'userPassword',
                inputType: 'password',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=label_partnerid}Partner ID:{/s}',
                name: 'partnerId',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=label_partnerpw}Partner Pw:{/s}',
                name: 'partnerPassword',
                inputType: 'password',
            },
        ];
    },

    createGeneralConfigFields: function () {
        const me = this;

        return [
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=mainSystem}Datenführendes System{/s}',
                store: me.createSystemsStore(),
                queryMode: 'local',
                displayField: 'display',
                valueField: 'value',
                name: 'mainSystem',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=baseCategory}Stammkategorie{/s}',
                store: me.createRemoteStore(Shopware.apps.Base.store.Category),
                name: 'baseCategory',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=ExportAllArticles}Alle Artikel exportieren{/s}',
                store: me.createYesNoStore(),
                queryMode: 'local',
                displayField: 'display',
                valueField: 'value',
                name: 'ExportAllArticles',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=targetShop}Zielshop für Bestellungen{/s}',
                store: me.createRemoteStore(Shopware.apps.Base.store.Shop),
                name: 'targetShop',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=shipping}Versandart{/s}',
                store: me.createRemoteStore(Shopware.apps.Base.store.Dispatch),
                name: 'shipping',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=customerGroup}Kundengruppe{/s}',
                store: me.createRemoteStore(Shopware.apps.Base.store.CustomerGroup),
                name: 'customerGroup',
            },
        ];
    },

    createPaymentMappingConfigFields: function () {
        return [
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentINVOICE}Zahlart RECHNUNG{/s}',
                name: 'paymentINVOICE',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentCREDIT_CARD}Zahlart KREDIT KARTE{/s}',
                name: 'paymentCREDIT_CARD',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentDIRECT_DEBIT}Zahlart LASTSCHRIFT{/s}',
                name: 'paymentDIRECT_DEBIT',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentTRANSFER}Zahlart ÜBERWEISUNG{/s}',
                name: 'paymentTRANSFER',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentCASH_PAID}Zahlart BARZAHLUNG{/s}',
                name: 'paymentCASH_PAID',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentCASH_ON_DELIVERY}Zahlart NACHNAME{/s}',
                name: 'paymentCASH_ON_DELIVERY',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentPAYPAL}Zahlart PAYPAL{/s}',
                name: 'paymentPAYPAL',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentINVOICE_TRANSFER}Zahlart RECHNUNG ÜBERWEISUNG{/s}',
                name: 'paymentINVOICE_TRANSFER',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentCLICKANDBUY}Zahlart CLICK AND BUY{/s}',
                name: 'paymentCLICKANDBUY',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentEXPRESS_CREDITWORTHINESS}Zahlart ???{/s}',
                name: 'paymentEXPRESS_CREDITWORTHINESS',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentPAYNET}Zahlart PAYNET{/s}',
                name: 'paymentPAYNET',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentCOD_CREDITWORTHINESS}Zahlart ???{/s}',
                name: 'paymentCOD_CREDITWORTHINESS',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentEBAY_EXPRESS}Zahlart EBAY EXPRESS{/s}',
                name: 'paymentEBAY_EXPRESS',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentMONEYBOOKERS}Zahlart MONEYBOOKERS{/s}',
                name: 'paymentMONEYBOOKERS',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentCREDIT_CARD_MB}Zahlart KREDIT KARTE MONEYBOOKERS{/s}',
                name: 'paymentCREDIT_CARD_MB',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentDIRECT_DEBIT_MB}Zahlart LASTSCHRIFT MONEYBOOKERS{/s}',
                name: 'paymentDIRECT_DEBIT_MB',
            },
            {
                fieldLabel: '{s namespace="backend/viaebConfigForm" name=paymentOTHERS}Zahlart ANDERE{/s}',
                name: 'paymentOTHERS',
            },
        ];
    },

    createSubmitButton: function () {
        const me = this;

        return {
            text: '{s namespace="backend/viaebConfigForm" name=saveButton}Speichern{/s}',
            cls: 'button primary',
            handler: function () {
                me.fireEvent('saveAfterbuyConfig', me.form);
            },
        };
    },

    createTestButton: function () {
        const me = this;

        return {
            text: '{s namespace="backend/viaebConfigForm" name=testButton}Verbindungstest{/s}',
            cls: 'button secondary',
            handler: function () {
                me.fireEvent('testAfterbuyConfig', me.form);
            },
        };
    },
});
