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
	 * Called when the email is being created for a given approver
	 * 	 
	 * @return string The label of the hyperlink to the approval form
	 */	 	
	abstract public function GetEmailLinkLabel();
	/**
	 * Called when the form is being shown to an approver
	 * 	 
	 * @param string sContactClass The approver object class
	 * @param string iContactId The approver object id
	 * @return string The title in pure text
	 */	 	
	abstract public function GetFormTitle($sContactClass, $iContactId);

	/**
	 * Called when the form is being shown to an approver
	 * 	 
	 * @param string sContactClass The approver object class
	 * @param string iContactId The approver object id
	 * @return string The body in pure text
	 */	 	
	abstract public function GetFormBody($sContactClass, $iContactId);

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
	 * @param integer $iTimeoutSec The object being under approval process
	 * @param boolean $bApproveOnTimeout Set to true to approve in case of timeout for the current step
	 * @return void
	 */
	public function AddStep($aContacts, $iTimeoutSec, $bApproveOnTimeout)
	{
		$aApprovers = array();
		foreach($aContacts as $aApproverData)
		{
			if (!MetaModel::IsValidClass($aApproverData['class']))
			{
				throw new Exception("Approval plugin: Wrong class ".$aApproverData['class']." for the approver");
			}
			$aApprovers[] = array(
				'class' => $aApproverData['class'],
				'id' => $aApproverData['id'],
				'passcode' => mt_rand(11111,99999),
			);
		}

		$aNewStep = array(
			'timeout_sec' => $iTimeoutSec,
			'timeout_approve' => $bApproveOnTimeout,
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
		$sImgApproved = utils::GetAbsoluteUrlModulesRoot().'approval-base/approve.png';
		$sImgRejected = utils::GetAbsoluteUrlModulesRoot().'approval-base/reject.png';

		$sHtml = '';
		//$sHtml = (string)$this;

		$sStarted = $this->Get('started');
		$sEnded = $this->Get('ended');
		switch ($this->Get('status'))
		{
		case 'ongoing':
			$sHtml .= '<p>'.Dict::Format('Approval:Tab:Intro-ongoing', $sStarted).'</p>';
			break;
		case 'accepted':
			$sHtml .= '<p>'."<img src=\"$sImgApproved\" style=\"vertical-align:middle;\">".Dict::Format('Approval:Tab:Intro-ended', $sStarted, $sEnded).'</p>';
			break;
		case 'rejected':
			$sHtml .= '<p>'."<img src=\"$sImgRejected\" style=\"vertical-align:middle;\">".Dict::Format('Approval:Tab:Intro-ended', $sStarted, $sEnded).'</p>';
			break;
		}

		foreach($this->GetSteps() as $iStep => $aStepData)
		{
			switch ($aStepData['status'])
			{
			case 'ongoing':
				$sStepSumary = Dict::S('Approval:Tab:StepSumary-Ongoing');
				break;

			case 'done':
				if ($aStepData['approved'])
				{
					$sStepSumary = Dict::S('Approval:Tab:StepSumary-OK');
				}
				else
				{
					$sStepSumary = Dict::S('Approval:Tab:StepSumary-KO');
				}
				break;

			case 'timedout':
				if ($aStepData['timeout_approve'])
				{
					$sStepSumary = Dict::S('Approval:Tab:StepSumary-OK-Timeout');
				}
				else
				{
					$sStepSumary = Dict::S('Approval:Tab:StepSumary-KO-Timeout');
				}
				break;

			default:
			case 'idle':
				$sStepSumary = '';
				break;
			}

			// The step is 0-based, display it 1-based
			$sHtml .= '<p>'.Dict::Format('Approval:Tab:Step', $iStep + 1).'</p>';
			$sHtml .= '<p>'.$sStepSumary.'</p>';

			$aDisplayData = array();
			foreach($aStepData['approvers'] as $aApproverData)
			{
				$oApprover = MetaModel::GetObject($aApproverData['class'], $aApproverData['id'], false);
				if ($oApprover)
				{
					$sApprover = $oApprover->GetHyperLink();
				}
				else
				{
					$sApprover = $aApproverData['class'].'::'.$aApproverData['id'];
				}
				if (array_key_exists('approval', $aApproverData))
				{
					$bApproved = $aApproverData['approval'];
					$sAnswerDate = $aApproverData['answer_time'];
					if ($bApproved)
					{
						$sAnswer = "<img src=\"$sImgApproved\" style=\"vertical-align:middle;\" title=\"$sAnswerDate\">";
					}
					else
					{
						$sAnswer = "<img src=\"$sImgRejected\" style=\"vertical-align:middle;\" title=\"$sAnswerDate\">";
					}
				}
				else
				{
					$sAnswer = '&nbsp;';
				}
				$aDisplayData[] = array(
					'approver' => $sApprover,
					'answer' => $sAnswer,
				);
			}
		
			$aDisplayConfig = array();
			$aDisplayConfig['approver'] = array('label' => Dict::S('Approval:Tab:Col-Approver'), 'description' => '');
			$aDisplayConfig['answer'] = array('label' => Dict::S('Approval:Tab:Col-Answer'), 'description' => '');
			$sHtml.= $oPage->GetTable($aDisplayConfig, $aDisplayData);
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
			$aStepData['started'] = time();

			$oObject = MetaModel::GetObject($this->Get('obj_class'), $this->Get('obj_key'));
			foreach($aStepData['approvers'] as &$aApproverData)
			{
				$oApprover = MetaModel::GetObject($aApproverData['class'], $aApproverData['id'], false);
				if ($oApprover)
				{
					$this->SendApprovalInvitation($oApprover, $oObject, $aApproverData['passcode']);
				}
			}
			$this->Set('timeout', time() + $aStepData['timeout_sec']);
			$this->SetSteps($aSteps);
			$this->DBUpdate();
		}
		else
		{
			// Done !
			//
			$this->Set('ended', time());
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
			}
		}
	}

	/**
	 * Processes a vote given by an approver:
	 * - find the approver
	 * - record the answer
	 * Then, start the next step if the current one is over 
	 */	 
	public function OnAnswer($iStep, $oApprover, $bApprove)
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
			if (($aApproverData['class'] == get_class($oApprover)) && ($oApprover->GetKey() == $aApproverData['id']))
			{
				// Record the approval
				$aApproverData['answer_time'] = time();
				$aApproverData['approval'] = $bApprove;
			}
		}
		$this->SetSteps($aSteps);
		$this->DBUpdate();

		$bStepResult = $this->GetStepResult($aStepData);
		if (!is_null($bStepResult))
		{
			$aStepData['status'] = 'done';
			$aStepData['ended'] = time();
			$aStepData['approved'] = $bStepResult;
			$this->SetSteps($aSteps);
			$this->Set('timeout', null);
			$this->DBUpdate();

			$this->Set('current_step', $iCurrentStep + 1);
			$this->StartNextStep();
		}
	}

	/**
	 * A given step is running out of time: terminate it and start the next one
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

		// We're 100% sure this event is relevant: interrupt the current step!
		//
		$aStepData['status'] = 'timedout';
		$aStepData['ended'] = time();
		$aStepData['approved'] = $aStepData['timeout_approve'];
		$this->SetSteps($aSteps);
		$this->Set('timeout', null);
		$this->DBUpdate();

		$this->Set('current_step', $iCurrentStep + 1);
		$this->StartNextStep();
	}

	/**
	 * Helper: do we consider that enough votes have been given?
	 */
	protected function GetStepResult($aStepData)
	{
		$bIsExpectingAnswers = false;
		foreach($aStepData['approvers'] as &$aApproverData)
		{
			if (array_key_exists('approval', $aApproverData))
			{
				if (!$aApproverData['approval'])
				{
					// One negative answer is enough
					return false;
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
			// We have all the answers and they are 100% positive
			return true;
		}
	}

	/**
	 * Build and send the message for a given approver
	 */	 	
	public function SendApprovalInvitation($oToPerson, $oObj, $sPassCode)
	{
		$aParams = array_merge($oObj->ToArgs('object'), $oToPerson->ToArgs('approver'));

		$sTitle = MetaModel::ApplyParams($this->GetEmailSubject(get_class($oToPerson), $oToPerson->GetKey()), $aParams);;
		$sIntroduction = MetaModel::ApplyParams($this->GetEmailBody(get_class($oToPerson), $oToPerson->GetKey()), $aParams);;
		$sLinkLabel = MetaModel::ApplyParams($this->GetEmailLinkLabel(get_class($oToPerson), $oToPerson->GetKey()), $aParams);;
		$sToken = $this->GetKey().'-'.$this->Get('current_step').'-'.get_class($oToPerson).'-'.$oToPerson->GetKey().'-'.$sPassCode;

		// Note: cloned from module 'Notify me !'
		$sSender = 'test@combodo.com';
		$aList = MetaModel::FlattenZlist(MetaModel::GetZListItems(get_class($oObj), 'details'));
		$sBody = '<html>';
		$sBody .= '<body>';
		$sBody .= '<h1>'.$sTitle.'</h1>';
		$sBody .= '<p>'.$sIntroduction.'</p>';
		$sBody .= '<h2>'.MetaModel::GetName(get_class($oObj)).": ".$oObj->GetHyperlink().'</h2>';
		$sBody .= '<table>';
		foreach($aList as $sAttCode)
		{
			if (($oObj->GetAttributeFlags($sAttCode) & OPT_ATT_HIDDEN) == 0)
			{
				$oAttDef = MetaModel::GetAttributeDef(get_class($oObj), $sAttCode);
				if ($oAttDef->IsScalar())
				{
					$sBody .= '<tr><td>'.$oAttDef->GetLabel().'</td><td>'.$oObj->GetAsHTML($sAttCode).'</td></tr>'."\n";
				}
			}
		}
		$sBody .= '</table>';
		$sApprovalUrl = utils::GetAbsoluteUrlModulesRoot().'approval-base/approve.php?token='.$sToken;
		$sBody .= '<p><a href="'.$sApprovalUrl.'">'.$sLinkLabel.'</a></p>';
		$sBody .= '</body>';
		$sBody .= '</html>';

		// Find out which attribute is the email attribute
		//
		$sEmailAttCode = 'email';
		foreach(MetaModel::ListAttributeDefs(get_class($this)) as $sAttCode => $oAttDef)
		{
			if ($oAttDef instanceof AttributeEmailAddress)
			{
				$sEmailAttCode = $sAttCode;
			}
		}
		$sTo = $oToPerson->Get($sEmailAttCode);
		
		$oEmail = new EMail();
		$oEmail->SetSubject($sTitle);
		$oEmail->SetBody($sBody);
		$oEmail->SetRecipientTO($sTo);
		$oEmail->SetRecipientFrom($sSender);
		$oEmail->SetRecipientReplyTo($sSender);
		$iRes = $oEmail->Send($aIssues);
		switch ($iRes)
		{
			case EMAIL_SEND_OK:
				break;

			case EMAIL_SEND_PENDING:
				break;

			case EMAIL_SEND_ERROR:
				break;
		}
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
		$oApprovals = new DBObjectSet($oApprovSearch, array(), array('obj_class' => $sClass, 'obj_key' => $oObject->GetKey()));
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
			MetaModel::BulkDelete($oOrphans);
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
				$oApproval->Set('obj_class', get_class($oObject));
				$oApproval->Set('obj_key', $oObject->GetKey());
				$oApproval->Set('started', time());
				$oApproval->DBInsert();

				$oApproval->StartNextStep();
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