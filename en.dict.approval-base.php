<?php
// Copyright (C) 2012 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

/**
 * Localized data
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */

Dict::Add('EN US', 'English', 'English', array(
	'Approval:Tab:Title' => 'Approval status',
	'Approval:Tab:Start' => 'Start',
	'Approval:Tab:End' => 'End',
	'Approval:Tab:StepEnd-Limit' => 'Time limit (implicit result)',
	'Approval:Tab:StepEnd-Theoretical' => 'Theoretical time limit (duration limited to %1$s mn)',
	'Approval:Tab:StepSumary-Ongoing' => 'Waiting for the replies',
	'Approval:Tab:StepSumary-OK' => 'Approved',
	'Approval:Tab:StepSumary-KO' => 'Rejected',
	'Approval:Tab:StepSumary-OK-Timeout' => 'Approved implicitely (run out of time)',
	'Approval:Tab:StepSumary-KO-Timeout' => 'Rejected implicitely (run out of time)',
	'Approval:Tab:StepSumary-Idle' => 'Not started',
	'Approval:Tab:StepSumary-Skipped' => 'Skipped',

	'Approval:Tab:Error' => 'An error occured during the approval process: %1$s',

	'Approval:Error:Email' => 'The email could not be sent (%1$s)',

	'Approval:Action-Approve' => 'Approve',
	'Approval:Action-Reject' => 'Reject',
	'Approval:Action-ViewMoreInfo' => 'View more information',

	'Approval:Form:Title' => 'Approval',
	'Approval:Form:Ref' => 'Approval process for %1$s',

	'Approval:Form:ApproverDeleted' => 'Sorry, the record corresponding to your identity has been deleted.',
	'Approval:Form:ObjectDeleted' => 'Sorry, the object of the approval has been deleted.',

	'Approval:Form:AlreadyApproved' => 'Sorry, the process has already been completed with result: Approved.',
	'Approval:Form:AlreadyRejected' => 'Sorry, the process has already been completed with result: Rejected.',

	'Approval:Form:StepApproved' => 'Sorry, this phase has been completed with result: Approved. The approval process is continuing...',
	'Approval:Form:StepRejected' => 'Sorry, this phase has been completed with result: Rejected. The approval process is continuing...',

	'Approval:Form:AnswerRecorded-Continue' => 'Your answer has been recorded. The approval process is continuing.',
	'Approval:Form:AnswerRecorded-Approved' => 'Your answer has been recorded: the approval is now complete with success.',
	'Approval:Form:AnswerRecorded-Rejected' => 'Your answer has been recorded: the approval is now complete with failure.',

	'Approval:ChangeTracking-MoreInfo' => 'Approval process',

	'Approval:Ongoing-Title' => 'Ongoing approvals',
	'Approval:Ongoing-Title+' => 'Approval processes for objects of class %1$s',
	'Approval:Ongoing-NothingCurrently' => 'There is no ongoing approval.',
));
?>
