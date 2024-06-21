go.Modules.register("community", "thoughts", {
	mainPanel: "go.modules.tutorial.gtd.MainPanel",
	title: t("Thoughts"),
	entities: ["ThoughtListGrouping", "ThoughtCategory","PortletThoughtlist","Settings",{
		name: "ThoughtList",
		relations: {
			group: {store: "ThoughtListGrouping", fk: "groupingId"},
			creator: {store: "UserDisplay", fk: "createdBy"},
			groups: {name: 'Groups'}
		}
	}, {
		name: "Thought",
		links: [{
			iconCls: "entity ic-check",
			linkWindow: function (entity, entityId) {
				return new go.modules.tutorial.gtd.ThoughtDialog();
			},

			linkDetail: function () {
				return new go.modules.tutorial.gtd.ThoughtDetail();
			},

			linkDetailCards: function () {

				const incomplete = new go.modules.tutorial.gtd.ThoughtLinkDetail({
					title:  t("Incomplete thoughts"),
					link: {
						entity: "Thought",
						filter: null
					}
				});

				incomplete.store.setFilter('completed',{complete:  false});

				const completed = 	new go.modules.tutorial.gtd.ThoughtLinkDetail({

					title:  t("Completed thoughts"),
					link: {
						entity: "Thought",
						filter: null
					}
				});
				completed.store.setFilter('completed',{complete:  true});

				return [
					incomplete,

					completed]
			}
		}],
		relations: {
			creator: {store: "UserDisplay", fk: "createdBy"},
			modifier: {store: "UserDisplay", fk: "modifiedBy"},
			responsible: {store: 'UserDisplay', fk: 'responsibleUserId'},
			thoughtlist: {store: 'ThoughtList', fk: 'thoughtlistId'},
			categories: {store: "ThoughtCategory", fk: "categories"},
		},

		/**
		 * Filter definitions
		 *
		 * Will be used by query fields where you can use these like:
		 *
		 * name: Piet,John age: < 40
		 *
		 * Or when adding custom saved filters.
		 */
		filters: [
			{
				wildcards: false,
				name: 'text',
				type: "string",
				multiple: false,
				title: t("Query")
			},
			{
				title: t("Commented at"),
				name: 'commentedat',
				multiple: false,
				type: 'date'
			}, {
				title: t("Modified at"),
				name: 'modifiedat',
				multiple: false,
				type: 'date'
			}, {
				title: t("Modified by"),
				name: 'modifiedBy',
				multiple: true,
				type: 'go.users.UserCombo',
				typeConfig: {value: null}
			}, {
				title: t("Created at"),
				name: 'createdat',
				multiple: false,
				type: 'date'
			}, {
				title: t("Created by"),
				name: 'createdby',
				multiple: true,
				type: 'go.users.UserCombo',
				typeConfig: {value: null}
			},
			{
				title: t("List"),
				name: 'thoughtlistid',
				multiple: false,
				type: "go.modules.tutorial.gtd.ThoughtlistCombo"
			},
			{
				title: t("Progress"),
				name: 'progress',
				multiple: false,
				type: "go.modules.tutorial.gtd.ProgressCombo"
			},
			{
				name: 'title',
				title: t("Title"),
				type: "string",
				multiple: true
			},{
				title: t("Due"),
				name: 'due',
				multiple: false,
				type: 'date'
			},{
				title: t("Start"),
				name: 'start',
				multiple: false,
				type: 'date'
			},{
				title: t("Responsible"),
				name: 'responsibleUserId',
				multiple: false,
				type: 'go.users.UserCombo',
				typeConfig: {value: null}
			}]

	}],
	initModule: function () {
		go.Alerts.on("beforeshow", function(alerts, alertConfig) {
			const alert = alertConfig.alert;
			if(alert.entity == "Thought" || alert.entity == "SupportTicket") {

				switch(alert.tag) {
					case "assigned":
						//replace panel promise
						alertConfig.panelPromise = alertConfig.panelPromise.then(async (panelCfg) => {
							let assigner;
							try {
								assigner = await go.Db.store("UserDisplay").single(alert.data.assignedBy);
							} catch (e) {
								assigner = {displayName: t("Unknown user")};
							}

							const msg = go.util.Format.dateTime(alert.triggerAt) + ": " +t("You were assigned to this thought by {assigner}").replace("{assigner}", assigner.displayName);
							panelCfg.items = [{html: msg }];
							panelCfg.notificationBody = msg;
							return panelCfg;
						});
						break;

					case "createdforyou":
//replace panel promise
						alertConfig.panelPromise = alertConfig.panelPromise.then(async (panelCfg) => {

							let creator;
							try {
								creator = await go.Db.store("UserDisplay").single(alert.data.createdBy);
							} catch (e) {
								creator = {displayName: t("Unknown user")};
							}

							const msg = go.util.Format.dateTime(alert.triggerAt) + ": " +t("A new thought was created in your list by {creator}").replace("{creator}", creator.displayName);
							panelCfg.items = [{html: msg}];
							panelCfg.notificationBody = msg
							return panelCfg;

						});
						break;
				}

			}
		});


		async function showBadge() {
			const count = await go.Jmap.request({method: "Thought/countMine"});

			GO.mainLayout.setNotification('thoughts', count,'orange');
		}

		GO.mainLayout.on("authenticated", () => {
			if(go.Modules.isAvailable("community", "thoughts")) {

				go.Db.store("Thought").on("changes", () => {
					showBadge();
				});

				showBadge();
			}
		})

	},


	userSettingsPanels: [
		"go.modules.tutorial.gtd.SettingsPanel"
	]
});

go.modules.tutorial.gtd.progress = {
	'needs-action' : t('Needs action'),
	'in-progress': t('In progress'),
	'completed': t('Completed'),
	'failed': t('Failed'),
	'cancelled' : t('Cancelled')
};

go.modules.tutorial.gtd.listTypes = {
	List : "list",
	Board : "board",
	Project : "project",
	Support : "support"

}
