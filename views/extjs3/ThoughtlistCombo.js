(function() {
	const cfg = {
		fieldLabel: t("List"),
		hiddenName: 'thoughtlistId',
		anchor: '100%',
		emptyText: t("Please select..."),
		pageSize: 50,
		valueField: 'id',
		displayField: 'name',
		triggerAction: 'all',
		editable: true,
		selectOnFocus: true,
		forceSelection: true,
		role: null, // set to "list" or "board" to filter the thoughtlist store
		allowBlank: false
	};

	go.modules.tutorial.gtd.ThoughtlistCombo = Ext.extend(go.form.ComboBox, Ext.apply(cfg,
		{
			initComponent: function () {
				this.supr().initComponent.call(this);

				this.store = new go.data.Store(
					{
						fields: ['id', 'name'],
						entityStore: this.initialConfig.role && this.initialConfig.role == "support" ? "SupportList" : "ThoughtList",
						filters: {
							permissionLevel: {
								permissionLevel: go.permissionLevels.create
							}
						}
					}
				)

				if (this.initialConfig.role) {
					this.store.setFilter('role', {role: this.initialConfig.role});
				}
				if (go.User.thoughtsSettings && !("value" in this.initialConfig)) {
					this.value = this.initialConfig.role == "support" ? go.User.supportSettings.defaultThoughtlistId : go.User.thoughtsSettings.defaultThoughtlistId;
				}
			}
	}));

	go.modules.tutorial.gtd.ThoughtlistComboBoxReset = Ext.extend(go.form.ComboBoxReset, Ext.apply(cfg, {
		allowBlank: true,
		initComponent: function() {
			this.supr().initComponent.call(this);

			this.store = new go.data.Store(
				{
					fields: ['id', 'name'],
					entityStore: this.initialConfig.role && this.initialConfig.role == "support" ? "SupportList" : "ThoughtList",
					filters: {
						permissionLevel: {
							permissionLevel: go.permissionLevels.write
						}
					}
				}
			)

			if(this.initialConfig.role) {
				this.store.setFilter('role', {role: this.initialConfig.role});
			}

			if(go.User.thoughtsSettings) {
				this.value = go.User.thoughtsSettings.defaultThoughtlistId;
			}

		}
	}));

	Ext.reg('thoughtlistcombo', go.modules.tutorial.gtd.ThoughtlistCombo );
	Ext.reg('thoughtlistcomboreset', go.modules.tutorial.gtd.ThoughtlistComboBoxReset );

})();