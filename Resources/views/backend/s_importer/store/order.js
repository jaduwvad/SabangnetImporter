Ext.define('Shopware.apps.SImporter.store.Order', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'SImporter'
        };
    },
    model: 'Shopware.apps.SImporter.model.Order'
});

