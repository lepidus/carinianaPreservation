describe("Cariniana Preservation Plugin - Missing requirements", function() {
    it("Checks for missing requirements message on submission for preservation", function() {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();

        cy.waitJQuery();
		cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^='+ pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);

        cy.get('button[id^=submitFormButton]').contains('Submit').click();
        cy.contains('The submission of the journal could not be carried out. The following data need to be filled: Journal Summary');
    });
});