describe("Cariniana Preservation Plugin - Preservation update", function () {
    it("Manually send a preservation update", function () {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();
        cy.waitJQuery();
        cy.get('button#plugins-button').click();

        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);

        cy.contains("Last preservation performed on:");

        cy.contains("The e-mail with the journal data will be sent to the e-mail address of Cariniana Network (cariniana-periodicos@ibict.br), with copy to the address(es): rvaca@mailinator.com, copia.extra.cariniana@gmail.com");

        cy.get('body').should('not.contain', 'Notes and comments');
        cy.get('body').should('not.contain', 'If you are interested, insert here some relevant information about the publication');

        cy.contains("Click on \"Submit\" to submit this journal for digital preservation by Cariniana");

        cy.get('.submitFormButton').contains('Submit').click();

        cy.get('body').should('not.contain', 'The submission of the journal could not be carried out. The following data need to be filled');
    });
    it("Blocks second update when no changes", function () {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();
        cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);
        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('No changes detected since last preservation. Nothing to update.');
    });
});
describe("Cariniana Preservation Plugin - Preservation update fails", function () {
    it("Removes ISSN and eISSN from the journal settings", function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('li a:contains("Journal")').click();
        cy.get('input[name="onlineIssn"]').clear();
        cy.get('input[name="printIssn"]').clear();
        cy.get('.pkpButton:visible').contains('Save').click();
    });
    it("Messages on preservation update submission", function () {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();

        cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);

        cy.contains("Last preservation performed on:");
        cy.contains(
            "The e-mail with the journal data will be sent to the e-mail address of Cariniana Network (cariniana-periodicos@ibict.br), with copy to the address(es): rvaca@mailinator.com, copia.extra.cariniana@gmail.com"
        );
        cy.get('body').should('not.contain', 'Notes and comments');
        cy.get('body').should('not.contain', 'If you are interested, insert here some relevant information about the publication');

        cy.contains("Click on \"Submit\" to submit this journal for digital preservation by Cariniana");

        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('The submission of the journal could not be carried out. The following data need to be filled: ISSN');
    });
});