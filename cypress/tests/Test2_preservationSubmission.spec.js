describe("Cariniana Preservation Plugin - Submission to preservation fails", function () {
    it("Removes ISSN and eISSN from the journal settings", function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('li a:contains("Journal")').click();
        cy.get('input[name="onlineIssn"]').clear();
        cy.get('input[name="printIssn"]').clear();
        cy.get('.pkpButton:visible').contains('Save').click();
    });
    it("Messages on submission for preservation", function () {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();

        cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);

        cy.contains("The e-mail with the journal data will be sent to the e-mail address of Cariniana Network (cariniana-periodicos@ibict.br), with copy to the address(es): rvaca@mailinator.com, copia.extra.cariniana@gmail.com");

        cy.contains("Notes and comments");
        cy.contains("If you are interested, insert here some relevant information about the publication");

        cy.contains('make the first submission of this journal for digital preservation');

        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('The submission of the journal could not be carried out. The following data need to be filled: ISSN');
    });
});
describe("Cariniana Preservation Plugin - Successful submission to preservation", function () {
    it("Sets ISSN and eISSN in the journal settings", function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('li a:contains("Journal")').click();
        cy.get('input[name="onlineIssn"]').type("0000-0000");
        cy.get('input[name="printIssn"]').type("0000-0000");
        cy.get('.pkpButton:visible').contains('Save').click();
    });
    it("Send a submission for preservation", function () {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();

        cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);

        cy.contains("The e-mail with the journal data will be sent to the e-mail address of Cariniana Network (cariniana-periodicos@ibict.br), with copy to the address(es): rvaca@mailinator.com, copia.extra.cariniana@gmail.com");

        cy.contains("Notes and comments");
        cy.contains("If you are interested, insert here some relevant information about the publication");

        cy.contains('make the first submission of this journal for digital preservation');

        cy.get('.submitFormButton').contains('Submit').click();
    });
});