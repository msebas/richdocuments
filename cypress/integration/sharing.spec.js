/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { randHash } from '../utils/'
const owner = randHash()
const recipient = randHash()

describe('Files default view', function() {
	before(function () {
		// Init user
		cy.nextcloudCreateUser(owner, 'password')
		cy.nextcloudCreateUser(recipient, 'password')

		cy.login(owner, 'password')
		cy.uploadFile('document.odt', 'application/vnd.oasis.opendocument.text')
		cy.uploadFile('spreadsheet.ods', 'application/vnd.oasis.opendocument.spreadsheet')
		cy.uploadFile('presentation.odp', 'application/vnd.oasis.opendocument.presentation')
		cy.uploadFile('drawing.odg', 'application/vnd.oasis.opendocument.drawing')
		cy.nextcloudShareWithUser('/document.odt', recipient, 31)
		cy.nextcloudShareWithUser('/spreadsheet.odt', recipient, 31)
		cy.nextcloudShareWithUser('/presentation.odt', recipient, 31)
		cy.nextcloudShareWithUser('/drawing.odt', recipient, 31)

		// FIXME: files app is thowing the following error for some reason
		// Uncaught TypeError: Cannot read property 'protocol' of undefined
		// Same for appswebroots setting in tests
		cy.on('uncaught:exception', (err, runnable) => {
			return false
		})
	})
	beforeEach(function() {
		cy.login(owner, 'password')
	})

	const fileTests = ['document.odt' /*, 'presentation.odp', 'spreadsheet.ods', 'drawing.odg' */]
	fileTests.forEach((filename) => {

		it('Classic UI: Open ' + filename + ' the viewer on file click', function() {
			cy.nextcloudTestingAppConfigSet('richdocuments', 'uiDefaults-UIMode', 'classic');
			cy.login(recipient, 'password')

			cy.visit('/apps/files', {
				onBeforeLoad(win) {
					cy.spy(win, 'postMessage').as('postMessage')
				},
			})
			cy.openFile(filename)

			cy.get('#viewer', { timeout: 15000 })
				.should('be.visible')
				.and('have.class', 'modal-mask')
				.and('not.have.class', 'icon-loading')

			cy.get('#collaboraframe').iframe().should('exist').as('collaboraframe')
			cy.get('@collaboraframe').within(() => {
				cy.get('#loleafletframe').iframe().should('exist').as('loleafletframe')
			})

			cy.get('@loleafletframe').find('#main-document-content').should('exist')

			// FIXME: wait for collabora to load (sidebar to be hidden)
			// FIXME: handle welcome popup / survey

			cy.screenshot('open-file_' + filename)

			// Share action
			cy.get('@loleafletframe').within(() => {
				cy.get('#main-menu #menu-file > a').click()
				cy.get('#main-menu #menu-shareas > a').click()
			})

			cy.get('#app-sidebar-vue')
				.should('be.visible')
			cy.get('.app-sidebar-header__maintitle')
				.should('be.visible')
				.should('contain.text', filename)
			// FIXME: wait for sidebar tab content
			// FIXME: validate sharing tab
			cy.screenshot('share-sidebar_' + filename)

			// Validate closing
			cy.get('@loleafletframe').within(() => {
				cy.get('#closebutton').click()
			})
			cy.get('#viewer', { timeout: 5000 }).should('not.exist')
		})

		it('Notebookbar UI: Open ' + filename + ' the viewer on file click', function() {
			cy.nextcloudTestingAppConfigSet('richdocuments', 'uiDefaults-UIMode', 'notebookbar');
			cy.login(recipient, 'password')

			cy.visit('/apps/files', {
				onBeforeLoad(win) {
					cy.spy(win, 'postMessage').as('postMessage')
				},
			})
			cy.openFile(filename)

			cy.get('#viewer', { timeout: 15000 })
				.should('be.visible')
				.and('have.class', 'modal-mask')
				.and('not.have.class', 'icon-loading')

			cy.get('#collaboraframe').iframe().should('exist').as('collaboraframe')
			cy.get('@collaboraframe').within(() => {
				cy.get('#loleafletframe').iframe().should('exist').as('loleafletframe')
			})

			cy.get('@loleafletframe').find('#main-document-content').should('exist')

			// FIXME: wait for collabora to load (sidebar to be hidden)
			// FIXME: handle welcome popup / survey

			cy.screenshot('open-file_' + filename)

			// Share action
			cy.get('@loleafletframe').within(() => {
				cy.get('button.icon-nextcloud-sidebar').click()
			})

			cy.get('#app-sidebar-vue')
				.should('be.visible')
			cy.get('.app-sidebar-header__maintitle')
				.should('be.visible')
				.should('contain.text', filename)
			// FIXME: wait for sidebar tab content
			// FIXME: validate sharing tab
			cy.screenshot('share-sidebar_' + filename)

			// Validate closing
			cy.get('@loleafletframe').within(() => {
				cy.get('#closebutton').click()
			})
			cy.get('#viewer', { timeout: 5000 }).should('not.exist')
		})

	})
})
