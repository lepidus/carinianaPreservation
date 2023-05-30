{**
 * templates/settingsForm.tpl
 *
 * Copyright (c) 2023 Lepidus Tecnologia
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
			{fbvFormSection title="plugins.generic.carinianaPreservation.settings.recipientEmail"}
				{fbvElement id="recipientEmail" class="recipientEmail" type="email" value="{$recipientEmail|escape}" required="true" label="plugins.generic.carinianaPreservation.settings.recipientEmail.description" size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
			{fbvFormSection title="plugins.generic.carinianaPreservation.settings.responsabilityStatement"}
				{capture assign="downloadStatementUrl"}{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadStatement" save=true}{/capture}
				{if $statementFile}
					<p>{translate key="plugins.generic.carinianaPreservation.settings.responsabilityStatement.alreadySent" downloadStatementUrl=$downloadStatementUrl}</p>
				{else}
					<p>{translate key="plugins.generic.carinianaPreservation.settings.responsabilityStatement.description" downloadStatementUrl=$downloadStatementUrl}</p>
				{/if}
				
				<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
				{include file="controllers/fileUploadContainer.tpl" id="statementUpload"}
			{/fbvFormSection}
		{/fbvFormArea}
		{fbvFormButtons submitText="common.save"}
	</form>
</div>