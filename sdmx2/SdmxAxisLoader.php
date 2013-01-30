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
	 * Файл имеет вид xml. Пример такого файла:
	 * <code>
	 *
	 * <?xml version="1.0" encoding="utf-8"?>
	 * <CodeLists
 	 * 		xmlns:structure="http://www.SDMX.org/resources/SDMXML/schemas/v1_0/structure">
	 * 		<structure:CodeList id="OKSM">
	 * 			<structure:Name xml:lang="ru">Страна</structure:Name>
	 * 			<structure:DefaultValue xml:lang="ru">RUS</structure:DefaultValue>
	 * 			<structure:Code value="RUS">
	 * 				<structure:Description xml:lang="ru">Российская Федерация</structure:Description>
	 * 			</structure:Code>
	 * 			<structure:Code value="ENG">
	 * 				<structure:Description xml:lang="ru">Великобритания</structure:Description>
	 * 			</structure:Code>
	 * 		</structure:CodeList>
	 * </CodeLists>
	 *
	 * </code>
	 *
	 * Пояснять не буду, всё вроде понятно из тегов и из того, что структура похожа на CodeList'ы из sdmx
	 * (единственное отличие -- значение по умолчанию, которого тут может и не быть)
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	abstract class SdmxAxisLoader {
		/**
		 * Имя файла по умолчанию
		 *
		 * При загрузке оси можно задать файл, из которого надо подгрузить ось,
		 * иначе будет использоваться это значение
		 *
		 * @var string
		 */
		const FILENAME = '.saved_axes.xml';

		/**
		 * Количество попыток прочитать файл
		 *
		 * Может случиться, что файл нельзя будет прочитать (занят другим потоком, например). В последнем случае надо попытаться ещё раз
		 * Константа описывает количество таких попыток
		 *
		 * @var int
		 */
		const TRYINGS_COUNT = 5;

		/**
		 * Заполнение оси
		 *
		 * Метод получает ось, которую надо заполнить и кусок из xml, соответствующий ему.
		 * Заполняет тип (dimension), значения и значение по умолчанию
		 *
		 * @param SdmxAxis $axis заполняемая ось
		 * @param SimpleXMLElement $xml кусок xml с данными по оси
		 * @return void
		 */
		static protected function FillAxis(SdmxAxis $axis, SimpleXMLElement $xml) {
			$axis->SetType('dimension');
			foreach ($xml->Code as $code)
				$axis->AddValue(strval($code->attributes()->value), strval($code->Description));

			if ($xml->DefaultValue)
				$axis->SetDefaultRawValue(strval($xml->DefaultValue));
		}

		/**
		 * Поиск и заполнение оси
		 *
		 * Метод пытается найти ось и заполняет её.
		 *
		 * @todo переписать нахрен :)
		 *
		 * @param SdmxAxis $axis ось, которая должна быть заполнена
		 * @param string $filename имя файла, из которого надо загружать данные. Если <var>''</var>, то будет использоваться self::FILENAME
		 * @return bool <var>true</var>, если успешно загружена и <var>false</var> иначе
		 */
		static function LoadAxis(SdmxAxis $axis, $filename = '') {
			if ($filename === '')
				$filename = self::FILENAME;

			$file = null;
			for ($try = 0; is_null($file) && $try < self::TRYINGS_COUNT; ++$try, sleep(0.1)) {
				$file = file_get_contents($filename);
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
