{**
 * templates/settingsForm.tpl
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Cariniana Preservation plugin settings
 *
 *}

<script>
	$(function() {ldelim}
        $('#carinianaSettingsForm').pkpHandler(
            '$.pkp.controllers.form.FileUploadFormHandler',
            {ldelim}
                $uploader: $('#statementUpload'),
                uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="uploadStatementFile" save=true escape=false},
					baseUrl: {$baseUrl|json_encode},
					filters: {ldelim}
						mime_types : [
							{ldelim} title : "Document files", extensions : "pdf,doc,docx" {rdelim}
						]
					{rdelim}
                {rdelim}
            {rdelim}
        );
    {rdelim});
</script>

<div id="carinianaPreservationSettings">
	<form class="pkp_form" id="carinianaSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
		{csrf}
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="carinianaSettingsFormNotification"}

		{fbvFormArea id="carinianaSettingsFormArea"}
			{fbvFormSection title="plugins.generic.carinianaPreservation.settings.responsabilityStatement"}
				{capture assign="downloadStatementUrl"}{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadStatement" save=true}{/capture}
				{if $alreadyPreserved}
					<div class="pkp_notification">
						{include
							file="controllers/notification/inPlaceNotificationContent.tpl"
							notificationId=responsabilityStatementAlreadySent
							notificationStyleClass="notifyInfo"
							notificationContents="plugins.generic.carinianaPreservation.settings.responsabilityStatement.preserved.notice"|translate
						}
					</div>
				{elseif $statementFile}
					<div class="pkp_notification">
						{include
							file="controllers/notification/inPlaceNotificationContent.tpl"
							notificationId=responsabilityStatementAlreadySent
							notificationStyleClass="notifyInfo"
							notificationContents="plugins.generic.carinianaPreservation.settings.responsabilityStatement.alreadySent.notice"|translate
						}
					</div>
					<p>{translate key="plugins.generic.carinianaPreservation.settings.responsabilityStatement.alreadySent.instructions" downloadStatementUrl=$downloadStatementUrl}</p>
				{else}
					<p>{translate key="plugins.generic.carinianaPreservation.settings.responsabilityStatement.description" downloadStatementUrl=$downloadStatementUrl}</p>
				{/if}

				{if not $alreadyPreserved}
					<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
					{include file="controllers/fileUploadContainer.tpl" id="statementUpload"}
				{/if}
			{/fbvFormSection}
			{fbvFormSection title="plugins.generic.carinianaPreservation.settings.extraCopyEmail"}
				{fbvElement id="extraCopyEmail" class="extraCopyEmail" type="email" value="{$extraCopyEmail|escape}" label="plugins.generic.carinianaPreservation.settings.extraCopyEmail.description" size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
		{/fbvFormArea}
		{fbvFormButtons submitText="common.save"}
	</form>
</div>