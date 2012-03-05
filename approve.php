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
 * Approval page
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */
require_once('../../approot.inc.php');
require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/nicewebpage.class.inc.php');
require_once(APPROOT.'/application/wizardhelper.class.inc.php');


class QuitException extends Exception
{}


function ReadMandatoryParam($sParam)
{
	$value = utils::ReadParam($sParam, null);
	if (is_null($value))
	{
		throw new Exception("Missing argument '$sParam'");
	}
	return $value; 
}


function GetContext($oP, $sToken)
{
	// For the moment, the token is made of <scheme_id>-<step>-<contact_class>-<contact_id>
	$aToken = explode('-', $sToken);
	if (count($aToken) != 5)
	{
		throw new CoreException("Unexpected value for token: '$sToken' does not have the required format");
	}

	$iSchemeId = $aToken[0];
	$iStep = $aToken[1];
	$sApproverClass = $aToken[2];
	$iApproverId = $aToken[3];
	$sPassCode = $aToken[4];

	try
	{
		$oScheme = MetaModel::GetObject('ApprovalScheme', $iSchemeId);
		$oApprover = MetaModel::GetObject($sApproverClass, $iApproverId);
		$oObject = MetaModel::GetObject($oScheme->Get('obj_class'), $oScheme->Get('obj_key'));
	}
	catch(CoreException $e)
	{
		throw new CoreException("Unexpected value for token: '$sToken' does not have the required format");
	}

	if ($oScheme->Get('status') == 'accepted')
	{
		$oP->p(Dict::Format('Approval:Form:AlreadyApproved', $oObject->GetHyperLink()));
		throw new QuitException();
	}
	elseif ($oScheme->Get('status') == 'rejected')
	{
		$oP->p(Dict::Format('Approval:Form:AlreadyRejected', $oObject->GetHyperLink()));
		throw new QuitException();
	}

	$aSteps = $oScheme->GetSteps();

	if ($iStep < $oScheme->Get('current_step'))
	{
		if ($aSteps[$iStep]['approved'])
		{
			$oP->p(Dict::Format('Approval:Form:StepApproved', $oObject->GetHyperLink()));
			throw new QuitException();
		}
		else
		{
			$oP->p(Dict::Format('Approval:Form:StepRejected', $oObject->GetHyperLink()));
			throw new QuitException();
		}
	}

	if ($iStep > $oScheme->Get('current_step'))
	{
		throw new CoreException("Unexpected value for step: '$iStep' is not started or exceeds allowed values");
	}

	// Find the approver amongst the existing approvers for the given step...
	//
	$bFoundRecord = false;
	foreach($aSteps[$iStep]['approvers'] as $aApproverData)
	{
		if (($aApproverData['class'] == get_class($oApprover)) && ($oApprover->GetKey() == $aApproverData['id']))
		{
			$bFoundRecord = true;

			if ($aApproverData['passcode'] != $sPassCode)
			{
				throw new CoreException("Wrong passcode");
			}
		}
	}

	if (!$bFoundRecord)
	{
		throw new CoreException("Unexpected approver for this step");
	}

	return array($oScheme, $iStep, $oApprover, $oObject);
}



function ShowApprovalForm($oP, $sToken)
{
	list($oScheme, $iStep, $oApprover, $oObject) = GetContext($oP, $sToken);

	$oScheme->DisplayApprovalForm($oP, $oApprover, $oObject, $sToken);
}

function SubmitAnswer($oP, $sToken, $bApprove)
{
	list($oScheme, $iStep, $oApprover, $oObject) = GetContext($oP, $sToken);

	// Record the approval/rejection
	//
	$oScheme->OnAnswer($iStep, $oApprover, $bApprove);

	if ($oScheme->Get('status') == 'accepted')
	{
		$oP->p(Dict::S('Approval:Form:AnswerRecorded-Approved'));
	}
	elseif ($oScheme->Get('status') == 'rejected')
	{
		$oP->p(Dict::S('Approval:Form:AnswerRecorded-Rejected'));
	}
	else
	{
		$oP->p(Dict::S('Approval:Form:AnswerRecorded-Continue'));
	}
}

/////////////////////////////
//
// Main
//


try
{
	require_once(APPROOT.'/application/startup.inc.php');
	require_once(MODULESROOT.'approval-base/approvalwebpage.class.inc.php');
	$sOperation = utils::ReadParam('operation', '');
	
//	$oUserOrg = GetUserOrg();
	$sCSSFileSuffix = 'approval-base/approve.css';
	if (@file_exists(MODULESROOT.$sCSSFileSuffix))
	{
//		$oP = new QuizzWebPage(Dict::S('Survey-Title'), $sCSSFileSuffix);
//		$oP->add($sCSSFileSuffix);
	}
	else
	{
//	$oP = new QuizzWebPage(Dict::S('Survey-Title'));
	}
	$oP = new ApprovalWebPage(Dict::S('Approval:Form:Title'));
	$oP->set_base(utils::GetAbsoluteUrlAppRoot().'pages/');

	$oP->add("<style>
</style>\n");


	$sToken = ReadMandatoryParam('token');
	switch ($sOperation)
	{
		case 'reject':
		SubmitAnswer($oP, $sToken, false);
		break;

		case 'approve':
		SubmitAnswer($oP, $sToken, true);
		break;
		
		default:
		ShowApprovalForm($oP, $sToken);
	}

	$oP->output();
}
catch(QuitException $e)
{
	$oP->output();
}
catch(CoreException $e)
{
	require_once(APPROOT.'/setup/setuppage.class.inc.php');
	$oP = new SetupWebPage(Dict::S('UI:PageTitle:FatalError'));
	$oP->set_base(utils::GetAbsoluteUrlAppRoot().'pages/');
	$oP->add("<h1>".Dict::S('UI:FatalErrorMessage')."</h1>\n");	
	$oP->error(Dict::Format('UI:Error_Details', $e->getHtmlDesc()));	
	$oP->output();

	if (MetaModel::IsLogEnabledIssue())
	{
		if (MetaModel::IsValidClass('EventIssue'))
		{
			try
			{
				$oLog = new EventIssue();
	
				$oLog->Set('message', $e->getMessage());
				$oLog->Set('userinfo', '');
				$oLog->Set('issue', $e->GetIssue());
				$oLog->Set('impact', 'Page could not be displayed');
				$oLog->Set('callstack', $e->getTrace());
				$oLog->Set('data', $e->getContextData());
				$oLog->DBInsertNoReload();
			}
			catch(Exception $e)
			{
				IssueLog::Error("Failed to log issue into the DB");
			}
		}

		IssueLog::Error($e->getMessage());
	}

	// For debugging only
	//throw $e;
}
catch(Exception $e)
{
	require_once(APPROOT.'/setup/setuppage.class.inc.php');
	$oP = new SetupWebPage(Dict::S('UI:PageTitle:FatalError'));
	$oP->set_base(utils::GetAbsoluteUrlAppRoot().'pages/');
	$oP->add("<h1>".Dict::S('UI:FatalErrorMessage')."</h1>\n");	
	$oP->error(Dict::Format('UI:Error_Details', $e->getMessage()));	
	$oP->output();

	if (MetaModel::IsLogEnabledIssue())
	{
		if (MetaModel::IsValidClass('EventIssue'))
		{
			try
			{
				$oLog = new EventIssue();
	
				$oLog->Set('message', $e->getMessage());
				$oLog->Set('userinfo', '');
				$oLog->Set('issue', 'PHP Exception');
				$oLog->Set('impact', 'Page could not be displayed');
				$oLog->Set('callstack', $e->getTrace());
				$oLog->Set('data', array());
				$oLog->DBInsertNoReload();
			}
			catch(Exception $e)
			{
				IssueLog::Error("Failed to log issue into the DB");
			}
		}

		IssueLog::Error($e->getMessage());
	}
}
?>
