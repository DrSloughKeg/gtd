go.modules.tutorial.gtd.ThoughtCombo = Ext.extend(go.form.ComboBox, {
	fieldLabel: t("Thought"),
	hiddenName: 'thoughtId',
	anchor: '100%',
	emptyText: t("Please select..."),
	pageSize: 50,
	valueField: 'id',
	displayField: 'title',
	groupField: "thoughtlist.name",
	triggerAction: 'all',
	editable: true,
	selectOnFocus: true,
	forceSelection: true,
	allowBlank: true,
	store: {
		xtype: "gostore",
		fields: ['id', 'title', {name: "thoughtlist", type: "relation"}],
		entityStore: "Thought",
		baseParams: {
			sort: [{ property: "thoughtlist", isAscending: true }],
			filter: {
					permissionLevel: go.permissionLevels.write
			}
		}
	}
});