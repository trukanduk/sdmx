<?php
	/**
	 * Файл содержит описания интерфейсов структуры данных для <var>SdmxLogariphmicDataSet</var> и итератор для этой структуры
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 0.1
	 */

	require_once("SdmxCoordinate.php");
	require_once("SdmxDataPoint.php");

	/**
	 * Структура данных в логарифмическом множестве точек
	 * 
	 * Структура данных, обёрткой над которой является класс <var>SdmxLogariphmicDataSet</var>.
	 * Внутри такого множества находится несколько структур с данным интерфейсом.
	 * @package sdmx
	 * @version 1.0
	 */
	interface ISdmxLogariphmicDataSetArray {

		/**
		 * Получение массива с приоритетами осей при сортировке
		 *
		 * Возвращается <strong>указатель</strong> на массив идентификаторов осей. Самая первая ось имеет наибольший приоритет,
		 * т.е. точки сравниваются сначала по ней, при равенстве по второй оси, и т.д.
		 * @return string[] массив идентификаторов осей в последовательности, соответствующей приоритетам при сортировке
		 */
		function GetAxesSortOrder();

		/**
		 * Сортировка структуры
		 *
		 * Устанавливает внутренний массив идентификаторов осей. Самая первая ось имеет наибольший приоритет,
		 * т.е. точки сравниваются сначала по ней, при равенстве по второй оси, и т.д.
		 * @param string[] $axesOrder массив идентификаторов осей; при сортировке наибольший приоритет имеет первая ось в массиве.
		 * @return ISdmxLogariphmicDataSetArray Объект-хозяин метода
		 */
		function Sort($axesOrder);

		/**
		 * Добавение точки в структуру
		 * 
		 * Точка добавляется в соответствии с сортировкой, т.е. после добавления будет "на своём месте"
		 * Скорость работы операции зависит от конкретных реализаций (скорее всего выгоднее сначала добавить все точки, а затем отсортировать)
		 * @param SdmxDataPoint $point Добавляемая точка
		 * @return ISdmxLogariphmicDataSetArray Объект-хозяин метода
		 */
		function AddPoint(SdmxDataPoint $point);

		/**
		 * Получение итератора на первую точку структуры
		 * @return ISdmxLogariphmicDataSetArrayIterator Итератор на элемент структуры
		 */
		function GetPointsIterator();

		/**
		 * Получение итератора на первую точку, бОльшую или равную данному массиву
		 *
		 * Входной массив имеет вид <var>[$ind => SdmxCoordinate, $axisId => $rawValue]</var>
		 * В одном массиве могут содержаться элементы в обеих формах (приоритет у объектов-координат <var>SdmxCoordinate</var>)
		 * @param mixed[] $coordinates Массив с координатами точек
		 * @return ISdmxLogariphmicDataSetArrayIterator Итератор на элемент
		 */
		function LowerBound($coordinates);

		/**
		 * Получение итератора на точку, строго бОльшую данного массива
		 *
		 * Входной массив имеет вид <var>[$ind => SdmxCoordinate, $axisId => $rawValue]</var>
		 * В одном массиве могут содержаться элементы в обеих формах (приоритет у объектов-координат <var>SdmxCoordinate</var>)
		 * @param mixed[] $coordinates Массив с координатами точек
		 * @return ISdmxLogariphmicDataSetArrayIterator Итератор на элемент
		 */
		function UpperBound($coordinates);
	}

	/**
	 * Итератор элемент структуры из логарифмического множества точек 
	 *
	 * @package sdmx
	 * @version 0.1
	 */
	interface ISdmxLogariphmicDataSetArrayIterator extends Iterator {
		/**
		 * Получение расстояния между двумя итераторами
		 *
		 * Оно может быть в т.ч. отрицательным!
		 * @param ISdmxLogariphmicDataSetArrayIterator $first первый итератор
		 * @param ISdmxLogariphmicDataSetArrayIterator $second второй итератор
		 * @return int расстояние
		 */
		static function Distance(ISdmxLogariphmicDataSetArrayIterator $first, ISdmxLogariphmicDataSetArrayIterator $second);
	}
?>
