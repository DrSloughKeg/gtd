go.modules.tutorial.gtd.ChooseThoughtlistGrid = Ext.extend(go.grid.GridPanel, {
	initComponent: function () {

		this.store = new go.data.Store({
			fields: [
				'id', 
                'name',
				'role',
                {name: 'creator', type: "relation"}
			],
			entityStore: "ThoughtList"
		});

		Ext.apply(this, {		
			singleSelect: true,
			columns: [
				{
					id: 'id',
					hidden: true,
					header: 'ID',
					width: dp(40),
					sortable: true,
					dataIndex: 'id'
				},
				{
					id: 'name',
					header: t('Name'),
					width: dp(75),
					sortable: true,
					dataIndex: 'name'
                },{
					id: 'role',
					header: t('Role'),
					width: dp(75),
					sortable: true,
					hidden: true,
					dataIndex: 'role',
					renderer: (v) => {
						return t((v).ucFirst());
					}
				},
				{	
					hidden: true,
					header: t('Created by'),
					width: dp(160),
					sortable: true,
					dataIndex: 'creator',
					renderer: function(v) {
						return v ? v.displayName : "-";
					}
				}
			],
			viewConfig: {
				totalDisplay: true,
				emptyText: 	'<i>description</i><p>' +t("No items to display") + '</p>'
            },
            listeners: {
				scope: this,
				rowclick: function (grid, rowIndex, e) {
					const row = this.getSelectionModel().getSelections()[0];
					this.selectedId = row.get("id");
				}
			},
			autoExpandColumn: 'name',
			// config options for stateful behavior
			stateful: true,
			stateId: 'choose-thoughtlist-grid'
        });

        this.store.load();
		go.modules.tutorial.gtd.ChooseThoughtlistGrid.superclass.initComponent.call(this);
	}
});

