go.modules.tutorial.gtd.ChooseThoughtlistDialog = Ext.extend(go.Window, {
	title: t("Choose a thoughtlist"),
    entityStore: "Thought",
    layout: 'form',
  width: dp(800),
  height: dp(800),
    modal: true,

	initComponent: function () {
        this.chooseThoughtlistGrid = new go.modules.tutorial.gtd.ChooseThoughtlistGrid({
            height: dp(640),
            tbar: ['->', {
                xtype: 'tbsearch'
            }]
        });

        this.thoughtListFromCsvCB = new Ext.form.Checkbox({
            xtype: 'xcheckbox',
            boxLabel: t('Import list ID from CSV file'),
            handler: (cb,checked) => {
                const el = this.chooseThoughtlistGrid.getEl();

                if(checked) {
                    el.mask();
                } else {
                    el.unmask();
                }
            }
        });

        this.openFileButton = new Ext.Button({
            iconCls: 'ic-file-upload',
            text: t("Upload"),
            width: dp(40),
            height: dp(30),
            handler: function() {
                if(!this.chooseThoughtlistGrid.selectedId && !this.thoughtListFromCsvCB.checked) {
                    Ext.Msg.show({
                        title:t("List not selected"),
                        msg: t("You have not selected any list. Select a list before proceeding."),
                        buttons: Ext.Msg.OK,
                        animEl: 'elId',
                        icon: Ext.MessageBox.WARNING
                     });
                } else {
                    let TLvalues = {};
                    if(!this.thoughtListFromCsvCB.checked) {
                        TLvalues = {thoughtlistId: this.chooseThoughtlistGrid.selectedId};
                    }
                    go.util.importFile(
                        'Thought', 
                        ".ics,.csv",
                        TLvalues,
                        {},
                        {
                            labels: {
                                start: t("start"),
                                due: t("due"),
                                completed: t("completed"),
                                title: t("title"),
                                description: t("description"),
                                status: t("status"),
                                priority: t("priority"),
                                percentComplete: t("percentage completed"),
                                categories: t("categories")
                            }
                        });
                }
            },
            scope: this
        });

        const propertiesPanel = new Ext.Panel({
            hideMode : 'offsets',
            //title : t("Properties"),
            labelAlign: 'top',
            layout : 'form',
            autoScroll : true,
            items : [{
                xtype: "container",
                layout: "form",
                defaults: {
                    anchor: '100%'
                },
                labelWidth: 16,
                items: [
                    this.thoughtListFromCsvCB,
                    this.chooseThoughtlistGrid
                ]
            }]
        });

        this.buttons = [this.openFileButton];

		this.items = [
            propertiesPanel
        ];

        go.modules.tutorial.gtd.ChooseThoughtlistDialog.superclass.initComponent.call(this);

        this.on("render", function () {
            this.search();
        }, this);
    },


    search : function(v) {
        this.chooseThoughtlistGrid.store.setFilter("search", {text: v});
        this.chooseThoughtlistGrid.store.load();
    },

});
