<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  David Enke 2013 
 * @author     David Enke (post@davidenke.de) 
 * @package    lessphp 
 * @license    LGPL 
 * @filesource
 */


/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Contao;

/**
 * Include lessc
 */
require_once(TL_ROOT . '/system/modules/lessphp/vendor/lessphp/lessc.inc.php');

/**
 * Class LessHelper
 *
 */
class LessHelper extends \Frontend {
	protected function createCssFile($objLessFile) {
		if (!$objLessFile) return;

		$strDestination = preg_replace('/\.less$/i', '.css', $objLessFile->path);

		$objCssFile = clone $objLessFile;
		$objCssFile->path = $strDestination;
		$objCssFile->extension = 'css';
		$objCssFile->hash = md5_file(TL_ROOT . '/' . $strDestination);
		$objCssFile->name = preg_replace('/\.less$/i', '.css', $objLessFile->name);
		$objCssFile->save(true);

		return $objCssFile;
	}

	public function renderLess(\PageModel $objPage, \LayoutModel $objLayout, \PageRegular $objPageRegular) {
		$arrCache = array();
		$arrExternal = deserialize($objLayout->external);
		$arrOrderExt = deserialize($objLayout->orderExt);

		// External style sheets
		if (is_array($arrExternal) && !empty($arrExternal)) {
			// Get the file entries from the database
			$objFiles = \FilesModel::findMultipleByIds($arrExternal);

			$objLess = new \lessc;
			//$objLess->setImportDir(array('/files'));
			if ($GLOBALS['TL_CONFIG']['gzipScripts']) {
				$objLess->setFormatter('compressed');
			}

			if ($objFiles !== null) {
				while ($objFiles->next()) {
					if (file_exists(TL_ROOT . '/' . $objFiles->path)) {
						$intFileId = $objFiles->uuid;

						if ($objFiles->extension == 'less') {
							$strDes = preg_replace('/\.less$/i', '.css', $objFiles->path);

							try {
								if ($GLOBALS['TL_CONFIG']['checkedCompile'])
									$objLess->checkedCompile(TL_ROOT . '/' . $objFiles->path, TL_ROOT . '/' . $strDes);
								else $objLess->compileFile(TL_ROOT . '/' . $objFiles->path, TL_ROOT . '/' . $strDes);
							} catch (exception $e) {
								$this->log('Could not compile less file "' . $objFiles->path . '"', 'LessHelper renderLess()', TL_ERROR);
							}

							$objFile = \FilesModel::findMultipleByBasepath($strDes);

							if (is_null($objFile)) {
								$objFile = $this->createCssFile($objFiles);
							}
							
														// replace old ID in $objLayout->orderExt
							$arrOrderExt = array_replace($arrOrderExt, array_fill_keys(array_keys($arrOrderExt, $intFileId), $objFile->uuid));


							$intFileId = $objFile->uuid;
						}

						$arrCache[] = $intFileId;
					}
				}
			}

			$objLayout->external = serialize($arrCache);
		}
	}
}

?>
