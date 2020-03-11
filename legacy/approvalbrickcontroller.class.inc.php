<?php

// Copyright (C) 2010-2018 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>

namespace Combodo\iTop\Portal\Controller;

// todo: est-ce la bonne méthode pour gérer les includes, où se trouve l'autoload ?
// The module should have a dependency to itop-portal-base, so its file are loaded AFTER the portal files.
require_once(MODULESROOT.'itop-portal-base/portal/src/controllers/abstractcontroller.class.inc.php');
require_once(MODULESROOT.'itop-portal-base/portal/src/controllers/brickcontroller.class.inc.php');

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserRights;
use IssueLog;
use MetaModel;
use AttributeDate;
use AttributeDateTime;
use DBSearch;
use DBObjectSet;
use Dict;
use ApprovalScheme;
use Combodo\iTop\Portal\Helper\ApplicationHelper;

class ApprovalBrickController extends BrickController
{
	public function DisplayAction(Request $oRequest, Application $oApp, $sBrickId, $sDataLoading = null)
	{
		$oBrick = ApplicationHelper::GetLoadedBrickFromId($oApp, $sBrickId);
		$aClassesConfig = $oBrick->GetClasses();

		$oMyself = UserRights::GetContactObject();

		$iProcessed = 0;
		$sOperation = $oRequest->get('operation');
		if ($sOperation != '')
		{
			$sComment = $oRequest->get('comment');
			$aSelected = $oRequest->get('selected');

			$oSearch = DBSearch::FromOQL('SELECT ApprovalScheme WHERE status = \'ongoing\' AND obj_class = :obj_class AND obj_key = :obj_key');

			foreach ($aSelected as $sClass => $aObjects)
			{
				foreach ($aObjects as $iKey)
				{
					$oSet = new DBObjectSet($oSearch, array(), array('obj_class'=>$sClass, 'obj_key'=>$iKey));
					$oApproval = $oSet->Fetch();
					if ($oApproval)
					{
						if ($sOperation == 'approve')
						{
							$oApproval->Approve($oMyself, $sComment);
						}
						elseif ($sOperation == 'reject')
						{
							$oApproval->Reject($oMyself, $sComment);
						}
					}
				}
			}
		}

		$aApprovals = ApprovalScheme::ListOngoingApprovals(get_class($oMyself), $oMyself->GetKey());
		$aObjects = array();
		foreach ($aApprovals as $oApproval)
		{
			$sObjClass = $oApproval->Get('obj_class');
			$iObjKey = $oApproval->Get('obj_key');
			if (array_key_exists($sObjClass, $aClassesConfig))
			{
				$aObjects[$sObjClass][$iObjKey] = MetaModel::GetObject($sObjClass, $iObjKey);
			}
		}

		$iTotalCount = 0;
		foreach ($aClassesConfig as $sClass => $aClassData)
		{
			$aData = array(
				'sId' => $sClass,
				'sClass' => $sClass,
				'sTitle' => MetaModel::GetName($sClass),
				'aItems' => array()
			);
			$aData['aColumns'][] = array(
				'sAttCode' => '_self_',
				'sLabel' => MetaModel::GetName($sClass)
			);
			foreach ($aClassData['fields'] as $sAttCode)
			{
				$aData['aColumns'][] = array(
					'sAttCode' => $sAttCode,
					'sLabel' => MetaModel::GetLabel($sClass, $sAttCode)
				);
			}
			if (array_key_exists($sClass, $aObjects))
			{
				foreach ($aObjects[$sClass] as $oObject)
				{
					$sUrl = $oApp['url_generator']->generate('p_approval_view_object', array('sObjectClass' => get_class($oObject), 'sObjectId' => $oObject->GetKey()));
					$sLabel = $oObject->GetAsHTML('friendlyname');
					$aValues = array(
						'_self_' => '<a href="'.$sUrl.'" data-toggle="modal" data-target="#modal-for-all">'.$sLabel.'</a>'
					);
					foreach ($aClassData['fields'] as $sAttCode)
					{
						$oAttDef = MetaModel::GetAttributeDef($sClass, $sAttCode);
						if ($oAttDef instanceof \AttributeExternalKey)
						{
							$sDisplayValue = $oObject->GetAsHTML($sAttCode.'_friendlyname');
						}
						else
						{
							$sDisplayValue = $oObject->GetAsHTML($sAttCode);
						}
						$aValues[$sAttCode] = $sDisplayValue;
					}
					$aData['aItems'][] = $aValues;
					$aData['aRowToKey'][] = $oObject->GetKey();
				}
			}
			$aData['iItemsCount'] = count($aData['aItems']);
			$iTotalCount += count($aData['aItems']);

			$aExpectedApprovals[$sClass] = $aData;
		}

		$aData = array(
			'oBrick' => $oBrick,
			'sBrickId' => $sBrickId,
			'sDateFormat' => AttributeDate::GetFormat()->ToMomentJS(),
			'sDateTimeFormat' => AttributeDateTime::GetFormat()->ToMomentJS(),
			'iTotalCount' => $iTotalCount,
			'aExpectedApprovals' => $aExpectedApprovals,
			'sMessage' => null
		);
		if ($sOperation != '')
		{
			$aData['sMessage'] = Dict::Format('Approval:Portal:Success', $iProcessed);
		}
		$oResponse = $oApp['twig']->render($oBrick->GetPageTemplatePath(), $aData);

		return $oResponse;
	}

	public function ViewObjectAction(Request $oRequest, Application $oApp, $sObjectClass, $sObjectId)
	{
		// Checking parameters
		if ($sObjectClass === '' || $sObjectId === '')
		{
			IssueLog::Info(__METHOD__ . ' at line ' . __LINE__ . ' : sObjectClass and sObjectId expected, "' . $sObjectClass . '" and "' . $sObjectId . '" given.');
			$oApp->abort(500, Dict::Format('UI:Error:2ParametersMissing', 'class', 'id'));
		}

		// Checking security layers
		// There is no security checks on purpose.
		// Retrieving object
		$oObject = MetaModel::GetObject($sObjectClass, $sObjectId, false /* MustBeFound */);
		if ($oObject === null)
		{
			// We should never be there as the secuirty helper makes sure that the object exists, but just in case.
			IssueLog::Info(__METHOD__ . ' at line ' . __LINE__ . ' : Could not load object ' . $sObjectClass . '::' . $sObjectId . '.');
			$oApp->abort(404, Dict::S('UI:ObjectDoesNotExist'));
		}

		$oObjectController = new ObjectController();

		$aData = array('sMode' => 'view');
		$aData['form'] = $oObjectController->HandleForm($oRequest, $oApp, $aData['sMode'], $sObjectClass, $sObjectId);
		$aData['form']['title'] = Dict::Format('Brick:Portal:Object:Form:View:Title', MetaModel::GetName($sObjectClass), $oObject->GetName());

		// Preparing response
		if ($oRequest->isXmlHttpRequest())
		{
			// We have to check whether the 'operation' parameter is defined or not in order to know if the form is required via ajax (to be displayed as a modal dialog) or if it's a lifecycle call from a existing form.
			if ($oRequest->request->get('operation') === null)
			{
				$oResponse = $oApp['twig']->render('approval-base/legacy/views/object/modal.html.twig', $aData);
			}
			else
			{
				$oResponse = $oApp->json($aData);
			}
		}
		else
		{
			// Adding brick if it was passed
			$sBrickId = $oRequest->get('sBrickId');
			if ($sBrickId !== null)
			{
				$oBrick = ApplicationHelper::GetLoadedBrickFromId($oApp, $sBrickId);
				if ($oBrick !== null)
				{
					$aData['oBrick'] = $oBrick;
				}
			}
			$aData['sPageTitle'] = $aData['form']['title'];
			$oResponse = $oApp['twig']->render('approval-base/legacy/views/object/layout.html.twig', $aData);
		}

		return $oResponse;
	}

	/**
	 * Handles attachment download on an object under approval.
	 *
	 * Note: This doesn't use the ObjectController::AttachmentAction() because Attachments might be linked to objects that are not allowed to the current user's scopes.
	 *
	 * @param Request $oRequest
	 * @param Application $oApp
	 * @param string $sAttachmentId
	 */
	public function AttachmentAction(Request $oRequest, Application $oApp, $sAttachmentId)
	{
		// Check if attachment exists
		$oAttachment = MetaModel::GetObject('Attachment', $sAttachmentId, false, true);
		if($oAttachment === null)
		{
			// We should never be there as the secuirty helper makes sure that the object exists, but just in case.
			IssueLog::Info(__METHOD__ . ' at line ' . __LINE__ . ' : Could not load attachment #' . $sAttachmentId . '.');
			$oApp->abort(404, Dict::S('UI:ObjectDoesNotExist'));
		}

		$sLinkedObjClass = $oAttachment->Get('item_class');
		$iLinkedObjId = $oAttachment->Get('item_id');
		$oCurrentContact = UserRights::GetContactObject();

		// Check if attachment linked to object in approval scope
		$aApprovals = ApprovalScheme::ListOngoingApprovals(get_class($oCurrentContact), $oCurrentContact->GetKey());
		$aObjects = array();
		$bFound = false;
		foreach ($aApprovals as $oApproval)
		{
			$sObjClass = $oApproval->Get('obj_class');
			$iObjKey = $oApproval->Get('obj_key');
			if( ($sObjClass === $sLinkedObjClass) && ($iObjKey == $iLinkedObjId) )
			{
				$bFound = true;
				break;
			}
		}

		if(!$bFound)
		{
			IssueLog::Info(__METHOD__ . ' at line ' . __LINE__ . ' : Attachment #' . $sAttachmentId . ' not linked to an object in Contact #' . $oCurrentContact->GetKey() . ' approval schemes.');
			$oApp->abort(404, Dict::S('UI:ObjectDoesNotExist'));
		}

		// Prepare response
		// - One year ahead: an attachement cannot change
		$iCacheSec = 31556926;
		$aHeaders = array();
		$aHeaders['Expires'] = '';
		$aHeaders['Cache-Control'] = 'no-transform, public,max-age='.$iCacheSec.',s-maxage='.$iCacheSec;
		// Reset the value set previously
		$aHeaders['Pragma'] = 'cache';
		// An arbitrary date in the past is ok
		$aHeaders['Last-Modified'] = 'Wed, 15 Jun 2015 13:21:15 GMT';

		/** @var \ormDocument $oDocument */
		$oDocument = $oAttachment->Get('contents');
		$aHeaders['Content-Type'] = $oDocument->GetMimeType();
		$aHeaders['Content-Disposition'] = 'attachment;filename="'.$oDocument->GetFileName().'"';

		return new Response($oDocument->GetData(), Response::HTTP_OK, $aHeaders);
	}

}