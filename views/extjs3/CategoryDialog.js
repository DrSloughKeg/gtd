go.modules.tutorial.gtd.CategoryDialog = Ext.extend(go.form.Dialog, {
	title: t("Category", "thoughts"),
	entityStore: "ThoughtCategory",
	titleField: "name",
	resizable: false,
	width: dp(400),
	height: dp(400),
	redirectOnSave: false,
	role: null,
	initFormItems: function () {
		var items = [{
				xtype: 'fieldset',
				items: [
					{
						xtype: 'textfield',
						name: 'name',
						fieldLabel: t("Name"),
						anchor: '100%',
						allowBlank: false
					},
					this.thoughtlistCombo = new go.modules.tutorial.gtd.ThoughtlistComboBoxReset({
						allowBlank: true,
						emptyText: t("All"),
						role: this.role
					})
					]
			}
		];

		const mod = this.role === "support" ? go.Modules.get("business", "support") : go.Modules.get("community", "thoughts");

		if(mod.userRights.mayChangeCategories)
		{
			this.ownerIdField = new Ext.form.Hidden({name:'ownerId',value:go.User.id});

			this.checkbox = new Ext.form.Checkbox({
				xtype:'xcheckbox',
				boxLabel:t("Global category", "thoughts"),
				hideLabel:true,
				submit:false,
				anchor: '100%',
				listeners: {scope:this,'check': function(me, checked) {
					this.ownerIdField.setValue(checked ? null : go.User.id);
					this.thoughtlistCombo.setDisabled(checked);
				}}
			});

			items[0].items.push(this.checkbox,this.ownerIdField);
		}
		return items;
	},

	onLoad: function() {
		this.supr().onLoad.call(this);

		const mod = this.role === "support" ? go.Modules.get("business", "support") : go.Modules.get("community", "thoughts");

		if (mod.userRights.mayChangeCategories) {
			this.checkbox.setValue(!this.thoughtlistCombo.getValue() && !this.ownerIdField.getValue());
		}

	}
});
