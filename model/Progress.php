<?php

namespace go\modules\tutorial\gtd\model;

/**
 * Progress
 *
 * Defines the progress of a thought(list).
 * If omitted, the default progress is defined as follows (in order of evaluation):
 * - "completed": if the "progress" property value of all thoughts is "completed".
 * - "failed": if at least one "progress" property value of a thought is "failed".
 * - "in-process": if at least one "progress" property value of a thought is "in-process".
 * - "needs-action": If none of the other criteria match.
 */
abstract class Progress
{
    const NeedsAction = 1; // Indicates the thought needs action.
    const InProcess = 2;    // Indicates the thought is in process.
    const Completed = 3;      // Indicates the thought is completed.
    const Failed = 4;            // Indicates the thought failed.
    const Cancelled = 5;      // Indicates the thought was cancelled.

	static $db = [
		self::NeedsAction => 'needs-action',
		self::InProcess => 'in-progress',
		self::Completed => 'completed',
		self::Failed => 'failed',
		self::Cancelled => 'cancelled'
	];
}