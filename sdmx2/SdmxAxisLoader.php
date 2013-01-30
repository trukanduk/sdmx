<?php
	/**
	 * Описание класса <var>SdmxAxisLoader</var> -- загрузчика сохранённых осей
	 *
	 * В sdmx-файле могут использоваться оси, которые не были объявлены. Клсаа <var>SdmxAxisLoader</var> загружает такие
	 * (определённые заранее) оси
	 *
	 * @todo значение по умолчанию может отсутствовать
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0
	 */

	require_once('SdmxAxis.php');

	/**
	 * Загрузчик осей
	 *
	 * Класс загружает оси из xml-файла, если таковые не описаны в самом sdmx (например, OKSM)
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	abstract class SdmxAxisLoader {
		const FILENAME = '.saved_axes.xml';
		const TRYINGS_COUNT = 5;

		static protected function FillAxis(SdmxAxis $axis, SimpleXMLElement $xml) {
			//$axis->SetName(strval($xml->Name))
			$axis->SetType('dimension');
			foreach ($xml->Code as $code)
				$axis->AddValue(strval($code->attributes()->value), strval($code->Description));

			// ***********************************************************************************************************
			// STUB надо, наверное, проверять наличие такого тега:
			$axis->SetDefaultRawValue(strval($xml->DefaultValue));
		}

		static function LoadAxis(SdmxAxis $axis) {
			$file = null;
			for ($try = 0; is_null($file) && $try < self::TRYINGS_COUNT; ++$try, sleep(0.1)) {
				$file = file_get_contents(self::FILENAME);
			}

			if (is_null($file))
				return false;

			$codeLists = simplexml_load_string($file);
			$filled = false;
			foreach ($codeLists->children('structure', true)->CodeList as $codeList) {
				if (strval($codeList->attributes()->id) == $axis->GetId()) {
					self::FillAxis($axis, $codeList);
					$filled = true;
					break;
				}
			}

			return $filled;
		}
	}
?>
