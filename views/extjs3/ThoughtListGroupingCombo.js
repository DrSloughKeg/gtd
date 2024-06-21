/* global Ext, go, GO */

/**
 * 
 * @type |||
 */
go.modules.tutorial.gtd.ThoughtListGroupingCombo = Ext.extend(go.form.ComboBox, {
	fieldLabel: t("Group"),
	hiddenName: 'groupingId',
	anchor: '100%',
	emptyText: t("Please select..."),
	pageSize: 50,
	valueField: 'id',
	displayField: 'name',
	triggerAction: 'all',
	editable: true,
	selectOnFocus: false,
	forceSelection: true,
	allowNew: true,
	initComponent: function () {

		Ext.applyIf(this, {
			store: new go.data.Store({
				fields: [
					'id',
					'name'
					],
				entityStore: "ThoughtListGrouping",
				sortInfo: {
					field: "name",
					direction: 'ASC' 
				}
			})
		});

		go.modules.tutorial.gtd.ThoughtListGroupingCombo.superclass.initComponent.call(this);

	}
});

