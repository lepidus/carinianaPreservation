describe("Cariniana Preservation Plugin - Submission to preservation", function() {
    it("Messages on submission for preservation", function(){
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();

        cy.waitJQuery();
		cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^='+ pluginRowId + '-preservationSubmission-button]').click();
        cy.wait(200);

        cy.contains("The e-mail with the journal data wil be sent to the e-mail address of Cariniana Network (cariniana@ibict.br), with copy to the address(es): rvaca@mailinator.com, copia.extra.cariniana@gmail.com");
        cy.contains("Click on \"Submit\" to submit this journal for digital preservation by Cariniana");
        
        cy.get('.submitFormButton').contains('Submit').click();
        cy.contains('The submission of the journal could not be carried out. The following data need to be filled: Journal Summary');
    });
});