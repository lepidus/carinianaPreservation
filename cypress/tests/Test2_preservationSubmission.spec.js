const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';

function openPreservationModal() {
    cy.get('a:contains("Website")').click();
    cy.waitJQuery();
    cy.get('button#plugins-button').click();
    cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
    cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
    cy.wait(200);
}

function enableLockss() {
    cy.get('a:contains("Distribution")').click();
    cy.contains('Archiving').click();
    cy.contains('LOCKSS and CLOCKSS').click();
    cy.get('input[name="enableLockss"]').check({ force: true });
    cy.contains('Save').click();
    cy.contains('Saved');
}

describe("Cariniana Preservation Plugin - Submission negative path (LOCKSS disabled, then missing ISSN)", () => {
    it("Shows lockssDisabled notice after submit attempt, then enables LOCKSS; clears ISSNs and attempts submission expecting ISSN requirement", () => {
        cy.login('dbarnes', null, 'publicknowledge');
        openPreservationModal();

        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('LOCKSS is not enabled in Distribution > Archiving');
        cy.get('a:contains("Website")').click();
        enableLockss();

        cy.get('li a:contains("Journal")').click();
        cy.get('input[name="onlineIssn"]').clear();
        cy.get('input[name="printIssn"]').clear();
        cy.get('.pkpButton:visible').contains('Save').click();

        openPreservationModal();
        cy.contains('The e-mail with the journal data will be sent');
        cy.contains('Notes and comments');
        cy.contains('make the first submission');

        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('The submission of the journal could not be carried out');
        cy.contains('ISSN');
    });
});

describe("Cariniana Preservation Plugin - Successful submission to preservation", () => {
    it("Sets ISSNs and performs first submission", () => {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('li a:contains("Journal")').click();
        cy.get('input[name="onlineIssn"]').clear().type('0000-0000');
        cy.get('input[name="printIssn"]').clear().type('0000-0000');
        cy.get('.pkpButton:visible').contains('Save').click();
        openPreservationModal();
        cy.contains('make the first submission');
        cy.get('.submitFormButton').contains('Submit').click();
    });
});