<?php
/**
 * @copyright Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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

namespace OCA\Richdocuments\Service;


use OCA\Richdocuments\AppInfo\Application;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICacheFactory;

class FontService {
	private const INVALIDATE_FONT_LIST_CACHE_AFTER_SECONDS = 3600;

	/**
	 * @var IAppData
	 */
	private $appData;
	/**
	 * @var \OCP\ICache
	 */
	private $cache;

	public function __construct(IAppData $appData,
								ICacheFactory $cacheFactory) {
		$this->appData = $appData;
		$this->cache = $cacheFactory->createDistributed(Application::APPNAME);
	}

	/**
	 * @return ISimpleFolder
	 * @throws \OCP\Files\NotPermittedException
	 */
	private function getFontAppDataDir(): ISimpleFolder {
		try {
			return $this->appData->getFolder('fonts');
		} catch (NotFoundException $e) {
			return $this->appData->newFolder('fonts');
		}
	}

	/**
	 * @return ISimpleFolder
	 * @throws \OCP\Files\NotPermittedException
	 */
	private function getFontOverviewAppDataDir(): ISimpleFolder {
		try {
			return $this->appData->getFolder('font-overviews');
		} catch (NotFoundException $e) {
			return $this->appData->newFolder('font-overviews');
		}
	}

	/**
	 * Get the list of available font files
	 *
	 * @return array
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getFontFileNames(): array {
		$cacheKey = 'fontFileNames';
		$cachedNames = $this->cache->get($cacheKey);
		if ($cachedNames === null) {
			$fontDir = $this->getFontAppDataDir();
			$cachedNames = array_map(
				function (ISimpleFile $f) use ($fontDir) {
					return $f->getName();
				},
				$fontDir->getDirectoryListing()
			);
			$this->cache->set($cacheKey, $cachedNames, self::INVALIDATE_FONT_LIST_CACHE_AFTER_SECONDS);
		}

		return $cachedNames;
	}

	/**
	 * @param string $fileName
	 * @param $newFileResource
	 * @return array
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function uploadFontFile(string $fileName, $newFileResource): array {
		$fontDir = $this->getFontAppDataDir();
		$newFile = $fontDir->newFile($fileName, $newFileResource);
		$this->generateFontOverview($newFile);
		return [
			'size' => $newFile->getSize(),
		];
	}

	/**
	 * @param string $fileName
	 * @return string
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getFontFile(string $fileName): string {
		$fontDir = $this->getFontAppDataDir();
		return $fontDir->getFile($fileName)->getContent();
	}

	/**
	 * @param string $fileName
	 * @return string
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getFontFileOverview(string $fileName): string {
		$fontDir = $this->getFontOverviewAppDataDir();
		return $fontDir->getFile($fileName . '.png')->getContent();
	}

	/**
	 * @param string $fileName
	 * @return void
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function deleteFontFile(string $fileName): void {
		$fontDir = $this->getFontAppDataDir();
		if ($fontDir->fileExists($fileName)) {
			$fontDir->getFile($fileName)->delete();
		}

		$overviewDir = $this->getFontOverviewAppDataDir();
		if ($overviewDir->fileExists($fileName . '.png')) {
			$overviewDir->getFile($fileName . '.png')->delete();
		}
	}

	/**
	 * @param ISimpleFile $fontFile
	 * @return void
	 */
	private function generateFontOverview(ISimpleFile $fontFile): void {
		try {
			$color = [0, 0, 0];
			$text = 'Lorem ipsum';

			// we need a temp file because imagettftext can't read the font file from a resource
			// but just a file path
			$tmpFontFile = tmpfile();
			$tmpFontFilePath = stream_get_meta_data($tmpFontFile)['uri'];
			fwrite($tmpFontFile, $fontFile->getContent());
			fflush($tmpFontFile);

			$im = imagecreatetruecolor(250, 30);
			$bg_color = imagecolorallocate($im, 255, 255, 255);
			$font_color = imagecolorallocate($im, $color[0], $color[1], $color[2]);
			imagefilledrectangle($im, 0, 0, 399, 29, $bg_color);
			imagettftext($im, 20, 0, 10, 22, $font_color, $tmpFontFilePath, $text);

			$overviewDir = $this->getFontOverviewAppDataDir();
			$imageFileResource = $overviewDir->newFile($fontFile->getName() . '.png')->write();
			imagepng($im, $imageFileResource);
			imagedestroy($im);
		} catch (\Exception | \Throwable $e) {
		}
	}
}
