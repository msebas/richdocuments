<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tests\Richdocuments;

use OCA\Richdocuments\PermissionManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PermissionManagerTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var PermissionManager */
	private $permissionManager;

	public function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->permissionManager = new PermissionManager($this->config, $this->groupManager);
	}

	public function testIsEnabledForUserEnabledNoRestrictions() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('richdocuments', 'use_groups', '')
			->willReturn('');

		$this->assertTrue($this->permissionManager->isEnabledForUser($user));
	}

	public function testIsEnabledForUserEnabledNotInGroup() {
		/** @var IUser|MockBuilder $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('TestUser');

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('richdocuments', 'use_groups', '')
			->willReturn('Enabled1|Enabled2|Enabled3');

		$this->groupManager
			->expects(self::any())
			->method('isInGroup')
			->willReturnCallback(function($user, $group) {
				return false;
			});

		$this->assertFalse($this->permissionManager->isEnabledForUser($user));
	}

	public function testIsEnabledForUserEnabledInGroup() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('TestUser');

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('richdocuments', 'use_groups', '')
			->willReturn('Enabled1|Enabled2|Enabled3');

		$this->groupManager
			->expects(self::any())
			->method('isInGroup')
			->willReturnCallback(function($user, $group) {
				if ($user === 'TestUser' && $group === 'Enabled2') {
					return true;
				}
				return false;
			});

		$this->assertTrue($this->permissionManager->isEnabledForUser($user));
	}
}
