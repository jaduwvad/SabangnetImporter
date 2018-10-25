Ext.define('Shopware.apps.SImporter.view.list.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.order-list-window',
    width: 500,
    height: 150,

    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },

    /**
 *      * Contains all snippets for the view component
 *           * @object
 *                */
    snippets:{
        title : '{s name=title}Sabangnet Import{/s}',
        titleImport: '{s name=title_import}Import configuration{/s}',
        choose: '{s name=choose_file_empty_text}Choose{/s}',
        chooseButton: '{s name=choose_button}Choose{/s}',
        file: '{s name=file}File{/s}',
        start: '{s name=start_import}Import{/s}'
    },

    /**
 *      * Initializes the component and builds up the main interface
 *           *
 *                * @return void
 *                     */
    initComponent: function() {
        var me = this;

        me.title = me.snippets.title;

        me.items = [
            me.getImportForm()
        ];

        me.callParent(arguments);
    },

    getImportForm: function() {
        var me = this;

        var toolbar = Ext.create('Ext.toolbar.Toolbar', {
            dock: 'bottom',
            cls: 'shopware-toolbar',
            items: [ '->',
                {
                    text: me.snippets.start,
                    cls: 'primary',
                    formBind: true,
                    handler: function () {
                        var form = this.up('form').getForm();
                        if (!form.isValid()) {
                            return;
                        }
                        form.submit({
                            url: ' {url controller=SImporter action=import}',
                            waitMsg: me.snippets.uploading,
                            success: function (fp, o) {
                                Ext.MessageBox.alert('Result', '<p>' + o.result.message + '</p>');
                            },
                            failure: function (fp, o) {
                                Ext.Msg.alert('Fehler', o.result.message);
                            }
                        });
                    }
                }
            ]
        });

        return Ext.create('Ext.form.Panel', {
            xtype: 'form',
            title: me.snippets.titleImport,
            bodyPadding: 5,
            layout: 'anchor',
            dockedItems: toolbar,
            defaults: {
                anchor: '100%',
                labelWidth: 200
            },
            items: [
                {
                    xtype: 'filefield',
                    emptyText: me.snippets.choose,
                    buttonText:  me.snippets.chooseButton,
                    name: 'file',
                    fieldLabel: me.snippets.file,
                    allowBlank: false
                }
            ]
        });
    }
});

