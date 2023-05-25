function loginAdminUser() {
    cy.get('input[id=username]').clear();
    cy.get('input[id=username]').type(Cypress.env('OJSAdminUsername'), { delay: 0 });
    cy.get('input[id=password]').type(Cypress.env('OJSAdminPassword'), { delay: 0 });
    cy.get('button[class=submit]').click();
}

describe("Cariniana Preservation Plugin - Checking of plugin's settings", function() {
    it("Setting of recipient e-mail in plugin's settings", function() {
        cy.visit(Cypress.env('baseUrl') + 'index.php/' + Cypress.env('journalAcronym') + '/management/settings/website');
        loginAdminUser();

        cy.get('#plugins-button').click();
        cy.get('#component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin > .first_column > .show_extras').click();
        cy.get('a[id^=component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin-settings-button]').click();
        
        cy.contains('E-mail de destino');
        cy.get('input[id^=recipientEmail]').clear().type('destino.cariniana@gmail.com');
        cy.get('button[id^=submitFormButton]').contains('Save').click();
        
        cy.get('a[id^=component-grid-settings-plugins-settingsplugingrid-category-generic-row-carinianapreservationplugin-settings-button]').click();
        cy.get('input[id^=recipientEmail]').should('have.value', 'destino.cariniana@gmail.com');

        cy.contains('Termo de responsabilidade');
        cy.contains('Faça o download, preencha e assine o documento dos termos de responsabilidade e em seguida anexe o arquivo no campo abaixo');

        cy.get('.pkpFormField--upload__uploadActions');
    });
});