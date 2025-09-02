describe("Cariniana Preservation Plugin - Checking of plugin's settings", function() {
    it("Sets recipient e-mail and responsability statement", function() {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        const extraCopyEmail = 'copia.extra.cariniana@gmail.com';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();

		cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^='+ pluginRowId + '-settings-button]').click();

        cy.contains('Responsability statement');
        cy.fixture('dummy.pdf', { encoding: 'base64' }).then((fileContent) => {
            cy.get('#statementUpload input[type=file]')
                .upload({
					fileContent,
					fileName: 'responsability_statement.pdf',
					mimeType: 'application/pdf',
					encoding: 'base64',
				});
		});
        cy.wait(200);
        cy.contains('Extra copy e-mail');
        cy.get('input[id^=extraCopyEmail]').clear().type(extraCopyEmail);
        cy.get('button[id^=submitFormButton]').contains('Save').click();

        cy.get('a[id^=' + pluginRowId + '-settings-button]').click();
        cy.get('input[id^=extraCopyEmail]').should('have.value', extraCopyEmail);
        cy.contains('has already been submitted previously');
    });

    it("Shows replace instructions and deletion note before first preservation", function() {
        const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin';
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('a:contains("Website")').click();
        cy.waitJQuery();
        cy.get('button#plugins-button').click();
        cy.get('#' + pluginRowId + ' > .first_column > .show_extras').click();
        cy.get('a[id^='+ pluginRowId + '-settings-button]').click();
        cy.contains('has already been submitted previously');
        cy.contains('will be deleted after the first preservation');
        cy.contains('If you wish to replace the previously submitted file');
        cy.get('#statementUpload input[type=file]').should('exist');
    });
});