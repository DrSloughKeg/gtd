
go.modules.tutorial.gtd.Portlet = Ext.extend(go.modules.tutorial.gtd.ThoughtGrid, {

	stateId: 'thoughts-portlet',


	autoHeight: true,
	maxHeight: dp(600),



	afterRender: function () {
		this.supr().afterRender.call(this);
		const lists = go.User.thoughtPortletThoughtLists.length ? go.User.thoughtPortletThoughtLists : [go.User.thoughtsSettings.defaultThoughtlistId];
		this.store.setFilter('thoughtlistIds', {thoughtlistId: lists});
		this.store.setFilter('incomplete', {complete: false, start: "<=now"});
		this.store.on("beforeload", () => {
			const date = go.util.Format.dateToUserTZ(new Date()).format("Y-m-d");
			this.store.setFilter('incomplete', {complete: false, start: "<=" + date});
		})
		this.store.load();

		this.on("rowclick", function (grid, rowClicked, e) {
			go.Router.goto('thought/' + grid.selModel.selections.keys[0]);
		});
	}
});

go.modules.tutorial.gtd.PortletSettingsDialog = Ext.extend(go.form.Dialog, {
	title: t("Visible lists"),
	entityStore: "User",
	width: dp(500),
	height: dp(500),
	modal: true,
	collapsible: false,
	maximizable: false,
	layout: 'fit',
	showCustomfields: false,

	initFormItems: function() {
		return [
			new Ext.form.FieldSet({
				xtype: 'fieldset',
				items: [
					{
						layout: "form",
						items: [
							new go.form.multiselect.Field({
								valueIsId: true,
								idField: 'thoughtListId',
								displayField: 'name',
								entityStore: 'ThoughtList',
								name: 'thoughtPortletThoughtLists',
								hideLabel: true,
								emptyText: t("Please select..."),
								pageSize: 50,
								fields: ['id', 'name']

							})
						]
					}
				]
			})
		];
	}
});


GO.mainLayout.onReady(function () {
	if (go.Modules.isAvailable("legacy", "summary") && go.Modules.isAvailable("community", "thoughts"))
	{
		var thoughtsGrid = new go.modules.tutorial.gtd.Portlet();

		GO.summary.portlets['portlet-thoughts'] = new GO.summary.Portlet({
			id: 'portlet-thoughts',
			//iconCls: 'go-module-icon-thoughts',
			title: t("Thoughts", "thoughts"),
			layout: 'fit',
			tools: [{
					id: 'gear',
					handler: function () {
						const dlg = new go.modules.tutorial.gtd.PortletSettingsDialog({
							listeners: {
								hide: function () {
									setTimeout(function() {
										thoughtsGrid.store.setFilter('thoughtlistIds', {thoughtlistId: go.User.thoughtPortletThoughtLists})
										thoughtsGrid.store.reload();
									})
								},
								scope: this
							}
						});
						dlg.load(go.User.id).show();
					}
				}, {
					id: 'close',
					handler: function (e, target, panel) {
						panel.removePortlet();
					}
				}],
			items: thoughtsGrid,
			autoHeight: true
		});
	}
});
