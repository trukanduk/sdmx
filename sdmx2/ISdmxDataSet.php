<?php
	/**
	 * Файл содержит описание интерфейса <var>ISdmxDataSet</var> и сопутствующее
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0
	 */

	require_once('SdmxAxis.php');
	require_once('SdmxDataPoint.php');

	/**
	 * Интерфейс для многомерных массивов данных
	 *
	 * Не описана полная функциональность, в идеале предполагается несколько реализаций,
	 * возможно, соптимизированных для разных типов запросов
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	interface ISdmxDataSet extends IteratorAggregate {
		/**
		 * Получение оси
		 *
		 * @param string $axisId Идентификатор требуемой оси
		 * @param mixed $default значение по умолчанию -- в случае отсутствия искомой оси
		 * @return mixed искомая ось (<var>SdmxAxis</var>) или <var>$default</var>.
		 */
		function GetAxis($axisId, $default = false);

		/**
		 * Добавление новой оси
		 *
		 * Сначала в объект должны загоняться оси, а потом уже точки. Если добавляется ось к набору точек,
		 * то вываливается исключение
		 *
		 * @throws Exception Если в объекте же содержались точки
		 * @param SdmxAxis $axis новая ось
		 * @return ISdmxDataSet объект-хозяин объекта
		 */
		function AddAxis(SdmxAxis $axis);

		/**
		 * Получение итератора на массив осей
		 *
		 * @return Iterator итератор на массив осей
		 */
		function GetAxesIterator();

		/**
		 * Получение количества осей
		 *
		 * @return int Количество осей
		 */
		function GetAxesCount();

		/**
		 * Получение количества фиксированных осей
		 *
		 * @return int количество фиксированных осей множества
		 */ 
		function GetFixedAxesCount();

		/**
		 * Получение количества нефиксированных осей
		 *
		 * @return int количество нефиксированных осей множества
		 */ 
		function GetUnfixedAxesCount();

		/**
		 * Фиксированна ли ось
		 *
		 * Фиксированная ось -- это ось с одним значением.
		 *
		 * @param string $axisId идентификатор интересуемой оси
		 * @param mixed $default значение, которое будет возвращено в случае отсутствия оси
		 * @return mixed В случае наличия оси -- <var>bool</var> или <var>$default</var> в случае её отсутствия
		 */
		function IsAxisFixed($axisId, $default = false);

		/**
		 * Получение итератора на массив фиксированных осей
		 *
		 * @return Iterator итератор на фиксированные оси
		 */
		function GetFixedAxesIterator();

		/**
		 * Получение итератора на массив нефиксированных осей
		 *
		 * @return Iterator итератор на нефиксированные оси
		 */
		function GetUnfixedAxesIterator();

		/**
		 * Получение итератора на массив значений оси
		 *
		 * Список значений какой-то оси в срезе может отличаться от полного.
		 * Функция возвращает итератор на массив со значениями (сырыми) оси конкретно этого объекта
		 *
		 * @param string $axisId идентификатор оси
		 * @param mixed $defаult Значение, которое вернётся в случае отсутствия оси
		 * @return Iterator итератор на массив со значениями оси
		 */
		function GetValuesIterator($axisId, $default = false);

		/**
		 * Получение количества значений оси
		 *
		 * @param string $axisId идентификатор оси
		 * @param mixed $default значение, которое вернётся при отсутствии оси
		 * @return mixed Количество значений (<var>int</var>) или <var>$default</var> в случае отсутствия таковой
		 */
		function GetValuesCount($axisId, $default = false);

		/**
		 * Добавление точки
		 *
		 * Все точки должны загоняться после добавления всех осей, а также все точки должны иметь коодринаты по всем осям
		 * DataSet'а (и, соответственно, только по ним).
		 *
		 * @throws Exception Если что-то не так с осями
		 * @param SdmxDataPoint $point дабавляемая точка
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function AddPoint(SdmxDataPoint $point);

		/**
		 * Сортирует точки и возвращает итератор
		 * 
		 * Сортирует точки в множестве. Если массив <var>$axesOrder</var> задан, то будет задан приоритет осей при сортировке
		 * (т.е. сначала будет сравниваться ось, стоящая раньше в массиве, при равенстве -- далее). Если не задан, то он будет
		 * сформирован сам в соответствии с последовательностью добавления осей в объект
		 *
		 * @param string[] $axesOrder массив с приоритетами осей при сравнении (или <var>null</var>, чтобы использовать очерёдность добавления осей)
		 * @return Iterator итератор на множество значений
		 */
		function SortPoints($axesOrder = null);

		/**
		 * Получение итератора на массив точек
		 *
		 * Точки будут находиться в неопределённом порядке. Чтобы получить итератор на отсортированный массив следует
		 * использовать метод SortPoints
		 *
		 * @return Iterator итератор на множество точек
		 */
		function GetPointsIterator();

		/**
		 * Получение среза по оси
		 *
		 * Возвращает ассоциативный массив вида <var>['&lt;сырое значение>' => ISdmxDataSet()]</var>, где в качестве индексов
		 * выступают все сырые значения оси с идентификатором <var>$axisId</var>, а в каждом IDataSet'е
		 * находятся все точки из делимого множества с значением оси <var>$axisId</var>, равным индексу в массиве
		 *
		 * @param string $axisId идентификатор оси, по которой произойдёт деление
		 * @return ISdmxDataSet[] массив с множествами
		 */
		function GetSlice($axisId);

		/**
		 * Очистка множества
		 *
		 * Удаляет все точки и все оси из множества (но если есть какие-то специфичные параметры, то они остаются)
		 *
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function Clear();

		function __DebugPrintAxes($printValues = true);
		function __DebugPrintPoints();
		function __DebugPrint();
	}
?>
