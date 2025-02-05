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

<div id="carinianaPreservationSubmission">
    <form class="pkp_form" id="preservationSubmissionForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="preservationSubmission" save=true}">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="preservationSubmissionFormNotification"}

        <p>{translate key="plugins.generic.carinianaPreservation.preservationSubmission.emailAddresses" emailCopies=$emailCopies}</p>

        {fbvFormSection title="plugins.generic.carinianaPreservation.headers.notesAndComments"}
            {fbvElement id="notesAndComments" class="notesAndComments" type="text" label="plugins.generic.carinianaPreservation.preservationSubmission.notesAndComments.description"}
        {/fbvFormSection}

        <p>{translate key="plugins.generic.carinianaPreservation.preservationSubmission.instruction"}</p>

        {fbvFormButtons submitText="form.submit" hideCancel=true}
    </form>
</div>