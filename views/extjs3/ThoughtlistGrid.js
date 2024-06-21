go.modules.tutorial.gtd.ThoughtlistsGrid = Ext.extend(go.NavGrid, {
	autoHeight: true,
	scrollLoader: false,
	showMoreLoader: true,
	loadMorePageSize: 20,
	cls: "go-thoughts-thought-list",

	initColumns: function() {

		go.modules.tutorial.gtd.ThoughtlistsGrid.superclass.initColumns.call(this);


		this.columns.push({
			id: 'group',
			header: t('Group'),
			sortable: false,
			dataIndex: 'group',
			// groupRenderer: function(v, un, r, rowIndex, colIndex, ds) {
			// 	if(!v) {
			// 		return "";
			// 	}
			// }
		})

	},

	initComponent: function () {

		this.view = new go.grid.GroupingView({
			showGroupName: false,
			emptyText: '<i>description</i><p>' + t("No items to display") + '</p>',
			totalDisplay: false,
			hideGroupedColumn: true,
			forceFit: true,
			autoFill: true,
			emptyGroupText: ""
		});

		Ext.apply(this, {
			store: new go.data.GroupingStore({
				groupField: "group",
				remoteGroup: true,
				remoteSort: true,
				fields: [
					'id',
					'name',
					{name: "group", type: "relation", mapping: "group.name"}
				],
				entityStore: this.support ? "SupportList" : "Thoughtlist",
				filters: {role: {role: 'list'}},
				sortInfo: {
					field: 'name',
					direction: 'ASC'
				}
			}),

			menuItems: [
				{
					itemId: "edit",
					iconCls: 'ic-edit',
					text: t("Edit"),
					handler: function() {
						var dlg = new go.modules.tutorial.gtd.ThoughtlistDialog({entityStore: this.support ? "SupportList" : "ThoughtList"});
						dlg.load(this.moreMenu.record.id).show();
					},
					scope: this
				},{
					itemId: "delete",
					iconCls: 'ic-delete',
					text: t("Delete"),
					handler: function() {
						Ext.MessageBox.confirm(t("Confirm delete"), t("Are you sure you want to delete this item?"), function (btn) {
							if (btn != "yes") {
								return;
							}
							go.Db.store(this.support ? "SupportList" : "Thoughtlist").set({destroy: [this.moreMenu.record.id]});
						}, this);
					},
					scope: this
				}
			],

			stateful: true,
			stateId: 'thought-lists-grid'
		});

		go.modules.tutorial.gtd.ThoughtlistsGrid.superclass.initComponent.call(this);


		this.on('beforeshowmenu', (menu, record) => {
			menu.getComponent("edit").setDisabled(record.get("permissionLevel") < go.permissionLevels.manage);
			menu.getComponent("delete").setDisabled(!go.Modules.get("community", 'thoughts').userRights.mayChangeThoughtlists || record.get("permissionLevel") < go.permissionLevels.manage);
		});
	},

	

});
