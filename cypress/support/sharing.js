
/*
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

Cypress.Commands.add('nextcloudShareWithUser', (path, user, permissions) => {
	cy.request({
		method: 'POST',
		url: `${Cypress.env('baseUrl')}/ocs/v1.php/apps/files_sharing/api/v1/shares`,
		form: true,
		body: {
			shareType: 0,
			path,
			shareWith: user,
			permissions,
		},
		headers: {
			'OCS-ApiRequest': 'true',
			'Content-Type': 'application/x-www-form-urlencoded',
		}
	}).then(response => {
		cy.log(`Shared file ${path} with user ${user}`, response.status)
	})
})
