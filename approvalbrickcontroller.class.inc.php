<?php

// Copyright (C) 2010-2016 Combodo SARL
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
require_once(MODULESROOT.'itop-portal-base/portal/src/controllers/abstractcontroller.class.inc.php');
require_once(MODULESROOT.'itop-portal-base/portal/src/controllers/brickcontroller.class.inc.php');

use \Silex\Application;
use \Symfony\Component\HttpFoundation\Request;
use \UserRights;
use \CMDBSource;
use \IssueLog;
use \MetaModel;
use \AttributeDefinition;
use \AttributeDate;
use \AttributeDateTime;
use \DBSearch;
use \DBObjectSearch;
use \DBObjectSet;
use \FieldExpression;
use \BinaryExpression;
use \VariableExpression;
use \SQLExpression;
use \UnaryExpression;
use \Dict;
use \Combodo\iTop\Portal\Helper\ApplicationHelper;
use \Combodo\iTop\Portal\Helper\SecurityHelper;
use \Combodo\iTop\Portal\Brick\AbstractBrick;
use \Combodo\iTop\Portal\Brick\ApprovalBrick;

class ApprovalBrickController extends BrickController
{
	public function DisplayAction(Request $oRequest, Application $oApp, $sBrickId, $sGroupingTab, $sDataLoading = null)
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

			$oSearch = DBSearch::FromOQL('SELECT ApprovalScheme WHERE obj_class = :obj_class AND obj_key = :obj_key');

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

		$aApprovals = \ApprovalScheme::ListOngoingApprovals(get_class($oMyself), $oMyself->GetKey());
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
					$sUrl = $oApp['url_generator']->generate('p_object_view', array('sObjectClass' => get_class($oObject), 'sObjectId' => $oObject->GetKey()));
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
}
