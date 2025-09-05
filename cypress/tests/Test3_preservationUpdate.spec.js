const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';

function openPreservationModal() {
    cy.get('a:contains("Website")').click();
    cy.waitJQuery();
    cy.get('button#plugins-button').click();
    cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
    cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
    cy.wait(250);
}

function checkLockssOption(check) {
    cy.login('dbarnes', null, 'publicknowledge');
    cy.get('a:contains("Distribution")').click();
    cy.contains('Archiving').click();
    cy.contains('LOCKSS and CLOCKSS').click();
    if (check) {
        cy.get('input[name="enableLockss"]').check();
    } else {
        cy.get('input[name="enableLockss"]').uncheck();
    }
    cy.get('.pkpButton:visible').contains('Save').click();
    cy.contains('Saved');
}

describe("Preservation update", function () {
    it("Is blocked if LOCKSS is disabled", function () {
        checkLockssOption(false);
        openPreservationModal();

        cy.contains('Last preservation performed on:');
        cy.contains('send an update with the changes since the last preservation');

        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('LOCKSS is not enabled in Distribution > Archiving');
    });
    it("And is successful after enabling LOCKSS", function () {
        checkLockssOption(true);
        openPreservationModal();

        cy.contains('Last preservation performed on:');
        cy.contains('send an update with the changes since the last preservation');
        cy.get('.submitFormButton').contains('Submit').click();

        cy.get('body').should('not.contain', 'The submission of the journal could not be carried out.');
    });
    it("Blocks second update if there are no real changes", function () {
        cy.login('dbarnes', null, 'publicknowledge');
        openPreservationModal();
        cy.get('.submitFormButton').contains('Submit').click();
        cy.waitJQuery();

        cy.contains('No changes detected since last preservation. Nothing to update.');
    });
    it("Fails and show errors if ISSN and eISSN are removed from the journal settings", function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('li a:contains("Journal")').click();
        cy.get('input[name="onlineIssn"]').clear();
        cy.get('input[name="printIssn"]').clear();
        cy.get('.pkpButton:visible').contains('Save').click();
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();

        cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);

        cy.contains("Last preservation performed on:");
        cy.contains("The e-mail with the journal data will be sent to the e-mail address of Cariniana Network (cariniana-periodicos@ibict.br)");
        cy.contains('send an update with the changes since the last preservation');
        cy.get('body').should('not.contain', 'Notes and comments');
        cy.get('body').should('not.contain', 'If you are interested, insert here some relevant information about the publication');

        cy.contains('send an update with the changes since the last preservation');

        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('The submission of the journal could not be carried out. The following data need to be filled: ISSN');
    });
});

describe("Plugin Settings Form after the first preservation", function () {
    it("Shows preserved notice", function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();
        cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-settings-button]').click();

        cy.contains('Nothing else is required here');
    });
    it("Hides upload area and removes re-upload instructions", function() {
        cy.get('#statementUpload').should('not.exist');
        cy.contains('has already been submitted previously').should('not.exist');
        cy.contains('If you wish to replace the previously submitted file').should('not.exist');
    });
});