Ext.define('Shopware.apps.SImporter', {
    extend: 'Enlight.app.SubApplication',

    name: 'Shopware.apps.SImporter',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'list.Window',
    ],

    models: [ 'Order' ],
    stores: [ 'Order' ],

    launch: function () {
        return this.getController('Main').mainWindow;
    }
});

