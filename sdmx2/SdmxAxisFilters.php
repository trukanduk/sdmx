<?php
	/**
	 * Описание интерфейса фильтра оси и простейших реализаций
	 *
	 * Фильтр позволяет добавлять только некоторые значения из оси
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 0.1
	 */

	/**
	 * Фильтр оси
	 *
	 * Фильтр по сути состоит из одной функции -- может ли элемент содержаться во множестве
	 * @package sdmx
	 * @version 1.0
	 */
	interface ISdmxAxisFilter {

		/**
		 * Получение соятояния значения
		 * @param string $rawValue Сырое значение, которое надо проверить
		 * @return bool возвращает, должно ли это значение оси содержаться в множестве
		 */
		function IsSifted($rawValue);
	}

	/**
	 * Класс с конструкторами вариаций фильтров
	 *
	 * В классе содержатся статические функции-конструкторы новых "типовых" фильтров
	 * @package sdmx
	 * @version 1.0
	 */
	abstract class SdmxAxisFilter {
		/**
		 * Создание фильтра, пропускаюего все значения, кроме данных
		 * @param string[] $values значения, которые не должны быть пропущены
		 * @return ISdmxAxisFilter фильтр, который пропускает все значения, кроме зананных в массиве
		 */
		static function Except($values) {
			return new SdmxExcludeAxisFilter($values);
		}

		/**
		 * Создание фильтра, пропускаюего только данные значения
		 * @param string[] $values значения, которые должны быть пропущены
		 * @return ISdmxAxisFilter фильтр, который пропускает все заданные значения и только их
		 */
		static function Only($values) {
			return new SdmxIncludeAxisFilter($values);
		}

		/**
		 * Создание фильтра, пропускающего все значения
		 * @return ISdmxAxisFilter фильтр, пропускающий всё
		 */
		static function All() {
			return new SdmxEmptyAxisFilter();
		}
	}

	/**
	 * Фильтр, пропускающий только заданные значения
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxIncludeAxisFilter implements ISdmxAxisFilter {

		/**
		 * Значения, которые будут пропущены
		 * @var string[]
		 */
		protected $values = array();

		/**
		 * Получение соятояния значения
		 * @param string $rawValue Сырое значение, которое надо проверить
		 * @return bool возвращает, должно ли это значение оси содержаться в множестве
		 */
		function IsSifted($rawValue) {
			return in_array($rawValue, $this->values);
		}

		/**
		 * Конструктор
		 * @param string[] $values сырые значения, которые должны быть пропущены
		 */
		function __construct($values) {
			$this->values = $values;
		}
	}

	/**
	 * Фильтр, пропускающий все значения, кроме заданных
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxExcludeAxisFilter implements ISdmxAxisFilter {

		/**
		 * Значения, которые будут пропущены
		 * @var string[]
		 */
		protected $values = array();

		/**
		 * Получение соятояния значения
		 * @param string $rawValue Сырое значение, которое надо проверить
		 * @return bool возвращает, должно ли это значение оси содержаться в множестве
		 */
		function IsSifted($rawValue) {
			return ( ! in_array($rawValue, $this->values));
		}

		/**
		 * Конструктор
		 * @param string[] $values сырые значения, которые не должны быть пропущены
		 */
		function __construct($values) {
			$this->values = $values;
		}
	}

	/**
	 * Фильтр, пропускающий все значения
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxEmptyAxisFilter implements ISdmxAxisFilter {

		/**
		 * Получение соcтояния значения
		 * @return bool всегда <var>true</var>
		 */
		function IsSifted($rawValue) {
			return true;
		}

		/**
		 * Конструктор
		 */
		function __construct() {}
	}
?>
