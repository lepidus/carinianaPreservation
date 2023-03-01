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
		$('#carinianaSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<div id="carinianaPreservationSettings">
	<form class="pkp_form" id="carinianaSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
		{csrf}
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="carinianaSettingsFormNotification"}

		{fbvFormArea id="carinianaSettingsFormArea" title="plugins.generic.carinianaPreservation.settings.title"}
			{fbvFormSection}
				{fbvElement id="recipientEmail" class="recipientEmail" type="email" value="{$recipientEmail|escape}" required="true" label="plugins.generic.carinianaPreservation.settings.recipientEmail" size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
		{/fbvFormArea}
		{fbvFormButtons submitText="common.save"}
	</form>
</div>