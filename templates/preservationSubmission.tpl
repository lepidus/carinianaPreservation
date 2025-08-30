{**
 * templates/preservationSubmission.tpl
 *
 * Copyright (c) 2023-2025 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Cariniana preservation submission form
 *
 *}

<script>
    $(function() {ldelim}
        $('#preservationSubmissionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<style>
    #preservationSubmissionFormNotification .title { display: none; }
</style>

<div id="carinianaPreservationSubmission">
    <form class="pkp_form" id="preservationSubmissionForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="preservationSubmission" save=true}">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="preservationSubmissionFormNotification"}

        {if !$isFirstPreservation && $lastPreservationTimestamp}
            <div class="pkp_notification">
                {include
                    file="controllers/notification/inPlaceNotificationContent.tpl"
                    notificationId=lastPreservationNotification
                    notificationStyleClass="notifyCommon"
                    notificationTitle="plugins.generic.carinianaPreservation.preservationSubmission.lastPreservation"|translate
                    notificationContents="$lastPreservationTimestamp"|date_format:"%d/%m/%Y %H:%M:%S"
                }
            </div>
        {/if}

        <p>{translate key="plugins.generic.carinianaPreservation.preservationSubmission.emailAddresses" emailCopies=$emailCopies}</p>

        {if $isFirstPreservation}
            {fbvFormSection title="plugins.generic.carinianaPreservation.headers.notesAndComments"}
                {fbvElement id="notesAndComments" class="notesAndComments" type="text" label="plugins.generic.carinianaPreservation.preservationSubmission.notesAndComments.description"}
            {/fbvFormSection}

            <p>{translate key="plugins.generic.carinianaPreservation.preservationSubmission.first.instruction"}</p>
        {else}
            <p>{translate key="plugins.generic.carinianaPreservation.preservationSubmission.update.instruction"}</p>
        {/if}

    <div id="preservationSubmission"><!-- anchor for form error link --></div>
    {fbvFormButtons submitText="form.submit" hideCancel=true}
    </form>
</div>