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
 * Module approval-base
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */



/**
 * An approval process associated to an object
 * Derive this class to implement an approval process
 * - A few abstract functions have to be defined to implement parallel and/or serialize approvals
 * - Advanced behavior can be implemented by overloading some of the methods (e.g. GetDisplayStatus to change the way it is displayed) 
 *    
 **/ 
abstract class ApprovalScheme extends DBObject
{
	public static function Init()
	{
		$aParams = array
		(
			"category" => "application",
			"key_type" => "autoincrement",
			"name_attcode" => array("obj_class", "obj_key"),
			"state_attcode" => "",
			"reconc_keys" => array(""),
			"db_table" => "approval_scheme",
			"db_key_field" => "id",
			"db_finalclass_field" => "",
			"display_template" => "",
		);
		MetaModel::Init_Params($aParams);
		MetaModel::Init_InheritAttributes();

		MetaModel::Init_AddAttribute(new AttributeString("obj_class", array("allowed_values"=>null, "sql"=>"obj_class", "default_value"=>"", "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeInteger("obj_key", array("allowed_values"=>null, "sql"=>"obj_key", "default_value"=>"", "is_null_allowed"=>false, "depends_on"=>array())));

		MetaModel::Init_AddAttribute(new AttributeDateTime("started", array("allowed_values"=>null, "sql"=>"started", "default_value"=>"", "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeDateTime("ended", array("allowed_values"=>null, "sql"=>"ended", "default_value"=>"", "is_null_allowed"=>true, "depends_on"=>array())));

		MetaModel::Init_AddAttribute(new AttributeDeadline("timeout", array("allowed_values"=>null, "sql"=>"timeout", "default_value"=>"", "is_null_allowed"=>true, "depends_on"=>array())));

		MetaModel::Init_AddAttribute(new AttributeInteger("current_step", array("allowed_values"=>null, "sql"=>"current_step", "default_value"=>0, "is_null_allowed"=>false, "depends_on"=>array())));

		MetaModel::Init_AddAttribute(new AttributeEnum("status", array("allowed_values"=>new ValueSetEnum('ongoing,accepted,rejected'), "sql"=>"status", "default_value"=>"ongoing", "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("last_error", array("allowed_values"=>null, "sql"=>"last_error", "default_value"=>"", "is_null_allowed"=>true, "depends_on"=>array())));

		// Serialized array of steps (ordered)
		// A step is and array of
		//		'timeout_sec' => <integer> (0 if no timeout)
		//		'timeout_approve' => <boolean> (true by default, meaning "approve by default") 
		//		'status' => <string> (idle|ongoing|done|timedout) 
		//		'started' => <boolean> (entry missing if not started yet) 
		//		'ended' => <boolean> (entry missing if not complete yet) 
		//		'approved' => <boolean> (entry missing if not complete yet) 
		//		'approvers' => array of
		//			'class' => <string> 
		//			'id' => <integer>
		//			'passcode' => <string>
		//			'answer_time' => <unix time> (entry missing if no reply yet)
		//			'approval' => <boolean> (entry missing if no reply yet)
		// 
		MetaModel::Init_AddAttribute(new AttributeText("steps", array("allowed_values"=>null, "sql"=>"steps", "default_value"=>"", "is_null_allowed"=>false, "depends_on"=>array())));
	}

	/**
	 * Called when an object is entering a new state (or just created), and before it gets saved
	 * The approval scheme should be prepared depending on the target object:
	 * 	find the relevant approvers
	 * 	perform parallel approval (several approvers in one step)
	 * 	perform serialized approval (several steps)
	 * 	tune the timeouts
	 * Available helpers:
	 * 	AddStep(aApprovers, iTimeoutSec, bApproveOnTimeout)
	 * 		 	 	 	 	 	 	 
	 * @param object oObject  The object concerned
	 * @param string sReachingState The state that this object has just reached
	 * @return null if no approval process is needed, an instance of ApprovalScheme otherwise
	 */	 	
	public static function GetApprovalScheme($oObject, $sReachingState)
	{
		return null;
	}

	/**
	 * Called when the email is being created for a given approver
	 * 	 
	 * @param string sContactClass The approver object class
	 * @param string iContactId The approver object id
	 * @return string The subject in pure text
	 */	 	
	abstract public function GetEmailSubject($sContactClass, $iContactId);

	/**
	 * Called when the email is being created for a given approver
	 * 	 
	 * @param string sContactClass The approver object class
	 * @param string iContactId The approver object id
	 * @return string The email body in HTML
	 */	 	
	abstract public function GetEmailBody($sContactClass, $iContactId);

	/**
	 * Called when the form is being created for a given approver
	 * 	 
	 * @param string sContactClass The approver object class
	 * @param string iContactId The approver object id
	 * @return string The form body in HTML
	 */	 	
	public function GetFormBody($sContactClass, $iContactId)
	{
		return $this->GetEmailSubject($sContactClass, $iContactId);
	}

	/**
	 * Called when the approval is being completed with success
	 * 	 
	 * @param object oObject The object being under approval process
	 * @return void The object can be modified within this handler, it will be saved later on
	 */	 	
	abstract public function DoApprove(&$oObject);

	/**
	 * Called when the approval is being completed with failure
	 * 	 
	 * @param object oObject The object being under approval process
	 * @return void The object can be modified within this handler, it will be saved later on
	 */	 	
	abstract public function DoReject(&$oObject);

	/**
	 * Optionaly override this verb to change the way object details are displayed
	 * Appeared in Version 1.2 of the module 	 
	 *
	 * @return void
	 */	 	
	public function DisplayObjectDetails($oPage, $oApprover, $oObject, $oSubstitute = null)
	{
		if ($this->IsLoginMandatoryToSeeObjectDetails($oApprover, $oObject))
		{
			require_once(APPROOT.'/application/loginwebpage.class.inc.php');
			LoginWebPage::DoLogin(); // Check user rights and prompt if needed
		}
		$oObject->DisplayBareProperties($oPage/*, $bEditMode = false*/);
	}

	/**
	 * Optionaly override this verb to change the way the changes are tracked in the object history and in the case log (if the comment are copied there)
	 * Appeared in Version 1.2 of the module 	 
	 *
	 * @return void
	 */	 	
	public function GetIssuerInfo($bApproved, $oApprover, $oSubstitute = null)
	{
		if ($oSubstitute)
		{
			if ($bApproved)
			{
				$sRes = Dict::Format('Approval:Approved-On-behalf-of', $oSubstitute->Get('friendlyname'), $oApprover->Get('friendlyname'));
			}
			else
			{
				$sRes = Dict::Format('Approval:Rejected-On-behalf-of', $oSubstitute->Get('friendlyname'), $oApprover->Get('friendlyname'));
			}
		}
		else
		{
			if ($bApproved)
			{
				$sRes = Dict::Format('Approval:Approved-By', $oApprover->Get('friendlyname'));
			}
			else
			{
				$sRes = Dict::Format('Approval:Rejected-By', $oApprover->Get('friendlyname'));
			}
		}
		return $sRes;
	}

	/**
	 * Optionaly override this verb to change the way working hours will be computed
	 * Appeared in Version 1.1 of the module 	 
	 * 	 
	 * @return string Name of a class implementing the interface iWorkingTimeComputer
	 */	 	
	protected function GetWorkingTimeComputer()
	{
		// This class is provided as the default way to compute the active time, aka 24x7, 24 hours a day!
		return 'DefaultWorkingTimeComputer';
	}

	/**
	 * Can be overriden for simulation purposes (troubleshooting, tutorial)
	 */
	public function Now()
	{
		return time();
	}

	/**
	 * Helper to decode the approval sequences (steps)
	 */
	public function GetSteps()
	{
		$sStepsRaw = $this->Get('steps');
		if (empty($sStepsRaw))
		{
			$aSteps = array();
		}
		else
		{
			$aSteps = unserialize($sStepsRaw);
		}
		return $aSteps;
	}

	/**
	 * Helper to encode the approval sequences (steps)
	 */
	protected function SetSteps($aSteps)
	{
		$this->Set('steps', serialize($aSteps));
	}

	/**
	 * Official mean to declare a new step at the end of the existing sequence
	 * 	 
	 * @param array aContact An array of array('class' => ..., 'id' => ...)
	 * @param integer $iTimeoutSec The timeout duration if (0 to disable the timeout feature)
	 * @param boolean $bApproveOnTimeout Set to true to approve in case of timeout for the current step
	 * @return void
	 */
	public function AddStep($aContacts, $iTimeoutSec = 0, $bApproveOnTimeout = true, $iExitCondition = self::EXIT_ON_FIRST_REJECT)
	{
		$aApprovers = array();
		foreach($aContacts as $aApproverData)
		{
			if (!MetaModel::IsValidClass($aApproverData['class']))
			{
				throw new Exception("Approval plugin: Wrong class ".$aApproverData['class']." for the approver");
			}
			$aApproverStatus = array(
				'class' => $aApproverData['class'],
				'id' => $aApproverData['id'],
				'passcode' => mt_rand(11111,99999),
			);
			if (array_key_exists('forward', $aApproverData))
			{
				$aApproverStatus['forward'] = array();
				foreach($aApproverData['forward'] as $aSubstituteData)
				{
					if (!MetaModel::IsValidClass($aSubstituteData['class']))
					{
						throw new Exception("Approval plugin: Wrong class ".$aApproverData['class']." for the approver");
					}
					$aSubstituteStatus = array(
						'class' => $aSubstituteData['class'],
						'id' => $aSubstituteData['id'],
						'passcode' => mt_rand(11111,99999),
						'timeout_percent' => $aSubstituteData['timeout_percent'],
					);
					if (array_key_exists('role', $aSubstituteData))
					{
						$aSubstituteStatus['role'] = $aSubstituteData['role'];
					}
					$aApproverStatus['forward'][] = $aSubstituteStatus;
				}
			}
			$aApprovers[] = $aApproverStatus;
		}

		$aNewStep = array(
			'timeout_sec' => $iTimeoutSec,
			'timeout_approve' => $bApproveOnTimeout,
			'exit_condition' => $iExitCondition,
			'status' => 'idle', 
			'approvers' => $aApprovers,
		);

		$aSteps = $this->GetSteps();
		$aSteps[] = $aNewStep;
		$this->SetSteps($aSteps);
	}

	/**
	 * Display the status as an additional tab in the given page
	 */	 	
	public function DisplayStatus($oPage)
	{
		$oPage->SetCurrentTab(Dict::S('Approval:Tab:Title'));
		$oPage->p($this->GetDisplayStatus($oPage));
	}

	/**
	 * Render the status in HTML
	 */	 	
	public function GetDisplayStatus($oPage)
	{
		$sImgOngoing = utils::GetAbsoluteUrlModulesRoot().'approval-base/waiting-reply.png';
		$sImgApproved = utils::GetAbsoluteUrlModulesRoot().'approval-base/approve.png';
		$sImgRejected = utils::GetAbsoluteUrlModulesRoot().'approval-base/reject.png';
		$sImgArrow = utils::GetAbsoluteUrlModulesRoot().'approval-base/arrow-next.png';
		$sImgBubbleTriangle = utils::GetAbsoluteUrlModulesRoot().'approval-base/bubble-triangle.png';

		$oPage->add_style(
<<<EOF
.approval-step-idle {
	background-color: #F6F6F1;
	opacity: 0.4;
	border-style: dashed;
	border-width: 1px;
	padding:10px;	
}
.approval-step-start {
	background-color: #F6F6F1;
	border-style: solid;
	border-width: 1px;
	padding:10px;	
}
.approval-step-ongoing {
	background-color: #F6F6F1;
	border-style: double;
	border-width: 5px;
	padding:10px;	
}
.approval-step-done-ok {
	background-color: #F6F6F1;
	border-style: solid;
	border-width: 2px;
	padding:10px;	
	border-color: #69BB69;
}
.approval-step-done-ko {
	background-color: #F6F6F1;
	border-style: solid;
	border-width: 2px;
	padding:10px;
	border-color: #BB6969;
}
.approval-idle{
	opacity: 0.4;
}
.approval-timelimit {
	font-weight: bolder;
}
.approval-theoreticallimit {
	opacity: 0.4;
}
.approval-step-header {
	margin: 5px;
	font-weight: bolder;
}
div.approver-label {
	padding: 10px;
	padding-left: 16px;
	margin: 5px;
	margin-right: 0;
	background-color: #A5CAFF;
	-moz-border-radius: 6px;
	-webkit-border-radius: 6px;
	border-radius: 6px;
}
div.approver-answer {
	padding: 10px;
	padding-left: 0px;
	padding-top: 13px;
}
div.approver-with-substitutes {
	background: url(../images/minus.gif) no-repeat left;
	cursor: pointer;	
	padding-left: 15px;
}
div.approver-with-substitutes-closed {
	background: url(../images/plus.gif) no-repeat left;
}
tr.approval-substitutes td div{
	padding-left: 15px;
}
.approval-substitutes.closed {
	display: none;
}
EOF
		);
		$sArrow = "<img src=\"$sImgArrow\" style=\"vertical-align:middle;\">";

		// Build the list of display information
		$aDisplayData = array();

		$aDisplayData[] = array(
			'date_html' => null,
			'time_html' => null,
			'content_html' => "<div class=\"approval-step-start\">".Dict::S('Approval:Tab:Start')."</div>\n",
		);

		$iStarted = AttributeDateTime::GetAsUnixSeconds($this->Get('started'));
		$iLastEnd = $iStarted;

		$sStarted = $this->GetDisplayTime($iStarted);
		$sCurrDay = $this->GetDisplayDay($iStarted);
		$aDisplayData[] = array(
			'date_html' => $sCurrDay,
			'time_html' => $sStarted,
			'content_html' => $sArrow,
		);

		foreach($this->GetSteps() as $iStep => $aStepData)
		{
			switch ($aStepData['status'])
			{
			case 'done':
			case 'timedout':
				$iStepEnd = $aStepData['ended'];
				$sTimeClass = '';
				$sTimeInfo = '';

				if ($aStepData['approved'])
				{
					$sDivClass = "approval-step-done-ok";
					if ($aStepData['status'] == 'timedout')
					{
						$sStepSumary = Dict::S('Approval:Tab:StepSumary-OK-Timeout');
					}
					else
					{
						$sStepSumary = Dict::S('Approval:Tab:StepSumary-OK');
					}
				}
				else
				{
					$sDivClass = "approval-step-done-ko";
					if ($aStepData['status'] == 'timedout')
					{
						$sStepSumary = Dict::S('Approval:Tab:StepSumary-KO-Timeout');
					}
					else
					{
						$sStepSumary = Dict::S('Approval:Tab:StepSumary-KO');
					}
				}
				$sArrowDivClass = "";
				break;

			case 'ongoing':
				if ($iLastEnd && $aStepData['timeout_sec'] > 0)
				{
					$iStepEnd = $this->ComputeDeadline($iLastEnd, $aStepData['timeout_sec']);
					$sTimeClass = 'approval-timelimit';
					$sTimeInfo = Dict::S('Approval:Tab:StepEnd-Limit');
				}
				else
				{
					// The limit cannot be determined
					$iStepEnd = 0;
					$sTimeClass = '';
					$sTimeInfo = '';
				}

				$sStepSumary = Dict::S('Approval:Tab:StepSumary-Ongoing');
				$sDivClass = "approval-step-ongoing";
				$sArrowDivClass = "approval-idle";
				break;

			case 'idle':
			default:
				if ($this->Get('status') == 'ongoing')
				{			
					if ($iLastEnd && $aStepData['timeout_sec'] > 0)
					{
						$iStepEnd = $this->ComputeDeadline($iLastEnd, $aStepData['timeout_sec']);
						$sTimeClass = 'approval-theoreticallimit';
						$sTimeInfo = Dict::Format('Approval:Tab:StepEnd-Theoretical', round($aStepData['timeout_sec'] / 60));
					}
					else
					{
						// The limit cannot be determined
						$iStepEnd = 0;
						$sTimeClass = '';
						$sTimeInfo = '';
					}
				}
				else
				{
					// The process has been terminated before this step
					$iStepEnd = 0;
					$sTimeClass = '';
					$sTimeInfo = '';
				}

				if ($this->Get('status') == 'ongoing')
				{
					$sStepSumary = Dict::S('Approval:Tab:StepSumary-Idle');
					$sDivClass = "approval-step-idle";
					$sArrowDivClass = "approval-idle";
				}
				else
				{
					$sStepSumary = Dict::S('Approval:Tab:StepSumary-Skipped');
					$sDivClass = "approval-step-idle";
					$sArrowDivClass = "approval-idle";
				}
				break;
			}
			$iLastEnd = $iStepEnd;

			$sStepHtml = '<div class="approval-step-header">'.$sStepSumary.'</div>';
			$sStepHtml .= '<table style="border-collapse: collapse;">';
			foreach($aStepData['approvers'] as $aApproverData)
			{
				$oApprover = MetaModel::GetObject($aApproverData['class'], $aApproverData['id'], false);
				if ($oApprover)
				{
					//$sApprover = $oApprover->GetHyperLink();
					$sApprover = $oApprover->GetName();
				}
				else
				{
					$sApprover = $aApproverData['class'].'::'.$aApproverData['id'];
				}
				if (array_key_exists('approval', $aApproverData))
				{
					$bApproved = $aApproverData['approval'];
					$sTitleHtml = $this->GetDisplayTime($aApproverData['answer_time']);
					if (isset($aApproverData['comment']) && $aApproverData['comment'] != '')
					{
						$sTitleHtml .= '<br/>'.str_replace(array("\r\n", "\n", "\r"), "<br/>", htmlentities($aApproverData['comment'], ENT_QUOTES, 'UTF-8'));
					}
					if ($bApproved)
					{
						$sAnswer = "<img src=\"$sImgApproved\">";
					}
					else
					{
						$sAnswer = "<img src=\"$sImgRejected\">";
					}
					$sTitleEsc = addslashes($sTitleHtml);
					$oPage->add_ready_script("$('#answer_$iStep"."_".$aApproverData['id']."').tooltip({items: 'div>img', content: '$sTitleEsc'});");
				}
				else
				{
					$sAnswer = "<img src=\"$sImgOngoing\">";
					if (($aStepData['status'] == 'ongoing') && !array_key_exists('forward', $aApproverData))
					{
						// Surround the icon with some meta data to allow a reply here
						$sAnswer = "<span class=\"approval-replier\" approver_class=\"{$aApproverData['class']}\" approver_id=\"{$aApproverData['id']}\">$sAnswer</span>";
					}
				}
				if (array_key_exists('forward', $aApproverData))
				{
					$bShowClosed = true;
					static $iId = 0;
					$sId = "substitutes_".$iId++;

					if (array_key_exists('replier_index', $aApproverData))
					{
						$sApproverAnswer = "<img src=\"$sImgOngoing\">";
					}
					else
					{
						// The answer is the one of the main approver
						$sApproverAnswer = $sAnswer;
					}
					if (($aStepData['status'] == 'ongoing') && !array_key_exists('approval', $aApproverData))
					{
						// Surround the icon with some meta data to allow a reply here
						$sApproverAnswer = "<span class=\"approval-replier\" approver_class=\"{$aApproverData['class']}\" approver_id=\"{$aApproverData['id']}\">$sApproverAnswer</span>";
					}

					$sApprover = "<div class=\"approver-with-substitutes\" id=\"{$sId}\">".$sApprover.'</div>';
					$sSubstitutes = "<table id=\"content_$sId\">";
					$sSubstitutes .= '<tr>';
					$sSubstitutes .= '<td>'.$sApprover.'</td>';
					$sSubstitutes .= '<td class="approval-substitutes">'.$sApproverAnswer.'</td>';
					$sSubstitutes .= '</tr>';

					foreach ($aApproverData['forward'] as $iReplierIndex => $aForwardData)
					{
						$oSubstitute = MetaModel::GetObject($aForwardData['class'], $aForwardData['id'], false);
						if ($oSubstitute)
						{
							//$sSubstitute = $oSubstitute->GetHyperLink();
							$sSubstitute = $oSubstitute->GetName();
						}
						else
						{
							$sSubstitute = $aForwardData['class'].'::'.$aForwardData['id'];
						}
						$sRole = isset($aForwardData['role']) ? ' ('.$aForwardData['role'].')' : '';

						if (array_key_exists('replier_index', $aApproverData) && ($iReplierIndex == $aApproverData['replier_index']))
						{
							// The result is known and this replier is the one who did answer
							$sSubstituteAnswer = $sAnswer;
							$sSubstituteClass = "";
							$bShowClosed = false;
						}
						elseif(array_key_exists('sent_time', $aForwardData))
						{
							$sSubstituteAnswer = "<img src=\"$sImgOngoing\">";
							$sSubstituteClass = "";
							$bShowClosed = false;
							if (($aStepData['status'] == 'ongoing') && !array_key_exists('approval', $aApproverData))
							{
								// Surround the icon with some meta data to allow a reply here
								$sSubstituteAnswer = "<span class=\"approval-replier\" approver_class=\"{$aApproverData['class']}\" approver_id=\"{$aApproverData['id']}\" substitute_class=\"{$aForwardData['class']}\" substitute_id=\"{$aForwardData['id']}\">$sSubstituteAnswer</span>";
							}
						}
						else
						{
							$sSubstituteAnswer = '';
							$sSubstituteClass = "approval-idle";
						}
						$sSubstitutes .= '<tr class="approval-substitutes">';
						//$sSubstitutes .= '<td>'.$aForwardData['timeout_percent'].'%: '.$sSubstitute.$sRole.'</td>';
						$sSubstitutes .= "<td><div class=\"$sSubstituteClass\">".$sSubstitute.$sRole.'</div></td>';
						$sSubstitutes .= '<td>'.$sSubstituteAnswer.'</td>';
						$sSubstitutes .= '</tr>';
					}		
					$sSubstitutes .= '</table>';

					$sApprover = $sSubstitutes;
					$oPage->add_ready_script("$('#{$sId}').click( function() { $('#content_{$sId} .approval-substitutes').toggleClass('closed'); } );\n");
					$oPage->add_ready_script("$('#{$sId}').click( function() { $(this).toggleClass('approver-with-substitutes-closed'); } );\n");
					if ($bShowClosed)
					{
						// Close it for the first display
						$oPage->add_ready_script("$('#content_{$sId} .approval-substitutes').toggleClass('closed');");
						$oPage->add_ready_script("$('#{$sId}').toggleClass('approver-with-substitutes-closed');");
					}
				}
				$sStepHtml .= '<tr>';
				$sStepHtml .= '<td style="vertical-align: top;"><div class="approver-label">'.$sApprover.'</div></td>';
				if (strlen($sAnswer) > 0)
				{
					$sTriangle = "<img src=\"$sImgBubbleTriangle\">";
					$sStepHtml .= '<td style="vertical-align: top;"><div class="approver-answer" id="answer_'.$iStep.'_'.$aApproverData['id'].'">'.$sTriangle.$sAnswer.'</div></td>';
				}
				else
				{
					$sStepHtml .= '<td>&nbsp;</td>';
				}
				$sStepHtml .= '</tr>';
			}
			$sStepHtml .= '</table>';

			$aDisplayData[] = array(
				'date_html' => null,
				'time_html' => null,
				'content_html' => "<div class=\"$sDivClass\">$sStepHtml</div>\n",
			);

			// New feature: the array entry 'exit_condition' might be missing
			$iExitCondition = isset($aStepData['exit_condition']) ? $aStepData['exit_condition'] : self::EXIT_ON_FIRST_REJECT;
			switch($iExitCondition)
			{
				case self::EXIT_ON_FIRST_REPLY:
				$sExplainCondition = Dict::S('Approval:Tab:StepEnd-Condition-FirstReply');
				break;
	
				case self::EXIT_ON_FIRST_APPROVE:
				$sExplainCondition = Dict::S('Approval:Tab:StepEnd-Condition-FirstApprove');
				break;
	
				case self::EXIT_ON_FIRST_REJECT:
				default:
				$sExplainCondition = Dict::S('Approval:Tab:StepEnd-Condition-FirstReject');
				break;
			}
			if ($iStepEnd)
			{
				// Display the date iif it has changed
				//
				if ($this->GetDisplayDay($iStepEnd) != $sCurrDay)
				{
					$sStepEndDate = $this->GetDisplayDay($iStepEnd);
					$sCurrDay = $sStepEndDate;
				}
				else
				{
					// Same day
					$sStepEndDate = '&nbsp;';
				}
	
				$aDisplayData[] = array(
					'date_html' => '<span class="'.$sTimeClass.'" title="'.$sTimeInfo.'">'.$sStepEndDate.'</span>',
					'time_html' => '<span class="'.$sTimeClass.'" title="'.$sTimeInfo.'">'.$this->GetDisplayTime($iStepEnd).'</span>',
					'content_html' => "<div class=\"$sArrowDivClass\" title=\"$sExplainCondition\">$sArrow</div>\n",
				);
			}
			else
			{
				$aDisplayData[] = array(
					'date_html' => '',
					'time_html' => '',
					'content_html' => "<div class=\"$sArrowDivClass\" title=\"$sExplainCondition\">$sArrow</div>\n",
				);
			}
		}

		switch ($this->Get('status'))
		{
		case 'ongoing':
			$sFinalStatus = "<img style=\"display: inline-block; vertical-align:middle;\" src=\"$sImgOngoing\">";
			$sDivClass = "approval-step-idle";
			break;
		case 'accepted':
			$sFinalStatus = "<img style=\"display: inline-block; vertical-align:middle;\" src=\"$sImgApproved\">";
			$sDivClass = "approval-step-done-ok";
			break;
		case 'rejected':
			$sFinalStatus = "<img style=\"display: inline-block; vertical-align:middle;\" src=\"$sImgRejected\">";
			$sDivClass = "approval-step-done-ko";
			break;
		}
		$aDisplayData[] = array(
			'date_html' => null,
			'time_html' => null,
			'content_html' => "<div class=\"$sDivClass\"><div style=\"display: inline-block; vertical-align: middle;\">".Dict::S('Approval:Tab:End').": </div>&nbsp;$sFinalStatus</div>\n",
		);

		// Diplay the information
		//
		$sHtml = '';
		$sHtml .= "<table>\n";
		$sHtml .= "<tr>\n";
		$sHtml .= "<td colspan=\"2\"></td>\n";
		foreach($aDisplayData as $aDisplayEvent)
		{
			if (!is_null($aDisplayEvent['date_html']))
			{
				if (strlen($aDisplayEvent['date_html']) > 0)
				{
					$sHtml .= "<td colspan=\"2\">".$aDisplayEvent['date_html']."</td>\n";
				}
				else
				{
					$sHtml .= "<td colspan=\"2\">&nbsp;</td>\n";
				}
			}
		}		
		$sHtml .= "</tr>\n";
		$sHtml .= "<tr>\n";
		$sHtml .= "<td colspan=\"2\"></td>\n";
		foreach($aDisplayData as $aDisplayEvent)
		{
			if (!is_null($aDisplayEvent['time_html']))
			{
				if (strlen($aDisplayEvent['time_html']) > 0)
				{
					$sHtml .= "<td colspan=\"2\">".$aDisplayEvent['time_html']."</td>\n";
				}
				else
				{
					$sHtml .= "<td>&nbsp;</td>\n";
				}
			}
		}		
		$sHtml .= "</tr>\n";
		$sHtml .= "<tr style=\"vertical-align:middle;\">\n";
		$sHtml .= "<td></td>\n";
		foreach($aDisplayData as $aDisplayEvent)
		{
			if ($aDisplayEvent['content_html'])
			{
				$sHtml .= "<td>".$aDisplayEvent['content_html']."</td>\n";
			}
		}		
		$sHtml .= "</tr>\n";
		$sHtml .= "</table>\n";

		$sLastError = $this->Get('last_error');
		if (strlen($sLastError) > 0)
		{
			$sHtml .= '<p>'.Dict::Format('Approval:Tab:Error', $sLastError).'</p>';
		}

		return $sHtml;
	}

	/**
	 * Start the step <current_step>, or terminates if either...
	 * - the last step executed has been rejected
	 * - there is no more step to process
	 * 
	 * On termination: determines + records the final status
	 * 	 and invokes the relevant verb (DoApprove/DoReject)	 	 
	 */	 
	public function StartNextStep()
	{
		$aSteps = $this->GetSteps();
		$iCurrentStep = $this->Get('current_step');

		// Determine the status for the previous step (if any)
		//
		if (array_key_exists($iCurrentStep - 1, $aSteps))
		{
			$aPrevStep = $aSteps[$iCurrentStep - 1];
			$bPrevApproved = $aPrevStep['approved'];
		}
		else
		{
			// Starting...
			$bPrevApproved = true;
		}

		if ($bPrevApproved && array_key_exists($iCurrentStep, $aSteps))
		{
			// Actually continue with the next step
			//
			$aStepData = &$aSteps[$iCurrentStep];
			$aStepData['status'] = 'ongoing';
			$aStepData['started'] = $this->Now();

			$oObject = MetaModel::GetObject($this->Get('obj_class'), $this->Get('obj_key'));
			foreach($aStepData['approvers'] as &$aApproverData)
			{
				$oApprover = MetaModel::GetObject($aApproverData['class'], $aApproverData['id'], false);
				if ($oApprover)
				{
					$this->SendApprovalInvitation($oApprover, $oObject, $aApproverData['passcode']);
				}
			}
			$this->SetSteps($aSteps);
			$this->Set('timeout', $this->ComputeTimeout());
			$this->DBUpdate();
		}
		else
		{
			// Done !
			//
			$this->Set('ended', $this->Now());
			$this->Set('status', $bPrevApproved ? 'accepted' : 'rejected');
			$this->DBUpdate();

			if ($oObject = MetaModel::GetObject($this->Get('obj_class'), $this->Get('obj_key'), false))
			{
				if ($bPrevApproved)
				{
					$this->DoApprove($oObject);
				}
				else
				{
					$this->DoReject($oObject);
				}
				if ($oObject->IsModified())
				{
					$oObject->DBUpdate();
				}
			}
		}
	}

	/**
	 * Processes a vote given by an approver:
	 * - find the approver
	 * - record the answer
	 * Then, start the next step if the current one is over 
	 */	 
	public function OnAnswer($iStep, $oApprover, $bApprove, $oSubstitute = null, $sComment = '')
	{
		if ($this->Get('status') != 'ongoing')
		{
			return;
		}

		$aSteps = $this->GetSteps();
		$iCurrentStep = $this->Get('current_step');
		if (!array_key_exists($iCurrentStep, $aSteps))
		{
			return;
		}
		$aStepData = &$aSteps[$iCurrentStep];
		foreach($aStepData['approvers'] as &$aApproverData)
		{
			if (($aApproverData['class'] == get_class($oApprover)) && ($aApproverData['id'] == $oApprover->GetKey()))
			{
				// Record the approval result
				//
				$aApproverData['approval'] = $bApprove;
				$aApproverData['answer_time'] = $this->Now();
				if ($sComment != '')
				{
					$aApproverData['comment'] = $sComment;

					$sAttCode = MetaModel::GetModuleSetting('approval-base', 'comment_attcode');
					if ($sAttCode != '')
					{
						if (MetaModel::IsValidAttCode($this->Get('obj_class'), $sAttCode))
						{
							if ($oObject = MetaModel::GetObject($this->Get('obj_class'), $this->Get('obj_key'), false))
							{
								$value = $oObject->Get($sAttCode);
								$oAttDef = MetaModel::GetAttributeDef($this->Get('obj_class'), $sAttCode);
								if ($oAttDef instanceof AttributeCaseLog)
								{
									$value->AddLogEntry($sComment, $this->GetIssuerInfo($bApprove, $oApprover, $oSubstitute));
								}
								else
								{
									// Cumulate into the given (hopefully) text attribute
									$sDate = date(Dict::S('UI:CaseLog:DateFormat'));
									$value .= "\n$sDate - ".$this->GetIssuerInfo($bApprove, $oApprover, $oSubstitute)." :";
									$value .= "\n".$sComment;
								}
								$oObject->Set($sAttCode, $value);
								$oObject->DBUpdate();
							}
						}
					}
				}

				// The answer may be originated by the approver or a substitute
				//
				if (!is_null($oSubstitute) && (array_key_exists('forward', $aApproverData)))
				{
					$iReplierIndex = null;
					foreach ($aApproverData['forward'] as $iIndex => $aSubstituteData)
					{
						if (($aSubstituteData['class'] == get_class($oSubstitute)) && ($aSubstituteData['id'] == $oSubstitute->GetKey()))
						{
							$iReplierIndex = $iIndex;
							break;
						}
					}
					if (!is_null($iReplierIndex))
					{
						$aApproverData['replier_index'] = $iReplierIndex;
					}
				}
			}
		}
		$this->SetSteps($aSteps);
		$this->Set('timeout', $this->ComputeTimeout());
		$this->DBUpdate();

		$bStepResult = $this->GetStepResult($aStepData);
		if (!is_null($bStepResult))
		{
			$aStepData['status'] = 'done';
			$aStepData['ended'] = $this->Now();
			$aStepData['approved'] = $bStepResult;
			$this->SetSteps($aSteps);
			$this->Set('timeout', null);
			$this->DBUpdate();

			$this->Set('current_step', $iCurrentStep + 1);
			$this->StartNextStep();
		}
	}

	/**
	 * Helper to compute current state start time - this information is not recorded
	 */
	public function ComputeLastStart()
	{
		$iStepStarted = AttributeDateTime::GetAsUnixSeconds($this->Get('started'));
		foreach($this->GetSteps() as $iStep => $aStepData)
		{
			switch ($aStepData['status'])
			{
			case 'done':
			case 'timedout':
				$iStepStarted = max($iStepStarted, $aStepData['ended']);
				break;
			}
		}
		return $iStepStarted;
	}
	 
	/**
	 * Helper to compute a target time, depending on the working hours
	 */
	protected function ComputeDeadline($iStartTime, $iDurationSec)
	{
		static $oComputer = null;
		if ($oComputer == null)
		{
			$sWorkingTimeComputer = $this->GetWorkingTimeComputer();
			if (!class_exists($sWorkingTimeComputer))
			{
				throw new CoreException("The provided working time computer is not a valid class: '$sWorkingTimeComputer'. Please, review the implementation of GetWorkingTimeComputer()");
			}
			$oComputer = new $sWorkingTimeComputer();
		}

		$oObject = MetaModel::GetObject($this->Get('obj_class'), $this->Get('obj_key'));
		$aCallSpec = array($oComputer, 'GetDeadline');
		if (!is_callable($aCallSpec))
		{
			throw new CoreException("Unknown class/verb '$sWorkingTimeComputer/GetDeadline'");
		}
		$oStartDate = new DateTime('@'.$iStartTime); // setTimestamp not available in PHP 5.2
		$oDeadline = call_user_func($aCallSpec, $oObject, $iDurationSec, $oStartDate);
		$iRet = $oDeadline->format('U');
		return $iRet;
	}

	/**
	 * Compute the next timeout (depends on the step and the eventual forwards)
	 */
	public function ComputeTimeout()
	{
		$aSteps = $this->GetSteps();
		$iCurrentStep = $this->Get('current_step');
		if (!array_key_exists($iCurrentStep, $aSteps))
		{
			return null;
		}
		$aStepData = $aSteps[$iCurrentStep];

		if ($aStepData['timeout_sec'] == 0)
		{
			// No timeout for the current step
			return null;
		}

		// Next timeout is the minimum amongst the overall timeout and the forward timeouts
		//
		$iStepStarted = $this->ComputeLastStart();
		$iMinTimeout = $aStepData['timeout_sec'];
		foreach($aStepData['approvers'] as $aApproverData)
		{
			// Skip this approver if the answer has been given (by the approver or any of the forwards)
			if (array_key_exists('approval', $aApproverData)) continue;
			// Skip this approver if no forwarding is planned
			if (!array_key_exists('forward', $aApproverData)) continue;

			foreach ($aApproverData['forward'] as $aForwardData)
			{
				// Skip this forward approver if already notified
				if (array_key_exists('sent_time', $aForwardData)) continue;

				$iMinTimeout = min($iMinTimeout, $aStepData['timeout_sec'] * $aForwardData['timeout_percent'] / 100);
			}
		}
		// Note: it is important to make sure that iMinTimeout is actually an integer (strange effects otherwise!) 
		return $this->ComputeDeadline($iStepStarted, floor($iMinTimeout));
	}

	/**
	 * A timeout can occur in two conditions:
	 * - The current step is running out of time: terminate it and start the next one
	 * - An forward has been declared for an approver who has not yet replied	 
	 */	 
	public function OnTimeout()
	{
		if ($this->Get('status') != 'ongoing')
		{
			return;
		}
		$iCurrentStep = $this->Get('current_step');

		$aSteps = $this->GetSteps();
		if (!array_key_exists($iCurrentStep, $aSteps))
		{
			return;
		}
		$aStepData = &$aSteps[$iCurrentStep];
		if ($aStepData['status'] != 'ongoing')
		{
			return;
		}

		$iStepStarted = $this->ComputeLastStart();
		if ($this->Now() >= $this->ComputeDeadline($iStepStarted, $aStepData['timeout_sec']))
		{
			// Time is over for the current step!
			//
			$aStepData['status'] = 'timedout';
			$aStepData['ended'] = $this->Now();
			$aStepData['approved'] = $aStepData['timeout_approve'];
			$this->SetSteps($aSteps);
			$this->Set('timeout', null);
			$this->DBUpdate();
	
			$this->Set('current_step', $iCurrentStep + 1);
			$this->StartNextStep();
		}
		else
		{
			// The time is over for some of the forward approvers
			//
			$oObject = MetaModel::GetObject($this->Get('obj_class'), $this->Get('obj_key'));
			foreach($aStepData['approvers'] as &$aApproverData)
			{
				// Skip this approver if the answer has been given (by the approver or any of the forwards)
				if (array_key_exists('approval', $aApproverData)) continue;
				// Skip this approver if no forwarding is planned
				if (!array_key_exists('forward', $aApproverData)) continue;

				foreach ($aApproverData['forward'] as &$aForwardData)
				{
					// Skip this forward approver if already notified
					if (array_key_exists('sent_time', $aForwardData)) continue;

					if ($this->Now() >= $this->ComputeDeadline($iStepStarted, $aStepData['timeout_sec'] * $aForwardData['timeout_percent'] / 100))
					{
						// Time is over for this approver: forward the notification
						//
						$aForwardData['sent_time'] = $this->Now();
						$oApprover = MetaModel::GetObject($aForwardData['class'], $aForwardData['id'], false);
						if ($oApprover)
						{
							$oSubstituteTo = MetaModel::GetObject($aApproverData['class'], $aApproverData['id'], false);
							$this->SendApprovalInvitation($oApprover, $oObject, $aForwardData['passcode'], $oSubstituteTo);
						}
					}
				}
			}
			// Record the changes and reset the timer to the next timeout
			$this->SetSteps($aSteps);
			$this->Set('timeout', $this->ComputeTimeout());
			$this->DBUpdate();
		}
	}

	/**
	 * Legacy behavior (defaults to this value if the flag is omitted).
	 * Terminate the step with failure as soon as one rejection occurs.
	 * The step successes if everybody approves.
	 */	
	const EXIT_ON_FIRST_REJECT = 1;
	/**
	 * Terminate the step with success as soon as one approval occurs.
	 * The step fails if everybody rejects.
	 */	
	const EXIT_ON_FIRST_APPROVE = 2;
	/**
	 * Terminate the step with the first reply.
	 * Failure or success of the step depends solely on this unique reply.
	 */	
	const EXIT_ON_FIRST_REPLY = 3;

	/**
	 * Helper: do we consider that enough votes have been given?
	 */
	protected function GetStepResult($aStepData)
	{
		// New feature: the array entry 'exit_condition' might be missing
		$iExitCondition = isset($aStepData['exit_condition']) ? $aStepData['exit_condition'] : self::EXIT_ON_FIRST_REJECT;

		$bIsExpectingAnswers = false;
		$bLastAnswer = null;
		foreach($aStepData['approvers'] as &$aApproverData)
		{
			if (array_key_exists('approval', $aApproverData))
			{
				$bLastAnswer = $aApproverData['approval'];
				if ($iExitCondition == self::EXIT_ON_FIRST_REPLY)
				{
					// One single answer makes it
					return $bLastAnswer;
				}

				if ($bLastAnswer)
				{
					if ($iExitCondition == self::EXIT_ON_FIRST_APPROVE)
					{
						// One positive answer is enough
						return true;
					}
				}
				else
				{
					if ($iExitCondition == self::EXIT_ON_FIRST_REJECT)
					{
						// One negative answer is enough
						return false;
					}
				}
			}
			else
			{
				// This answer is still missing
				$bIsExpectingAnswers = true;
			}
		}
		if ($bIsExpectingAnswers)
		{
			// We are still waiting for some votes
			return null;
		}
		else
		{
			// 100% positive or 100% negative, or the latest reply (the latter is a nonsense and should never occur)
			return $bLastAnswer;
		}
	}

	/**
	 * Build and send the message for a given approver (can be a forwarded approval request)
	 */	 	
	public function SendApprovalInvitation($oToPerson, $oObj, $sPassCode, $oSubstituteTo = null)
	{
		$aParams = array_merge($oObj->ToArgs('object'), $oToPerson->ToArgs('approver'));

		$sTitle = MetaModel::ApplyParams($this->GetEmailSubject(get_class($oToPerson), $oToPerson->GetKey()), $aParams);
		$sIntroduction = MetaModel::ApplyParams($this->GetEmailBody(get_class($oToPerson), $oToPerson->GetKey()), $aParams);
		$sToken = $this->GetKey().'-'.$this->Get('current_step').'-'.get_class($oToPerson).'-'.$oToPerson->GetKey().'-'.$sPassCode;

		$sReplyUrl = utils::GetAbsoluteUrlModulesRoot().'approval-base/approve.php?token='.$sToken;

		$sBody = '<html>';
		$sBody .= '<body>';
		$sBody .= '<h3>'.$sTitle.'</h3>';
		$sBody .= '<p>'.$sIntroduction.'</p>';
		$sBody .= '<p>';
		$sBody .= '<a href="'.$sReplyUrl.'">'.Dict::S('Approval:Action-ApproveOrReject').'</a>';
		$sBody .= '</p>';

		$sBody .= '</body>';
		$sBody .= '</html>';

		$oEmail = new EMail();
		$oEmail->SetSubject($sTitle);
		$oEmail->SetBody($sBody);
		$oEmail->SetRecipientTO($this->GetApproverEmailAddress($oToPerson));
		$oEmail->SetRecipientFrom($this->GetEmailSender($oToPerson, $oObj));
		$oEmail->SetRecipientReplyTo($this->GetEmailReplyTo($oToPerson, $oObj));
		$iRes = $oEmail->Send($aIssues);
		switch ($iRes)
		{
			case EMAIL_SEND_OK:
				break;

			case EMAIL_SEND_PENDING:
				break;

			case EMAIL_SEND_ERROR:
				$sErrors = implode(', ', $aIssues);
				$this->Set('last_error', Dict::Format('Approval:Error:Email', $sErrors));
				break;
		}
	}
	
	protected function MakeFormHeader($oPage, $oApprover, $oObject, $sToken, $oSubstitute = null)
	{
		$aParams = array_merge($oObject->ToArgs('object'), $oApprover->ToArgs('approver'));

		$sIntroduction = MetaModel::ApplyParams($this->GetFormBody(get_class($oApprover), $oApprover->GetKey()), $aParams);
		$oPage->add("<div id=\"form_approval_introduction\">".$sIntroduction."</div>\n");
	}

	protected function MakeFormInputs($oPage, $oApprover, $oObject, $sToken, $oSubstitute = null)
	{
		$oPage->add("<div class=\"wizContainer\" id=\"form_approval\">\n");
		$oPage->add("<form action=\"\" id=\"form_approve\" method=\"post\">\n");
		$oPage->add("<input type=\"hidden\" id=\"my_operation\" name=\"operation\" value=\"_not_set_\">");
		$oPage->add("<input type=\"hidden\" name=\"token\" value=\"$sToken\">");
	
		$oPage->add('<div title="'.Dict::S('Approval:Comment-Tooltip').'">'.Dict::S('Approval:Comment-Label').'</div>');
		$oPage->add("<textarea type=\"textarea\" name=\"comment\" id=\"comment\" class=\"resizable\" cols=\"80\" rows=\"5\"></textarea>");
		$oPage->add("<input type=\"submit\" id=\"approval-button\" onClick=\"$('#my_operation').val('do_approve');\" value=\"".Dict::S('Approval:Action-Approve')."\">");
		$oPage->add("<input type=\"submit\" id=\"rejection-button\" onClick=\"$('#my_operation').val('do_reject');\" value=\"".Dict::S('Approval:Action-Reject')."\">");
		$oPage->add("<span id=\"comment_mandatory\">".Dict::S('Approval:Comment-Mandatory')."</span>");
		$oPage->add("</form>");
		$oPage->add("</div>");

		$oPage->add_ready_script(
<<<EOF
function RefreshRejectionButtonState()
{
	var sComment = $.trim($('#comment').val());
	if (sComment.length == 0)
	{
		$('#rejection-button').prop('disabled', true);
		$('#comment_mandatory').show();
	}
	else
	{
		$('#rejection-button').prop('disabled', false);
		$('#comment_mandatory').hide();
	}
}
$('#comment').bind('change keyup', function () {
	RefreshRejectionButtonState();
});
RefreshRejectionButtonState();
EOF
		);
	}

	protected function MakeFormFooter($oPage, $oApprover, $oObject, $sToken, $oSubstitute = null)
	{
		$aParams = array_merge($oObject->ToArgs('object'), $oApprover->ToArgs('approver'));

		// Object details
		//
		if ($this->IsAllowedToSeeObjectDetails($oApprover, $oObject))
		{
			$this->DisplayObjectDetails($oPage, $oApprover, $oObject, $oSubstitute);
		}
		else
		{
			$sIntroduction = MetaModel::ApplyParams($this->GetEmailBody(get_class($oApprover), $oApprover->GetKey()), $aParams);
			$oPage->add('<div styl="font-size: smaller;">'.$sIntroduction.'</div>');
		}
	}

	/**
	 * Build and output the approval form for a given user
	 **/	
	public function DisplayApprovalForm($oPage, $oApprover, $oObject, $sToken, $oSubstitute = null)
	{
		$this->MakeFormHeader($oPage, $oApprover, $oObject, $sToken, $oSubstitute);
		$this->MakeFormInputs($oPage, $oApprover, $oObject, $sToken, $oSubstitute);
		$this->MakeFormFooter($oPage, $oApprover, $oObject, $sToken, $oSubstitute);
	}

	/**
	 * Overridable to change the display of days	
	 */	
	public function GetDisplayDay($iTime)
	{
		return date('Y-m-d', $iTime);
	}

	/**
	 * Overridable to change the display of time	
	 */	
	public function GetDisplayTime($iTime)
	{
		return date('H:i', $iTime);
	}

	/**
	 * Overridable to determine the approver email address in a different way	
	 */	
	public function GetApproverEmailAddress($oApprover)
	{
		// Find out which attribute is the email attribute
		//
		$sEmailAttCode = 'email';
		foreach(MetaModel::ListAttributeDefs(get_class($oApprover)) as $sAttCode => $oAttDef)
		{
			if ($oAttDef instanceof AttributeEmailAddress)
			{
				$sEmailAttCode = $sAttCode;
			}
		}
		$sAddress = $oApprover->Get($sEmailAttCode);
		return $sAddress;
	}

	/**
	 * Overridable to specify the email sender in a more dynamic way
	 */	
	public function GetEmailSender($oApprover, $oObject)
	{
		return MetaModel::GetModuleSetting('approval-base', 'email_sender');
	}

	/**
	 * Overridable to specify the email reply-to in a more dynamic way
	 */	
	public function GetEmailReplyTo($oApprover, $oObject)
	{
		return MetaModel::GetModuleSetting('approval-base', 'email_reply_to');
	}

	/**
	 * Overridable to disable the link to view more information on the object
	 */	
	public function IsAllowedToSeeObjectDetails($oApprover, $oObject)
	{
		if (get_class($oApprover) != 'Person')
		{
			return false;
		}

		$oSearch = DBObjectSearch::FromOQL_AllData("SELECT User WHERE contactid = :approver_id");
		$oSet = new DBObjectSet($oSearch, array(), array('approver_id' => $oApprover->GetKey()));
		if ($oSet->Count() > 0)
		{
			// The approver has a login: show the link!
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Overridable to force the login when viewing object details
	 */	
	public function IsLoginMandatoryToSeeObjectDetails($oApprover, $oObject)
	{
		return false;
	}
}


/**
 * Add the approval status to the object details page, and delete approval schemes when deleting objects
 */
class ApprovalBasePlugin implements iApplicationUIExtension, iApplicationObjectExtension
{
	//////////////////////////////////////////////////
	// Implementation of iApplicationUIExtension
	//////////////////////////////////////////////////

	public function OnDisplayProperties($oObject, WebPage $oPage, $bEditMode = false)
	{
	}

	public function OnDisplayRelations($oObject, WebPage $oPage, $bEditMode = false)
	{
		$sClass = get_class($oObject);
		if (!$this->IsInScope($sClass))
		{
			// skip !
			return;
		}

		$oApprovSearch = DBObjectSearch::FromOQL('SELECT ApprovalScheme WHERE obj_class = :obj_class AND obj_key = :obj_key');
		$oApprovSearch->AllowAllData();
		// Get the ongoing approval
		$oApprovals = new DBObjectSet($oApprovSearch, array('started' => false), array('obj_class' => $sClass, 'obj_key' => $oObject->GetKey()));
		if($oScheme = $oApprovals->Fetch())
		{
			$oScheme->DisplayStatus($oPage);
		}
	}

	public function OnFormSubmit($oObject, $sFormPrefix = '')
	{
	}

	public function OnFormCancel($sTempId)
	{
	}

	public function EnumUsedAttributes($oObject)
	{
		return array();
	}

	public function GetIcon($oObject)
	{
		return '';
	}

	public function GetHilightClass($oObject)
	{
		// Possible return values are:
		// HILIGHT_CLASS_CRITICAL, HILIGHT_CLASS_WARNING, HILIGHT_CLASS_OK, HILIGHT_CLASS_NONE	
		return HILIGHT_CLASS_NONE;
	}

	public function EnumAllowedActions(DBObjectSet $oSet)
	{
		// No action
		return array();
	}

	//////////////////////////////////////////////////
	// Implementation of iApplicationObjectExtension
	//////////////////////////////////////////////////

	public function OnIsModified($oObject)
	{
		return false;
	}

	public function OnCheckToWrite($oObject)
	{
	}

	public function OnCheckToDelete($oObject)
	{
	}

	public function OnDBUpdate($oObject, $oChange = null)
	{
		$sReachingState = $oObject->GetState();
		if (!empty($sReachingState))
		{
			$this->OnReachingState($oObject, $sReachingState);
		}
	}

	public function OnDBInsert($oObject, $oChange = null)
	{
		$sReachingState = $oObject->GetState();
		if (!empty($sReachingState))
		{
			$this->OnReachingState($oObject, $sReachingState);
		}
	}

	public function OnDBDelete($oObject, $oChange = null)
	{
		if ($this->IsInScope(get_class($oObject)))
		{
			$oOrphans = DBObjectSearch::FromOQL("SELECT ApprovalScheme WHERE obj_class = '".get_class($oObject)."' AND obj_key = ".$oObject->GetKey());
			$oOrphans->AllowAllData();
			$oSet = new DBObjectSet($oOrphans);
			while ($oScheme = $oSet->Fetch())
			{
				$oScheme->DBDelete();
			}
		}
	}

	//////////////////////////////////////////////////
	// Helpers
	//////////////////////////////////////////////////

	protected function OnReachingState($oObject, $sReachingState)
	{
		foreach(self::EnumApprovalProcesses() as $sApprovClass)
		{
			$aCallSpec = array($sApprovClass, 'GetApprovalScheme');
			if(!is_callable($aCallSpec))
			{
				throw new Exception("Approval plugin: please implement the function GetApprovalScheme");
			}

			// Calling: GetApprovalScheme($oObject, $sReachingState)
			$oApproval = call_user_func($aCallSpec, $oObject, $sReachingState);
			if (!is_null($oApproval))
			{
				// Make sure that there is no ongoing approval for that object
				// (unfortunately the original state value is unknown at this point)
				//
				$oApprovSearch = DBObjectSearch::FromOQL('SELECT ApprovalScheme WHERE status = \'ongoing\' AND obj_class = :obj_class AND obj_key = :obj_key');
				$oApprovSearch->AllowAllData();
				$oApprovals = new DBObjectSet($oApprovSearch, array(), array('obj_class' => get_class($oObject), 'obj_key' => $oObject->GetKey()));
				if ($oApprovals->Count() == 0)
				{
					$oApproval->Set('obj_class', get_class($oObject));
					$oApproval->Set('obj_key', $oObject->GetKey());
					$oApproval->Set('started', $oApproval->Now());
					$oApproval->DBInsert();
	
					$oApproval->StartNextStep();
				}
			}
		}
	}

	public function IsInScope($sClass)
	{
		return true;
	}

	public static function EnumApprovalProcesses()
	{
		static $aProcesses = null;

		if (is_null($aProcesses))
		{
			$aProcesses = MetaModel::EnumChildClasses('ApprovalScheme', ENUM_CHILD_CLASSES_EXCLUDETOP);
		}
		return $aProcesses;
	}
}

/**
 * Hook to trigger the timeout on ongoing approvals
 */
class CheckApprovalTimeout implements iBackgroundProcess
{
	public function GetPeriodicity()
	{	
		return 60; // seconds
	}

	public function Process($iTimeLimit)
	{
		$oMyChange = new CMDBChange();
		$oMyChange->Set("date", time());
		$oMyChange->Set("userinfo", "Automatic timeout");
		$iChangeId = $oMyChange->DBInsertNoReload();
		// or ... ???
		// CMDBObject::SetTrackInfo($sTrackInfo);

      $aReport = array();

		$oSet = new DBObjectSet(DBObjectSearch::FromOQL('SELECT ApprovalScheme WHERE status = \'ongoing\' AND timeout <= NOW()'));
		while ((time() < $iTimeLimit) && ($oScheme = $oSet->Fetch()))
		{
			$oScheme->OnTimeout();
			$aReport[] = 'Timeout for approval #'.$oScheme->GetKey();
		}
		
		if (count($aReport) == 0)
		{
			return "No approval has timed out";
		}
		else
		{
			return implode('; ', $aReport);
		}
	}
}


?>