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
	 * @version 1.1
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
	 * <AxisData xmlns:structure="http://www.SDMX.org/resources/SDMXML/schemas/v1_0/structure">>
	 *      <CodeLists>
	 * 			<CodeList id="OKSM">
	 * 				<Name xml:lang="ru">Страна</Name>
	 * 				<DefaultValue xml:lang="ru">RUS</DefaultValue>
	 * 				<Code value="RUS">
	 * 					<Description xml:lang="ru">Российская Федерация</Description>
	 * 				</Code>
	 * 				<Code value="ENG">
	 * 					<Description xml:lang="ru">Великобритания</Description>
	 * 				</Code>
	 * 			</CodeList>
	 * 		</CodeLists>
	 * 		<AttributeNames>
	 * 			<Attribute id="EI">
	 * 				<Name xml:lang="ru">единица измерения</Name>
	 * 			</Attribute>
	 * 			<Attribute id="PERIOD">
	 * 				<Name xml:lang="ru">период</Name>
	 * 			</Attribute>
	 * 		</AttributeNames>
	 * </AxisData>
	 *
	 * </code>
	 *
	 * Пояснять не буду, всё вроде понятно из тегов и из того, что структура похожа на CodeList'ы из sdmx
	 * (единственное отличие -- значение по умолчанию, которого тут может и не быть)
	 *
	 * @package sdmx
	 * @version 1.1
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
		 * Заполнение значений оси
		 *
		 * Метод получает ось, которую надо заполнить и кусок из xml, соответствующий ему.
		 * Заполняет тип (dimension), значения и значение по умолчанию
		 *
		 * @param SdmxAxis $axis заполняемая ось
		 * @param SimpleXMLElement $xml кусок xml с данными по оси
		 * @return void
		 */
		static protected function FillAxisValues(SdmxAxis $axis, SimpleXMLElement $xml) {
			$axis->SetType('dimension');
			foreach ($xml->Code as $code)
				$axis->AddValue(strval($code->attributes()->value), strval($code->Description));

			if ($xml->DefaultValue)
				$axis->SetDefaultRawValue(strval($xml->DefaultValue));
		}

		/**
		 * Открытие файла с осями
		 * 
		 * Открывает файл и возвращает SimpleXMLElement на его основе.
		 * @param string $filename если <var>$filename == ''</var>, то будет использоваться <var>self::FILENAME</var>
		 * @return SimpleXMLElement Элемент, построенный из файла
		 */
		static protected function OpenFile($filename) {
			if ($filename === '')
				$file = self::FILENAME;
			else
				$file = $filename;

			if ( ! file_exists($file))
				return false;

			$text = false;
			for ($try = 0; ! $text && $try < self::TRYINGS_COUNT; ++$try) {
				$text = file_get_contents($file);
				if ( ! $text)
					usleep(100000);
			}

			if ($text)
				return simplexml_load_string($text);
			else
				return false;
		}

		/**
		 * Поиск и заполнение значений оси
		 *
		 * Метод пытается найти значения оси и заполнить их
		 *
		 * @todo переписать нахрен :)
		 *
		 * @param SdmxAxis $axis ось, которая должна быть заполнена
		 * @param string $filename имя файла, из которого надо загружать данные. Если <var>''</var>, то будет использоваться self::FILENAME
		 * @return bool <var>true</var>, если успешно загружена и <var>false</var> иначе
		 */
		static function LoadAxisValues(SdmxAxis $axis, $filename = '') {
			$axisData = self::OpenFile($filename);

			if ( ! $axisData)
				return false;

			foreach ($axisData->CodeLists->CodeList as $codeList) {
				if (strval($codeList['id']) == $axis->GetId()) {
					self::FillAxisValues($axis, $codeList);
					return true;
				}
			}

			return false;
		}

		/**
		 * Загрузка имени оси
		 *
		 * Применимо для аттрибутов -- у них нет имён, только идентификаторы
		 *
		 * @param SdmxAxis $axis ось аттрибута, которой будет установлено имя
		 * @param string $filename имя файла. если <var>''</var>, то будет использовано self::FILENAME
		 * @return bool результат операции: <var>true</var>, если всё хорошо, иначе false
		 */
		static function LoadAxisName(SdmxAxis $axis, $filename = '') {
			$axisData = self::OpenFile($filename);

			if ( ! $axisData)
				return false;

			foreach ($axisData->AttributeNames->Attribute as $attribute) {
				if (strval($attribute['id']) == $axis->GetId()) {
					$axis->SetName(strval($attribute->Name));
					return true;
				}
			}

			return false;
		}
	}
?>
