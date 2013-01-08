<?php
	/**
	 * Описание класса <var>SdmxData</var>
	 * 
	 * Файл содержит описание класса <var>SdmxData</var> - класса по работе с sdmx-файлами
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 0.1
	 */

	/**
	 * Sdmx-объект
	 *
	 * Основной класс по работе с Sdmx-файлами.
	 *
	 * @todo размерности
	 * @todo dataSet
	 *
	 * @version 0.1
	 * @package sdmx
	 */
	class SdmxData {
		/**
		 * Объект <var>SimpleXMLElement</var> данного файла
		 *
		 * @var SimpleXMLElement
		 */
		protected $rawXml;

		/**
		 * Получение исходного xml
		 *
		 * Возвращает исходный xml файла в виде объекта <var>SimpleXMLElement</var>
		 *
		 * @return SimpleXMLElement исходный xml файла
		 */
		function GetRawXml() {
			return $this->rawXml;
		}

		/**
		 * Установка исходного xml
		 *
		 * @param SimpleXMLElement $xml новый объект
		 * @return SdmxData объект-хозяин метода
		 */
		protected function SetRawXml(SimpleXMLElement $xml) {
			$this->rawXml = $xml;
			return $this;
		}

		/**
		 * Заголовки файла
		 *
		 * @var SimpleXMLElement Объект, соответствующий тегу &lt;header>
		 */
		protected $headers;

		/**
		 * Получение заголовков
		 *
		 * @return SimpleXMLElement Объект с заголовками файла
		 */
		function GetHeaders() {
			return $this->headers;
		}

		/**
		 * Установка заголовков
		 *
		 * @param SimpleXMLElement $headers новый объект с заголовками
		 * @return SdmxData объект-хозяин метода
		 */
		protected function SetHeaders(SimpleXMLElement $headers) {
			$this->headers = $headers;
			return $this;
		}

		/**
		 * Получение заголовка
		 *
		 * Парсит данный объект и вынимает из него заголовки
		 *
		 * @param SimpleXMLElement $xml исходный объект
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseHeaders(SimpleXMLElement $xml) {
			return $this->SetHeaders($xml);
		}

		/**
		 * Описание файла
		 *
		 * @var SimpleXMLElement Объект, соответствующий тегу &lt;Description>
		 */
		protected $description;

		/**
		 * Получение описания
		 *
		 * @return SimpleXMLElement Объект с описанием файла
		 */
		function GetDescription() {
			return $this->description;
		}

		/**
		 * Установка описания
		 *
		 * @param SimpleXMLElement $description новый объект с описанием
		 * @return SdmxData объект-хозяин метода
		 */
		protected function SetDescription(SimpleXMLElement $description) {
			$this->description = $description;
			return $this;
		}

		/**
		 * Получение описания
		 *
		 * Достаёт описание из данного объекта
		 * @param SimpleXMLElement $xml объект с описанием
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseDescription(SimpleXMLElement $xml) {
			return $this->GetDescription($xml);
		}

		/**
		 * Конструктор
		 * 
		 * @param string $filename Имя файла
		 */
		function __construct($filename) {
			$this->SetRawXml(simplexml_load_file($filename));

			$this->ParseHeaders($this->GetRawXml()->GenericData->Header)
			     ->ParseDescription($this->GetRawXml()->GenericData->Description);

			// STUB
		}
	}
?>
