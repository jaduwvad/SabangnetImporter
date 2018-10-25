Ext.define('Shopware.apps.SImporter.model.Order', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'SImporter'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'trackingNumber', type: 'string' },
        { name : 'postcode', type: 'string' },
        { name : 'item', type: 'int' },
        { name : 'order', type: 'int' },
        { name : 'company', type: 'string' },
        { name : 'createdAt', type: 'datetime' }
    ]
});
