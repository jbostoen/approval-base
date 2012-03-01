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
	'Approval:Tab:Intro-ongoing' => 'An approval is ongoing since %1$s',
	'Approval:Tab:Intro-ended' => 'An approval process has been executed between %1$s and %2$s',
	'Approval:Tab:Step' => 'Step #%1$d',
	'Approval:Tab:StepSumary-Ongoing' => 'Waiting for the replies',
	'Approval:Tab:StepSumary-OK' => 'Approved',
	'Approval:Tab:StepSumary-KO' => 'Rejected',
	'Approval:Tab:StepSumary-OK-Timeout' => 'Approved implicitely (run out of time)',
	'Approval:Tab:StepSumary-KO-Timeout' => 'Rejected implicitely (run out of time)',
	'Approval:Tab:Col-Approver' => 'Approver',
	'Approval:Tab:Col-Answer' => 'Answer',

	'Approval:Form:Title' => 'Approval form',
	'Approval:Form:Btn-Approve' => 'Approve',
	'Approval:Form:Btn-Reject' => 'Reject',

	'Approval:Form:AlreadyApproved' => 'Sorry, the approval process for %1$s is already complete with result: Approved.',
	'Approval:Form:AlreadyRejected' => 'Sorry, the approval process for %1$s is already complete with result: Rejected.',

	'Approval:Form:StepApproved' => 'Sorry, the approval step for %1$s is already complete with result: Approved. The approval process in continuing.',
	'Approval:Form:StepRejected' => 'Sorry, the approval step for %1$s is already complete with result: Rejected. The approval process in continuing.',

	'Approval:Form:AnswerRecorded-Continue' => 'Your answer has been recorded. The approval process in continuing.',
	'Approval:Form:AnswerRecorded-Approved' => 'Your answer has been recorded: the approval is complete with success.',
	'Approval:Form:AnswerRecorded-Rejected' => 'Your answer has been recorded: the approval is complete with failure.',
));
?>
