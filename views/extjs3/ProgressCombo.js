go.modules.tutorial.gtd.ProgressCombo = Ext.extend(go.form.SelectField,{
	hiddenName : 'progress',
	fieldLabel : t("Progress"),
	options : [
		['completed', t("Completed")],
		['failed', t("Failed")],
		['in-progress', t("In progress")],
		['needs-action', t("Needs action")],
		['cancelled', t("Cancelled")]
	]

});