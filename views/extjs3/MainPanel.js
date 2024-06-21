/** 
 * Copyright Intermesh
 * 
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 * 
 * If you have questions write an e-mail to info@intermesh.nl
 *
 * @copyright Copyright Intermesh
 * @author Merijn Schering <mschering@intermesh.nl>
 */
go.modules.tutorial.gtd.MainPanel = Ext.extend(go.modules.ModulePanel, {
	support: false,
	title: t("Thoughts"),
	layout: 'responsive',
	layoutConfig: {
		triggerWidth: 1000
	},

	initComponent: function () {
		this.statePrefix = this.support ? 'support-' : 'thoughts-';
		this.createThoughtGrid();
		this.createThoughtlistGrid();
		this.createCategoriesGrid();

		this.thoughtDetail = new go.modules.tutorial.gtd.ThoughtDetail({
			support: this.support,
			entityStore: this.support ? "SupportTicket" : "Thought",
			region: 'east',
			split: true,
			stateId: this.statePrefix  + '-thought-detail',
			tbar: [this.thoughtBackButton = new Ext.Button({
				//cls: 'go-narrow',
				hidden: true,
				iconCls: "ic-arrow-back",
				handler: function () {
					go.Router.goto(this.support ? "support" : "thoughts");
				},
				scope: this
			})]
		});

		const showCompleted = Ext.state.Manager.get(this.statePrefix + "show-completed");
		const showProjectThoughts = Ext.state.Manager.get(this.statePrefix + "show-project-thoughts");
		const showAssignedToMe = Ext.state.Manager.get(this.statePrefix + "assigned-to-me");
		const showUnassigned = Ext.state.Manager.get(this.statePrefix + "show-unassigned");

		if(this.support) {
			this.filterPanel = new go.modules.tutorial.gtd.ProgressGrid({
				tbar: [{
					xtype: "tbtitle",
					text: t("Status", 'thoughts','community')
				}],
				filterName: "progress",
				filteredStore: this.thoughtGrid.store

			});

		} else {

			this.filterPanel = new go.NavMenu({
				region: 'north',
				store: new Ext.data.ArrayStore({
					fields: ['name', 'icon', 'iconCls', 'inputValue'],
					data: [
						[t("Today"), 'content_paste', 'green', 'today'],
						[t("Due in seven days"), 'filter_7', 'purple', '7days'],
						[t("All"), 'assignment', 'red', 'all'],
						[t("Unscheduled"), 'event_busy', 'blue', 'unscheduled'],
						[t("Scheduled"), 'events', 'orange', 'scheduled'],

					]
				})
			});
		}


		this.sidePanel = new Ext.Panel({
			width: dp(300),
			cls: 'go-sidenav',
			region: "west",
			split: true,
			stateId: this.support ? "support-west" : "thoughts-west",
			tbar: this.sidePanelTbar = new Ext.Toolbar({
				//cls: 'go-narrow',
				hidden: true,
				items: ["->",  {

					iconCls: "ic-arrow-forward",
					tooltip: t("Thoughts"),
					handler: function () {
						this.thoughtGrid.show();
					},
					scope: this
				}]
			}),

			autoScroll: true,
			layout: "anchor",
			defaultAnchor: '100%',

			items:[
				this.filterPanel,
				this.thoughtlistsGrid,
				this.categoriesGrid,
				{xtype:'filterpanel', store: this.thoughtGrid.store, entity: this.support ? "SupportTicket" : "Thought",}

			]
		});
		if(!this.support) {

			let cfToggles = Ext.create({
				xtype: "fieldset",
				items: [
					{
						hideLabel: true,
						xtype: "checkbox",
						boxLabel: t("Show completed"),
						checked: showCompleted,
						listeners: {
							scope: this,
							check: function(cb, checked) {
								this.showCompleted(checked);
								Ext.state.Manager.set(this.statePrefix + "show-completed", checked);
								this.thoughtGrid.store.load();
							}
						}
					}
				]
			});
			if(go.Modules.isAvailable("legacy", "projects2")) {
				cfToggles.add({
					hideLabel: true,
					xtype: "checkbox",
					boxLabel: t("Show project thoughts"),
					checked: showProjectThoughts,
					listeners: {
						scope: this,
						check: function (cb, checked) {
							this.toggleProjectThoughts(checked);
							Ext.state.Manager.set(this.statePrefix + "show-project-thoughts", checked);
							this.thoughtGrid.store.load();
						}
					}
				})
			}

			this.sidePanel.items.insert(1, cfToggles);
		}

		this.sidePanel.items.insert(this.support ? 1 : 2, Ext.create({
			xtype: "panel",
			layout: "form",
			tbar: [
				{
					xtype:"tbtitle",
					text: t("Assigned", 'thoughts','community')
				}
			],
			items: [
				{xtype:'fieldset',items:[{
					hideLabel: true,
					xtype: "checkbox",
					boxLabel: t("Mine", 'thoughts','community'),
					checked: showAssignedToMe,
					listeners: {
						scope: this,
						check: function(cb, checked) {
							Ext.state.Manager.set(this.statePrefix + "assigned-to-me", checked);
							this.setAssignmentFilters();
							this.thoughtGrid.store.load();
						}
					}
				},{
					hideLabel: true,
					xtype: "checkbox",
					boxLabel: t("Unassigned", "thoughts", "community"),
					checked: showUnassigned,
					listeners: {
						scope: this,
						check: function(cb, checked) {
							Ext.state.Manager.set(this.statePrefix + "show-unassigned", checked);
							this.setAssignmentFilters();
							this.thoughtGrid.store.load();
						}
					}
				}]}
			]
		}));

		this.centerPanel = new Ext.Panel({
			layout:'responsive',
			stateId: this.statePrefix + "west",
			region: "center",
			listeners: {
				afterlayout: (panel, layout) => {
					this.sidePanelTbar.setVisible(layout.isNarrow());
					this.showNavButton.setVisible(layout.isNarrow())
				}
			},
			split: true,
			narrowWidth: dp(400),
			items:[
				this.thoughtGrid,
				this.sidePanel
			]
		});

		this.items = [
			this.centerPanel, //first is default in narrow mode
			this.thoughtDetail
		];

		this.on("afterlayout", (panel, layout) => {
			this.thoughtBackButton.setVisible(layout.isNarrow());
		});

		go.modules.tutorial.gtd.MainPanel.superclass.initComponent.call(this);

		this.on("afterrender", this.runModule, this);
	},

	showCompleted : function(show) {
		this.thoughtGrid.store.setFilter('completed', show ? null : {complete:  false});
	},

	toggleProjectThoughts: function(show) {
		this.thoughtGrid.store.setFilter("role", show ? {role:  go.modules.tutorial.gtd.listTypes.Project } : null);
		if(show) {
			this.thoughtGrid.store.setFilter(this.thoughtlistsGrid.getId(), null); // eew
		} else {
			this.setDefaultSelection();
		}
		this.thoughtlistsGrid.setVisible(!show);
		this.categoriesGrid.setVisible(!show);
	},

	setAssignmentFilters: function() {
		let numSelectedFilters = 0;
		if(Ext.state.Manager.get(this.statePrefix + "assigned-to-me")) {
			numSelectedFilters++
		}
		if(Ext.state.Manager.get(this.statePrefix + "show-unassigned")) {
			numSelectedFilters++;
		}

		if(numSelectedFilters === 0) {
			this.thoughtGrid.store.setFilter('assignedToMe', null);
		} else if(numSelectedFilters === 1) {
			const cnd = Ext.state.Manager.get(this.statePrefix + "assigned-to-me") ? go.User.id : null;
			this.thoughtGrid.store.setFilter('assignedToMe', {responsibleUserId: cnd})
		} else {
			this.thoughtGrid.store.setFilter('assignedToMe',
				{
					operator: "OR",
					conditions: [
						{responsibleUserId: go.User.id},
						{responsibleUserId: null}
					]
				}
			);
		}
	},

	runModule : function() {

		if(this.support) {
			this.setAssignmentFilters();
		} else {

			this.filterPanel.on("afterrender", () => {

				let index = this.filterPanel.store.find('inputValue', statusFilter);
				if (index == -1) {
					index = 0;
				}

				this.filterPanel.selectRange(index, index);

			});
			this.filterPanel.on("selectionchange", this.onStatusSelectionChange, this);

			this.showCompleted(Ext.state.Manager.get(this.statePrefix + "show-completed"));
			this.toggleProjectThoughts(Ext.state.Manager.get(this.statePrefix + "show-project-thoughts"));

			let statusFilter = Ext.state.Manager.get(this.statePrefix + "status-filter");
			if (!statusFilter) {
				statusFilter = 'today';
			}

			this.setStatusFilter(statusFilter);
		}


		if(!Ext.state.Manager.get(this.statePrefix + "show-project-thoughts")) {
			this.setDefaultSelection();
		}

		this.thoughtlistsGrid.store.load();
		this.thoughtGrid.store.load();

	},

	getSettings : function() {
		return this.support ? go.User.supportSettings : go.User.thoughtsSettings;
	},

	setDefaultSelection : function() {
		let selectedListIds = [], settings = this.getSettings();
		if(settings.rememberLastItems) {
			selectedListIds = settings.lastThoughtlistIds;
		} else if(settings.defaultThoughtlistId) {
			selectedListIds.push(settings.defaultThoughtlistId);
		}

		this.filterCategories(selectedListIds);

		this.thoughtlistsGrid.setDefaultSelection(selectedListIds);

		this.thoughtGrid.store.setFilter("role", selectedListIds.length == 0 ? {role:  !this.support ? go.modules.tutorial.gtd.listTypes.List : go.modules.tutorial.gtd.listTypes.Support} : null);


		this.checkCreateThoughtList();
	},

	onStatusSelectionChange: function(view, nodes) {

		const rec = view.store.getAt(nodes[0].viewIndex);
		this.setStatusFilter(rec.data.inputValue);
		this.thoughtGrid.store.load();
	},




	setStatusFilter : function(inputValue) {

		switch(inputValue) {

			case "today": // thoughts today

				this.thoughtGrid.store.setFilter("status", {
					start: "<=now"
				});

				break;

			case '7days':
				this.thoughtGrid.store.setFilter("status", {
					due: "<=7days"
				});
				break;

			case "unscheduled":
				this.thoughtGrid.store.setFilter('status',{
					scheduled: false
				});
				break;

			case "scheduled":
				this.thoughtGrid.store.setFilter('status',{
					scheduled: true
				});
				break;

			case "all": // all
				this.thoughtGrid.store.setFilter("status", null);
				break;
		}

		Ext.state.Manager.set(this.statePrefix + "status-filter", inputValue);
	},

	createCategoriesGrid: function() {
		this.categoriesGrid = new go.modules.tutorial.gtd.CategoriesGrid({
			role:  !this.support ? go.modules.tutorial.gtd.listTypes.List : go.modules.tutorial.gtd.listTypes.Support,
			filterName: "categories",
			filteredStore: this.thoughtGrid.store,
			autoHeight: true,
			split: true,
			tbar: [{
					xtype: 'tbtitle',
					text: t('Categories', 'thoughts','community')
				}, '->', {
					iconCls: 'ic-add',
					tooltip: t('Add'),
					handler: function (e, toolEl) {
						const dlg = new go.modules.tutorial.gtd.CategoryDialog({
							role:  !this.support ? go.modules.tutorial.gtd.listTypes.List : go.modules.tutorial.gtd.listTypes.Support
						})

						const firstSelected = this.thoughtlistsGrid.getSelectionModel().getSelected();
						if(firstSelected) {
							dlg.setValues({thoughtlistId: firstSelected.id});
						}
						dlg.show();
					},
					scope: this
				}],
			listeners: {
				rowclick: function(grid, row, e) {
					if(e.target.className != 'x-grid3-row-checker') {
						//if row was clicked and not the checkbox then switch to grid in narrow mode
						this.categoriesGrid.show();
					}
				},
				scope: this
			}
		});

	},

	createThoughtlistGrid : function() {
		this.thoughtlistsGrid = new go.modules.tutorial.gtd.ThoughtlistsGrid({
			filteredStore: this.thoughtGrid.store,
			filterName: 'thoughtlistId',
			selectFirst: false,
			support: this.support,
			split: true,
			tbar: [{
					xtype: 'tbtitle',
					text: t('Lists', 'thoughts','community')
				}, '->', {
					xtype: "tbsearch"
				},{
				// hidden: !go.Modules.get("community", 'thoughts') || !go.Modules.get("community", 'thoughts').userRights.mayChangeThoughtlists,
				hidden: !this.canEditThoughtLists(),
					iconCls: 'ic-add',
					tooltip: t('Add'),
					handler: function (e, toolEl) {
						let dlg = new go.modules.tutorial.gtd.ThoughtlistDialog({entityStore: this.support ? "SupportList" : "Thoughtlist"});
						dlg.show();
					},
					scope: this
				}],
			listeners: {
				afterrender: function(grid) {
					new Ext.dd.DropTarget(grid.getView().mainBody, {
						ddGroup : 'ThoughtlistsDD',
						notifyDrop :  (source, e, data) => {
							const selections = source.dragData.selections,
								dropRowIndex = grid.getView().findRowIndex(e.target),
								thoughtlistId = grid.getView().grid.store.data.items[dropRowIndex].id;

							const update = {};
							selections.forEach((r) => {
								update[r.id] = {thoughtlistId: thoughtlistId};
							})

							go.Db.store(this.support ? "SupportTicket" : "Thought").set({update: update});
						}
					});
				},
				rowclick: function(grid, row, e) {
					if(e.target.className != 'x-grid3-row-checker') {
						//if row was clicked and not the checkbox then switch to grid in narrow mode
						this.thoughtGrid.show();
					}
				},
				scope: this
			}
		});

		if(this.support) {
			this.thoughtlistsGrid.getStore().setFilter("role", {role: "support"});

			this.thoughtlistsGrid.getStore().baseParams = this.thoughtlistsGrid.getStore().baseParams || {};
			this.thoughtlistsGrid.getStore().baseParams.limit = 1000;
		}

		this.thoughtlistsGrid.on('selectionchange', this.onThoughtlistSelectionChange, this); //add buffer because it clears selection first
	},

	// checkValues: function() {
	// 	if(this.thoughtDateField.getValue() != null && this.thoughtNameTextField.getValue() != "") {
	// 		this.addThoughtButton.setDisabled(false);
	// 	} else {
	// 		this.addThoughtButton.setDisabled(true);
	// 	}
	// },

	canEditThoughtLists: function() {
		if(this.support) {
			const modRights = go.Modules.get("business", "support").userRights;
			return modRights.mayChangeThoughtlists;
		} else {
			return go.Modules.get("community", 'thoughts') && go.Modules.get("community", 'thoughts').userRights.mayChangeThoughtlists;
		}

	},
	
	createThoughtGrid : function() {

		this.thoughtGrid = new go.modules.tutorial.gtd.ThoughtGrid({
			support: this.support,
			stateId: this.statePrefix  + '-thoughts-grid-main',
			enableDrag: true,
			ddGroup: 'ThoughtlistsDD',
			split: true,
			region: 'center',
			multiSelectToolbarItems: [
				{
					iconCls: "ic-merge-type",
					tooltip: t("Merge"),
					handler: function() {
						const ids = this.thoughtGrid.getSelectionModel().getSelections().column('id');
						// console.warn(ids);
						if(ids.length < 2) {
							Ext.MessageBox.alert(t("Error"), t("Please select at least two items"));
						} else
						{
							Ext.MessageBox.confirm(t("Merge"), t("The selected items will be merged into one. The item you selected first will be used primarily. Are you sure?"), async function(btn) {

								if(btn != "yes") {
									return;
								}

								try {
									Ext.getBody().mask(t("Saving..."));
									const entity = this.support ? "SupportTicket" : "Thought";
									const result = await go.Db.store(entity).merge(ids);
									await go.Db.store(entity).getUpdates();

									setTimeout(() => {
										const dlg = new go.modules.tutorial.gtd.ThoughtDialog({
											role: this.support ? "support" : "list",
											entityStore: entity,
										});
										dlg.load(result.id);
										dlg.show();
									})
								} catch(e) {
									Ext.MessageBox.alert(t("Error"), e.message);
								} finally {
									Ext.getBody().unmask();
								}
							}, this);
						}
					},
					scope: this
				}
				],
			tbar: [
					this.showNavButton = new Ext.Button({
						hidden: true,
						iconCls: "ic-menu",
						handler: function () {
							this.sidePanel.show();
						},
						scope: this
					}),
					'->',
					{
						xtype: 'tbsearch'
					},
					this.addButton = new Ext.Button({
						disabled: true,
						iconCls: 'ic-add',
						tooltip: t('Add'),
						cls: 'primary',
						handler: function (btn) {
							let dlg = new go.modules.tutorial.gtd.ThoughtDialog({
								entityStore: this.support ? "SupportTicket" : "Thought",
								role: this.support ? "support" : "list"
							});
							dlg.setValues({
								thoughtlistId: this.addThoughtlistId
							}).show();
						},
						scope: this
					}),
					{
						iconCls: 'ic-more-vert',
						menu: [
							// {
							// 	text: "refresh",
							// 	handler: function () {
							// 		const store = this.thoughtGrid.store, o = go.util.clone(store.lastOptions);
							// 		o.params = o.params || {};
							// 		o.params.position = 0;
							// 		o.add = false;
							// 		o.keepScrollPosition = true;
							//
							// 		if (store.lastOptions.params && store.lastOptions.params.position) {
							// 			o.params.limit = store.lastOptions.params.position + (store.lastOptions.limit || store.baseParams.limit || 20);
							// 		}
							//
							// 		store.load(o);
							// 	},
							// 	scope: this
							// }
							// ,
							{
								iconCls: 'ic-cloud-upload',
								text: t("Import"),
								handler: function() {
									var dlg = new go.modules.tutorial.gtd.ChooseThoughtlistDialog();
									dlg.show();
								},
								scope: this
							},
							{
								iconCls: 'ic-cloud-download',
								text: t("Export"),
								menu: [
									{
										text: 'vCalendar',
										iconCls: 'filetype filetype-ics',
										handler: function() {
											go.util.exportToFile(
												this.support ? 'SupportTicket' : 'Thought',
												Object.assign(go.util.clone(this.thoughtGrid.store.baseParams), this.thoughtGrid.store.lastOptions.params, {limit: 0, position: 0}),
												'ics');
										},
										scope: this
									}, {
										text: 'Microsoft Excel',
										iconCls: 'filetype filetype-xls',
										handler: function() {
											go.util.exportToFile(
												this.support ? 'SupportTicket' : 'Thought',
												Object.assign(go.util.clone(this.thoughtGrid.store.baseParams), this.thoughtGrid.store.lastOptions.params, {limit: 0, position: 0}),
												'xlsx');
										},
										scope: this
									},{
										text: 'Comma Separated Values',
										iconCls: 'filetype filetype-csv',
										handler: function() {
											go.util.exportToFile(
												this.support ? 'SupportTicket' : 'Thought',
												Object.assign(go.util.clone(this.thoughtGrid.store.baseParams), this.thoughtGrid.store.lastOptions.params, {limit: 0, position: 0}),
												'csv');
										},
										scope: this
									},{
										text: t("Web page") + ' (HTML)',
										iconCls: 'filetype filetype-html',
										handler: function() {
											go.util.exportToFile(
												this.support ? 'SupportTicket' : 'Thought',
												Object.assign(go.util.clone(this.thoughtGrid.store.baseParams), this.thoughtGrid.store.lastOptions.params, {limit: 0, position: 0}),
												'html');
										},
										scope: this
									}
								]
							},
							{
								itemId: "delete",
								iconCls: 'ic-delete',
								text: t("Delete"),
								handler: function () {
									this.thoughtGrid.deleteSelected();
								},
								scope: this
							}
						]
					}

				],

			listeners: {				
				rowdblclick: this.onThoughtGridDblClick,
				scope: this,				
				keypress: this.onThoughtGridKeyPress
			}
		});

		this.thoughtGrid.on('navigate', function (grid, rowIndex, record) {
			go.Router.goto((this.support ? "support/" : "thought/") + record.id);
		}, this);
		
	
	},

	createThought : function() {

		go.Db.store("Thought").set({
			create: {"client-id-1" : {
					title: this.thoughtNameTextField.getValue(),
					start: this.thoughtDateField.getValue(),
					thoughtlistId: this.addThoughtlistId
				}}
		}).then (() => {
				this.thoughtNameTextField.reset();
		});

	},


	filterCategories : function(ids) {

		const conditions = [
			{
				ownerId: go.User.id,
			},
			{
				global: true
			}
		];

		if(ids.length) {
			conditions.push({
				thoughtlistId: ids
			});
		}

		this.categoriesGrid.store.setFilter('thoughtlist',{
			operator: "or",
			conditions: conditions
		}).load();
	},

	onThoughtlistSelectionChange : function (ids, sm) {

		this.checkCreateThoughtList();

		this.filterCategories(ids);

		//		this.thoughtGrid.store.setFilter("role", ids.length == 0 ? {role:  go.modules.tutorial.gtd.listTypes.List} : null);
		this.thoughtGrid.store.setFilter("role", ids.length == 0 ? {role:  !this.support ? go.modules.tutorial.gtd.listTypes.List : go.modules.tutorial.gtd.listTypes.Support} : null);

		const settings = this.getSettings();
		if(settings.rememberLastItems && settings.lastThoughtlistIds.join(",") != ids.join(",")) {

			go.Db.store("User").save({
				[this.support ? "supportSettings" : "thoughtsSettings"]: {
					lastThoughtlistIds: ids
				}
			}, go.User.id);

		}
	},

	checkCreateThoughtList: function() {
		this.addThoughtlistId = undefined;
		go.Db.store(this.support ? "SupportList" : "ThoughtList").get(this.thoughtlistsGrid.getSelectedIds()).then((result) => {

			result.entities.forEach((thoughtlist) => {
				if (!this.addThoughtlistId && thoughtlist.permissionLevel >= go.permissionLevels.create) {
					this.addThoughtlistId = thoughtlist.id;
				}
			});

			if(!this.addThoughtlistId) {
				this.addThoughtlistId = this.getSettings().defaultThoughtlistId;
			}

			this.addButton.setDisabled(!this.addThoughtlistId);
		});
	},

	
	onThoughtGridDblClick : function (grid, rowIndex, e) {

		const record = grid.getStore().getAt(rowIndex);
		if (record.get('permissionLevel') < go.permissionLevels.write) {
			return;
		}

		let dlg = new go.modules.tutorial.gtd.ThoughtDialog({
			entityStore: this.support ? "SupportTicket" : "Thought",
			role: this.support ? "support" : "list"
		});
		dlg.load(record.id).show();
	},
	
	onThoughtGridKeyPress : function(e) {
		if(e.keyCode != e.ENTER) {
			return;
		}
		var record = this.thoughtGrid.getSelectionModel().getSelected();
		if(!record) {
			return;
		}

		if (record.get('permissionLevel') < go.permissionLevels.write) {
			return;
		}

		const dlg = new go.modules.tutorial.gtd.ThoughtDialog({
			role: this.support ? "support" : "list",
			entityStore: this.support ? "SupportTicket" : "Thought",
		});
		dlg.load(record.id).show();
	}	
});

