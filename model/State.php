<?php

namespace go\modules\tutorial\gtd\model;

/**
 * Progress
 *
 * Defines the state of a thought(list).
 */
abstract class State
{
    const NeedsAction = 1; // Indicates the thought needs action.
    const Cancelled = 2;    // Indicates the thought was cancelled.
    const Completed = 3;      // Indicates the thought is completed.
    const Delegated = 4;            // Indicates the thought has been delgated to another person
    const Reference = 5;      // Indicates the thought is saved as a reference.
	const Scheduled = 6; 	// Indicates the thought has been scheduled to become a project
	const Backlog = 7; 		//Indicates the thought is saved to a backlog

	static $db = [
		self::NeedsAction => 'needs-action',
		self::Cancelled => 'cancelled',
		self::Completed => 'completed',
		self::Delegated => 'delegated',
		self::Reference => 'reference',
		self::Scheduled => 'scheduled',
		self::Backlog => 'backlog'
	];
}