/* global go, Ext */

go.modules.tutorial.gtd.SettingsPanel = Ext.extend(Ext.Panel, {
	title: t("Thoughts"),
	iconCls: 'ic-check',
	labelWidth: 125,
	layout: "form",
	initComponent: function () {

		//The account dialog is an go.form.Dialog that loads the current User as entity.
		this.items = [{
			xtype: "fieldset",
			title: t("Display options for lists"),
			items: [
				{
					xtype: "thoughtlistcombo",
					hiddenName: "thoughtsSettings.defaultThoughtlistId",
					fieldLabel: t("Default list"),
					role: 'list',
					allowBlank: true
				},
				this.defaultThoughtlistOptions = new go.form.RadioGroup({
					allowBlank: true,
					fieldLabel: t('Start in'),
					name: 'thoughtsSettings.rememberLastItems',
					columns: 1,

					items: [
						{
							boxLabel: t("Default thoughtlist"),
							inputValue: false
						},
						{
							boxLabel: t("Remember last selected thoughtlist"),
							inputValue: true
						}
					]
				}),
				{
					xtype: "checkbox",
					hideLabel: true,
					boxLabel: t("Set today for start and due date when creating new thoughts"),
					name: "thoughtsSettings.defaultDate"

				}
			]
		}
		];

		go.modules.tutorial.gtd.SettingsPanel.superclass.initComponent.call(this);
	}
});
