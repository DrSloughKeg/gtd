go.modules.tutorial.gtd.CategoriesGrid = Ext.extend(go.NavGrid, {
	role: "list",
	initComponent: function () {

		Ext.apply(this, {
			//hideMenuButton: !go.Modules.get("community", 'thoughts').userRights.mayChangeCategories,
			store: new go.data.Store({
				fields: ['id', 'name'],
				entityStore: "ThoughtCategory",
				sortInfo: {
					field: "name"
				}
			}),
			menuItems: [
				{
					itemId: "edit",
					iconCls: 'ic-edit',
					text: t("Edit"),
					handler: function() {
						var dlg = new go.modules.tutorial.gtd.CategoryDialog();
						dlg.thoughtlistCombo.store.setFilter("role", {role: this.role});
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
							go.Db.store("ThoughtCategory").set({destroy: [this.moreMenu.record.id]});
						}, this);
					},
					scope: this
				}
			],

			stateId: 'categories-grid'
		});

		go.modules.tutorial.gtd.CategoriesGrid.superclass.initComponent.call(this);

		this.on('beforeshowmenu', (menu, record) => {
			menu.getComponent("edit").setDisabled(record.json.permissionLevel < go.permissionLevels.write);
			menu.getComponent("delete").setDisabled(record.json.permissionLevel < go.permissionLevels.writeAndDelete);
		});

	}
});
