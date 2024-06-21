go.modules.tutorial.gtd.ProgressGrid = Ext.extend(go.NavGrid, {
	autoHeight: true,
	saveSelection: true,
	stateId: "thought-progress-grid",
	hideMenuButton: true,
	initComponent: function () {
		Ext.apply(this, {
			store: new Ext.data.ArrayStore({
				fields: ['value', 'name'],
				id: 0,
				data: [
					['completed', t("Completed")],
					['failed', t("Failed")],
					['in-progress', t("In progress")],
					['needs-action', t("Needs action")],
					['cancelled', t("Cancelled")]
				]
			}),

			stateful: true,
		});

		go.modules.tutorial.gtd.ProgressGrid.superclass.initComponent.call(this);

	}

});
