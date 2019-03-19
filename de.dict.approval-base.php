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
Dict::Add('DE DE', 'German', 'Deutsch', array(
	'Approval:Tab:Title' => 'Status der Genehmigung',
	'Approval:Tab:Start' => 'Start',
	'Approval:Tab:End' => 'Ende',
	'Approval:Tab:StepEnd-Limit' => 'Zeitlimit (Implizites Ergebnis)',
	'Approval:Tab:StepEnd-Theoretical' => 'Theoretisches Zeitlimit (Dauer begrenzt auf %1$s mn)',
	'Approval:Tab:StepSumary-Ongoing' => 'Warten auf Antwort',
	'Approval:Tab:StepSumary-OK' => 'Genehmigt',
	'Approval:Tab:StepSumary-KO' => 'Abgelehnt',
	'Approval:Tab:StepSumary-OK-Timeout' => 'Genehmigt (Zeitüberschreitung)',
	'Approval:Tab:StepSumary-KO-Timeout' => 'Abgelehnt (Zeitüberschreitung)',
	'Approval:Tab:StepSumary-Idle' => 'Nicht begonnen',
	'Approval:Tab:StepSumary-Skipped' => 'Übersprungen',
	'Approval:Tab:End-Abort' => 'Der Genehmigungsprozess wurde übergangen von %1$s am %2$s',

	'Approval:Tab:StepEnd-Condition-FirstReject' => 'Dieser Schritt endet mit der ersten Ablehnung, oder wenn 100% genehmigen',
	'Approval:Tab:StepEnd-Condition-FirstApprove' => 'Dieser Schritt endet mit der ersten Genehmigung, oder wenn 100% ablehnen',
	'Approval:Tab:StepEnd-Condition-FirstReply' => 'Dieser Schritt endet mit der ersten Rückmeldung',
	'Approval:Tab:Error' => 'Während des Genehmigungsprozesses trat ein Fehler auf: %1$s',

	'Approval:Comment-Label' => 'Ihr Kommentar',
	'Approval:Comment-Tooltip' => 'Im Falle einer Ablehnung zwingend erforderlich, ansonsten optional',
	'Approval:Comment-Mandatory' => 'Für die Ablehnung ist ein Kommentar zwingend erforderlich.',
	'Approval:Comment-Reused' => 'Anwort wurde bereits gegeben in Schritt in Schritt %1$s, mit dem Kommentar "%2$s"',
	'Approval:Action-Approve' => 'Genehmigen',
	'Approval:Action-Reject' => 'Ablehnen',
	'Approval:Action-ApproveOrReject' => 'Genehmigen oder Ablehnen',
	'Approval:Action-Abort' => 'Genehmigungsprozess übergehen',

	'Approval:Form:Title' => 'Genehmigung',
	'Approval:Form:Ref' => 'Genhemigungsprozess für %1$s',

	'Approval:Form:ApproverDeleted' => 'Der zu ihrer Identität gehörende Datensatz wurde gelöscht.',
	'Approval:Form:ObjectDeleted' => 'Das zu genehmigende Objekt wurde in iTop gelöscht.',

	'Approval:Form:AnswerGivenBy' => 'Entschuldigung, die Rückmeldung erfolgte bereits durch \'%1$s\'', 
	'Approval:Form:AlreadyApproved' => 'Der Prozess wurde bereits mit dem Ergebniss "Genehmigt" abgeschlossen.',
	'Approval:Form:AlreadyRejected' => 'Der Prozess wurde bereits mit dem Ergebnis "Abgelehnt" abgeschlossen.',

	'Approval:Form:StepApproved' => 'Dieser Schritt wurde schon mit dem Ergebnis "Genehmigt" abgeschlossen. Der Genehmigungsprozess wird fortgesetzt...',
	'Approval:Form:StepRejected' => 'Dieser Schritt wurde schon mit dem Ergebnis "Abgelehnt" abgeschlossen. Der Genehmigungsprozess wird fortgesetzt...',

	'Approval:Abort:Explain' => 'Sie haben angefordert den Genehmigungsprozess zu <b>übergehen</b>. Dies wird wird den Prozess anhalten und kein Genehmiger darf mehr eine Rückmeldung geben.',

	'Approval:Form:AnswerRecorded-Continue' => 'Die Auswahl wurde gespeichert. Der Genehmigungsprozess wird fortgesetzt.',
	'Approval:Form:AnswerRecorded-Approved' => 'Die Auswahl wurde gespeichert. Der Genehmigungsprozess ist erfolgreich abgeschlossen.',
	'Approval:Form:AnswerRecorded-Rejected' => 'Die Auswahl wurde gespeichert. Der Genehmigungsprozess ist mit dem Ergebniss "Abgelehnt" abgeschlossen.',

	'Approval:Approved-On-behalf-of' => 'Genehmigt von %1$s im Auftrag von %2$s',
	'Approval:Rejected-On-behalf-of' => 'Abgelehnt von %1$s im Auftrag von %2$s',
	'Approval:Approved-By' => 'Genehmigt von %1$s',
	'Approval:Rejected-By' => 'Abgelehnt von %1$s',

	'Approval:Ongoing-Title' => 'laufende Freigaben',
	'Approval:Ongoing-Title+' => 'Freigabe Prozesse für Objekte der Klasse %1$s',
	'Approval:Ongoing-FilterMyApprovals' => 'Zeige Objekte, die auf meine Freigabe warten.',
	'Approval:Ongoing-NothingCurrently' => 'Es gibt keine laufenden Freigaben.',

	'Approval:Remind-Btn' => 'Erinnerung versenden...',
	'Approval:Remind-DlgTitle' => 'Erinnerung versenden',
	'Approval:Remind-DlgBody' => 'Die folgenden Kontakte werden erneut benachrichtigt:',
	'Approval:ReminderDone' => 'Eine Erinnerung wurde an %1$d Personen versandt.',

	'Approval:Portal:Title' => 'Items awaiting your approval~~',
	'Approval:Portal:Title+' => 'Please select items to approve and use the buttons located at the bottom of the page~~',
	'Approval:Portal:NoItem' => 'There is currently no item expecting your approval~~',
	'Approval:Portal:Btn:Approve' => 'Approve~~',
	'Approval:Portal:Btn:Reject' => 'Reject~~',
	'Approval:Portal:CommentTitle' => 'Approval comment (mandatory in case of reject)~~',
	'Approval:Portal:CommentPlaceholder' => '~~',
	'Approval:Portal:Success' => 'Your feedback has been recorded.~~',
	'Approval:Portal:Dlg:Approve' => 'Please confirm that you want to approve <em><span class="approval-count">?</span></em> item(s)~~',
	'Approval:Portal:Dlg:ApproveOne' => 'Please confirm that you want to approve this item~~',
	'Approval:Portal:Dlg:Btn:Approve' => 'Approve!~~',
	'Approval:Portal:Dlg:Reject' => 'Please confirm that you want to reject <em><span class="approval-count">?</span></em> item(s)~~',
	'Approval:Portal:Dlg:RejectOne' => 'Please confirm that you want to reject this item~~',
	'Approval:Portal:Dlg:Btn:Reject' => 'Reject!~~',

	'Class:TriggerOnApprovalRequest' => 'Trigger (wenn eine Freigabe angefordert wird)~~',
	'Class:TriggerOnApprovalRequest+' => 'Trigger bei Freigabeanforderung',
	'Class:ActionEmailApprovalRequest' => 'E-Mail Freigabeanforderung',
	'Class:ActionEmailApprovalRequest/Attribute:subject_reminder' => 'Betreff (Erinnerung)',
	'Class:ActionEmailApprovalRequest/Attribute:subject_reminder+' => 'Betreff der Mail, falls eine Erinnerung gesendet wird.',
));

//
// Class: ApprovalScheme
//

Dict::Add('DE DE', 'German', 'Deutsch', array(
	'Class:ApprovalScheme' => 'ApprovalScheme~~',
	'Class:ApprovalScheme+' => '~~',
	'Class:ApprovalScheme/Attribute:obj_class' => 'Obj class~~',
	'Class:ApprovalScheme/Attribute:obj_class+' => '~~',
	'Class:ApprovalScheme/Attribute:obj_key' => 'Obj key~~',
	'Class:ApprovalScheme/Attribute:obj_key+' => '~~',
	'Class:ApprovalScheme/Attribute:started' => 'Started~~',
	'Class:ApprovalScheme/Attribute:started+' => '~~',
	'Class:ApprovalScheme/Attribute:ended' => 'Ended~~',
	'Class:ApprovalScheme/Attribute:ended+' => '~~',
	'Class:ApprovalScheme/Attribute:timeout' => 'Timeout~~',
	'Class:ApprovalScheme/Attribute:timeout+' => '~~',
	'Class:ApprovalScheme/Attribute:current_step' => 'Current step~~',
	'Class:ApprovalScheme/Attribute:current_step+' => '~~',
	'Class:ApprovalScheme/Attribute:status' => 'Status~~',
	'Class:ApprovalScheme/Attribute:status+' => '~~',
	'Class:ApprovalScheme/Attribute:status/Value:ongoing' => 'Ongoing~~',
	'Class:ApprovalScheme/Attribute:status/Value:ongoing+' => '~~',
	'Class:ApprovalScheme/Attribute:status/Value:accepted' => 'Accepted~~',
	'Class:ApprovalScheme/Attribute:status/Value:accepted+' => '~~',
	'Class:ApprovalScheme/Attribute:status/Value:rejected' => 'Rejected~~',
	'Class:ApprovalScheme/Attribute:status/Value:rejected+' => '~~',
	'Class:ApprovalScheme/Attribute:last_error' => 'Last error~~',
	'Class:ApprovalScheme/Attribute:last_error+' => '~~',
	'Class:ApprovalScheme/Attribute:abort_comment' => 'Abort comment~~',
	'Class:ApprovalScheme/Attribute:abort_comment+' => '~~',
	'Class:ApprovalScheme/Attribute:abort_user_id' => 'Abort user id~~',
	'Class:ApprovalScheme/Attribute:abort_user_id+' => '~~',
	'Class:ApprovalScheme/Attribute:abort_date' => 'Abort date~~',
	'Class:ApprovalScheme/Attribute:abort_date+' => '~~',
	'Class:ApprovalScheme/Attribute:label' => 'Label~~',
	'Class:ApprovalScheme/Attribute:label+' => '~~',
	'Class:ApprovalScheme/Attribute:steps' => 'Steps~~',
	'Class:ApprovalScheme/Attribute:steps+' => '~~',
));
