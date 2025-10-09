const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';

function openPreservationModal() {
    cy.get('nav').contains('Settings').click();
    cy.get('nav').contains('Website').click({ force: true });
    cy.waitJQuery();
    cy.get('button[id="plugins-button"]').click();
    cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
    cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
    cy.wait(200);
}

function enableLockss() {
    cy.get('nav').contains('Settings').click();
    cy.get('nav').contains('Distribution').click({ force: true });
    cy.contains('Archiving').click({force: true});
    cy.contains('LOCKSS and CLOCKSS').click();
    cy.get('input[name="enableLockss"]').check();
    cy.get('.pkpButton:visible').contains('Save').click();
    cy.contains('Saved');
}

describe("Preservation attempt is not successfull", () => {
    it("LOCKSS Archiving option is disabled", () => {
        cy.login('dbarnes', null, 'publicknowledge');
        openPreservationModal();
        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('LOCKSS is not enabled in Distribution > Archiving');
    });
    it("LOCKSS enabled, but ISSNs are missing", () => {
        cy.login('dbarnes', null, 'publicknowledge');
        enableLockss();
        cy.get('nav').contains('Settings').click();
        cy.get('nav').contains('Journal').click({ force: true });
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

describe("Successful submission to preservation", () => {
    it("LOCKSS enabled, ISSNs in place and performs first submission on preservation Form", () => {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('nav').contains('Settings').click();
        cy.get('nav').contains('Journal').click({ force: true });
        cy.get('input[name="onlineIssn"]').clear().type('0000-0000');
        cy.get('input[name="printIssn"]').clear().type('0000-0000');
        cy.get('.pkpButton:visible').contains('Save').click();
        openPreservationModal();
        cy.contains('make the first submission');
        cy.get('.submitFormButton').contains('Submit').click();
    });
});